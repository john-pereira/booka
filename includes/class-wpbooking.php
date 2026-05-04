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
        // Semanas 2 e 3: aqui entram as classes admin e public
        // require_once WPBOOKING_PLUGIN_DIR . 'admin/class-admin.php';
        // require_once WPBOOKING_PLUGIN_DIR . 'public/class-public.php';
    }

    private function define_admin_hooks(): void {
        // add_action( 'admin_menu', [ $admin, 'add_plugin_page' ] );
    }

    private function define_public_hooks(): void {
        // add_shortcode( 'wpbooking_form', [ $public, 'render_form' ] );
    }

    public function get_plugin_name(): string {
        return $this->plugin_name;
    }

    public function get_version(): string {
        return $this->version;
    }
}