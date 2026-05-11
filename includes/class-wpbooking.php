<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class WpBooking_Plugin {

    private static ?self $instance = null;
    private string $version;
    private string $plugin_name;

    private function __construct() {
        $this->version     = WPBOOKING_VERSION;
        $this->plugin_name = 'wpbooking';
    }

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run(): void {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies(): void {
        require_once WPBOOKING_PLUGIN_DIR . 'admin/class-admin.php';
        require_once WPBOOKING_PLUGIN_DIR . 'public/class-public.php'; // novo
    }


    private function define_admin_hooks(): void {
        $admin = new WpBooking_Admin(
        $this->get_plugin_name(),
        $this->get_version()
        );

        // Registrar o CPT
        add_action( 'init', [ $admin, 'register_post_type' ] );

        // Meta boxes
        add_action( 'add_meta_boxes', [ $admin, 'add_meta_boxes' ] );
        add_action( 'save_post_wpbooking', [ $admin, 'save_meta_boxes' ], 10, 2 );

        // Colunas customizadas na listagem
        add_filter( 'manage_wpbooking_posts_columns',       [ $admin, 'set_columns' ] );
        add_action( 'manage_wpbooking_posts_custom_column', [ $admin, 'render_column' ], 10, 2 );
        add_filter( 'manage_edit-wpbooking_sortable_columns', [ $admin, 'sortable_columns' ] );

        // Filtro status pedidos
        add_action( 'restrict_manage_posts', [ $admin, 'add_status_filter' ] );
        add_filter( 'parse_query',           [ $admin, 'filter_by_status'  ] );
    }

    private function define_public_hooks(): void {
        $public = new WpBooking_Public(
            $this->get_plugin_name(),
            $this->get_version()
        );

        add_action( 'wp_enqueue_scripts', [ $public, 'enqueue_styles'  ] );
        add_action( 'wp_enqueue_scripts', [ $public, 'enqueue_scripts' ] );
        add_shortcode( 'wpbooking_form',  [ $public, 'render_form'     ] );

        // Ajax: logado e não-logado (clientes do site não têm conta WP)
        add_action( 'wp_ajax_wpbooking_submit',        [ $public, 'handle_ajax' ] );
        add_action( 'wp_ajax_nopriv_wpbooking_submit', [ $public, 'handle_ajax' ] );
        // registro do bloco
        add_action( 'init', [ $this, 'register_blocks' ] );
    }

    public function get_plugin_name(): string {
        return $this->plugin_name;
    }

    public function get_version(): string {
        return $this->version;
    }

    public function register_blocks(): void {
    register_block_type(
        WPBOOKING_PLUGIN_DIR . 'blocks/booking-form'
        // block.json é lido automaticamente
    );
}
}