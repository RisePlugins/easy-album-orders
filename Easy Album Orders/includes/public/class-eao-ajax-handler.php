<?php
/**
 * AJAX handler for front-end operations.
 *
 * Handles all AJAX requests for cart operations, checkout, etc.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AJAX handler class.
 *
 * @since 1.0.0
 */
class EAO_Ajax_Handler {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Cart operations (available to all users - no login required).
        add_action( 'wp_ajax_eao_add_to_cart', array( $this, 'add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_eao_add_to_cart', array( $this, 'add_to_cart' ) );

        add_action( 'wp_ajax_eao_update_cart_item', array( $this, 'update_cart_item' ) );
        add_action( 'wp_ajax_nopriv_eao_update_cart_item', array( $this, 'update_cart_item' ) );

        add_action( 'wp_ajax_eao_remove_from_cart', array( $this, 'remove_from_cart' ) );
        add_action( 'wp_ajax_nopriv_eao_remove_from_cart', array( $this, 'remove_from_cart' ) );

        add_action( 'wp_ajax_eao_get_cart', array( $this, 'get_cart' ) );
        add_action( 'wp_ajax_nopriv_eao_get_cart', array( $this, 'get_cart' ) );

        add_action( 'wp_ajax_eao_checkout', array( $this, 'process_checkout' ) );
        add_action( 'wp_ajax_nopriv_eao_checkout', array( $this, 'process_checkout' ) );

        add_action( 'wp_ajax_eao_get_order_for_edit', array( $this, 'get_order_for_edit' ) );
        add_action( 'wp_ajax_nopriv_eao_get_order_for_edit', array( $this, 'get_order_for_edit' ) );

        // Payment processing (Stripe).
        add_action( 'wp_ajax_eao_create_payment_intent', array( $this, 'create_payment_intent' ) );
        add_action( 'wp_ajax_nopriv_eao_create_payment_intent', array( $this, 'create_payment_intent' ) );

        add_action( 'wp_ajax_eao_confirm_payment', array( $this, 'confirm_payment' ) );
        add_action( 'wp_ajax_nopriv_eao_confirm_payment', array( $this, 'confirm_payment' ) );

        // Saved addresses.
        add_action( 'wp_ajax_eao_save_address', array( $this, 'save_address' ) );
        add_action( 'wp_ajax_nopriv_eao_save_address', array( $this, 'save_address' ) );

        add_action( 'wp_ajax_eao_delete_address', array( $this, 'delete_address' ) );
        add_action( 'wp_ajax_nopriv_eao_delete_address', array( $this, 'delete_address' ) );

        add_action( 'wp_ajax_eao_get_saved_addresses', array( $this, 'get_saved_addresses' ) );
        add_action( 'wp_ajax_nopriv_eao_get_saved_addresses', array( $this, 'get_saved_addresses' ) );
    }

    /**
     * Add item to cart.
     *
     * @since 1.0.0
     */
    public function add_to_cart() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
        }

        // Get and validate data.
        $client_album_id = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;

        if ( ! $client_album_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid album.', 'easy-album-orders' ) ) );
        }

        // Validate client album exists.
        $client_album = EAO_Client_Album::get( $client_album_id );
        if ( ! $client_album ) {
            wp_send_json_error( array( 'message' => __( 'Album not found.', 'easy-album-orders' ) ) );
        }

        // Collect form data.
        $album_name       = isset( $_POST['album_name'] ) ? sanitize_text_field( $_POST['album_name'] ) : '';
        $design_index     = isset( $_POST['design_index'] ) ? absint( $_POST['design_index'] ) : 0;
        $material_id      = isset( $_POST['material_id'] ) ? sanitize_key( $_POST['material_id'] ) : '';
        $color_id         = isset( $_POST['color_id'] ) ? sanitize_key( $_POST['color_id'] ) : '';
        $color_name       = isset( $_POST['color_name'] ) ? sanitize_text_field( $_POST['color_name'] ) : '';
        $size_id          = isset( $_POST['size_id'] ) ? sanitize_key( $_POST['size_id'] ) : '';
        $engraving_method = isset( $_POST['engraving_method'] ) ? sanitize_key( $_POST['engraving_method'] ) : '';
        $engraving_text   = isset( $_POST['engraving_text'] ) ? sanitize_text_field( $_POST['engraving_text'] ) : '';
        $engraving_font   = isset( $_POST['engraving_font'] ) ? sanitize_text_field( $_POST['engraving_font'] ) : '';

        // Shipping address fields.
        $shipping_name     = isset( $_POST['shipping_name'] ) ? sanitize_text_field( $_POST['shipping_name'] ) : '';
        $shipping_address1 = isset( $_POST['shipping_address1'] ) ? sanitize_text_field( $_POST['shipping_address1'] ) : '';
        $shipping_address2 = isset( $_POST['shipping_address2'] ) ? sanitize_text_field( $_POST['shipping_address2'] ) : '';
        $shipping_city     = isset( $_POST['shipping_city'] ) ? sanitize_text_field( $_POST['shipping_city'] ) : '';
        $shipping_state    = isset( $_POST['shipping_state'] ) ? sanitize_text_field( $_POST['shipping_state'] ) : '';
        $shipping_zip      = isset( $_POST['shipping_zip'] ) ? sanitize_text_field( $_POST['shipping_zip'] ) : '';

        // Validate required fields.
        if ( empty( $album_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter an album name.', 'easy-album-orders' ) ) );
        }

        if ( empty( $material_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select a material.', 'easy-album-orders' ) ) );
        }

        if ( empty( $size_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select a size.', 'easy-album-orders' ) ) );
        }

        // Validate shipping address fields.
        if ( empty( $shipping_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter the recipient name.', 'easy-album-orders' ) ) );
        }

        if ( empty( $shipping_address1 ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a street address.', 'easy-album-orders' ) ) );
        }

        if ( empty( $shipping_city ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a city.', 'easy-album-orders' ) ) );
        }

        if ( empty( $shipping_state ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a state.', 'easy-album-orders' ) ) );
        }

        if ( empty( $shipping_zip ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a ZIP code.', 'easy-album-orders' ) ) );
        }

        // Get design data.
        $designs = get_post_meta( $client_album_id, '_eao_designs', true );
        $designs = is_array( $designs ) ? $designs : array();
        $design  = isset( $designs[ $design_index ] ) ? $designs[ $design_index ] : null;

        if ( ! $design ) {
            wp_send_json_error( array( 'message' => __( 'Please select a design.', 'easy-album-orders' ) ) );
        }

        // Get material, size, and engraving data.
        $material         = EAO_Helpers::get_material( $material_id );
        $size             = EAO_Helpers::get_size( $size_id );
        $engraving_option = $engraving_method ? EAO_Helpers::get_engraving_option( $engraving_method ) : null;

        // Calculate pricing.
        $base_price         = floatval( $design['base_price'] );
        $material_upcharge  = $material ? floatval( $material['upcharge'] ) : 0;
        $size_upcharge      = $size ? floatval( $size['upcharge'] ) : 0;
        $engraving_upcharge = $engraving_option ? floatval( $engraving_option['upcharge'] ) : 0;

        // Determine credits to apply based on design-specific settings.
        $credit_type     = 'none';
        $applied_credits = 0;

        // Calculate the album total before credits.
        $album_total = $base_price + $material_upcharge + $size_upcharge + $engraving_upcharge;

        // Check for free album credits first.
        $available_free_credits = EAO_Album_Order::get_available_free_credits( $client_album_id, $design_index );
        if ( $available_free_credits > 0 ) {
            // Free album credit covers the base price.
            $credit_type     = 'free_album';
            $applied_credits = $base_price;
        } else {
            // Check for remaining dollar credit pool.
            $available_dollar_credit = EAO_Album_Order::get_available_dollar_credits( $client_album_id, $design_index );
            if ( $available_dollar_credit > 0 ) {
                $credit_type = 'dollar';
                // Apply up to the available credit, but not more than the album total.
                $applied_credits = min( $available_dollar_credit, $album_total );
            }
        }

        // Create order post.
        $order_data = array(
            'post_type'   => 'album_order',
            'post_status' => 'publish',
            'post_title'  => $album_name . ' - ' . current_time( 'Y-m-d H:i:s' ),
        );

        $order_id = wp_insert_post( $order_data );

        if ( is_wp_error( $order_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to create order.', 'easy-album-orders' ) ) );
        }

        // Get cart token for browser identification.
        $cart_token = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';

        // Save order meta.
        update_post_meta( $order_id, '_eao_client_album_id', $client_album_id );
        update_post_meta( $order_id, '_eao_album_name', $album_name );
        update_post_meta( $order_id, '_eao_order_status', EAO_Album_Order::STATUS_SUBMITTED );
        update_post_meta( $order_id, '_eao_submission_date', current_time( 'mysql' ) );
        update_post_meta( $order_id, '_eao_cart_token', $cart_token );

        // Design info.
        update_post_meta( $order_id, '_eao_design_index', $design_index );
        update_post_meta( $order_id, '_eao_design_name', $design['name'] );
        update_post_meta( $order_id, '_eao_design_pdf_id', $design['pdf_id'] );
        update_post_meta( $order_id, '_eao_base_price', $base_price );

        // Material info.
        update_post_meta( $order_id, '_eao_material_id', $material_id );
        update_post_meta( $order_id, '_eao_material_name', $material ? $material['name'] : '' );
        update_post_meta( $order_id, '_eao_material_color', $color_name );
        update_post_meta( $order_id, '_eao_material_color_id', $color_id );
        update_post_meta( $order_id, '_eao_material_upcharge', $material_upcharge );

        // Size info.
        update_post_meta( $order_id, '_eao_size_id', $size_id );
        update_post_meta( $order_id, '_eao_size_name', $size ? $size['name'] : '' );
        update_post_meta( $order_id, '_eao_size_upcharge', $size_upcharge );

        // Engraving info.
        if ( $engraving_method && $engraving_option ) {
            update_post_meta( $order_id, '_eao_engraving_method_id', $engraving_method );
            update_post_meta( $order_id, '_eao_engraving_method', $engraving_option['name'] );
            update_post_meta( $order_id, '_eao_engraving_text', $engraving_text );
            update_post_meta( $order_id, '_eao_engraving_font', $engraving_font );
            update_post_meta( $order_id, '_eao_engraving_upcharge', $engraving_upcharge );
        } else {
            update_post_meta( $order_id, '_eao_engraving_upcharge', 0 );
        }

        // Credits (design-specific).
        update_post_meta( $order_id, '_eao_credit_type', $credit_type );
        update_post_meta( $order_id, '_eao_applied_credits', $applied_credits );

        // Shipping address (per album).
        update_post_meta( $order_id, '_eao_shipping_name', $shipping_name );
        update_post_meta( $order_id, '_eao_shipping_address1', $shipping_address1 );
        update_post_meta( $order_id, '_eao_shipping_address2', $shipping_address2 );
        update_post_meta( $order_id, '_eao_shipping_city', $shipping_city );
        update_post_meta( $order_id, '_eao_shipping_state', $shipping_state );
        update_post_meta( $order_id, '_eao_shipping_zip', $shipping_zip );

        // Get updated cart.
        $cart_html  = $this->get_cart_html( $client_album_id, $cart_token );
        $cart_total = $this->get_cart_total( $client_album_id, $cart_token );
        $cart_count = count( EAO_Album_Order::get_cart_items( $client_album_id, $cart_token ) );

        wp_send_json_success( array(
            'message'    => __( 'Album added to cart!', 'easy-album-orders' ),
            'order_id'   => $order_id,
            'cart_html'  => $cart_html,
            'cart_total' => eao_format_price( $cart_total ),
            'cart_count' => $cart_count,
        ) );
    }

    /**
     * Update cart item.
     *
     * @since 1.0.0
     */
    public function update_cart_item() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
        }

        $order_id   = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $cart_token = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order.', 'easy-album-orders' ) ) );
        }

        // Check order exists and is editable.
        $order = EAO_Album_Order::get( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'easy-album-orders' ) ) );
        }

        $status = EAO_Album_Order::get_order_status( $order_id );
        if ( EAO_Album_Order::STATUS_SUBMITTED !== $status ) {
            wp_send_json_error( array( 'message' => __( 'This order can no longer be edited.', 'easy-album-orders' ) ) );
        }

        // Verify order belongs to this cart token.
        $order_cart_token = get_post_meta( $order_id, '_eao_cart_token', true );
        if ( $cart_token && $order_cart_token !== $cart_token ) {
            wp_send_json_error( array( 'message' => __( 'You cannot edit this order.', 'easy-album-orders' ) ) );
        }

        // Get client album ID.
        $client_album_id = get_post_meta( $order_id, '_eao_client_album_id', true );

        // Update fields (similar to add_to_cart but updating existing).
        $album_name       = isset( $_POST['album_name'] ) ? sanitize_text_field( $_POST['album_name'] ) : '';
        $design_index     = isset( $_POST['design_index'] ) ? absint( $_POST['design_index'] ) : 0;
        $material_id      = isset( $_POST['material_id'] ) ? sanitize_key( $_POST['material_id'] ) : '';
        $color_name       = isset( $_POST['color_name'] ) ? sanitize_text_field( $_POST['color_name'] ) : '';
        $size_id          = isset( $_POST['size_id'] ) ? sanitize_key( $_POST['size_id'] ) : '';
        $engraving_method = isset( $_POST['engraving_method'] ) ? sanitize_key( $_POST['engraving_method'] ) : '';
        $engraving_text   = isset( $_POST['engraving_text'] ) ? sanitize_text_field( $_POST['engraving_text'] ) : '';
        $engraving_font   = isset( $_POST['engraving_font'] ) ? sanitize_text_field( $_POST['engraving_font'] ) : '';

        // Shipping address fields.
        $shipping_name     = isset( $_POST['shipping_name'] ) ? sanitize_text_field( $_POST['shipping_name'] ) : '';
        $shipping_address1 = isset( $_POST['shipping_address1'] ) ? sanitize_text_field( $_POST['shipping_address1'] ) : '';
        $shipping_address2 = isset( $_POST['shipping_address2'] ) ? sanitize_text_field( $_POST['shipping_address2'] ) : '';
        $shipping_city     = isset( $_POST['shipping_city'] ) ? sanitize_text_field( $_POST['shipping_city'] ) : '';
        $shipping_state    = isset( $_POST['shipping_state'] ) ? sanitize_text_field( $_POST['shipping_state'] ) : '';
        $shipping_zip      = isset( $_POST['shipping_zip'] ) ? sanitize_text_field( $_POST['shipping_zip'] ) : '';

        // Get design data.
        $designs = get_post_meta( $client_album_id, '_eao_designs', true );
        $designs = is_array( $designs ) ? $designs : array();
        $design  = isset( $designs[ $design_index ] ) ? $designs[ $design_index ] : null;

        // Get material, size, and engraving data.
        $material         = EAO_Helpers::get_material( $material_id );
        $size             = EAO_Helpers::get_size( $size_id );
        $engraving_option = $engraving_method ? EAO_Helpers::get_engraving_option( $engraving_method ) : null;

        // Calculate pricing.
        $base_price         = $design ? floatval( $design['base_price'] ) : 0;
        $material_upcharge  = $material ? floatval( $material['upcharge'] ) : 0;
        $size_upcharge      = $size ? floatval( $size['upcharge'] ) : 0;
        $engraving_upcharge = $engraving_option ? floatval( $engraving_option['upcharge'] ) : 0;

        // Calculate the album total before credits.
        $album_total = $base_price + $material_upcharge + $size_upcharge + $engraving_upcharge;

        // Determine credits to apply (exclude current order from count).
        $credit_type     = 'none';
        $applied_credits = 0;

        // Check for free album credits first (exclude this order from the count).
        $available_free_credits = EAO_Album_Order::get_available_free_credits( $client_album_id, $design_index, $order_id );
        if ( $available_free_credits > 0 ) {
            $credit_type     = 'free_album';
            $applied_credits = $base_price;
        } else {
            // Check for remaining dollar credit pool (exclude this order).
            $available_dollar_credit = EAO_Album_Order::get_available_dollar_credits( $client_album_id, $design_index, $order_id );
            if ( $available_dollar_credit > 0 ) {
                $credit_type = 'dollar';
                // Apply up to the available credit, but not more than the album total.
                $applied_credits = min( $available_dollar_credit, $album_total );
            }
        }

        // Update meta.
        update_post_meta( $order_id, '_eao_album_name', $album_name );
        update_post_meta( $order_id, '_eao_design_index', $design_index );
        update_post_meta( $order_id, '_eao_design_name', $design ? $design['name'] : '' );
        update_post_meta( $order_id, '_eao_base_price', $base_price );

        update_post_meta( $order_id, '_eao_material_id', $material_id );
        update_post_meta( $order_id, '_eao_material_name', $material ? $material['name'] : '' );
        update_post_meta( $order_id, '_eao_material_color', $color_name );
        update_post_meta( $order_id, '_eao_material_upcharge', $material_upcharge );

        update_post_meta( $order_id, '_eao_size_id', $size_id );
        update_post_meta( $order_id, '_eao_size_name', $size ? $size['name'] : '' );
        update_post_meta( $order_id, '_eao_size_upcharge', $size_upcharge );

        if ( $engraving_method && $engraving_option ) {
            update_post_meta( $order_id, '_eao_engraving_method_id', $engraving_method );
            update_post_meta( $order_id, '_eao_engraving_method', $engraving_option['name'] );
            update_post_meta( $order_id, '_eao_engraving_text', $engraving_text );
            update_post_meta( $order_id, '_eao_engraving_font', $engraving_font );
            update_post_meta( $order_id, '_eao_engraving_upcharge', $engraving_upcharge );
        } else {
            update_post_meta( $order_id, '_eao_engraving_method_id', '' );
            update_post_meta( $order_id, '_eao_engraving_method', '' );
            update_post_meta( $order_id, '_eao_engraving_text', '' );
            update_post_meta( $order_id, '_eao_engraving_font', '' );
            update_post_meta( $order_id, '_eao_engraving_upcharge', 0 );
        }

        // Credits (design-specific).
        update_post_meta( $order_id, '_eao_credit_type', $credit_type );
        update_post_meta( $order_id, '_eao_applied_credits', $applied_credits );

        // Shipping address (per album).
        update_post_meta( $order_id, '_eao_shipping_name', $shipping_name );
        update_post_meta( $order_id, '_eao_shipping_address1', $shipping_address1 );
        update_post_meta( $order_id, '_eao_shipping_address2', $shipping_address2 );
        update_post_meta( $order_id, '_eao_shipping_city', $shipping_city );
        update_post_meta( $order_id, '_eao_shipping_state', $shipping_state );
        update_post_meta( $order_id, '_eao_shipping_zip', $shipping_zip );

        // Get updated cart.
        $cart_html  = $this->get_cart_html( $client_album_id, $cart_token );
        $cart_total = $this->get_cart_total( $client_album_id, $cart_token );

        wp_send_json_success( array(
            'message'    => __( 'Album updated!', 'easy-album-orders' ),
            'cart_html'  => $cart_html,
            'cart_total' => eao_format_price( $cart_total ),
        ) );
    }

    /**
     * Remove item from cart.
     *
     * @since 1.0.0
     */
    public function remove_from_cart() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
        }

        $order_id   = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $cart_token = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order.', 'easy-album-orders' ) ) );
        }

        // Check order exists and is removable.
        $order = EAO_Album_Order::get( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'easy-album-orders' ) ) );
        }

        $status = EAO_Album_Order::get_order_status( $order_id );
        if ( EAO_Album_Order::STATUS_SUBMITTED !== $status ) {
            wp_send_json_error( array( 'message' => __( 'This order can no longer be removed.', 'easy-album-orders' ) ) );
        }

        // Verify order belongs to this cart token.
        $order_cart_token = get_post_meta( $order_id, '_eao_cart_token', true );
        if ( $cart_token && $order_cart_token !== $cart_token ) {
            wp_send_json_error( array( 'message' => __( 'You cannot remove this order.', 'easy-album-orders' ) ) );
        }

        // Get client album ID before deletion.
        $client_album_id = get_post_meta( $order_id, '_eao_client_album_id', true );

        // Delete the order.
        wp_delete_post( $order_id, true );

        // Get updated cart.
        $cart_html  = $this->get_cart_html( $client_album_id, $cart_token );
        $cart_total = $this->get_cart_total( $client_album_id, $cart_token );
        $cart_count = count( EAO_Album_Order::get_cart_items( $client_album_id, $cart_token ) );

        wp_send_json_success( array(
            'message'    => __( 'Album removed from cart.', 'easy-album-orders' ),
            'cart_html'  => $cart_html,
            'cart_total' => eao_format_price( $cart_total ),
            'cart_count' => $cart_count,
        ) );
    }

    /**
     * Get current cart.
     *
     * @since 1.0.0
     */
    public function get_cart() {
        $client_album_id = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;
        $cart_token      = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';

        if ( ! $client_album_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid album.', 'easy-album-orders' ) ) );
        }

        $cart_html  = $this->get_cart_html( $client_album_id, $cart_token );
        $cart_total = $this->get_cart_total( $client_album_id, $cart_token );
        $cart_count = count( EAO_Album_Order::get_cart_items( $client_album_id, $cart_token ) );

        wp_send_json_success( array(
            'cart_html'  => $cart_html,
            'cart_total' => eao_format_price( $cart_total ),
            'cart_count' => $cart_count,
        ) );
    }

    /**
     * Process checkout.
     *
     * Handles checkout for free orders or when Stripe is disabled.
     * For paid orders with Stripe enabled, use create_payment_intent/confirm_payment instead.
     *
     * @since 1.0.0
     */
    public function process_checkout() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
        }

        $client_album_id = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;
        $cart_token      = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';

        if ( ! $client_album_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid album.', 'easy-album-orders' ) ) );
        }

        // Get cart items for this browser's cart only.
        $cart_items = EAO_Album_Order::get_cart_items( $client_album_id, $cart_token );

        if ( empty( $cart_items ) ) {
            wp_send_json_error( array( 'message' => __( 'Your cart is empty.', 'easy-album-orders' ) ) );
        }

        // Check if payment is required.
        $stripe = new EAO_Stripe();
        if ( $stripe->is_enabled() ) {
            // Calculate cart total.
            $total = 0;
            foreach ( $cart_items as $item ) {
                $total += EAO_Album_Order::calculate_total( $item->ID );
            }

            // If total > 0, require payment flow.
            if ( $total > 0 ) {
                wp_send_json_error( array(
                    'message'          => __( 'Payment required.', 'easy-album-orders' ),
                    'payment_required' => true,
                    'total'            => $total,
                    'formatted_total'  => eao_format_price( $total ),
                ) );
            }
        }

        // Continue with free order checkout (credits cover the entire amount).
        // Collect customer info.
        $customer_name    = isset( $_POST['customer_name'] ) ? sanitize_text_field( $_POST['customer_name'] ) : '';
        $customer_email   = isset( $_POST['customer_email'] ) ? sanitize_email( $_POST['customer_email'] ) : '';
        $customer_phone   = isset( $_POST['customer_phone'] ) ? EAO_Helpers::sanitize_phone( $_POST['customer_phone'] ) : '';
        $shipping_address = isset( $_POST['shipping_address'] ) ? sanitize_textarea_field( $_POST['shipping_address'] ) : '';
        $client_notes     = isset( $_POST['client_notes'] ) ? sanitize_textarea_field( $_POST['client_notes'] ) : '';

        // Collect order IDs for email notification.
        $order_ids = array();

        // Update all cart items to "ordered" status.
        foreach ( $cart_items as $item ) {
            $order_ids[] = $item->ID;

            // Update status.
            EAO_Album_Order::update_status( $item->ID, EAO_Album_Order::STATUS_ORDERED );

            // Add customer info.
            update_post_meta( $item->ID, '_eao_customer_name', $customer_name );
            update_post_meta( $item->ID, '_eao_customer_email', $customer_email );
            update_post_meta( $item->ID, '_eao_customer_phone', $customer_phone );
            update_post_meta( $item->ID, '_eao_shipping_address', $shipping_address );
            update_post_meta( $item->ID, '_eao_client_notes', $client_notes );
            update_post_meta( $item->ID, '_eao_order_date', current_time( 'mysql' ) );

            // Mark as free (no payment needed).
            update_post_meta( $item->ID, '_eao_payment_status', 'free' );
        }

        /**
         * Fires after checkout is completed.
         *
         * @since 1.0.0
         *
         * @param array $order_ids       Array of order IDs.
         * @param int   $client_album_id The client album ID.
         */
        do_action( 'eao_order_checkout_complete', $order_ids, $client_album_id );

        wp_send_json_success( array(
            'message'      => __( 'Order submitted successfully!', 'easy-album-orders' ),
            'order_count'  => count( $cart_items ),
            'redirect_url' => add_query_arg( 'order_complete', '1', get_permalink( $client_album_id ) ),
        ) );
    }

    /**
     * Create a Payment Intent for the checkout.
     *
     * This creates a Stripe Payment Intent and returns the client secret
     * needed to confirm the payment on the frontend with Stripe.js.
     *
     * @since 1.1.0
     */
    public function create_payment_intent() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
        }

        $client_album_id = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;
        $cart_token      = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';
        $customer_email  = isset( $_POST['customer_email'] ) ? sanitize_email( $_POST['customer_email'] ) : '';
        $customer_name   = isset( $_POST['customer_name'] ) ? sanitize_text_field( $_POST['customer_name'] ) : '';

        if ( ! $client_album_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid album.', 'easy-album-orders' ) ) );
        }

        // Get cart items.
        $cart_items = EAO_Album_Order::get_cart_items( $client_album_id, $cart_token );

        if ( empty( $cart_items ) ) {
            wp_send_json_error( array( 'message' => __( 'Your cart is empty.', 'easy-album-orders' ) ) );
        }

        // Calculate total.
        $total     = 0;
        $order_ids = array();
        foreach ( $cart_items as $item ) {
            $total += EAO_Album_Order::calculate_total( $item->ID );
            $order_ids[] = $item->ID;
        }

        // Validate total.
        if ( $total <= 0 ) {
            // If total is 0 (fully credited), skip payment.
            wp_send_json_success( array(
                'skip_payment' => true,
                'message'      => __( 'No payment required.', 'easy-album-orders' ),
            ) );
        }

        // Initialize Stripe.
        $stripe = new EAO_Stripe();

        if ( ! $stripe->is_enabled() ) {
            // If Stripe is disabled, allow checkout without payment.
            wp_send_json_success( array(
                'skip_payment' => true,
                'message'      => __( 'Payment processing is disabled.', 'easy-album-orders' ),
            ) );
        }

        // Get client album title for description.
        $album_title = get_the_title( $client_album_id );

        // Create Payment Intent metadata.
        $metadata = array(
            'client_album_id' => $client_album_id,
            'cart_token'      => $cart_token,
            'order_ids'       => implode( ',', $order_ids ),
            'customer_name'   => $customer_name,
            'customer_email'  => $customer_email,
            'album_title'     => $album_title,
        );

        // Get currency from general settings.
        $general_settings = get_option( 'eao_general_settings', array() );
        $currency         = isset( $general_settings['currency'] ) ? strtolower( $general_settings['currency'] ) : 'usd';

        // Create Payment Intent.
        $result = $stripe->create_payment_intent( $total, $currency, $metadata, $customer_email );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        // Store Payment Intent ID with orders for later verification.
        foreach ( $cart_items as $item ) {
            update_post_meta( $item->ID, '_eao_payment_intent_id', $result['id'] );
        }

        wp_send_json_success( array(
            'client_secret'   => $result['client_secret'],
            'payment_intent'  => $result['id'],
            'publishable_key' => $stripe->get_publishable_key(),
            'amount'          => $total,
            'formatted_total' => eao_format_price( $total ),
        ) );
    }

    /**
     * Confirm payment was successful and complete checkout.
     *
     * Called after the frontend confirms the payment with Stripe.js.
     * Verifies the payment status and updates order records.
     *
     * @since 1.1.0
     */
    public function confirm_payment() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
        }

        $payment_intent_id = isset( $_POST['payment_intent_id'] ) ? sanitize_text_field( $_POST['payment_intent_id'] ) : '';
        $client_album_id   = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;
        $cart_token        = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';

        if ( ! $payment_intent_id || ! $client_album_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'easy-album-orders' ) ) );
        }

        // Verify payment with Stripe.
        $stripe = new EAO_Stripe();
        $intent = $stripe->get_payment_intent( $payment_intent_id );

        if ( is_wp_error( $intent ) ) {
            wp_send_json_error( array( 'message' => $intent->get_error_message() ) );
        }

        // Check payment status.
        if ( 'succeeded' !== $intent->status ) {
            wp_send_json_error( array(
                'message' => __( 'Payment was not successful. Please try again.', 'easy-album-orders' ),
                'status'  => $intent->status,
            ) );
        }

        // Get cart items and verify they match the payment.
        $cart_items = EAO_Album_Order::get_cart_items( $client_album_id, $cart_token );

        if ( empty( $cart_items ) ) {
            wp_send_json_error( array( 'message' => __( 'Cart items not found.', 'easy-album-orders' ) ) );
        }

        // Verify Payment Intent matches what's stored on orders.
        foreach ( $cart_items as $item ) {
            $stored_intent = get_post_meta( $item->ID, '_eao_payment_intent_id', true );
            if ( $stored_intent !== $payment_intent_id ) {
                wp_send_json_error( array( 'message' => __( 'Payment verification failed.', 'easy-album-orders' ) ) );
            }
        }

        // Collect customer info.
        $customer_name  = isset( $_POST['customer_name'] ) ? sanitize_text_field( $_POST['customer_name'] ) : '';
        $customer_email = isset( $_POST['customer_email'] ) ? sanitize_email( $_POST['customer_email'] ) : '';
        $customer_phone = isset( $_POST['customer_phone'] ) ? EAO_Helpers::sanitize_phone( $_POST['customer_phone'] ) : '';
        $client_notes   = isset( $_POST['client_notes'] ) ? sanitize_textarea_field( $_POST['client_notes'] ) : '';

        $order_ids = array();

        foreach ( $cart_items as $item ) {
            $order_ids[] = $item->ID;

            // Update status to ordered.
            EAO_Album_Order::update_status( $item->ID, EAO_Album_Order::STATUS_ORDERED );

            // Save customer info.
            update_post_meta( $item->ID, '_eao_customer_name', $customer_name );
            update_post_meta( $item->ID, '_eao_customer_email', $customer_email );
            update_post_meta( $item->ID, '_eao_customer_phone', $customer_phone );
            update_post_meta( $item->ID, '_eao_client_notes', $client_notes );
            update_post_meta( $item->ID, '_eao_order_date', current_time( 'mysql' ) );

            // Save payment info.
            update_post_meta( $item->ID, '_eao_payment_status', 'paid' );
            update_post_meta( $item->ID, '_eao_payment_amount', $intent->amount / 100 );
            update_post_meta( $item->ID, '_eao_stripe_charge_id', $intent->latest_charge );
        }

        /**
         * Fires after checkout with payment is completed.
         *
         * @since 1.1.0
         *
         * @param array  $order_ids       Array of order IDs.
         * @param int    $client_album_id The client album ID.
         */
        do_action( 'eao_order_checkout_complete', $order_ids, $client_album_id );

        /**
         * Fires after a Stripe payment is completed.
         *
         * @since 1.1.0
         *
         * @param array  $order_ids        Array of order IDs.
         * @param string $payment_intent_id Stripe Payment Intent ID.
         */
        do_action( 'eao_payment_complete', $order_ids, $payment_intent_id );

        wp_send_json_success( array(
            'message'      => __( 'Payment successful! Order submitted.', 'easy-album-orders' ),
            'order_count'  => count( $cart_items ),
            'redirect_url' => add_query_arg( 'order_complete', '1', get_permalink( $client_album_id ) ),
        ) );
    }

    /**
     * Get order data for editing.
     *
     * @since 1.0.0
     */
    public function get_order_for_edit() {
        $order_id   = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $cart_token = isset( $_POST['cart_token'] ) ? sanitize_key( $_POST['cart_token'] ) : '';

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order.', 'easy-album-orders' ) ) );
        }

        $order = EAO_Album_Order::get( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'easy-album-orders' ) ) );
        }

        // Verify order belongs to this cart token.
        $order_cart_token = get_post_meta( $order_id, '_eao_cart_token', true );
        if ( $cart_token && $order_cart_token !== $cart_token ) {
            wp_send_json_error( array( 'message' => __( 'You cannot edit this order.', 'easy-album-orders' ) ) );
        }

        $status = EAO_Album_Order::get_order_status( $order_id );
        if ( EAO_Album_Order::STATUS_SUBMITTED !== $status ) {
            wp_send_json_error( array( 'message' => __( 'This order can no longer be edited.', 'easy-album-orders' ) ) );
        }

        // Get order data.
        $data = array(
            'order_id'          => $order_id,
            'album_name'        => get_post_meta( $order_id, '_eao_album_name', true ),
            'design_index'      => get_post_meta( $order_id, '_eao_design_index', true ),
            'material_id'       => get_post_meta( $order_id, '_eao_material_id', true ),
            'color_id'          => get_post_meta( $order_id, '_eao_material_color_id', true ),
            'color_name'        => get_post_meta( $order_id, '_eao_material_color', true ),
            'size_id'           => get_post_meta( $order_id, '_eao_size_id', true ),
            'engraving_method'  => get_post_meta( $order_id, '_eao_engraving_method_id', true ),
            'engraving_text'    => get_post_meta( $order_id, '_eao_engraving_text', true ),
            'engraving_font'    => get_post_meta( $order_id, '_eao_engraving_font', true ),
            'shipping_name'     => get_post_meta( $order_id, '_eao_shipping_name', true ),
            'shipping_address1' => get_post_meta( $order_id, '_eao_shipping_address1', true ),
            'shipping_address2' => get_post_meta( $order_id, '_eao_shipping_address2', true ),
            'shipping_city'     => get_post_meta( $order_id, '_eao_shipping_city', true ),
            'shipping_state'    => get_post_meta( $order_id, '_eao_shipping_state', true ),
            'shipping_zip'      => get_post_meta( $order_id, '_eao_shipping_zip', true ),
        );

        wp_send_json_success( $data );
    }

    /**
     * Get cart HTML.
     *
     * @since 1.0.0
     *
     * @param int    $client_album_id The client album ID.
     * @param string $cart_token      Optional. Cart token for browser identification.
     * @return string Cart HTML.
     */
    private function get_cart_html( $client_album_id, $cart_token = '' ) {
        $cart_items = EAO_Album_Order::get_cart_items( $client_album_id, $cart_token );

        if ( empty( $cart_items ) ) {
            return '<div class="eao-cart__empty" id="eao-cart-empty">
                <p>' . esc_html__( 'Your cart is empty.', 'easy-album-orders' ) . '</p>
                <p class="eao-cart__empty-hint">' . esc_html__( 'Configure an album above and click "Add to Cart" to get started.', 'easy-album-orders' ) . '</p>
            </div>';
        }

        ob_start();
        foreach ( $cart_items as $item ) {
            EAO_Template_Loader::get_template_part( 'cart-item', '', array( 'order' => $item ) );
        }
        return ob_get_clean();
    }

    /**
     * Get cart total.
     *
     * @since 1.0.0
     *
     * @param int    $client_album_id The client album ID.
     * @param string $cart_token      Optional. Cart token for browser identification.
     * @return float Cart total.
     */
    private function get_cart_total( $client_album_id, $cart_token = '' ) {
        $cart_items = EAO_Album_Order::get_cart_items( $client_album_id, $cart_token );
        $total      = 0;

        foreach ( $cart_items as $item ) {
            $total += EAO_Album_Order::calculate_total( $item->ID );
        }

        return $total;
    }

    /**
     * Save a new address.
     *
     * @since 1.0.0
     */
    public function save_address() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
        }

        $client_album_id = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;

        if ( ! $client_album_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid album.', 'easy-album-orders' ) ) );
        }

        // Collect address data.
        $address = array(
            'id'        => 'addr_' . uniqid(),
            'name'      => isset( $_POST['shipping_name'] ) ? sanitize_text_field( $_POST['shipping_name'] ) : '',
            'address1'  => isset( $_POST['shipping_address1'] ) ? sanitize_text_field( $_POST['shipping_address1'] ) : '',
            'address2'  => isset( $_POST['shipping_address2'] ) ? sanitize_text_field( $_POST['shipping_address2'] ) : '',
            'city'      => isset( $_POST['shipping_city'] ) ? sanitize_text_field( $_POST['shipping_city'] ) : '',
            'state'     => isset( $_POST['shipping_state'] ) ? sanitize_text_field( $_POST['shipping_state'] ) : '',
            'zip'       => isset( $_POST['shipping_zip'] ) ? sanitize_text_field( $_POST['shipping_zip'] ) : '',
        );

        // Validate required fields.
        if ( empty( $address['name'] ) || empty( $address['address1'] ) || empty( $address['city'] ) || empty( $address['state'] ) || empty( $address['zip'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Please fill in all required address fields.', 'easy-album-orders' ) ) );
        }

        // Get existing addresses.
        $saved_addresses = get_post_meta( $client_album_id, '_eao_saved_addresses', true );
        $saved_addresses = is_array( $saved_addresses ) ? $saved_addresses : array();

        // Add new address.
        $saved_addresses[] = $address;

        // Save.
        update_post_meta( $client_album_id, '_eao_saved_addresses', $saved_addresses );

        wp_send_json_success( array(
            'message'   => __( 'Address saved!', 'easy-album-orders' ),
            'address'   => $address,
            'addresses' => $saved_addresses,
        ) );
    }

    /**
     * Delete a saved address.
     *
     * @since 1.0.0
     */
    public function delete_address() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
        }

        $client_album_id = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;
        $address_id      = isset( $_POST['address_id'] ) ? sanitize_key( $_POST['address_id'] ) : '';

        if ( ! $client_album_id || ! $address_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'easy-album-orders' ) ) );
        }

        // Get existing addresses.
        $saved_addresses = get_post_meta( $client_album_id, '_eao_saved_addresses', true );
        $saved_addresses = is_array( $saved_addresses ) ? $saved_addresses : array();

        // Remove the address.
        $saved_addresses = array_filter( $saved_addresses, function( $addr ) use ( $address_id ) {
            return $addr['id'] !== $address_id;
        } );

        // Re-index array.
        $saved_addresses = array_values( $saved_addresses );

        // Save.
        update_post_meta( $client_album_id, '_eao_saved_addresses', $saved_addresses );

        wp_send_json_success( array(
            'message'   => __( 'Address deleted.', 'easy-album-orders' ),
            'addresses' => $saved_addresses,
        ) );
    }

    /**
     * Get saved addresses for a client album.
     *
     * @since 1.0.0
     */
    public function get_saved_addresses() {
        $client_album_id = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;

        if ( ! $client_album_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid album.', 'easy-album-orders' ) ) );
        }

        $saved_addresses = get_post_meta( $client_album_id, '_eao_saved_addresses', true );
        $saved_addresses = is_array( $saved_addresses ) ? $saved_addresses : array();

        wp_send_json_success( array(
            'addresses' => $saved_addresses,
        ) );
    }
}

