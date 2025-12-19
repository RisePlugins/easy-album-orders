<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin activator class.
 *
 * @since 1.0.0
 */
class EAO_Activator {

    /**
     * Activation routine.
     *
     * Performs tasks that need to run when the plugin is activated:
     * - Create necessary database tables
     * - Set default options
     * - Register custom post types and flush rewrite rules
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Set plugin version in options.
        update_option( 'eao_version', EAO_VERSION );

        // Set default options if they don't exist.
        self::set_default_options();

        // Register post types to flush rewrite rules.
        self::register_post_types();

        // Flush rewrite rules.
        flush_rewrite_rules();

        // Set activation flag for admin notice.
        set_transient( 'eao_activation_notice', true, 30 );
    }

    /**
     * Set default plugin options.
     *
     * @since  1.0.0
     * @access private
     */
    private static function set_default_options() {
        // Default general settings.
        $default_settings = array(
            'currency'             => 'USD',
            'currency_symbol'      => '$',
            'currency_position'    => 'before',
            'email_notifications'  => true,
            'admin_email'          => get_option( 'admin_email' ),
        );

        // Only set defaults if option doesn't exist.
        if ( false === get_option( 'eao_general_settings' ) ) {
            update_option( 'eao_general_settings', $default_settings );
        }

        // Default materials (empty array).
        if ( false === get_option( 'eao_materials' ) ) {
            update_option( 'eao_materials', array() );
        }

        // Default sizes (empty array).
        if ( false === get_option( 'eao_sizes' ) ) {
            update_option( 'eao_sizes', array() );
        }

        // Default engraving options (empty array).
        if ( false === get_option( 'eao_engraving_options' ) ) {
            update_option( 'eao_engraving_options', array() );
        }
    }

    /**
     * Register post types for rewrite rules flush.
     *
     * @since  1.0.0
     * @access private
     */
    private static function register_post_types() {
        // Include post type classes.
        require_once EAO_PLUGIN_DIR . 'includes/post-types/class-eao-client-album.php';
        require_once EAO_PLUGIN_DIR . 'includes/post-types/class-eao-album-order.php';

        // Register post types.
        $client_album = new EAO_Client_Album();
        $client_album->register_post_type();

        $album_order = new EAO_Album_Order();
        $album_order->register_post_type();
    }
}

