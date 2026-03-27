<?php
/**
 * Plugin Name:       Hospital Alerts Bar
 * Plugin URI:        https://example.com/hospital-alerts-bar
 * Description:       Display hospital announcements as a responsive, fixed alert bar with slider support, date-based visibility, and full admin control.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:           s3thhub
 * License:           GPL v2 or later
 * Text Domain:       hospital-alerts-bar
 */

defined( 'ABSPATH' ) || exit;

define( 'HAB_VERSION',     '1.0.0' );
define( 'HAB_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'HAB_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'HAB_PLUGIN_FILE', __FILE__ );

// Core includes
require_once HAB_PLUGIN_DIR . 'includes/class-hab-post-type.php';
require_once HAB_PLUGIN_DIR . 'includes/class-hab-meta-boxes.php';
require_once HAB_PLUGIN_DIR . 'includes/class-hab-settings.php';
require_once HAB_PLUGIN_DIR . 'includes/class-hab-frontend.php';
require_once HAB_PLUGIN_DIR . 'includes/class-hab-shortcode.php';

/**
 * Bootstrap all components.
 */
function hab_init() {
    HAB_Post_Type::instance();
    HAB_Meta_Boxes::instance();
    HAB_Settings::instance();
    HAB_Frontend::instance();
    HAB_Shortcode::instance();
}
add_action( 'plugins_loaded', 'hab_init' );

/**
 * Activation: flush rewrite rules.
 */
function hab_activate() {
    HAB_Post_Type::register_cpt();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'hab_activate' );

/**
 * Deactivation: flush rewrite rules.
 */
function hab_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'hab_deactivate' );
