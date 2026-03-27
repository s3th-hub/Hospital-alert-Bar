<?php
defined( 'ABSPATH' ) || exit;

/**
 * Registers the 'hospital_alert' custom post type.
 */
class HAB_Post_Type {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    private function hooks() {
        add_action( 'init', array( __CLASS__, 'register_cpt' ) );
        add_filter( 'manage_hospital_alert_posts_columns',       array( $this, 'custom_columns' ) );
        add_action( 'manage_hospital_alert_posts_custom_column', array( $this, 'render_columns' ), 10, 2 );
    }

    /**
     * Register the CPT — also called on activation.
     */
    public static function register_cpt() {
        $labels = array(
            'name'               => _x( 'Hospital Alerts', 'post type general name', 'hospital-alerts-bar' ),
            'singular_name'      => _x( 'Hospital Alert',  'post type singular name', 'hospital-alerts-bar' ),
            'add_new'            => __( 'Add New Alert',   'hospital-alerts-bar' ),
            'add_new_item'       => __( 'Add New Alert',   'hospital-alerts-bar' ),
            'edit_item'          => __( 'Edit Alert',      'hospital-alerts-bar' ),
            'new_item'           => __( 'New Alert',       'hospital-alerts-bar' ),
            'view_item'          => __( 'View Alert',      'hospital-alerts-bar' ),
            'search_items'       => __( 'Search Alerts',   'hospital-alerts-bar' ),
            'not_found'          => __( 'No alerts found', 'hospital-alerts-bar' ),
            'not_found_in_trash' => __( 'No alerts found in Trash', 'hospital-alerts-bar' ),
            'menu_name'          => __( 'Hospital Alerts', 'hospital-alerts-bar' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => 'hab_settings',   // nest under our settings page
            'capability_type'    => 'post',
            'supports'           => array( 'title' ),
            'has_archive'        => false,
            'rewrite'            => false,
        );

        register_post_type( 'hospital_alert', $args );
    }

    /** Add custom admin columns */
    public function custom_columns( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['hab_position']  = __( 'Position',   'hospital-alerts-bar' );
                $new['hab_start']     = __( 'Start Date', 'hospital-alerts-bar' );
                $new['hab_end']       = __( 'End Date',   'hospital-alerts-bar' );
                $new['hab_active']    = __( 'Active Now', 'hospital-alerts-bar' );
            }
        }
        return $new;
    }

    /** Render custom column content */
    public function render_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'hab_position':
                $pos = get_post_meta( $post_id, '_hab_position', true );
                echo esc_html( ucfirst( $pos ?: 'top' ) );
                break;

            case 'hab_start':
                $start = get_post_meta( $post_id, '_hab_start_date', true );
                echo $start ? esc_html( date_i18n( get_option('date_format'), strtotime( $start ) ) ) : '—';
                break;

            case 'hab_end':
                $end = get_post_meta( $post_id, '_hab_end_date', true );
                echo $end ? esc_html( date_i18n( get_option('date_format'), strtotime( $end ) ) ) : '—';
                break;

            case 'hab_active':
                $active = HAB_Frontend::is_alert_active( $post_id );
                if ( $active ) {
                    echo '<span style="color:#46b450;font-weight:600;">&#10003; ' . esc_html__( 'Yes', 'hospital-alerts-bar' ) . '</span>';
                } else {
                    echo '<span style="color:#dc3232;">&#10007; ' . esc_html__( 'No', 'hospital-alerts-bar' ) . '</span>';
                }
                break;
        }
    }
}
