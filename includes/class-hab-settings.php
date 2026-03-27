<?php
defined( 'ABSPATH' ) || exit;

/**
 * Settings page for Hospital Alerts Bar.
 */
class HAB_Settings {

    private static $instance  = null;
    const OPTION_KEY          = 'hab_settings';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    private function hooks() {
        add_action( 'admin_menu',            array( $this, 'register_menu' ) );
        add_action( 'admin_init',            array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_settings_assets' ) );
    }

    public function register_menu() {
        add_menu_page(
            __( 'Hospital Alerts', 'hospital-alerts-bar' ),
            __( 'Hospital Alerts', 'hospital-alerts-bar' ),
            'manage_options',
            'hab_settings',
            array( $this, 'render_page' ),
            'dashicons-megaphone',
            30
        );

        add_submenu_page(
            'hab_settings',
            __( 'Settings', 'hospital-alerts-bar' ),
            __( 'Settings', 'hospital-alerts-bar' ),
            'manage_options',
            'hab_settings',
            array( $this, 'render_page' )
        );
    }

    public function enqueue_settings_assets( $hook ) {
        if ( 'toplevel_page_hab_settings' !== $hook ) return;
        wp_enqueue_style(
            'hab-admin-style',
            HAB_PLUGIN_URL . 'assets/css/hab-admin.css',
            array(),
            HAB_VERSION
        );
    }

    public function register_settings() {
        register_setting(
            'hab_settings_group',
            self::OPTION_KEY,
            array(
                'sanitize_callback' => array( $this, 'sanitize_options' ),
                'default'           => self::defaults(),
            )
        );

        add_settings_section(
            'hab_main_section',
            __( 'Global Alert Bar Settings', 'hospital-alerts-bar' ),
            '__return_false',
            'hab_settings'
        );

        $fields = array(
            'enabled'   => __( 'Enable Alert Bar',  'hospital-alerts-bar' ),
            'font_size' => __( 'Global Font Size',  'hospital-alerts-bar' ),
            'padding'   => __( 'Global Padding',    'hospital-alerts-bar' ),
        );

        foreach ( $fields as $id => $title ) {
            add_settings_field(
                'hab_' . $id,
                $title,
                array( $this, 'render_field_' . $id ),
                'hab_settings',
                'hab_main_section'
            );
        }
    }

    /** Default option values */
    public static function defaults() {
        return array(
            'enabled'   => '1',
            'font_size' => '15',
            'padding'   => '14',
        );
    }

    /** Get a single option with fallback to default */
    public static function get( $key ) {
        $opts = get_option( self::OPTION_KEY, self::defaults() );
        return isset( $opts[ $key ] ) ? $opts[ $key ] : ( self::defaults()[ $key ] ?? '' );
    }

    /** Sanitize on save */
    public function sanitize_options( $input ) {
        $clean = array();
        $clean['enabled']   = ! empty( $input['enabled'] ) ? '1' : '0';
        $clean['font_size'] = absint( $input['font_size'] ?? 15 );
        $clean['padding']   = absint( $input['padding']   ?? 14 );

        // Clamp values to sensible ranges
        $clean['font_size'] = max( 10, min( 40, $clean['font_size'] ) );
        $clean['padding']   = max( 4,  min( 60, $clean['padding'] ) );

        return $clean;
    }

    /* ---------------------------------------------------------------
     * Field renderers
     * --------------------------------------------------------------- */

    public function render_field_enabled() {
        $val = self::get( 'enabled' );
        ?>
        <label class="hab-toggle">
            <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enabled]"
                value="1" <?php checked( $val, '1' ); ?> />
            <span class="hab-toggle-slider"></span>
            <span class="hab-toggle-label">
                <?php esc_html_e( 'Show alert bar on the front-end', 'hospital-alerts-bar' ); ?>
            </span>
        </label>
        <?php
    }

    public function render_field_font_size() {
        $val = self::get( 'font_size' );
        ?>
        <div class="hab-range-wrap">
            <input type="number" min="10" max="40"
                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[font_size]"
                value="<?php echo esc_attr( $val ); ?>" class="small-text" />
            <span class="hab-unit">px</span>
            <p class="description"><?php esc_html_e( 'Font size for all alert messages (10–40 px).', 'hospital-alerts-bar' ); ?></p>
        </div>
        <?php
    }

    public function render_field_padding() {
        $val = self::get( 'padding' );
        ?>
        <div class="hab-range-wrap">
            <input type="number" min="4" max="60"
                name="<?php echo esc_attr( self::OPTION_KEY ); ?>[padding]"
                value="<?php echo esc_attr( $val ); ?>" class="small-text" />
            <span class="hab-unit">px</span>
            <p class="description"><?php esc_html_e( 'Top/bottom padding for the alert bar (4–60 px).', 'hospital-alerts-bar' ); ?></p>
        </div>
        <?php
    }

    /** Render the settings page */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        ?>
        <div class="wrap hab-settings-wrap">
            <h1 class="hab-settings-title">
                <span class="dashicons dashicons-megaphone"></span>
                <?php esc_html_e( 'Hospital Alerts Bar — Settings', 'hospital-alerts-bar' ); ?>
            </h1>

            <?php settings_errors( 'hab_settings_group' ); ?>

            <div class="hab-settings-layout">
                <div class="hab-settings-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'hab_settings_group' );
                        do_settings_sections( 'hab_settings' );
                        submit_button( __( 'Save Settings', 'hospital-alerts-bar' ) );
                        ?>
                    </form>
                </div>

                <div class="hab-settings-sidebar">
                    <div class="hab-info-card">
                        <h3><?php esc_html_e( 'Quick Help', 'hospital-alerts-bar' ); ?></h3>
                        <ul>
                            <li><?php esc_html_e( '1. Create alerts via Hospital Alerts → Add New Alert.', 'hospital-alerts-bar' ); ?></li>
                            <li><?php esc_html_e( '2. Set start &amp; end dates for automatic visibility.', 'hospital-alerts-bar' ); ?></li>
                            <li><?php esc_html_e( '3. The bar auto-displays site-wide before &lt;/body&gt;.', 'hospital-alerts-bar' ); ?></li>
                            <li><?php esc_html_e( '4. Use shortcode [hospital_alerts] anywhere.', 'hospital-alerts-bar' ); ?></li>
                        </ul>
                        <hr/>
                        <p><strong><?php esc_html_e( 'Shortcode:', 'hospital-alerts-bar' ); ?></strong><br/>
                        <code>[hospital_alerts]</code></p>
                        <p><strong><?php esc_html_e( 'Template tag:', 'hospital-alerts-bar' ); ?></strong><br/>
                        <code>&lt;?php hab_render_alerts(); ?&gt;</code></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
