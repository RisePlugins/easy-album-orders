<?php
/**
 * Album Order custom post type.
 *
 * Registers and manages the Album Order post type which represents
 * individual album orders submitted by clients.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Album Order post type class.
 *
 * @since 1.0.0
 */
class EAO_Album_Order {

    /**
     * Post type slug.
     *
     * @since 1.0.0
     * @var   string
     */
    const POST_TYPE = 'album_order';

    /**
     * Order status: Submitted (in cart, editable).
     *
     * @since 1.0.0
     * @var   string
     */
    const STATUS_SUBMITTED = 'submitted';

    /**
     * Order status: Ordered (checkout completed, locked).
     *
     * @since 1.0.0
     * @var   string
     */
    const STATUS_ORDERED = 'ordered';

    /**
     * Order status: Shipped (fulfilled).
     *
     * @since 1.0.0
     * @var   string
     */
    const STATUS_SHIPPED = 'shipped';

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Constructor can be used to set up additional hooks if needed.
    }

    /**
     * Register the Album Order custom post type.
     *
     * @since 1.0.0
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Album Orders', 'Post type general name', 'easy-album-orders' ),
            'singular_name'         => _x( 'Album Order', 'Post type singular name', 'easy-album-orders' ),
            'menu_name'             => _x( 'Album Orders', 'Admin Menu text', 'easy-album-orders' ),
            'name_admin_bar'        => _x( 'Album Order', 'Add New on Toolbar', 'easy-album-orders' ),
            'add_new'               => __( 'Add New', 'easy-album-orders' ),
            'add_new_item'          => __( 'Add New Album Order', 'easy-album-orders' ),
            'new_item'              => __( 'New Album Order', 'easy-album-orders' ),
            'edit_item'             => __( 'Edit Album Order', 'easy-album-orders' ),
            'view_item'             => __( 'View Album Order', 'easy-album-orders' ),
            'all_items'             => __( 'All Album Orders', 'easy-album-orders' ),
            'search_items'          => __( 'Search Album Orders', 'easy-album-orders' ),
            'parent_item_colon'     => __( 'Parent Album Orders:', 'easy-album-orders' ),
            'not_found'             => __( 'No album orders found.', 'easy-album-orders' ),
            'not_found_in_trash'    => __( 'No album orders found in Trash.', 'easy-album-orders' ),
            'archives'              => _x( 'Album Order archives', 'The post type archive label', 'easy-album-orders' ),
            'filter_items_list'     => _x( 'Filter album orders list', 'Screen reader text', 'easy-album-orders' ),
            'items_list_navigation' => _x( 'Album orders list navigation', 'Screen reader text', 'easy-album-orders' ),
            'items_list'            => _x( 'Album orders list', 'Screen reader text', 'easy-album-orders' ),
        );

        $args = array(
            'labels'              => $labels,
            'description'         => __( 'Individual album orders from clients.', 'easy-album-orders' ),
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // We'll add it manually to our custom menu.
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-cart',
            'supports'            => array( 'title' ),
            'show_in_rest'        => false,
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Get all valid order statuses.
     *
     * @since 1.0.0
     *
     * @return array Associative array of status slugs and labels.
     */
    public static function get_statuses() {
        return array(
            self::STATUS_SUBMITTED => __( 'Submitted', 'easy-album-orders' ),
            self::STATUS_ORDERED   => __( 'Ordered', 'easy-album-orders' ),
            self::STATUS_SHIPPED   => __( 'Shipped', 'easy-album-orders' ),
        );
    }

    /**
     * Get status label.
     *
     * @since 1.0.0
     *
     * @param string $status The status slug.
     * @return string The status label.
     */
    public static function get_status_label( $status ) {
        $statuses = self::get_statuses();
        return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
    }

    /**
     * Get all album orders.
     *
     * @since 1.0.0
     *
     * @param array $args Optional. Additional query arguments.
     * @return WP_Post[] Array of album order posts.
     */
    public static function get_all( $args = array() ) {
        $defaults = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $query_args = wp_parse_args( $args, $defaults );

        return get_posts( $query_args );
    }

    /**
     * Get album orders by status.
     *
     * @since 1.0.0
     *
     * @param string $status The order status to filter by.
     * @param array  $args   Optional. Additional query arguments.
     * @return WP_Post[] Array of album order posts.
     */
    public static function get_by_status( $status, $args = array() ) {
        $args['meta_query'] = array(
            array(
                'key'   => '_eao_order_status',
                'value' => $status,
            ),
        );

        return self::get_all( $args );
    }

    /**
     * Get album orders for a specific client album.
     *
     * @since 1.0.0
     *
     * @param int   $client_album_id The client album post ID.
     * @param array $args            Optional. Additional query arguments.
     * @return WP_Post[] Array of album order posts.
     */
    public static function get_by_client_album( $client_album_id, $args = array() ) {
        $args['meta_query'] = array(
            array(
                'key'   => '_eao_client_album_id',
                'value' => $client_album_id,
            ),
        );

        return self::get_all( $args );
    }

    /**
     * Get "submitted" (cart) orders for a client album.
     *
     * @since 1.0.0
     *
     * @param int $client_album_id The client album post ID.
     * @return WP_Post[] Array of album order posts in cart.
     */
    public static function get_cart_items( $client_album_id ) {
        return self::get_all( array(
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key'   => '_eao_client_album_id',
                    'value' => $client_album_id,
                ),
                array(
                    'key'   => '_eao_order_status',
                    'value' => self::STATUS_SUBMITTED,
                ),
            ),
        ) );
    }

    /**
     * Get an album order by ID.
     *
     * @since 1.0.0
     *
     * @param int $order_id The order post ID.
     * @return WP_Post|null The album order post or null if not found.
     */
    public static function get( $order_id ) {
        $post = get_post( $order_id );

        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return null;
        }

        return $post;
    }

    /**
     * Get album order meta data.
     *
     * @since 1.0.0
     *
     * @param int    $order_id The order post ID.
     * @param string $key      Optional. Specific meta key to retrieve (without _eao_ prefix).
     * @return mixed The meta value(s).
     */
    public static function get_meta( $order_id, $key = '' ) {
        if ( empty( $key ) ) {
            return get_post_meta( $order_id );
        }

        return get_post_meta( $order_id, '_eao_' . $key, true );
    }

    /**
     * Update album order meta data.
     *
     * @since 1.0.0
     *
     * @param int    $order_id The order post ID.
     * @param string $key      The meta key (without prefix).
     * @param mixed  $value    The meta value.
     * @return int|bool Meta ID on success, false on failure.
     */
    public static function update_meta( $order_id, $key, $value ) {
        return update_post_meta( $order_id, '_eao_' . $key, $value );
    }

    /**
     * Get the order status.
     *
     * @since 1.0.0
     *
     * @param int $order_id The order post ID.
     * @return string The order status.
     */
    public static function get_order_status( $order_id ) {
        $status = self::get_meta( $order_id, 'order_status' );
        return $status ? $status : self::STATUS_SUBMITTED;
    }

    /**
     * Update the order status.
     *
     * @since 1.0.0
     *
     * @param int    $order_id The order post ID.
     * @param string $status   The new status.
     * @return bool True on success, false on failure.
     */
    public static function update_status( $order_id, $status ) {
        if ( ! array_key_exists( $status, self::get_statuses() ) ) {
            return false;
        }

        self::update_meta( $order_id, 'order_status', $status );

        // Update timestamp based on status.
        $timestamp = current_time( 'mysql' );

        switch ( $status ) {
            case self::STATUS_ORDERED:
                self::update_meta( $order_id, 'order_date', $timestamp );
                break;
            case self::STATUS_SHIPPED:
                self::update_meta( $order_id, 'shipped_date', $timestamp );
                break;
        }

        /**
         * Fires after an order status is updated.
         *
         * @since 1.0.0
         *
         * @param int    $order_id The order post ID.
         * @param string $status   The new status.
         */
        do_action( 'eao_order_status_updated', $order_id, $status );

        return true;
    }

    /**
     * Calculate the total price for an order.
     *
     * @since 1.0.0
     *
     * @param int $order_id The order post ID.
     * @return float The calculated total price.
     */
    public static function calculate_total( $order_id ) {
        $base_price         = floatval( self::get_meta( $order_id, 'base_price' ) );
        $material_upcharge  = floatval( self::get_meta( $order_id, 'material_upcharge' ) );
        $size_upcharge      = floatval( self::get_meta( $order_id, 'size_upcharge' ) );
        $engraving_upcharge = floatval( self::get_meta( $order_id, 'engraving_upcharge' ) );
        $credit_type        = self::get_meta( $order_id, 'credit_type' );
        $applied_credits    = floatval( self::get_meta( $order_id, 'applied_credits' ) );

        // For free album credits, the base price is covered (client pays only upgrades).
        // For dollar credits, subtract the dollar amount from the total.
        // The 'applied_credits' field now stores the actual dollar amount to subtract.
        $total = ( $base_price + $material_upcharge + $size_upcharge + $engraving_upcharge ) - $applied_credits;

        return max( 0, $total );
    }

    /**
     * Count how many free album credits have been used for a specific design.
     *
     * @since 1.0.0
     *
     * @param int $client_album_id The client album ID.
     * @param int $design_index    The design index.
     * @param int $exclude_order   Optional. Order ID to exclude from count (for edits).
     * @return int Number of free credits used.
     */
    public static function count_used_free_credits( $client_album_id, $design_index, $exclude_order = 0 ) {
        $args = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'   => '_eao_client_album_id',
                    'value' => $client_album_id,
                ),
                array(
                    'key'   => '_eao_design_index',
                    'value' => $design_index,
                ),
                array(
                    'key'   => '_eao_credit_type',
                    'value' => 'free_album',
                ),
            ),
        );

        // Exclude a specific order (useful when editing).
        if ( $exclude_order > 0 ) {
            $args['post__not_in'] = array( $exclude_order );
        }

        $orders = get_posts( $args );

        return count( $orders );
    }

    /**
     * Get available free credits for a design.
     *
     * @since 1.0.0
     *
     * @param int $client_album_id The client album ID.
     * @param int $design_index    The design index.
     * @param int $exclude_order   Optional. Order ID to exclude from count (for edits).
     * @return int Number of free credits available.
     */
    public static function get_available_free_credits( $client_album_id, $design_index, $exclude_order = 0 ) {
        $designs = get_post_meta( $client_album_id, '_eao_designs', true );
        $designs = is_array( $designs ) ? $designs : array();

        if ( ! isset( $designs[ $design_index ] ) ) {
            return 0;
        }

        $design = $designs[ $design_index ];
        $total  = isset( $design['free_album_credits'] ) ? absint( $design['free_album_credits'] ) : 0;
        $used   = self::count_used_free_credits( $client_album_id, $design_index, $exclude_order );

        return max( 0, $total - $used );
    }

    /**
     * Get the dollar credit amount for a design.
     *
     * @since 1.0.0
     *
     * @param int $client_album_id The client album ID.
     * @param int $design_index    The design index.
     * @return float Dollar credit amount.
     */
    public static function get_design_dollar_credit( $client_album_id, $design_index ) {
        $designs = get_post_meta( $client_album_id, '_eao_designs', true );
        $designs = is_array( $designs ) ? $designs : array();

        if ( ! isset( $designs[ $design_index ] ) ) {
            return 0;
        }

        $design = $designs[ $design_index ];
        return isset( $design['dollar_credit'] ) ? floatval( $design['dollar_credit'] ) : 0;
    }

    /**
     * Get full order details.
     *
     * @since 1.0.0
     *
     * @param int $order_id The order post ID.
     * @return array Complete order details.
     */
    public static function get_order_details( $order_id ) {
        return array(
            'id'                  => $order_id,
            'album_name'          => self::get_meta( $order_id, 'album_name' ),
            'client_album_id'     => self::get_meta( $order_id, 'client_album_id' ),
            'status'              => self::get_order_status( $order_id ),
            'design'              => array(
                'name'       => self::get_meta( $order_id, 'design_name' ),
                'pdf_id'     => self::get_meta( $order_id, 'design_pdf_id' ),
                'base_price' => self::get_meta( $order_id, 'base_price' ),
            ),
            'material'            => array(
                'name'     => self::get_meta( $order_id, 'material_name' ),
                'color'    => self::get_meta( $order_id, 'material_color' ),
                'upcharge' => self::get_meta( $order_id, 'material_upcharge' ),
            ),
            'size'                => array(
                'name'     => self::get_meta( $order_id, 'size_name' ),
                'upcharge' => self::get_meta( $order_id, 'size_upcharge' ),
            ),
            'engraving'           => array(
                'method'   => self::get_meta( $order_id, 'engraving_method' ),
                'text'     => self::get_meta( $order_id, 'engraving_text' ),
                'font'     => self::get_meta( $order_id, 'engraving_font' ),
                'upcharge' => self::get_meta( $order_id, 'engraving_upcharge' ),
            ),
            'credits'             => self::get_meta( $order_id, 'applied_credits' ),
            'total'               => self::calculate_total( $order_id ),
            'customer'            => array(
                'name'    => self::get_meta( $order_id, 'customer_name' ),
                'email'   => self::get_meta( $order_id, 'customer_email' ),
                'phone'   => self::get_meta( $order_id, 'customer_phone' ),
                'address' => self::get_meta( $order_id, 'shipping_address' ),
            ),
            'notes'               => array(
                'client'       => self::get_meta( $order_id, 'client_notes' ),
                'photographer' => self::get_meta( $order_id, 'photographer_notes' ),
            ),
            'dates'               => array(
                'submitted' => self::get_meta( $order_id, 'submission_date' ),
                'ordered'   => self::get_meta( $order_id, 'order_date' ),
                'shipped'   => self::get_meta( $order_id, 'shipped_date' ),
            ),
        );
    }
}

