<?php
/**
 * Helper functions.
 *
 * Utility functions used throughout the plugin.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helpers class.
 *
 * @since 1.0.0
 */
class EAO_Helpers {

    /**
     * Format a price with currency symbol.
     *
     * @since 1.0.0
     *
     * @param float $price The price to format.
     * @return string Formatted price with currency symbol.
     */
    public static function format_price( $price ) {
        $settings = get_option( 'eao_general_settings', array() );
        $symbol   = isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
        $position = isset( $settings['currency_position'] ) ? $settings['currency_position'] : 'before';

        $formatted = number_format( floatval( $price ), 2 );

        if ( 'before' === $position ) {
            return $symbol . $formatted;
        }

        return $formatted . $symbol;
    }

    /**
     * Get a material by ID.
     *
     * @since 1.0.0
     *
     * @param string $material_id The material ID.
     * @return array|null Material data or null if not found.
     */
    public static function get_material( $material_id ) {
        $materials = get_option( 'eao_materials', array() );

        foreach ( $materials as $material ) {
            if ( isset( $material['id'] ) && $material['id'] === $material_id ) {
                return $material;
            }
        }

        return null;
    }

    /**
     * Get a size by ID.
     *
     * @since 1.0.0
     *
     * @param string $size_id The size ID.
     * @return array|null Size data or null if not found.
     */
    public static function get_size( $size_id ) {
        $sizes = get_option( 'eao_sizes', array() );

        foreach ( $sizes as $size ) {
            if ( isset( $size['id'] ) && $size['id'] === $size_id ) {
                return $size;
            }
        }

        return null;
    }

    /**
     * Get an engraving option by ID.
     *
     * @since 1.0.0
     *
     * @param string $option_id The engraving option ID.
     * @return array|null Engraving option data or null if not found.
     */
    public static function get_engraving_option( $option_id ) {
        $options = get_option( 'eao_engraving_options', array() );

        foreach ( $options as $option ) {
            if ( isset( $option['id'] ) && $option['id'] === $option_id ) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Get available sizes for a material.
     *
     * @since 1.0.0
     *
     * @param string $material_id The material ID.
     * @return array Array of available sizes.
     */
    public static function get_available_sizes_for_material( $material_id ) {
        $material = self::get_material( $material_id );
        $sizes    = get_option( 'eao_sizes', array() );

        if ( ! $material ) {
            return $sizes;
        }

        // If material has restricted sizes, filter them.
        if ( ! empty( $material['restricted_sizes'] ) ) {
            return array_filter( $sizes, function( $size ) use ( $material ) {
                return in_array( $size['id'], $material['restricted_sizes'], true );
            } );
        }

        return $sizes;
    }

    /**
     * Check if engraving is allowed for a material.
     *
     * @since 1.0.0
     *
     * @param string $material_id The material ID.
     * @return bool True if engraving is allowed, false otherwise.
     */
    public static function is_engraving_allowed( $material_id ) {
        $material = self::get_material( $material_id );

        if ( ! $material ) {
            return false;
        }

        return ! empty( $material['allow_engraving'] );
    }

    /**
     * Calculate album price.
     *
     * @since 1.0.0
     *
     * @param float $base_price        The base price.
     * @param float $material_upcharge The material upcharge.
     * @param float $size_upcharge     The size upcharge.
     * @param float $engraving_upcharge The engraving upcharge.
     * @param float $credits           Credits to apply.
     * @return float The calculated total price.
     */
    public static function calculate_price( $base_price, $material_upcharge = 0, $size_upcharge = 0, $engraving_upcharge = 0, $credits = 0 ) {
        $total = ( floatval( $base_price ) + floatval( $material_upcharge ) + floatval( $size_upcharge ) + floatval( $engraving_upcharge ) ) - floatval( $credits );

        return max( 0, $total );
    }

    /**
     * Get attachment URL by ID.
     *
     * @since 1.0.0
     *
     * @param int    $attachment_id The attachment ID.
     * @param string $size          Optional. Image size. Default 'full'.
     * @return string The attachment URL or empty string.
     */
    public static function get_attachment_url( $attachment_id, $size = 'full' ) {
        if ( empty( $attachment_id ) ) {
            return '';
        }

        $url = wp_get_attachment_image_url( $attachment_id, $size );

        return $url ? $url : '';
    }

    /**
     * Sanitize a phone number.
     *
     * @since 1.0.0
     *
     * @param string $phone The phone number to sanitize.
     * @return string Sanitized phone number.
     */
    public static function sanitize_phone( $phone ) {
        return preg_replace( '/[^0-9+\-\(\)\s]/', '', $phone );
    }

    /**
     * Generate a unique order number.
     *
     * @since 1.0.0
     *
     * @param int $order_id The order post ID.
     * @return string Formatted order number.
     */
    public static function generate_order_number( $order_id ) {
        return 'EAO-' . str_pad( $order_id, 6, '0', STR_PAD_LEFT );
    }

    /**
     * Get status color class.
     *
     * @since 1.0.0
     *
     * @param string $status The order status.
     * @return string CSS class for the status color.
     */
    public static function get_status_color_class( $status ) {
        $classes = array(
            'submitted' => 'eao-status--yellow',
            'ordered'   => 'eao-status--blue',
            'shipped'   => 'eao-status--green',
        );

        return isset( $classes[ $status ] ) ? $classes[ $status ] : 'eao-status--gray';
    }

    /**
     * Check if current user can manage album orders.
     *
     * @since 1.0.0
     *
     * @return bool True if user can manage orders, false otherwise.
     */
    public static function current_user_can_manage() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Log an error message.
     *
     * @since 1.0.0
     *
     * @param string $message The error message to log.
     * @param array  $context Optional. Additional context data.
     */
    public static function log_error( $message, $context = array() ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( 'Easy Album Orders: ' . $message . ' ' . wp_json_encode( $context ) );
        }
    }
}

/**
 * Get the currency symbol.
 *
 * @since 1.0.0
 *
 * @return string Currency symbol.
 */
function eao_get_currency_symbol() {
    $settings = get_option( 'eao_general_settings', array() );
    return isset( $settings['currency_symbol'] ) ? $settings['currency_symbol'] : '$';
}

/**
 * Format a price with currency symbol.
 *
 * Wrapper function for EAO_Helpers::format_price().
 *
 * @since 1.0.0
 *
 * @param float $price The price to format.
 * @return string Formatted price with currency symbol.
 */
function eao_format_price( $price ) {
    return EAO_Helpers::format_price( $price );
}

/**
 * Calculate album price.
 *
 * Wrapper function for EAO_Helpers::calculate_price().
 *
 * @since 1.0.0
 *
 * @param float $base_price        The base price.
 * @param float $material_upcharge The material upcharge.
 * @param float $size_upcharge     The size upcharge.
 * @param float $engraving_upcharge The engraving upcharge.
 * @param float $credits           Credits to apply.
 * @return float The calculated total price.
 */
function eao_calculate_price( $base_price, $material_upcharge = 0, $size_upcharge = 0, $engraving_upcharge = 0, $credits = 0 ) {
    return EAO_Helpers::calculate_price( $base_price, $material_upcharge, $size_upcharge, $engraving_upcharge, $credits );
}

