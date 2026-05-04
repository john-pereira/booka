<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class WpBooking_Activator {

    public static function activate(): void {
        self::create_tables();
        self::set_default_options();
        // Força o WordPress a recriar as regras de rewrite
        flush_rewrite_rules();
    }

    private static function create_tables(): void {
        global $wpdb;
        $table   = $wpdb->prefix . 'wpbooking_bookings';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(100)        NOT NULL,
            email       VARCHAR(150)        NOT NULL,
            phone       VARCHAR(30)         NOT NULL,
            service     VARCHAR(100)        NOT NULL,
            booking_date DATE              NOT NULL,
            booking_time TIME              NOT NULL,
            status      ENUM('pending','confirmed','cancelled')
                        NOT NULL DEFAULT 'pending',
            created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql ); // dbDelta cria OU atualiza a tabela com segurança
    }

    private static function set_default_options(): void {
        // Só adiciona se ainda não existir
        add_option( 'wpbooking_sms_enabled',       '0' );
        add_option( 'wpbooking_whatsapp_enabled',  '0' );
        add_option( 'wpbooking_twilio_sid',        '' );
        add_option( 'wpbooking_twilio_token',      '' );
        add_option( 'wpbooking_twilio_from',       '' );
        add_option( 'wpbooking_confirmation_msg',
            'Olá {name}! Seu agendamento de {service} em {date} às {time} foi confirmado.' );
    }
}