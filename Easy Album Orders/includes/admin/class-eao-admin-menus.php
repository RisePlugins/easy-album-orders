<?php
/**
 * Admin menu registration.
 *
 * Handles the registration and display of admin menus
 * for the Easy Album Orders plugin.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin menus class.
 *
 * @since 1.0.0
 */
class EAO_Admin_Menus {

    /**
     * The ID of this plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /**
     * Register all admin menus.
     *
     * @since 1.0.0
     */
    public function register_menus() {
        // Client Albums parent menu.
        add_menu_page(
            __( 'Client Albums', 'easy-album-orders' ),
            __( 'Client Albums', 'easy-album-orders' ),
            'manage_options',
            'edit.php?post_type=client_album',
            '',
            'dashicons-format-gallery',
            26
        );

        // Album Options submenu.
        add_submenu_page(
            'edit.php?post_type=client_album',
            __( 'Album Options', 'easy-album-orders' ),
            __( 'Album Options', 'easy-album-orders' ),
            'manage_options',
            'eao-album-options',
            array( $this, 'render_album_options_page' )
        );

        // Album Orders menu (standalone).
        add_menu_page(
            __( 'Album Orders', 'easy-album-orders' ),
            __( 'Album Orders', 'easy-album-orders' ),
            'manage_options',
            'edit.php?post_type=album_order',
            '',
            'dashicons-cart',
            27
        );
    }

    /**
     * Render the Album Options settings page.
     *
     * @since 1.0.0
     */
    public function render_album_options_page() {
        // Check user capabilities.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle form submission.
        if ( isset( $_POST['eao_save_options'] ) && check_admin_referer( 'eao_album_options_nonce', 'eao_nonce' ) ) {
            $this->save_album_options();
        }

        // Get current options.
        $materials         = get_option( 'eao_materials', array() );
        $sizes             = get_option( 'eao_sizes', array() );
        $engraving_options = get_option( 'eao_engraving_options', array() );
        $general_settings  = get_option( 'eao_general_settings', array() );

        // Include the view.
        include EAO_PLUGIN_DIR . 'includes/admin/views/album-options-page.php';
    }

    /**
     * Save album options from the settings form.
     *
     * @since  1.0.0
     * @access private
     */
    private function save_album_options() {
        // Sanitize and save materials.
        if ( isset( $_POST['eao_materials'] ) && is_array( $_POST['eao_materials'] ) ) {
            $materials = $this->sanitize_materials( $_POST['eao_materials'] );
            update_option( 'eao_materials', $materials );
        } else {
            update_option( 'eao_materials', array() );
        }

        // Sanitize and save sizes.
        if ( isset( $_POST['eao_sizes'] ) && is_array( $_POST['eao_sizes'] ) ) {
            $sizes = $this->sanitize_sizes( $_POST['eao_sizes'] );
            update_option( 'eao_sizes', $sizes );
        } else {
            update_option( 'eao_sizes', array() );
        }

        // Sanitize and save engraving options.
        if ( isset( $_POST['eao_engraving_options'] ) && is_array( $_POST['eao_engraving_options'] ) ) {
            $engraving = $this->sanitize_engraving_options( $_POST['eao_engraving_options'] );
            update_option( 'eao_engraving_options', $engraving );
        } else {
            update_option( 'eao_engraving_options', array() );
        }

        // Sanitize and save general settings.
        if ( isset( $_POST['eao_general_settings'] ) && is_array( $_POST['eao_general_settings'] ) ) {
            $general = $this->sanitize_general_settings( $_POST['eao_general_settings'] );
            update_option( 'eao_general_settings', $general );
        }

        // Sanitize and save email settings.
        if ( isset( $_POST['eao_email_settings'] ) && is_array( $_POST['eao_email_settings'] ) ) {
            $email = $this->sanitize_email_settings( $_POST['eao_email_settings'] );
            update_option( 'eao_email_settings', $email );
        }

        // Sanitize and save Stripe settings.
        if ( isset( $_POST['eao_stripe_settings'] ) && is_array( $_POST['eao_stripe_settings'] ) ) {
            $stripe = $this->sanitize_stripe_settings( $_POST['eao_stripe_settings'] );
            update_option( 'eao_stripe_settings', $stripe );
        }

        // Add success message.
        add_settings_error(
            'eao_messages',
            'eao_message',
            __( 'Settings saved successfully.', 'easy-album-orders' ),
            'updated'
        );
    }

    /**
     * Sanitize materials array.
     *
     * @since  1.0.0
     * @access private
     *
     * @param array $materials Raw materials data.
     * @return array Sanitized materials data.
     */
    private function sanitize_materials( $materials ) {
        $sanitized = array();

        foreach ( $materials as $material ) {
            if ( empty( $material['name'] ) ) {
                continue;
            }

            $sanitized_material = array(
                'id'              => isset( $material['id'] ) ? sanitize_key( $material['id'] ) : wp_generate_uuid4(),
                'name'            => sanitize_text_field( $material['name'] ),
                'image_id'        => isset( $material['image_id'] ) ? absint( $material['image_id'] ) : 0,
                'upcharge'        => isset( $material['upcharge'] ) ? floatval( $material['upcharge'] ) : 0,
                'allow_engraving' => isset( $material['allow_engraving'] ) ? true : false,
                'restricted_sizes'=> isset( $material['restricted_sizes'] ) && is_array( $material['restricted_sizes'] )
                    ? array_map( 'sanitize_key', $material['restricted_sizes'] )
                    : array(),
                'colors'          => array(),
            );

            // Sanitize colors.
            if ( isset( $material['colors'] ) && is_array( $material['colors'] ) ) {
                foreach ( $material['colors'] as $color ) {
                    if ( empty( $color['name'] ) ) {
                        continue;
                    }

                    // Sanitize texture_region as JSON - strip slashes and validate.
                    $texture_region = '';
                    if ( ! empty( $color['texture_region'] ) ) {
                        // Remove any accumulated slashes (can have many layers from repeated saves).
                        $region_value = $color['texture_region'];
                        
                        // Keep stripping slashes until we get valid JSON or no more changes.
                        // Use a high limit to handle deeply corrupted data.
                        for ( $attempt = 0; $attempt < 100; $attempt++ ) {
                            $decoded = json_decode( $region_value, true );
                            if ( $decoded !== null && isset( $decoded['x'] ) && isset( $decoded['y'] ) && isset( $decoded['zoom'] ) ) {
                                // Valid JSON with expected structure - re-encode cleanly.
                                $texture_region = wp_json_encode( array(
                                    'x'    => sanitize_text_field( $decoded['x'] ),
                                    'y'    => sanitize_text_field( $decoded['y'] ),
                                    'zoom' => sanitize_text_field( $decoded['zoom'] ),
                                ) );
                                break;
                            }
                            // Try stripping more slashes.
                            $new_value = stripslashes( $region_value );
                            if ( $new_value === $region_value ) {
                                // No more slashes to strip - data is unrecoverable.
                                break;
                            }
                            $region_value = $new_value;
                        }
                    }

                    $sanitized_material['colors'][] = array(
                        'id'               => isset( $color['id'] ) ? sanitize_key( $color['id'] ) : wp_generate_uuid4(),
                        'name'             => sanitize_text_field( $color['name'] ),
                        'type'             => isset( $color['type'] ) && in_array( $color['type'], array( 'solid', 'texture' ), true )
                            ? $color['type']
                            : 'solid',
                        'color_value'      => isset( $color['color_value'] ) ? sanitize_hex_color( $color['color_value'] ) : '#000000',
                        'texture_image_id' => isset( $color['texture_image_id'] ) ? absint( $color['texture_image_id'] ) : 0,
                        'texture_region'   => $texture_region,
                        'preview_image_id' => isset( $color['preview_image_id'] ) ? absint( $color['preview_image_id'] ) : 0,
                    );
                }
            }

            $sanitized[] = $sanitized_material;
        }

        return $sanitized;
    }

    /**
     * Sanitize sizes array.
     *
     * @since  1.0.0
     * @access private
     *
     * @param array $sizes Raw sizes data.
     * @return array Sanitized sizes data.
     */
    private function sanitize_sizes( $sizes ) {
        $sanitized = array();

        foreach ( $sizes as $size ) {
            if ( empty( $size['name'] ) ) {
                continue;
            }

            $sanitized[] = array(
                'id'         => isset( $size['id'] ) ? sanitize_key( $size['id'] ) : wp_generate_uuid4(),
                'name'       => sanitize_text_field( $size['name'] ),
                'dimensions' => isset( $size['dimensions'] ) ? sanitize_text_field( $size['dimensions'] ) : '',
                'upcharge'   => isset( $size['upcharge'] ) ? floatval( $size['upcharge'] ) : 0,
                'image_id'   => isset( $size['image_id'] ) ? absint( $size['image_id'] ) : 0,
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize engraving options array.
     *
     * @since  1.0.0
     * @access private
     *
     * @param array $options Raw engraving options data.
     * @return array Sanitized engraving options data.
     */
    private function sanitize_engraving_options( $options ) {
        $sanitized = array();

        foreach ( $options as $option ) {
            if ( empty( $option['name'] ) ) {
                continue;
            }

            $sanitized[] = array(
                'id'              => isset( $option['id'] ) ? sanitize_key( $option['id'] ) : wp_generate_uuid4(),
                'name'            => sanitize_text_field( $option['name'] ),
                'upcharge'        => isset( $option['upcharge'] ) ? floatval( $option['upcharge'] ) : 0,
                'character_limit' => isset( $option['character_limit'] ) ? absint( $option['character_limit'] ) : 0,
                'fonts'           => isset( $option['fonts'] ) ? sanitize_textarea_field( $option['fonts'] ) : '',
                'description'     => isset( $option['description'] ) ? sanitize_textarea_field( $option['description'] ) : '',
                'image_id'        => isset( $option['image_id'] ) ? absint( $option['image_id'] ) : 0,
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize general settings array.
     *
     * @since  1.0.0
     * @access private
     *
     * @param array $settings Raw settings data.
     * @return array Sanitized settings data.
     */
    private function sanitize_general_settings( $settings ) {
        return array(
            'currency'          => isset( $settings['currency'] ) ? sanitize_text_field( $settings['currency'] ) : 'USD',
            'currency_symbol'   => isset( $settings['currency_symbol'] ) ? sanitize_text_field( $settings['currency_symbol'] ) : '$',
            'currency_position' => isset( $settings['currency_position'] ) && in_array( $settings['currency_position'], array( 'before', 'after' ), true )
                ? $settings['currency_position']
                : 'before',
            'brand_color'       => isset( $settings['brand_color'] ) ? sanitize_hex_color( $settings['brand_color'] ) : '#e67e22',
        );
    }

    /**
     * Sanitize email settings.
     *
     * @since  1.0.0
     * @access private
     *
     * @param array $settings Raw email settings.
     * @return array Sanitized email settings.
     */
    private function sanitize_email_settings( $settings ) {
        return array(
            // Master toggle.
            'email_notifications'           => isset( $settings['email_notifications'] ) ? true : false,
            'admin_email'                   => isset( $settings['admin_email'] ) ? sanitize_email( $settings['admin_email'] ) : get_option( 'admin_email' ),

            // Sender settings.
            'from_name'                     => isset( $settings['from_name'] ) ? sanitize_text_field( $settings['from_name'] ) : '',
            'from_email'                    => isset( $settings['from_email'] ) ? sanitize_email( $settings['from_email'] ) : '',

            // Branding.
            'logo_url'                      => isset( $settings['logo_url'] ) ? esc_url_raw( $settings['logo_url'] ) : '',
            'accent_color'                  => isset( $settings['accent_color'] ) ? sanitize_hex_color( $settings['accent_color'] ) : '#e67e22',

            // Order Confirmation.
            'enable_order_confirmation'     => isset( $settings['enable_order_confirmation'] ) ? true : false,
            'order_confirmation_subject'    => isset( $settings['order_confirmation_subject'] ) ? sanitize_text_field( $this->clean_subject_line( $settings['order_confirmation_subject'] ) ) : '',

            // New Order Alert.
            'enable_new_order_alert'        => isset( $settings['enable_new_order_alert'] ) ? true : false,
            'new_order_alert_subject'       => isset( $settings['new_order_alert_subject'] ) ? sanitize_text_field( $this->clean_subject_line( $settings['new_order_alert_subject'] ) ) : '',

            // Shipped Notification.
            'enable_shipped_notification'   => isset( $settings['enable_shipped_notification'] ) ? true : false,
            'shipped_notification_subject'  => isset( $settings['shipped_notification_subject'] ) ? sanitize_text_field( $this->clean_subject_line( $settings['shipped_notification_subject'] ) ) : '',

            // Cart Reminder.
            'enable_cart_reminder'          => isset( $settings['enable_cart_reminder'] ) ? true : false,
            'cart_reminder_days'            => isset( $settings['cart_reminder_days'] ) ? absint( $settings['cart_reminder_days'] ) : 3,
            'cart_reminder_subject'         => isset( $settings['cart_reminder_subject'] ) ? sanitize_text_field( $this->clean_subject_line( $settings['cart_reminder_subject'] ) ) : '',
        );
    }

    /**
     * Clean subject line by stripping accumulated backslashes.
     *
     * Fixes a bug where backslashes could accumulate before apostrophes
     * and quotes through repeated form saves.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $subject Raw subject line value.
     * @return string Cleaned subject line.
     */
    private function clean_subject_line( $subject ) {
        // Strip any accumulated backslashes (can be deeply nested from bug).
        // Keep stripping until no more changes occur.
        $previous = '';
        $cleaned  = $subject;
        while ( $cleaned !== $previous ) {
            $previous = $cleaned;
            $cleaned  = stripslashes( $cleaned );
        }
        return $cleaned;
    }

    /**
     * Sanitize Stripe settings.
     *
     * @since  1.1.0
     * @access private
     *
     * @param array $settings Raw Stripe settings.
     * @return array Sanitized Stripe settings.
     */
    private function sanitize_stripe_settings( $settings ) {
        return array(
            // Enable/Disable.
            'enabled'              => isset( $settings['enabled'] ) ? true : false,

            // Mode (test or live).
            'mode'                 => isset( $settings['mode'] ) && in_array( $settings['mode'], array( 'test', 'live' ), true )
                ? $settings['mode']
                : 'test',

            // Test API Keys.
            'test_publishable_key' => isset( $settings['test_publishable_key'] )
                ? $this->sanitize_stripe_key( $settings['test_publishable_key'] )
                : '',
            'test_secret_key'      => isset( $settings['test_secret_key'] )
                ? $this->sanitize_stripe_key( $settings['test_secret_key'] )
                : '',

            // Live API Keys.
            'live_publishable_key' => isset( $settings['live_publishable_key'] )
                ? $this->sanitize_stripe_key( $settings['live_publishable_key'] )
                : '',
            'live_secret_key'      => isset( $settings['live_secret_key'] )
                ? $this->sanitize_stripe_key( $settings['live_secret_key'] )
                : '',

            // Webhook Secret.
            'webhook_secret'       => isset( $settings['webhook_secret'] )
                ? $this->sanitize_stripe_key( $settings['webhook_secret'] )
                : '',

            // Statement Descriptor (max 22 chars, alphanumeric + spaces/hyphens).
            'statement_descriptor' => isset( $settings['statement_descriptor'] )
                ? substr( preg_replace( '/[^a-zA-Z0-9 \-]/', '', sanitize_text_field( $settings['statement_descriptor'] ) ), 0, 22 )
                : 'Album Order',
        );
    }

    /**
     * Sanitize a Stripe API key.
     *
     * Removes whitespace and validates basic format.
     *
     * @since  1.1.0
     * @access private
     *
     * @param string $key Raw API key.
     * @return string Sanitized API key.
     */
    private function sanitize_stripe_key( $key ) {
        // Remove whitespace and sanitize.
        $key = trim( sanitize_text_field( $key ) );

        // Remove any accidental quotes or slashes.
        $key = stripslashes( $key );
        $key = str_replace( array( '"', "'" ), '', $key );

        return $key;
    }
}

