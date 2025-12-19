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
        $shipping_address = isset( $_POST['shipping_address'] ) ? sanitize_textarea_field( $_POST['shipping_address'] ) : '';

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

        if ( empty( $shipping_address ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a shipping address.', 'easy-album-orders' ) ) );
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

        // Save order meta.
        update_post_meta( $order_id, '_eao_client_album_id', $client_album_id );
        update_post_meta( $order_id, '_eao_album_name', $album_name );
        update_post_meta( $order_id, '_eao_order_status', EAO_Album_Order::STATUS_SUBMITTED );
        update_post_meta( $order_id, '_eao_submission_date', current_time( 'mysql' ) );

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
        update_post_meta( $order_id, '_eao_shipping_address', $shipping_address );

        // Get updated cart.
        $cart_html  = $this->get_cart_html( $client_album_id );
        $cart_total = $this->get_cart_total( $client_album_id );
        $cart_count = count( EAO_Album_Order::get_cart_items( $client_album_id ) );

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

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

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
        $shipping_address = isset( $_POST['shipping_address'] ) ? sanitize_textarea_field( $_POST['shipping_address'] ) : '';

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
        update_post_meta( $order_id, '_eao_shipping_address', $shipping_address );

        // Get updated cart.
        $cart_html  = $this->get_cart_html( $client_album_id );
        $cart_total = $this->get_cart_total( $client_album_id );

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

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

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

        // Get client album ID before deletion.
        $client_album_id = get_post_meta( $order_id, '_eao_client_album_id', true );

        // Delete the order.
        wp_delete_post( $order_id, true );

        // Get updated cart.
        $cart_html  = $this->get_cart_html( $client_album_id );
        $cart_total = $this->get_cart_total( $client_album_id );
        $cart_count = count( EAO_Album_Order::get_cart_items( $client_album_id ) );

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

        if ( ! $client_album_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid album.', 'easy-album-orders' ) ) );
        }

        $cart_html  = $this->get_cart_html( $client_album_id );
        $cart_total = $this->get_cart_total( $client_album_id );
        $cart_count = count( EAO_Album_Order::get_cart_items( $client_album_id ) );

        wp_send_json_success( array(
            'cart_html'  => $cart_html,
            'cart_total' => eao_format_price( $cart_total ),
            'cart_count' => $cart_count,
        ) );
    }

    /**
     * Process checkout.
     *
     * @since 1.0.0
     */
    public function process_checkout() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'eao_public_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
        }

        $client_album_id = isset( $_POST['client_album_id'] ) ? absint( $_POST['client_album_id'] ) : 0;

        if ( ! $client_album_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid album.', 'easy-album-orders' ) ) );
        }

        // Get cart items.
        $cart_items = EAO_Album_Order::get_cart_items( $client_album_id );

        if ( empty( $cart_items ) ) {
            wp_send_json_error( array( 'message' => __( 'Your cart is empty.', 'easy-album-orders' ) ) );
        }

        // Collect customer info.
        $customer_name    = isset( $_POST['customer_name'] ) ? sanitize_text_field( $_POST['customer_name'] ) : '';
        $customer_email   = isset( $_POST['customer_email'] ) ? sanitize_email( $_POST['customer_email'] ) : '';
        $customer_phone   = isset( $_POST['customer_phone'] ) ? EAO_Helpers::sanitize_phone( $_POST['customer_phone'] ) : '';
        $shipping_address = isset( $_POST['shipping_address'] ) ? sanitize_textarea_field( $_POST['shipping_address'] ) : '';
        $client_notes     = isset( $_POST['client_notes'] ) ? sanitize_textarea_field( $_POST['client_notes'] ) : '';

        // Update all cart items to "ordered" status.
        foreach ( $cart_items as $item ) {
            // Update status.
            EAO_Album_Order::update_status( $item->ID, EAO_Album_Order::STATUS_ORDERED );

            // Add customer info.
            update_post_meta( $item->ID, '_eao_customer_name', $customer_name );
            update_post_meta( $item->ID, '_eao_customer_email', $customer_email );
            update_post_meta( $item->ID, '_eao_customer_phone', $customer_phone );
            update_post_meta( $item->ID, '_eao_shipping_address', $shipping_address );
            update_post_meta( $item->ID, '_eao_client_notes', $client_notes );
        }

        /**
         * Fires after checkout is completed.
         *
         * @since 1.0.0
         *
         * @param int   $client_album_id The client album ID.
         * @param array $cart_items      Array of order posts.
         */
        do_action( 'eao_checkout_completed', $client_album_id, $cart_items );

        wp_send_json_success( array(
            'message'      => __( 'Order submitted successfully!', 'easy-album-orders' ),
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
        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

        if ( ! $order_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order.', 'easy-album-orders' ) ) );
        }

        $order = EAO_Album_Order::get( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 'message' => __( 'Order not found.', 'easy-album-orders' ) ) );
        }

        $status = EAO_Album_Order::get_order_status( $order_id );
        if ( EAO_Album_Order::STATUS_SUBMITTED !== $status ) {
            wp_send_json_error( array( 'message' => __( 'This order can no longer be edited.', 'easy-album-orders' ) ) );
        }

        // Get order data.
        $data = array(
            'order_id'         => $order_id,
            'album_name'       => get_post_meta( $order_id, '_eao_album_name', true ),
            'design_index'     => get_post_meta( $order_id, '_eao_design_index', true ),
            'material_id'      => get_post_meta( $order_id, '_eao_material_id', true ),
            'color_id'         => get_post_meta( $order_id, '_eao_material_color_id', true ),
            'color_name'       => get_post_meta( $order_id, '_eao_material_color', true ),
            'size_id'          => get_post_meta( $order_id, '_eao_size_id', true ),
            'engraving_method' => get_post_meta( $order_id, '_eao_engraving_method_id', true ),
            'engraving_text'   => get_post_meta( $order_id, '_eao_engraving_text', true ),
            'engraving_font'   => get_post_meta( $order_id, '_eao_engraving_font', true ),
            'shipping_address' => get_post_meta( $order_id, '_eao_shipping_address', true ),
        );

        wp_send_json_success( $data );
    }

    /**
     * Get cart HTML.
     *
     * @since 1.0.0
     *
     * @param int $client_album_id The client album ID.
     * @return string Cart HTML.
     */
    private function get_cart_html( $client_album_id ) {
        $cart_items = EAO_Album_Order::get_cart_items( $client_album_id );

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
     * @param int $client_album_id The client album ID.
     * @return float Cart total.
     */
    private function get_cart_total( $client_album_id ) {
        $cart_items = EAO_Album_Order::get_cart_items( $client_album_id );
        $total      = 0;

        foreach ( $cart_items as $item ) {
            $total += EAO_Album_Order::calculate_total( $item->ID );
        }

        return $total;
    }
}

