<?php
/**
 * Plugin Name:       WP Booking
 * Plugin URI:        https://github.com/john-pereira/wpbooking
 * Description:       Booking simples para pequenos negócios com confirmação via SMS e WhatsApp.
 * Version:           1.0.0
 * Author:            John Pereira
 * Author URI:        https://github.com/john-pereira
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpbooking
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPBOOKING_VERSION',     '1.0.0' );
define( 'WPBOOKING_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'WPBOOKING_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'WPBOOKING_PLUGIN_FILE', __FILE__ );

register_activation_hook(   __FILE__, [ 'WpBooking_Activator',   'activate'   ] );
register_deactivation_hook( __FILE__, [ 'WpBooking_Deactivator', 'deactivate' ] );

require_once WPBOOKING_PLUGIN_DIR . 'includes/class-wpbooking.php';
require_once WPBOOKING_PLUGIN_DIR . 'includes/class-activator.php';
require_once WPBOOKING_PLUGIN_DIR . 'includes/class-deactivator.php';

function wpbooking(): WpBooking_Plugin {
    return WpBooking_Plugin::get_instance();
}

wpbooking()->run();