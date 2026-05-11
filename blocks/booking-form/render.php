<?php
// $attributes vem do block.json — os atributos do bloco
$title    = $attributes['title']    ?? 'Fazer um agendamento';
$subtitle = $attributes['subtitle'] ?? 'Confirmaremos via SMS ou WhatsApp.';

// Reutiliza exatamente o mesmo shortcode
echo do_shortcode(
    sprintf( '[wpbooking_form title="%s" subtitle="%s"]',
        esc_attr( $title ),
        esc_attr( $subtitle )
    )
);