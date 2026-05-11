<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WpBooking_Admin {

    private string $plugin_name;
    private string $version;

    public function __construct( string $plugin_name, string $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    public function register_post_type(): void {
        $labels = [
            'name'               => 'Agendamentos',
            'singular_name'      => 'Agendamento',
            'menu_name'          => 'WP Booking',
            'add_new'            => 'Novo agendamento',
            'add_new_item'       => 'Adicionar agendamento',
            'edit_item'          => 'Editar agendamento',
            'view_item'          => 'Ver agendamento',
            'all_items'          => 'Todos os agendamentos',
            'search_items'       => 'Buscar agendamentos',
            'not_found'          => 'Nenhum agendamento encontrado.',
            'not_found_in_trash' => 'Lixeira vazia.',
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,   // não aparece no front-end
            'show_ui'             => true,    // aparece no admin
            'show_in_menu'        => true,
            'show_in_rest'        => false,   // sem Gutenberg para este CPT
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => [ 'title' ], // título = nome do cliente
            'has_archive'         => false,
            'rewrite'             => false,
            'menu_icon'           => 'dashicons-calendar-alt',
            'menu_position'       => 25,
        ];

        register_post_type( 'wpbooking', $args );
    }


    public function add_meta_boxes(): void {
    add_meta_box(
        'wpbooking_details',        // ID único
        'Detalhes do agendamento',  // título exibido
        [ $this, 'render_meta_box' ], // callback de render
        'wpbooking',                // CPT
        'normal',                   // posição: normal, side, advanced
        'high'                      // prioridade
    );
}

    public function render_meta_box( WP_Post $post ): void {
        // Nonce de segurança — valida que o submit veio do admin
        wp_nonce_field( 'wpbooking_save_meta', 'wpbooking_nonce' );

        // Busca valores salvos (ou vazio se novo)
        $fields = [
            'name'         => get_post_meta( $post->ID, '_wpbooking_name',    true ),
            'email'        => get_post_meta( $post->ID, '_wpbooking_email',   true ),
            'phone'        => get_post_meta( $post->ID, '_wpbooking_phone',   true ),
            'service'      => get_post_meta( $post->ID, '_wpbooking_service', true ),
            'booking_date' => get_post_meta( $post->ID, '_wpbooking_date',    true ),
            'booking_time' => get_post_meta( $post->ID, '_wpbooking_time',    true ),
            'status'       => get_post_meta( $post->ID, '_wpbooking_status',  true ) ?: 'pending',
        ];
        ?>
        <table class="form-table">
            <tr>
                <th><label for="wpbooking_name">Nome</label></th>
                <td><input type="text" id="wpbooking_name" name="wpbooking_name"
                    value="<?php echo esc_attr( $fields['name'] ); ?>"
                    class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="wpbooking_email">E-mail</label></th>
                <td><input type="email" id="wpbooking_email" name="wpbooking_email"
                    value="<?php echo esc_attr( $fields['email'] ); ?>"
                    class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="wpbooking_phone">Telefone / WhatsApp</label></th>
                <td><input type="text" id="wpbooking_phone" name="wpbooking_phone"
                    value="<?php echo esc_attr( $fields['phone'] ); ?>"
                    class="regular-text" placeholder="+61 4XX XXX XXX" /></td>
            </tr>
            <tr>
                <th><label for="wpbooking_service">Serviço</label></th>
                <td><input type="text" id="wpbooking_service" name="wpbooking_service"
                    value="<?php echo esc_attr( $fields['service'] ); ?>"
                    class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="wpbooking_date">Data</label></th>
                <td><input type="date" id="wpbooking_date" name="wpbooking_date"
                    value="<?php echo esc_attr( $fields['booking_date'] ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="wpbooking_time">Horário</label></th>
                <td><input type="time" id="wpbooking_time" name="wpbooking_time"
                    value="<?php echo esc_attr( $fields['booking_time'] ); ?>" /></td>
            </tr>
            <tr>
                <th><label for="wpbooking_status">Status</label></th>
                <td>
                    <select id="wpbooking_status" name="wpbooking_status">
                        <?php foreach ( ['pending'=>'Pendente','confirmed'=>'Confirmado','cancelled'=>'Cancelado'] as $val => $label ): ?>
                        <option value="<?php echo $val; ?>" <?php selected( $fields['status'], $val ); ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_boxes( int $post_id, WP_Post $post ): void {

        // 1. Verificar nonce — garante que veio do formulário correto
        if ( ! isset( $_POST['wpbooking_nonce'] ) ||
            ! wp_verify_nonce( $_POST['wpbooking_nonce'], 'wpbooking_save_meta' ) ) {
            return;
        }

        // 2. Verificar permissão do usuário
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // 3. Não salvar em autosave ou revisão
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;

        // 4. Mapa de campos: nome do input => chave do meta => sanitização
        $fields = [
            'wpbooking_name'    => [ '_wpbooking_name',    'sanitize_text_field' ],
            'wpbooking_email'   => [ '_wpbooking_email',   'sanitize_email'      ],
            'wpbooking_phone'   => [ '_wpbooking_phone',   'sanitize_text_field' ],
            'wpbooking_service' => [ '_wpbooking_service', 'sanitize_text_field' ],
            'wpbooking_date'    => [ '_wpbooking_date',    'sanitize_text_field' ],
            'wpbooking_time'    => [ '_wpbooking_time',    'sanitize_text_field' ],
        ];

        foreach ( $fields as $input => [ $meta_key, $sanitizer ] ) {
            if ( isset( $_POST[ $input ] ) ) {
                update_post_meta(
                    $post_id,
                    $meta_key,
                    call_user_func( $sanitizer, $_POST[ $input ] )
                );
            }
        }

        // Status: validar contra valores permitidos
        $allowed_statuses = [ 'pending', 'confirmed', 'cancelled' ];
        if ( isset( $_POST['wpbooking_status'] ) &&
            in_array( $_POST['wpbooking_status'], $allowed_statuses, true ) ) {
            update_post_meta( $post_id, '_wpbooking_status', $_POST['wpbooking_status'] );
        }
    }

    public function set_columns( array $columns ): array {
        // Remove colunas padrão desnecessárias
        unset( $columns['date'], $columns['author'] );

        return [
            'cb'               => $columns['cb'],   // checkbox
            'title'            => 'Cliente',
            'wpbooking_service'=> 'Serviço',
            'wpbooking_date'   => 'Data',
            'wpbooking_time'   => 'Horário',
            'wpbooking_status' => 'Status',
        ];
    }

    public function render_column( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'wpbooking_service':
                echo esc_html( get_post_meta( $post_id, '_wpbooking_service', true ) );
                break;
            case 'wpbooking_date':
                $date = get_post_meta( $post_id, '_wpbooking_date', true );
                echo esc_html( $date ? date( 'd/m/Y', strtotime( $date ) ) : '—' );
                break;
            case 'wpbooking_time':
                echo esc_html( get_post_meta( $post_id, '_wpbooking_time', true ) ?: '—' );
                break;
            case 'wpbooking_status':
                $status = get_post_meta( $post_id, '_wpbooking_status', true );
                $map    = [
                    'pending'   => [ 'label' => 'Pendente',   'color' => '#854F0B', 'bg' => '#FAEEDA' ],
                    'confirmed' => [ 'label' => 'Confirmado', 'color' => '#085041', 'bg' => '#E1F5EE' ],
                    'cancelled' => [ 'label' => 'Cancelado',  'color' => '#791F1F', 'bg' => '#FCEBEB' ],
                ];
                $s = $map[ $status ] ?? $map['pending'];
                printf(
                    '<span style="background:%s;color:%s;padding:2px 10px;border-radius:20px;font-size:11px">%s</span>',
                    esc_attr( $s['bg'] ),
                    esc_attr( $s['color'] ),
                    esc_html( $s['label'] )
                );
                break;
        }
    }

    public function sortable_columns( array $columns ): array {
        $columns['wpbooking_date']   = 'wpbooking_date';
        $columns['wpbooking_status'] = 'wpbooking_status';
        return $columns;
    }

    public function add_status_filter(): void {
        global $typenow;
        if ( 'wpbooking' !== $typenow ) return;

        $current = $_GET['wpbooking_status_filter'] ?? '';
        ?>
        <select name="wpbooking_status_filter">
            <option value="">Todos os status</option>
            <?php foreach ( ['pending'=>'Pendente','confirmed'=>'Confirmado','cancelled'=>'Cancelado'] as $val => $label ): ?>
            <option value="<?php echo esc_attr($val); ?>" <?php selected($current,$val); ?>>
                <?php echo esc_html($label); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function filter_by_status( WP_Query $query ): void {
        global $pagenow, $typenow;

        if ( ! is_admin() ) return;
        if ( 'edit.php' !== $pagenow ) return;
        if ( 'wpbooking' !== $typenow ) return;

        $status = $_GET['wpbooking_status_filter'] ?? '';
        if ( empty( $status ) ) return;

        $query->query_vars['meta_key']   = '_wpbooking_status';
        $query->query_vars['meta_value'] = sanitize_text_field( $status );
    }

    // Adicionar no define_admin_hooks() em class-wpbooking.php:
// add_action( 'admin_menu', [ $admin, 'add_settings_page' ] );
// add_action( 'admin_init', [ $admin, 'register_settings'  ] );

public function add_settings_page(): void {
    add_submenu_page(
        'edit.php?post_type=wpbooking',
        'Configurações',
        'Configurações',
        'manage_options',
        'wpbooking-settings',
        [ $this, 'render_settings_page' ]
    );
}

public function register_settings(): void {
    register_setting( 'wpbooking_options', 'wpbooking_laravel_url',
        [ 'sanitize_callback' => 'esc_url_raw' ] );
    register_setting( 'wpbooking_options', 'wpbooking_twilio_sid',
        [ 'sanitize_callback' => 'sanitize_text_field' ] );
    register_setting( 'wpbooking_options', 'wpbooking_twilio_token',
        [ 'sanitize_callback' => 'sanitize_text_field' ] );
    register_setting( 'wpbooking_options', 'wpbooking_twilio_from',
        [ 'sanitize_callback' => 'sanitize_text_field' ] );
    register_setting( 'wpbooking_options', 'wpbooking_confirmation_msg',
        [ 'sanitize_callback' => 'sanitize_textarea_field' ] );
}

    public function render_settings_page(): void { ?>
        <div class="wrap">
            <h1>WP Booking — Configurações</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'wpbooking_options' ); ?>
                <table class="form-table">
                    <tr>
                        <th>URL da API Laravel</th>
                        <td><input type="url" name="wpbooking_laravel_url" class="regular-text"
                            value="<?php echo esc_attr( get_option('wpbooking_laravel_url') ); ?>"
                            placeholder="https://sua-api.railway.app" /></td>
                    </tr>
                    <tr>
                        <th>Mensagem de confirmação</th>
                        <td><textarea name="wpbooking_confirmation_msg" rows="3" class="large-text"><?php
                            echo esc_textarea( get_option('wpbooking_confirmation_msg',
                                'Olá {name}! Seu {service} em {date} às {time} foi confirmado.') );
                        ?></textarea>
                        <p class="description">Variáveis: {name} {phone} {service} {date} {time}</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php }

}