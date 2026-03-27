<?php
defined( 'ABSPATH' ) || exit;

/**
 * Handles front-end output of the alert bar.
 */
class HAB_Frontend {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    private function hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_footer',          array( $this, 'output_alert_bar' ), 100 );
    }

    /** Enqueue front-end CSS and JS */
    public function enqueue_assets() {
        if ( ! self::is_enabled() ) return;

        wp_enqueue_style(
            'hab-frontend',
            HAB_PLUGIN_URL . 'assets/css/hab-frontend.css',
            array(),
            HAB_VERSION
        );

        wp_enqueue_script(
            'hab-frontend',
            HAB_PLUGIN_URL . 'assets/js/hab-frontend.js',
            array( 'jquery' ),
            HAB_VERSION,
            true
        );

        // Pass dynamic settings to JS
        $settings = array(
            'interval'  => 5000,   // ms between slides
            'animation' => 400,    // ms slide animation
        );
        wp_localize_script( 'hab-frontend', 'habSettings', $settings );

        // Inline CSS variables (font-size, padding) — handled via a tiny dynamic style block
        // We use wp_add_inline_style so no inline styles pollute the markup
        $font_size = absint( HAB_Settings::get( 'font_size' ) );
        $padding   = absint( HAB_Settings::get( 'padding' ) );

        $css = sprintf(
            ':root { --hab-font-size: %dpx; --hab-padding: %dpx; }',
            $font_size,
            $padding
        );
        wp_add_inline_style( 'hab-frontend', $css );
    }

    /** Output the alert bar HTML before </body> */
    public function output_alert_bar() {
        if ( ! self::is_enabled() ) return;

        $alerts = self::get_active_alerts();
        if ( empty( $alerts ) ) return;

        echo self::build_html( $alerts ); // phpcs:ignore WordPress.Security.EscapeOutput
    }

    /**
     * Build HTML for the alert bar.
     *
     * @param  WP_Post[] $alerts
     * @return string
     */
    public static function build_html( $alerts ) {
        // Separate top / bottom
        $groups = array( 'top' => array(), 'bottom' => array() );
        foreach ( $alerts as $alert ) {
            $pos              = get_post_meta( $alert->ID, '_hab_position', true ) ?: 'top';
            $groups[ $pos ][] = $alert;
        }

        $output = '';

        foreach ( $groups as $position => $group ) {
            if ( empty( $group ) ) continue;

            $bar_id = 'hab-bar-' . esc_attr( $position );

            $output .= '<div id="' . $bar_id . '" ';
            $output .= 'class="hab-bar hab-bar--' . esc_attr( $position ) . '" ';
            $output .= 'role="region" aria-label="' . esc_attr__( 'Hospital Alerts', 'hospital-alerts-bar' ) . '">';

            // Close button
            $output .= '<button class="hab-close" aria-label="' . esc_attr__( 'Dismiss alerts', 'hospital-alerts-bar' ) . '">';
            $output .= '<svg viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
            $output .= '</button>';

            // Slider wrapper
            $output .= '<div class="hab-slider-wrap">';
            $output .= '<div class="hab-slider" data-count="' . count( $group ) . '">';

            foreach ( $group as $index => $alert ) {
                $bg   = get_post_meta( $alert->ID, '_hab_bg_color',   true ) ?: '#d32f2f';
                $col  = get_post_meta( $alert->ID, '_hab_text_color', true ) ?: '#ffffff';
                $msg  = get_post_meta( $alert->ID, '_hab_message',    true );
                $title = get_the_title( $alert );

                $active_class = ( 0 === $index ) ? ' hab-slide--active' : '';

                $output .= '<div class="hab-slide' . $active_class . '" ';
                $output .= 'style="--slide-bg:' . esc_attr( $bg ) . ';--slide-color:' . esc_attr( $col ) . ';">';

                $output .= '<div class="hab-slide-inner">';

                if ( $title ) {
                    $output .= '<strong class="hab-alert-title">' . esc_html( $title ) . '</strong>';
                    if ( $msg ) {
                        $output .= '<span class="hab-alert-sep" aria-hidden="true"> — </span>';
                    }
                }

                if ( $msg ) {
                    $output .= '<span class="hab-alert-msg">' . esc_html( $msg ) . '</span>';
                }

                $output .= '</div>'; // .hab-slide-inner
                $output .= '</div>'; // .hab-slide
            }

            $output .= '</div>'; // .hab-slider

            // Navigation dots (only when multiple alerts)
            if ( count( $group ) > 1 ) {
                $output .= '<div class="hab-dots" role="tablist" aria-label="' . esc_attr__( 'Alert navigation', 'hospital-alerts-bar' ) . '">';
                foreach ( $group as $i => $alert ) {
                    $output .= '<button class="hab-dot' . ( 0 === $i ? ' hab-dot--active' : '' ) . '" ';
                    $output .= 'role="tab" aria-selected="' . ( 0 === $i ? 'true' : 'false' ) . '" ';
                    $output .= 'data-index="' . $i . '" ';
                    $output .= 'aria-label="' . sprintf( esc_attr__( 'Alert %d', 'hospital-alerts-bar' ), $i + 1 ) . '">';
                    $output .= '</button>';
                }
                $output .= '</div>'; // .hab-dots
            }

            $output .= '</div>'; // .hab-slider-wrap
            $output .= '</div>'; // .hab-bar
        }

        return $output;
    }

    /**
     * Retrieve all active alerts (date check).
     *
     * @return WP_Post[]
     */
    public static function get_active_alerts() {
        $args = array(
            'post_type'      => 'hospital_alert',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order date',
            'order'          => 'ASC',
        );

        $posts = get_posts( $args );

        return array_filter( $posts, function( $post ) {
            return self::is_alert_active( $post->ID );
        } );
    }

    /**
     * Check whether an alert is within its visibility window.
     *
     * @param  int $post_id
     * @return bool
     */
    public static function is_alert_active( $post_id ) {
        $start = get_post_meta( $post_id, '_hab_start_date', true );
        $end   = get_post_meta( $post_id, '_hab_end_date',   true );

        $now   = current_time( 'timestamp' ); // respects WP timezone

        if ( $start ) {
            $start_ts = strtotime( $start . ' 00:00:00' );
            if ( $now < $start_ts ) return false;
        }

        if ( $end ) {
            $end_ts = strtotime( $end . ' 23:59:59' );
            if ( $now > $end_ts ) return false;
        }

        return true;
    }

    /** Whether the bar is globally enabled */
    private static function is_enabled() {
        return '1' === HAB_Settings::get( 'enabled' );
    }
}

/**
 * Template tag for use in theme files.
 *
 * @return void
 */
function hab_render_alerts() {
    if ( ! class_exists( 'HAB_Frontend' ) ) return;
    $alerts = HAB_Frontend::get_active_alerts();
    if ( $alerts ) {
        echo HAB_Frontend::build_html( $alerts ); // phpcs:ignore WordPress.Security.EscapeOutput
    }
}
