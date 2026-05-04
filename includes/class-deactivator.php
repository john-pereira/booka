<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class WpBooking_Deactivator {

    public static function deactivate(): void {
        // Na desativação: só limpamos regras de rewrite
        // NÃO apagamos dados — isso vai no uninstall.php
        flush_rewrite_rules();
    }
}

// uninstall.php (arquivo separado na raiz do plugin)
// Executado apenas quando o usuário clica "Apagar" no painel:
//
// if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
// global $wpdb;
// $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpbooking_bookings" );
// delete_option( 'wpbooking_sms_enabled' );
// delete_option( 'wpbooking_twilio_sid' );
// ... etc