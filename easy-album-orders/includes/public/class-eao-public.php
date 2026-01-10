<?php
/**
 * Public-facing functionality.
 *
 * Handles all public-facing functionality including
 * enqueuing styles and scripts for the front-end.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Public class.
 *
 * @since 1.0.0
 */
class EAO_Public {

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
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function enqueue_styles() {
        // Only load on client album pages.
        if ( ! $this->is_client_album_page() ) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name . '-public',
            EAO_PLUGIN_URL . 'assets/css/public.css',
            array(),
            $this->version,
            'all'
        );

        // Add dynamic brand color CSS.
        $this->add_brand_color_css();
    }

    /**
     * Add dynamic brand color CSS variables.
     *
     * Outputs inline CSS to override the default accent color with
     * the photographer's custom brand color from settings.
     *
     * @since 1.0.0
     */
    private function add_brand_color_css() {
        $general_settings = get_option( 'eao_general_settings', array() );
        $brand_color      = isset( $general_settings['brand_color'] ) ? $general_settings['brand_color'] : '#1a1a1b';

        // Only output custom CSS if the color differs from default.
        if ( '#1a1a1b' === $brand_color ) {
            return;
        }

        // Calculate hover color (15% darker).
        $hover_color = $this->darken_color( $brand_color, 15 );
        
        // Calculate light color (10% opacity for box shadows).
        $light_color = $this->hex_to_rgba( $brand_color, 0.1 );

        // Calculate background tint color (very light blend with white).
        $bg_color = $this->lighten_color( $brand_color, 95 );

        $custom_css = sprintf(
            ':root {
                --eao-accent: %1$s;
                --eao-accent-hover: %2$s;
                --eao-accent-light: %3$s;
                --eao-accent-bg: %4$s;
            }',
            esc_attr( $brand_color ),
            esc_attr( $hover_color ),
            esc_attr( $light_color ),
            esc_attr( $bg_color )
        );

        wp_add_inline_style( $this->plugin_name . '-public', $custom_css );
    }

    /**
     * Darken a hex color by a percentage.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $hex     Hex color code.
     * @param int    $percent Percentage to darken (0-100).
     * @return string Darkened hex color.
     */
    private function darken_color( $hex, $percent ) {
        // Remove # if present.
        $hex = ltrim( $hex, '#' );

        // Convert to RGB.
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        // Darken.
        $r = max( 0, min( 255, $r - ( $r * $percent / 100 ) ) );
        $g = max( 0, min( 255, $g - ( $g * $percent / 100 ) ) );
        $b = max( 0, min( 255, $b - ( $b * $percent / 100 ) ) );

        // Convert back to hex.
        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    /**
     * Lighten a hex color by blending towards white.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $hex     Hex color code.
     * @param int    $percent Percentage to lighten (0-100). 100 = white.
     * @return string Lightened hex color.
     */
    private function lighten_color( $hex, $percent ) {
        // Remove # if present.
        $hex = ltrim( $hex, '#' );

        // Convert to RGB.
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        // Blend towards white (255).
        $r = $r + ( ( 255 - $r ) * $percent / 100 );
        $g = $g + ( ( 255 - $g ) * $percent / 100 );
        $b = $b + ( ( 255 - $b ) * $percent / 100 );

        // Clamp values and convert back to hex.
        return sprintf( '#%02x%02x%02x', min( 255, $r ), min( 255, $g ), min( 255, $b ) );
    }

    /**
     * Convert hex color to rgba.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $hex   Hex color code.
     * @param float  $alpha Alpha transparency (0-1).
     * @return string RGBA color string.
     */
    private function hex_to_rgba( $hex, $alpha = 1 ) {
        // Remove # if present.
        $hex = ltrim( $hex, '#' );

        // Convert to RGB.
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        return sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, $alpha );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        // Only load on client album pages.
        if ( ! $this->is_client_album_page() ) {
            return;
        }

        // Check if Stripe is enabled.
        $stripe         = new EAO_Stripe();
        $stripe_enabled = $stripe->is_enabled();

        // Enqueue Stripe.js if enabled.
        if ( $stripe_enabled ) {
            wp_enqueue_script(
                'stripe-js',
                'https://js.stripe.com/v3/',
                array(),
                null,
                true
            );
        }

        // Enqueue PDF.js library for proof viewer.
        wp_enqueue_script(
            'pdf-js',
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
            array(),
            '3.11.174',
            true
        );

        // Script dependencies.
        $script_deps = array( 'jquery', 'pdf-js' );
        if ( $stripe_enabled ) {
            $script_deps[] = 'stripe-js';
        }

        wp_enqueue_script(
            $this->plugin_name . '-public',
            EAO_PLUGIN_URL . 'assets/js/public.js',
            $script_deps,
            $this->version,
            true
        );

        // Get current client album data.
        global $post;
        $client_album_id = $post ? $post->ID : 0;

        // Get global settings for front-end use.
        $materials         = get_option( 'eao_materials', array() );
        $sizes             = get_option( 'eao_sizes', array() );
        $engraving_options = get_option( 'eao_engraving_options', array() );
        $general_settings  = get_option( 'eao_general_settings', array() );

        // Localize script with data.
        wp_localize_script(
            $this->plugin_name . '-public',
            'eaoPublic',
            array(
                'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
                'nonce'            => wp_create_nonce( 'eao_public_nonce' ),
                'clientAlbumId'    => $client_album_id,
                'materials'        => $materials,
                'sizes'            => $sizes,
                'engravingOptions' => $engraving_options,
                'currency'         => array(
                    'symbol'   => isset( $general_settings['currency_symbol'] ) ? $general_settings['currency_symbol'] : '$',
                    'position' => isset( $general_settings['currency_position'] ) ? $general_settings['currency_position'] : 'before',
                ),
                'pdfWorkerUrl'     => 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js',
                'stripe'           => array(
                    'enabled'        => $stripe_enabled,
                    'publishableKey' => $stripe_enabled ? $stripe->get_publishable_key() : '',
                    'testMode'       => $stripe_enabled ? $stripe->is_test_mode() : true,
                ),
                'i18n'             => array(
                    'addToCart'            => __( 'Add to Cart', 'easy-album-orders' ),
                    'updateCart'           => __( 'Update Cart', 'easy-album-orders' ),
                    'removeFromCart'       => __( 'Remove', 'easy-album-orders' ),
                    'checkout'             => __( 'Checkout', 'easy-album-orders' ),
                    'emptyCart'            => __( 'Your cart is empty.', 'easy-album-orders' ),
                    'confirmRemove'        => __( 'Are you sure you want to remove this album?', 'easy-album-orders' ),
                    'confirmCheckout'      => __( 'Are you sure you want to complete this order? You will not be able to make changes after checkout.', 'easy-album-orders' ),
                    'selectDesign'         => __( 'Please select an album design.', 'easy-album-orders' ),
                    'selectMaterial'       => __( 'Please select a material.', 'easy-album-orders' ),
                    'selectSize'           => __( 'Please select a size.', 'easy-album-orders' ),
                    'enterAlbumName'       => __( 'Please enter an album name.', 'easy-album-orders' ),
                    'enterShippingName'    => __( 'Please enter the recipient name.', 'easy-album-orders' ),
                    'enterShippingAddress' => __( 'Please enter a street address.', 'easy-album-orders' ),
                    'enterShippingCity'    => __( 'Please enter a city.', 'easy-album-orders' ),
                    'enterShippingState'   => __( 'Please enter a state.', 'easy-album-orders' ),
                    'enterShippingZip'     => __( 'Please enter a ZIP code.', 'easy-album-orders' ),
                    'errorOccurred'        => __( 'An error occurred. Please try again.', 'easy-album-orders' ),
                    'freeAlbumCredit'      => __( 'Free Album Credit', 'easy-album-orders' ),
                    'albumCredit'          => __( 'Album Credit', 'easy-album-orders' ),
                    'confirmDeleteAddress' => __( 'Are you sure you want to delete this address?', 'easy-album-orders' ),
                    'deleteAddress'        => __( 'Delete address', 'easy-album-orders' ),
                    'addressSaved'         => __( 'Address saved!', 'easy-album-orders' ),
                    'addressSaveFailed'    => __( 'Could not save address. Your order will still be processed.', 'easy-album-orders' ),
                    'enterCustomerName'    => __( 'Please enter your name.', 'easy-album-orders' ),
                    'enterCustomerEmail'   => __( 'Please enter your email address.', 'easy-album-orders' ),
                    'invalidEmail'         => __( 'Please enter a valid email address.', 'easy-album-orders' ),
                    'processing'           => __( 'Processing...', 'easy-album-orders' ),
                    'submitOrder'          => __( 'Submit Order', 'easy-album-orders' ),
                    'proofViewer'          => __( 'Proof Viewer', 'easy-album-orders' ),
                    'page'                 => __( 'Page', 'easy-album-orders' ),
                    'of'                   => __( 'of', 'easy-album-orders' ),
                    'loading'              => __( 'Loading...', 'easy-album-orders' ),
                    'loadingPdf'           => __( 'Loading proof...', 'easy-album-orders' ),
                    'slideView'            => __( 'Slide View', 'easy-album-orders' ),
                    'gridView'             => __( 'Grid View', 'easy-album-orders' ),
                    'close'                => __( 'Close', 'easy-album-orders' ),
                    'previous'             => __( 'Previous', 'easy-album-orders' ),
                    'next'                 => __( 'Next', 'easy-album-orders' ),
                    'continueToPayment'    => __( 'Continue to Payment', 'easy-album-orders' ),
                    'payNow'               => __( 'Pay Now', 'easy-album-orders' ),
                    'paymentDetails'       => __( 'Payment Details', 'easy-album-orders' ),
                    'securePayment'        => __( 'Payments are secure and encrypted', 'easy-album-orders' ),
                    'paymentFailed'        => __( 'Payment failed. Please try again.', 'easy-album-orders' ),
                    'paymentSuccessful'    => __( 'Payment successful! Order submitted.', 'easy-album-orders' ),
                ),
            )
        );
    }

    /**
     * Check if current page is a client album page.
     *
     * @since  1.0.0
     * @access private
     *
     * @return bool True if on a client album page, false otherwise.
     */
    private function is_client_album_page() {
        return is_singular( 'client_album' );
    }
}

