jQuery( document ).ready( function( $ ) {

    $( '#wpbooking-form' ).on( 'submit', function( e ) {
        e.preventDefault();

        const $btn = $( '#wpb_submit' );
        const $msg = $( '#wpbooking-msg' );

        // Validação básica no cliente
        const required = ['wpb_name','wpb_phone','wpb_service','wpb_date','wpb_time'];
        let valid = true;
        required.forEach( function( id ) {
            if ( ! $( '#' + id ).val().trim() ) {
                valid = false;
                $( '#' + id ).css( 'border-color', '#E24B4A' );
            } else {
                $( '#' + id ).css( 'border-color', '' );
            }
        });

        if ( ! valid ) {
            showMsg( 'Por favor, preencha todos os campos obrigatórios.', 'error' );
            return;
        }

        $btn.prop( 'disabled', true ).text( 'Enviando...' );
        $msg.hide();

        $.ajax({
            url:    wpbooking_ajax.ajax_url,   // passado pelo wp_localize_script
            method: 'POST',
            data: {
                action:      'wpbooking_submit',
                nonce:       wpbooking_ajax.nonce,
                wpb_name:    $( '#wpb_name' ).val(),
                wpb_phone:   $( '#wpb_phone' ).val(),
                wpb_email:   $( '#wpb_email' ).val(),
                wpb_service: $( '#wpb_service' ).val(),
                wpb_date:    $( '#wpb_date' ).val(),
                wpb_time:    $( '#wpb_time' ).val(),
            },
            success: function( res ) {
                if ( res.success ) {
                    showMsg( res.data.message, 'success' );
                    $( '#wpbooking-form' )[0].reset();
                } else {
                    showMsg( res.data.message || 'Erro ao enviar. Tente novamente.', 'error' );
                }
            },
            error: function() {
                showMsg( 'Erro de conexão. Tente novamente.', 'error' );
            },
            complete: function() {
                $btn.prop( 'disabled', false ).text( 'Confirmar agendamento' );
            }
        });

        function showMsg( text, type ) {
            $msg.removeClass( 'success error' )
                .addClass( type )
                .text( text )
                .show();
        }
    });
});