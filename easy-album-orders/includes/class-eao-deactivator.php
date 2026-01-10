<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin deactivator class.
 *
 * @since 1.0.0
 */
class EAO_Deactivator {

    /**
     * Deactivation routine.
     *
     * Performs cleanup tasks when the plugin is deactivated.
     * Note: We don't delete options or data on deactivation.
     * Data cleanup should only happen on uninstall.
     *
     * @since 1.0.0
     */
    public static function deactivate() {
        // Flush rewrite rules.
        flush_rewrite_rules();

        // Clear any scheduled events.
        self::clear_scheduled_events();

        // Clear transients.
        self::clear_transients();
    }

    /**
     * Clear any scheduled cron events.
     *
     * @since  1.0.0
     * @access private
     */
    private static function clear_scheduled_events() {
        // Clear any scheduled events that the plugin may have created.
        wp_clear_scheduled_hook( 'eao_daily_cleanup' );
        wp_clear_scheduled_hook( 'eao_send_notifications' );
        wp_clear_scheduled_hook( 'eao_cart_reminder_check' );
    }

    /**
     * Clear plugin transients.
     *
     * @since  1.0.0
     * @access private
     */
    private static function clear_transients() {
        delete_transient( 'eao_activation_notice' );
    }
}

