<?php
/**
 * Admin functionality.
 *
 * Handles all admin-specific functionality including
 * enqueuing styles and scripts for the admin area.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class.
 *
 * @since 1.0.0
 */
class EAO_Admin {

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

        $this->init_hooks();
    }

    /**
     * Initialize admin hooks.
     *
     * @since  1.0.0
     * @access private
     */
    private function init_hooks() {
        // Display activation notice.
        add_action( 'admin_notices', array( $this, 'activation_notice' ) );

        // AJAX handlers.
        add_action( 'wp_ajax_eao_get_attachment_url', array( $this, 'ajax_get_attachment_url' ) );
        add_action( 'wp_ajax_eao_process_refund', array( $this, 'ajax_process_refund' ) );

        // Admin notices for status updates.
        add_action( 'admin_notices', array( $this, 'status_update_notices' ) );

        // KPI section on album orders list page (before subsubsub views).
        add_filter( 'views_edit-album_order', array( $this, 'render_order_kpis' ) );
    }

    /**
     * Display admin notices for status updates.
     *
     * @since 1.0.0
     */
    public function status_update_notices() {
        if ( ! isset( $_GET['eao_status_updated'] ) ) {
            return;
        }

        $status   = sanitize_key( $_GET['eao_status_updated'] );
        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;

        if ( 'error' === $status ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php esc_html_e( 'There was an error updating the order status.', 'easy-album-orders' ); ?></p>
            </div>
            <?php
            return;
        }

        $order_number = $order_id ? EAO_Helpers::generate_order_number( $order_id ) : '';
        $status_label = EAO_Album_Order::get_status_label( $status );

        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                printf(
                    /* translators: 1: Order number, 2: Status label */
                    esc_html__( 'Order %1$s has been marked as %2$s.', 'easy-album-orders' ),
                    '<strong>' . esc_html( $order_number ) . '</strong>',
                    '<strong>' . esc_html( $status_label ) . '</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * AJAX handler to process a refund.
     *
     * @since 1.2.0
     */
    public function ajax_process_refund() {
        // Verify nonce.
        if ( ! check_ajax_referer( 'eao_refund_order', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'easy-album-orders' ) ) );
        }

        // Check permissions.
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'easy-album-orders' ) ) );
        }

        $order_id  = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $charge_id = isset( $_POST['charge_id'] ) ? sanitize_text_field( $_POST['charge_id'] ) : '';
        $amount    = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : null;
        $reason    = isset( $_POST['reason'] ) ? sanitize_key( $_POST['reason'] ) : 'requested_by_customer';
        $is_full   = isset( $_POST['is_full'] ) && 'true' === $_POST['is_full'];

        // Validate order exists.
        if ( ! $order_id || 'album_order' !== get_post_type( $order_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order.', 'easy-album-orders' ) ) );
        }

        // Validate charge ID matches what's stored.
        $stored_charge = get_post_meta( $order_id, '_eao_stripe_charge_id', true );
        if ( $charge_id !== $stored_charge ) {
            wp_send_json_error( array( 'message' => __( 'Payment reference mismatch.', 'easy-album-orders' ) ) );
        }

        // Check payment status - only refund paid orders.
        $payment_status = get_post_meta( $order_id, '_eao_payment_status', true );
        if ( ! in_array( $payment_status, array( 'paid', 'partial_refund' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'This order cannot be refunded.', 'easy-album-orders' ) ) );
        }

        // Initialize Stripe and process refund.
        $stripe = new EAO_Stripe();

        // For full refund, pass null to refund entire amount.
        $refund_amount = $is_full ? null : $amount;
        $result = $stripe->create_refund( $charge_id, $refund_amount, $reason );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        // Update order meta.
        $existing_refund = floatval( get_post_meta( $order_id, '_eao_refund_amount', true ) );
        $total_refunded  = $existing_refund + $result['amount'];
        $payment_amount  = floatval( get_post_meta( $order_id, '_eao_payment_amount', true ) );

        // Determine new payment status.
        $new_status = ( $total_refunded >= $payment_amount ) ? 'refunded' : 'partial_refund';

        update_post_meta( $order_id, '_eao_payment_status', $new_status );
        update_post_meta( $order_id, '_eao_refund_amount', $total_refunded );
        update_post_meta( $order_id, '_eao_refund_date', current_time( 'mysql' ) );
        update_post_meta( $order_id, '_eao_refund_id', $result['id'] );

        /**
         * Fires after a refund is processed.
         *
         * @since 1.2.0
         *
         * @param int    $order_id       Order ID.
         * @param array  $result         Refund result from Stripe.
         * @param float  $total_refunded Total amount refunded.
         */
        do_action( 'eao_refund_processed', $order_id, $result, $total_refunded );

        wp_send_json_success( array(
            'message'        => sprintf(
                /* translators: %s: Refunded amount */
                __( 'Successfully refunded %s', 'easy-album-orders' ),
                eao_format_price( $result['amount'] )
            ),
            'refund_amount'  => $result['amount'],
            'total_refunded' => $total_refunded,
            'payment_status' => $new_status,
        ) );
    }

    /**
     * AJAX handler to get attachment URL by ID.
     *
     * @since 1.0.0
     */
    public function ajax_get_attachment_url() {
        // Verify nonce.
        check_ajax_referer( 'eao_admin_nonce', 'nonce' );

        // Check permissions.
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'easy-album-orders' ) );
        }

        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

        if ( ! $attachment_id ) {
            wp_send_json_error( __( 'Invalid attachment ID.', 'easy-album-orders' ) );
        }

        // Get thumbnail URL if available, otherwise full URL.
        $thumb_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
        $full_url  = wp_get_attachment_url( $attachment_id );

        if ( ! $full_url ) {
            wp_send_json_error( __( 'Attachment not found.', 'easy-album-orders' ) );
        }

        wp_send_json_success(
            array(
                'url'       => $thumb_url ? $thumb_url : $full_url,
                'full_url'  => $full_url,
            )
        );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since 1.0.0
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_styles( $hook ) {
        // Only load on our plugin pages.
        if ( ! $this->is_plugin_page( $hook ) ) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name . '-admin',
            EAO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since 1.0.0
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts( $hook ) {
        // Only load on our plugin pages.
        if ( ! $this->is_plugin_page( $hook ) ) {
            return;
        }

        // Enqueue WordPress media uploader.
        wp_enqueue_media();

        // Enqueue Chart.js on reports page.
        if ( 'album_order_page_eao-reports' === $hook ) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                array(),
                '4.4.1',
                true
            );
        }

        wp_enqueue_script(
            $this->plugin_name . '-admin',
            EAO_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'jquery-ui-sortable' ),
            $this->version,
            true
        );

        // Localize script with data.
        wp_localize_script(
            $this->plugin_name . '-admin',
            'eaoAdmin',
            array(
                'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
                'nonce'                => wp_create_nonce( 'eao_admin_nonce' ),
                'confirmDelete'        => __( 'Are you sure you want to delete this item?', 'easy-album-orders' ),
                'mediaTitle'           => __( 'Select or Upload Media', 'easy-album-orders' ),
                'mediaButton'          => __( 'Use this media', 'easy-album-orders' ),
                'pdfMediaTitle'        => __( 'Select or Upload PDF', 'easy-album-orders' ),
                'pdfMediaButton'       => __( 'Use this PDF', 'easy-album-orders' ),
                'addColor'             => __( 'Add Color', 'easy-album-orders' ),
                'editColor'            => __( 'Edit Color', 'easy-album-orders' ),
                'textureUploadTitle'   => __( 'Select Texture Image', 'easy-album-orders' ),
                'textureUploadButton'  => __( 'Use this image', 'easy-album-orders' ),
                'previewUploadTitle'   => __( 'Select Preview Image', 'easy-album-orders' ),
                'previewUploadButton'  => __( 'Use this image', 'easy-album-orders' ),
                'uploadTexture'        => __( 'Click to upload texture image', 'easy-album-orders' ),
                'textureRequired'      => __( 'Please upload a texture image for texture/pattern type.', 'easy-album-orders' ),
                'emailTitles'          => array(
                    'order_confirmation'   => __( 'Order Confirmation Email', 'easy-album-orders' ),
                    'new_order_alert'      => __( 'New Order Alert Email', 'easy-album-orders' ),
                    'shipped_notification' => __( 'Shipped Notification Email', 'easy-album-orders' ),
                    'cart_reminder'        => __( 'Cart Reminder Email', 'easy-album-orders' ),
                ),
                'sendingReminders'     => __( 'Sending...', 'easy-album-orders' ),
                'copied'               => __( 'Copied!', 'easy-album-orders' ),
                'copyUrl'              => __( 'Copy URL', 'easy-album-orders' ),
            )
        );
    }

    /**
     * Check if current page is a plugin admin page.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $hook The current admin page hook.
     * @return bool True if on a plugin page, false otherwise.
     */
    private function is_plugin_page( $hook ) {
        $plugin_pages = array(
            'toplevel_page_eao-client-albums',
            'client-albums_page_eao-album-options',
            'edit.php',
            'post.php',
            'post-new.php',
        );

        // Check if we're on one of our custom pages.
        if ( in_array( $hook, $plugin_pages, true ) ) {
            return true;
        }

        // Check if we're editing our post types.
        global $post_type;
        $our_post_types = array( 'client_album', 'album_order' );

        if ( in_array( $post_type, $our_post_types, true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Display activation notice.
     *
     * @since 1.0.0
     */
    public function activation_notice() {
        // Check if transient is set.
        if ( ! get_transient( 'eao_activation_notice' ) ) {
            return;
        }

        // Delete transient so it only shows once.
        delete_transient( 'eao_activation_notice' );

        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                printf(
                    /* translators: %s: Link to settings page */
                    esc_html__( 'Thank you for installing Easy Album Orders! Get started by %s.', 'easy-album-orders' ),
                    '<a href="' . esc_url( admin_url( 'admin.php?page=eao-album-options' ) ) . '">' . esc_html__( 'configuring your album options', 'easy-album-orders' ) . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render KPI section on album orders list page.
     *
     * Displays quick statistics about album orders including
     * total revenue, orders this month, pending orders, and orders to ship.
     * Hooked into views_edit-album_order filter to render before subsubsub.
     *
     * @since 1.0.0
     *
     * @param array $views The list table views.
     * @return array The unmodified views array.
     */
    public function render_order_kpis( $views ) {
        // Calculate KPIs.
        $kpis = $this->calculate_order_kpis();

        ?>
        <div class="eao-kpi-section">
            <div class="eao-kpi-grid">
                <!-- Total Revenue -->
                <div class="eao-kpi-card">
                    <div class="eao-kpi-card__icon eao-kpi-card__icon--revenue">
                        <?php EAO_Icons::render( 'credit-card', array( 'size' => 20 ) ); ?>
                    </div>
                    <div class="eao-kpi-card__content">
                        <span class="eao-kpi-card__value"><?php echo esc_html( eao_format_price( $kpis['total_revenue'] ) ); ?></span>
                        <span class="eao-kpi-card__label"><?php esc_html_e( 'Total Revenue', 'easy-album-orders' ); ?></span>
                    </div>
                </div>

                <!-- Orders This Month -->
                <div class="eao-kpi-card">
                    <div class="eao-kpi-card__icon eao-kpi-card__icon--month">
                        <?php EAO_Icons::render( 'calendar', array( 'size' => 20 ) ); ?>
                    </div>
                    <div class="eao-kpi-card__content">
                        <span class="eao-kpi-card__value"><?php echo esc_html( $kpis['orders_this_month'] ); ?></span>
                        <span class="eao-kpi-card__label"><?php esc_html_e( 'This Month', 'easy-album-orders' ); ?></span>
                    </div>
                </div>

                <!-- Pending (In Cart) -->
                <div class="eao-kpi-card">
                    <div class="eao-kpi-card__icon eao-kpi-card__icon--pending">
                        <?php EAO_Icons::render( 'shopping-cart', array( 'size' => 20 ) ); ?>
                    </div>
                    <div class="eao-kpi-card__content">
                        <span class="eao-kpi-card__value"><?php echo esc_html( $kpis['pending_orders'] ); ?></span>
                        <span class="eao-kpi-card__label"><?php esc_html_e( 'In Cart', 'easy-album-orders' ); ?></span>
                    </div>
                </div>

                <!-- Ready to Ship -->
                <div class="eao-kpi-card">
                    <div class="eao-kpi-card__icon eao-kpi-card__icon--ship">
                        <?php EAO_Icons::render( 'truck', array( 'size' => 20 ) ); ?>
                    </div>
                    <div class="eao-kpi-card__content">
                        <span class="eao-kpi-card__value"><?php echo esc_html( $kpis['ready_to_ship'] ); ?></span>
                        <span class="eao-kpi-card__label"><?php esc_html_e( 'Ready to Ship', 'easy-album-orders' ); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return $views;
    }

    /**
     * Calculate KPIs for album orders.
     *
     * @since  1.0.0
     * @access private
     *
     * @return array Array of KPI values.
     */
    private function calculate_order_kpis() {
        global $wpdb;

        // Get counts by status.
        $pending_orders = count( EAO_Album_Order::get_by_status( EAO_Album_Order::STATUS_SUBMITTED ) );
        $ready_to_ship  = count( EAO_Album_Order::get_by_status( EAO_Album_Order::STATUS_ORDERED ) );

        // Get orders this month (ordered or shipped only - not just submitted).
        $first_day_of_month = date( 'Y-m-01 00:00:00' );
        $orders_this_month = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_eao_order_status'
                WHERE p.post_type = 'album_order'
                AND p.post_status = 'publish'
                AND pm.meta_value IN ('ordered', 'shipped')
                AND p.post_date >= %s",
                $first_day_of_month
            )
        );

        // Calculate total revenue from paid orders.
        $paid_order_ids = $wpdb->get_col(
            "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_eao_order_status'
            WHERE p.post_type = 'album_order'
            AND p.post_status = 'publish'
            AND pm.meta_value IN ('ordered', 'shipped')"
        );

        $total_revenue = 0;
        foreach ( $paid_order_ids as $order_id ) {
            $total_revenue += EAO_Album_Order::calculate_total( $order_id );
        }

        return array(
            'total_revenue'     => $total_revenue,
            'orders_this_month' => absint( $orders_this_month ),
            'pending_orders'    => $pending_orders,
            'ready_to_ship'     => $ready_to_ship,
        );
    }
}

