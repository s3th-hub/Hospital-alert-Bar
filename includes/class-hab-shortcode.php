<?php
defined( 'ABSPATH' ) || exit;

/**
 * Registers the [hospital_alerts] shortcode.
 */
class HAB_Shortcode {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    private function hooks() {
        add_shortcode( 'hospital_alerts', array( $this, 'render' ) );
    }

    /**
     * [hospital_alerts] shortcode handler.
     *
     * @param  array  $atts    Shortcode attributes (reserved for future use).
     * @param  string $content Enclosed content (unused).
     * @return string          HTML output.
     */
    public function render( $atts, $content = '' ) {
        $atts = shortcode_atts( array(), $atts, 'hospital_alerts' );

        if ( '1' !== HAB_Settings::get( 'enabled' ) ) {
            return '';
        }

        // Ensure front-end assets are loaded (for shortcode in page builders, etc.)
        if ( ! wp_style_is( 'hab-frontend', 'enqueued' ) ) {
            wp_enqueue_style( 'hab-frontend' );
        }
        if ( ! wp_script_is( 'hab-frontend', 'enqueued' ) ) {
            wp_enqueue_script( 'hab-frontend' );
        }

        $alerts = HAB_Frontend::get_active_alerts();
        if ( empty( $alerts ) ) {
            return '';
        }

        // Wrap in a relative container so the bar is inline, not fixed
        return '<div class="hab-shortcode-wrap">' . HAB_Frontend::build_html( $alerts ) . '</div>';
    }
}
