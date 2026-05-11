<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WpBooking_Public {

    private string $plugin_name;
    private string $version;

    public function __construct( string $plugin_name, string $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    public function enqueue_styles(): void {
        wp_enqueue_style(
            $this->plugin_name,
            WPBOOKING_PLUGIN_URL . 'assets/css/wpbooking-public.css',
            [],
            $this->version
        );
    }

    public function enqueue_scripts(): void {
        wp_enqueue_script(
            $this->plugin_name,
            WPBOOKING_PLUGIN_URL . 'assets/js/wpbooking-public.js',
            [ 'jquery' ],
            $this->version,
            true  // carrega no rodapé
        );

        // Passa dados do PHP para o JS via wp_localize_script
        wp_localize_script( $this->plugin_name, 'wpbooking_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wpbooking_submit' ),
        ]);
    }

    public function render_form( array $atts = [] ): string {
        // Atributos configuráveis: [wpbooking_form title="Agende já"]
        $atts = shortcode_atts([
            'title'    => 'Fazer um agendamento',
            'subtitle' => 'Confirmaremos via SMS ou WhatsApp.',
        ], $atts, 'wpbooking_form' );

        ob_start(); ?>
        <div class="wpbooking-form-wrap">
            <h3><?php echo esc_html( $atts['title'] ); ?></h3>
            <p class="sub"><?php echo esc_html( $atts['subtitle'] ); ?></p>

            <form id="wpbooking-form" novalidate>
                <?php wp_nonce_field( 'wpbooking_submit', 'wpbooking_front_nonce' ); ?>

                <div class="row">
                    <div class="field">
                        <label for="wpb_name">Nome completo *</label>
                        <input type="text" id="wpb_name" name="wpb_name" required />
                    </div>
                    <div class="field">
                        <label for="wpb_phone">Telefone / WhatsApp *</label>
                        <input type="tel" id="wpb_phone" name="wpb_phone"
                            placeholder="+61 4XX XXX XXX" required />
                    </div>
                </div>

                <div class="field">
                    <label for="wpb_email">E-mail</label>
                    <input type="email" id="wpb_email" name="wpb_email" />
                </div>

                <div class="field">
                    <label for="wpb_service">Serviço desejado *</label>
                    <input type="text" id="wpb_service" name="wpb_service" required />
                </div>

                <div class="row">
                    <div class="field">
                        <label for="wpb_date">Data *</label>
                        <input type="date" id="wpb_date" name="wpb_date"
                            min="<?php echo esc_attr( date('Y-m-d') ); ?>" required />
                    </div>
                    <div class="field">
                        <label for="wpb_time">Horário *</label>
                        <input type="time" id="wpb_time" name="wpb_time" required />
                    </div>
                </div>

                <button type="submit" id="wpb_submit">Confirmar agendamento</button>
            </form>
            <div id="wpbooking-msg" class="wpbooking-msg" style="display:none"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_ajax(): void {
        // 1. Verificar nonce
        if ( ! check_ajax_referer( 'wpbooking_submit', 'nonce', false ) ) {
            wp_send_json_error([ 'message' => 'Requisição inválida.' ]);
        }

        // 2. Sanitizar entradas
        $data = [
            'name'    => sanitize_text_field( $_POST['wpb_name']    ?? '' ),
            'phone'   => sanitize_text_field( $_POST['wpb_phone']   ?? '' ),
            'email'   => sanitize_email(      $_POST['wpb_email']   ?? '' ),
            'service' => sanitize_text_field( $_POST['wpb_service'] ?? '' ),
            'date'    => sanitize_text_field( $_POST['wpb_date']    ?? '' ),
            'time'    => sanitize_text_field( $_POST['wpb_time']    ?? '' ),
        ];

        // 3. Validar campos obrigatórios
        foreach ( ['name','phone','service','date','time'] as $field ) {
            if ( empty( $data[ $field ] ) ) {
                wp_send_json_error([ 'message' => 'Preencha todos os campos obrigatórios.' ]);
            }
        }

        // 4. Validar formato de data
        $d = DateTime::createFromFormat( 'Y-m-d', $data['date'] );
        if ( ! $d || $d < new DateTime('today') ) {
            wp_send_json_error([ 'message' => 'Data inválida.' ]);
        }

        // 5. Criar o CPT Booking
        $post_id = wp_insert_post([
            'post_title'  => sanitize_text_field( $data['name'] ),
            'post_status' => 'publish',
            'post_type'   => 'wpbooking',
        ]);

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error([ 'message' => 'Erro ao salvar agendamento.' ]);
        }

        // 6. Salvar meta data
        update_post_meta( $post_id, '_wpbooking_name',    $data['name']    );
        update_post_meta( $post_id, '_wpbooking_phone',   $data['phone']   );
        update_post_meta( $post_id, '_wpbooking_email',   $data['email']   );
        update_post_meta( $post_id, '_wpbooking_service', $data['service'] );
        update_post_meta( $post_id, '_wpbooking_date',    $data['date']    );
        update_post_meta( $post_id, '_wpbooking_time',    $data['time']    );
        update_post_meta( $post_id, '_wpbooking_status',  'pending'        );

        // 7. Notificar Laravel API (Semana 4 — por enquanto só loga)
        $this->notify_laravel( array_merge( $data, [ 'booking_id' => $post_id ] ) );

        wp_send_json_success([
            'message' => 'Agendamento recebido! Você receberá uma confirmação em breve via SMS ou WhatsApp.',
        ]);
    }

    private function notify_laravel( array $data ): void {
        // Semana 4: substituir pela URL real do Laravel
        $api_url = get_option( 'wpbooking_laravel_url', '' );
        if ( empty( $api_url ) ) return;

        wp_remote_post( $api_url . '/api/bookings', [
            'timeout'     => 10,
            'headers'     => [ 'Content-Type' => 'application/json' ],
            'body'        => wp_json_encode( $data ),
            'data_format' => 'body',
        ]);
    }
}