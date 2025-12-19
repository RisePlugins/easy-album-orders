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

        wp_enqueue_script(
            $this->plugin_name . '-public',
            EAO_PLUGIN_URL . 'assets/js/public.js',
            array( 'jquery' ),
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

