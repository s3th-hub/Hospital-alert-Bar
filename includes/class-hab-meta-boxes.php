<?php
defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders meta boxes for the hospital_alert CPT.
 */
class HAB_Meta_Boxes {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    private function hooks() {
        add_action( 'add_meta_boxes', array( $this, 'register' ) );
        add_action( 'save_post_hospital_alert', array( $this, 'save' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /** Enqueue color picker on alert edit screen */
    public function enqueue_admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || 'hospital_alert' !== $screen->post_type ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_style(
            'hab-admin-style',
            HAB_PLUGIN_URL . 'assets/css/hab-admin.css',
            array(),
            HAB_VERSION
        );

        wp_enqueue_script(
            'hab-admin-script',
            HAB_PLUGIN_URL . 'assets/js/hab-admin.js',
            array( 'wp-color-picker', 'jquery' ),
            HAB_VERSION,
            true
        );

        wp_localize_script( 'hab-admin-script', 'hab_admin_data', array(
            'preview_placeholder' => __( 'Sample alert message will appear here.', 'hospital-alerts-bar' ),
        ) );
    }

    public function register() {
        add_meta_box(
            'hab_alert_details',
            __( 'Alert Details', 'hospital-alerts-bar' ),
            array( $this, 'render' ),
            'hospital_alert',
            'normal',
            'high'
        );
    }

    public function render( $post ) {
        wp_nonce_field( 'hab_save_meta', 'hab_meta_nonce' );

        $bg_color   = get_post_meta( $post->ID, '_hab_bg_color',    true ) ?: '#d32f2f';
        $text_color = get_post_meta( $post->ID, '_hab_text_color',  true ) ?: '#ffffff';
        $message    = get_post_meta( $post->ID, '_hab_message',     true );
        $start      = get_post_meta( $post->ID, '_hab_start_date',  true );
        $end        = get_post_meta( $post->ID, '_hab_end_date',    true );
        $position   = get_post_meta( $post->ID, '_hab_position',    true ) ?: 'top';
        ?>
        <div class="hab-meta-wrap">

            <div class="hab-field">
                <label for="hab_message"><strong><?php esc_html_e( 'Alert Message', 'hospital-alerts-bar' ); ?></strong></label>
                <textarea id="hab_message" name="hab_message" rows="4"
                    placeholder="<?php esc_attr_e( 'Enter the full alert message…', 'hospital-alerts-bar' ); ?>"
                ><?php echo esc_textarea( $message ); ?></textarea>
                <p class="description"><?php esc_html_e( 'This text is displayed in the alert bar. HTML is not allowed.', 'hospital-alerts-bar' ); ?></p>
            </div>

            <div class="hab-field-row">
                <div class="hab-field">
                    <label for="hab_bg_color"><strong><?php esc_html_e( 'Background Color', 'hospital-alerts-bar' ); ?></strong></label>
                    <input type="text" id="hab_bg_color" name="hab_bg_color"
                        value="<?php echo esc_attr( $bg_color ); ?>"
                        class="hab-color-picker" data-default-color="#d32f2f" />
                </div>

                <div class="hab-field">
                    <label for="hab_text_color"><strong><?php esc_html_e( 'Text Color', 'hospital-alerts-bar' ); ?></strong></label>
                    <input type="text" id="hab_text_color" name="hab_text_color"
                        value="<?php echo esc_attr( $text_color ); ?>"
                        class="hab-color-picker" data-default-color="#ffffff" />
                </div>
            </div>

            <div class="hab-field-row">
                <div class="hab-field">
                    <label for="hab_start_date"><strong><?php esc_html_e( 'Start Date', 'hospital-alerts-bar' ); ?></strong></label>
                    <input type="date" id="hab_start_date" name="hab_start_date"
                        value="<?php echo esc_attr( $start ); ?>" />
                </div>

                <div class="hab-field">
                    <label for="hab_end_date"><strong><?php esc_html_e( 'End Date', 'hospital-alerts-bar' ); ?></strong></label>
                    <input type="date" id="hab_end_date" name="hab_end_date"
                        value="<?php echo esc_attr( $end ); ?>" />
                </div>
            </div>

            <div class="hab-field">
                <label><strong><?php esc_html_e( 'Display Position', 'hospital-alerts-bar' ); ?></strong></label>
                <div class="hab-radio-group">
                    <label>
                        <input type="radio" name="hab_position" value="top"
                            <?php checked( $position, 'top' ); ?> />
                        <?php esc_html_e( 'Top of page', 'hospital-alerts-bar' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="hab_position" value="bottom"
                            <?php checked( $position, 'bottom' ); ?> />
                        <?php esc_html_e( 'Bottom of page', 'hospital-alerts-bar' ); ?>
                    </label>
                </div>
            </div>

            <div class="hab-preview-row">
                <strong><?php esc_html_e( 'Live Preview:', 'hospital-alerts-bar' ); ?></strong>
                <div id="hab_preview_bar"
                    style="background:<?php echo esc_attr( $bg_color ); ?>;color:<?php echo esc_attr( $text_color ); ?>;">
                    <?php echo esc_html( $message ?: __( 'Sample alert message will appear here.', 'hospital-alerts-bar' ) ); ?>
                </div>
            </div>

        </div>
        <?php
    }

    public function save( $post_id, $post ) {
        // Nonce check
        if ( ! isset( $_POST['hab_meta_nonce'] ) ||
             ! wp_verify_nonce( $_POST['hab_meta_nonce'], 'hab_save_meta' ) ) {
            return;
        }

        // Autosave / permissions
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = array(
            '_hab_message'    => 'sanitize_textarea_field',
            '_hab_bg_color'   => 'sanitize_hex_color',
            '_hab_text_color' => 'sanitize_hex_color',
            '_hab_start_date' => 'sanitize_text_field',
            '_hab_end_date'   => 'sanitize_text_field',
            '_hab_position'   => 'sanitize_text_field',
        );

        $post_keys = array(
            '_hab_message'    => 'hab_message',
            '_hab_bg_color'   => 'hab_bg_color',
            '_hab_text_color' => 'hab_text_color',
            '_hab_start_date' => 'hab_start_date',
            '_hab_end_date'   => 'hab_end_date',
            '_hab_position'   => 'hab_position',
        );

        foreach ( $fields as $meta_key => $sanitizer ) {
            $post_key = $post_keys[ $meta_key ];
            if ( isset( $_POST[ $post_key ] ) ) {
                $value = call_user_func( $sanitizer, wp_unslash( $_POST[ $post_key ] ) );
                // Validate position
                if ( '_hab_position' === $meta_key ) {
                    $value = in_array( $value, array( 'top', 'bottom' ), true ) ? $value : 'top';
                }
                update_post_meta( $post_id, $meta_key, $value );
            }
        }
    }
}
