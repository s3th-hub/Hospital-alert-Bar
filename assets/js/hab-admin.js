/**
 * Hospital Alerts Bar — Admin Script
 * Initialises WP color pickers and drives the live preview.
 * Version: 1.0.0
 */
( function ( $ ) {
    'use strict';

    $( function () {

        /* ------------------------------------------------------------------
         * Initialise WP color pickers
         * ------------------------------------------------------------------ */
        $( '.hab-color-picker' ).wpColorPicker({
            change: function () {
                // Small delay to let the picker update its value
                setTimeout( updatePreview, 50 );
            },
            clear: function () {
                setTimeout( updatePreview, 50 );
            },
        } );

        /* ------------------------------------------------------------------
         * Live preview updater
         * ------------------------------------------------------------------ */
        function updatePreview() {
            var bg      = $( '#hab_bg_color' ).val()   || '#d32f2f';
            var color   = $( '#hab_text_color' ).val() || '#ffffff';
            var message = $.trim( $( '#hab_message' ).val() );

            var $preview = $( '#hab_preview_bar' );
            $preview.css({ 'background-color': bg, 'color': color });

            if ( message ) {
                $preview.text( message );
            } else {
                $preview.text( hab_admin_data.preview_placeholder );
            }
        }

        // Trigger preview on message change
        $( '#hab_message' ).on( 'input', updatePreview );

        // Initial render
        updatePreview();

    } );

} )( jQuery );
