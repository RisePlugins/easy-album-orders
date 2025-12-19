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

                    $sanitized_material['colors'][] = array(
                        'id'            => isset( $color['id'] ) ? sanitize_key( $color['id'] ) : wp_generate_uuid4(),
                        'name'          => sanitize_text_field( $color['name'] ),
                        'type'          => isset( $color['type'] ) && in_array( $color['type'], array( 'solid', 'texture' ), true )
                            ? $color['type']
                            : 'solid',
                        'color_value'   => isset( $color['color_value'] ) ? sanitize_hex_color( $color['color_value'] ) : '#000000',
                        'texture_id'    => isset( $color['texture_id'] ) ? absint( $color['texture_id'] ) : 0,
                        'texture_coords'=> isset( $color['texture_coords'] ) ? sanitize_text_field( $color['texture_coords'] ) : '',
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
            'currency'            => isset( $settings['currency'] ) ? sanitize_text_field( $settings['currency'] ) : 'USD',
            'currency_symbol'     => isset( $settings['currency_symbol'] ) ? sanitize_text_field( $settings['currency_symbol'] ) : '$',
            'currency_position'   => isset( $settings['currency_position'] ) && in_array( $settings['currency_position'], array( 'before', 'after' ), true )
                ? $settings['currency_position']
                : 'before',
            'email_notifications' => isset( $settings['email_notifications'] ) ? true : false,
            'admin_email'         => isset( $settings['admin_email'] ) ? sanitize_email( $settings['admin_email'] ) : get_option( 'admin_email' ),
        );
    }
}

