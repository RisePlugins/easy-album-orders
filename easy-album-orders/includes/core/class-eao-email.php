<?php
/**
 * Email handler class.
 *
 * Handles all email notifications for the plugin including
 * order confirmations, new order alerts, and shipping notifications.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Email class.
 *
 * @since 1.0.0
 */
class EAO_Email {

    /**
     * Email settings.
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private $settings;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->settings = get_option( 'eao_email_settings', array() );
    }

    /**
     * Initialize email hooks.
     *
     * @since 1.0.0
     */
    public function init() {
        // Hook into checkout completion.
        add_action( 'eao_order_checkout_complete', array( $this, 'send_order_confirmation' ), 10, 2 );
        add_action( 'eao_order_checkout_complete', array( $this, 'send_new_order_alert' ), 10, 2 );

        // Hook into order status change to shipped.
        add_action( 'eao_order_status_changed', array( $this, 'send_shipped_notification' ), 10, 3 );

        // Set HTML content type for emails.
        add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );

        // AJAX handler for email preview.
        add_action( 'wp_ajax_eao_preview_email', array( $this, 'ajax_preview_email' ) );

        // AJAX handler for manual cart reminder send.
        add_action( 'wp_ajax_eao_send_cart_reminders', array( $this, 'ajax_send_cart_reminders' ) );

        // Cart reminder cron job.
        add_action( 'eao_cart_reminder_check', array( $this, 'process_cart_reminders' ) );

        // Ensure cron is scheduled (in case activation didn't run properly).
        $this->maybe_schedule_cart_reminder_cron();
    }

    /**
     * Ensure the cart reminder cron is scheduled.
     *
     * @since 1.0.0
     */
    private function maybe_schedule_cart_reminder_cron() {
        if ( ! wp_next_scheduled( 'eao_cart_reminder_check' ) ) {
            wp_schedule_event( time(), 'daily', 'eao_cart_reminder_check' );
        }
    }

    /**
     * Process cart reminders.
     *
     * Finds orders with 'submitted' status that are older than the configured
     * number of days and sends reminder emails.
     *
     * @since 1.0.0
     */
    public function process_cart_reminders() {
        // Check if cart reminders are enabled.
        if ( ! $this->is_email_enabled( 'cart_reminder' ) ) {
            return;
        }

        // Get reminder delay days (default 3).
        $days = absint( $this->get_setting( 'cart_reminder_days', 3 ) );
        if ( $days < 1 ) {
            $days = 3;
        }

        // Calculate the date threshold.
        $date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // Query for submitted orders older than the threshold.
        $args = array(
            'post_type'      => 'album_order',
            'post_status'    => 'publish',
            'posts_per_page' => 50, // Process in batches.
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_eao_order_status',
                    'value'   => 'submitted',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_eao_cart_reminder_sent',
                    'compare' => 'NOT EXISTS', // Only orders that haven't received a reminder.
                ),
            ),
            'date_query'     => array(
                array(
                    'before' => $date_threshold,
                ),
            ),
        );

        $orders = get_posts( $args );

        if ( empty( $orders ) ) {
            return;
        }

        // Group orders by customer email and client album.
        $grouped_orders = array();

        foreach ( $orders as $order ) {
            $customer_email  = get_post_meta( $order->ID, '_eao_customer_email', true );
            $client_album_id = get_post_meta( $order->ID, '_eao_client_album_id', true );

            if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
                continue;
            }

            $key = $customer_email . '_' . $client_album_id;

            if ( ! isset( $grouped_orders[ $key ] ) ) {
                $grouped_orders[ $key ] = array(
                    'customer_email'  => $customer_email,
                    'client_album_id' => $client_album_id,
                    'order_ids'       => array(),
                );
            }

            $grouped_orders[ $key ]['order_ids'][] = $order->ID;
        }

        // Send reminders for each group.
        foreach ( $grouped_orders as $group ) {
            $this->send_cart_reminder( $group['order_ids'], $group['client_album_id'], $group['customer_email'] );
        }
    }

    /**
     * Send cart reminder email.
     *
     * @since 1.0.0
     *
     * @param array  $order_ids       Array of order IDs in the cart.
     * @param int    $client_album_id Client album ID.
     * @param string $customer_email  Customer email address.
     */
    public function send_cart_reminder( $order_ids, $client_album_id, $customer_email ) {
        if ( empty( $order_ids ) || empty( $customer_email ) ) {
            return;
        }

        // Get customer name from the first order.
        $first_order_id = $order_ids[0];
        $customer_name  = get_post_meta( $first_order_id, '_eao_customer_name', true );

        if ( empty( $customer_name ) ) {
            $customer_name = __( 'there', 'easy-album-orders' ); // Fallback.
        }

        // Build order data.
        $orders_data = $this->get_orders_data( $order_ids );
        $total       = $this->calculate_orders_total( $order_ids );

        // Get client album URL.
        $album_url = get_permalink( $client_album_id );

        // Get client album title.
        $album_title = get_the_title( $client_album_id );

        // Build email content.
        $subject = $this->get_setting( 'cart_reminder_subject', __( 'Don\'t forget your album order!', 'easy-album-orders' ) );
        $subject = $this->replace_placeholders( $subject, array(
            'customer_name' => $customer_name,
            'album_title'   => $album_title,
        ) );

        $body = $this->get_cart_reminder_template( $orders_data, $total, $customer_name, $album_url );

        // Send email.
        $sent = wp_mail( $customer_email, $subject, $body, $this->get_headers() );

        // Mark orders as having received a reminder (to prevent duplicate sends).
        if ( $sent ) {
            foreach ( $order_ids as $order_id ) {
                update_post_meta( $order_id, '_eao_cart_reminder_sent', current_time( 'mysql' ) );
            }

            // Invalidate the pending reminders cache.
            $this->invalidate_pending_reminders_cache();
        }
    }

    /**
     * Invalidate the pending cart reminders cache.
     *
     * Called after reminders are sent to ensure the admin UI shows accurate counts.
     *
     * @since 1.0.0
     * @access private
     */
    private function invalidate_pending_reminders_cache() {
        // Delete all possible cached counts (days 1-30).
        for ( $days = 1; $days <= 30; $days++ ) {
            delete_transient( 'eao_pending_cart_reminders_' . $days );
        }
    }

    /**
     * AJAX handler for manually sending cart reminders.
     *
     * @since 1.0.0
     */
    public function ajax_send_cart_reminders() {
        check_ajax_referer( 'eao_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized access.', 'easy-album-orders' ) );
        }

        // Temporarily enable cart reminders for this run.
        $this->settings['enable_cart_reminder'] = true;

        // Process reminders.
        $this->process_cart_reminders();

        // Count how many were sent.
        $days           = absint( $this->get_setting( 'cart_reminder_days', 3 ) );
        $date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // Check if any orders still need reminders (shouldn't be any after successful send).
        $remaining_args = array(
            'post_type'      => 'album_order',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_eao_order_status',
                    'value'   => 'submitted',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_eao_cart_reminder_sent',
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'date_query'     => array(
                array(
                    'before' => $date_threshold,
                ),
            ),
        );
        $remaining = count( get_posts( $remaining_args ) );

        if ( 0 === $remaining ) {
            wp_send_json_success( array(
                'message' => __( 'Cart reminders sent successfully!', 'easy-album-orders' ),
            ) );
        } else {
            wp_send_json_error( __( 'Some reminders could not be sent. Check email settings.', 'easy-album-orders' ) );
        }
    }

    /**
     * AJAX handler for email preview.
     *
     * @since 1.0.0
     */
    public function ajax_preview_email() {
        check_ajax_referer( 'eao_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized access.', 'easy-album-orders' ) );
        }

        $email_type = isset( $_POST['email_type'] ) ? sanitize_text_field( $_POST['email_type'] ) : '';

        if ( empty( $email_type ) ) {
            wp_send_json_error( __( 'Invalid email type.', 'easy-album-orders' ) );
        }

        // Refresh settings in case they changed.
        $this->settings = get_option( 'eao_email_settings', array() );

        // Generate preview with sample data.
        $preview = $this->generate_email_preview( $email_type );

        if ( ! $preview ) {
            wp_send_json_error( __( 'Unknown email type.', 'easy-album-orders' ) );
        }

        wp_send_json_success( $preview );
    }

    /**
     * Generate email preview with sample data.
     *
     * @since 1.0.0
     *
     * @param string $email_type The type of email to preview.
     * @return array|false Preview data or false if invalid type.
     */
    private function generate_email_preview( $email_type ) {
        // Sample data for previews.
        $sample_customer_name  = 'Sarah Johnson';
        $sample_customer_email = 'sarah.johnson@example.com';
        $sample_customer_phone = '(555) 123-4567';
        $sample_album_title    = 'Johnson Wedding Album';

        // Sample order data.
        $sample_orders = array(
            array(
                'id'                => 12345,
                'order_number'      => 'EAO-12345',
                'album_name'        => 'Our Wedding Day',
                'design_name'       => '30-Page Layflat Album',
                'material_name'     => 'Italian Leather',
                'material_color'    => 'Burgundy',
                'size_name'         => '12x12',
                'engraving_text'    => 'Sarah & Michael • 06.15.2024',
                'engraving_font'    => 'Elegant Script',
                'engraving_method'  => 'Gold Foil Stamp',
                'base_price'        => 500.00,
                'material_upcharge' => 150.00,
                'size_upcharge'     => 75.00,
                'engraving_upcharge'=> 49.00,
                'applied_credits'   => 100.00,
                'total'             => 674.00,
                'shipping_name'     => 'Sarah Johnson',
                'shipping_address1' => '123 Maple Street',
                'shipping_address2' => 'Apt 4B',
                'shipping_city'     => 'Portland',
                'shipping_state'    => 'OR',
                'shipping_zip'      => '97201',
            ),
            array(
                'id'                => 12346,
                'order_number'      => 'EAO-12346',
                'album_name'        => 'Parent Album - Mom & Dad',
                'design_name'       => '20-Page Classic Album',
                'material_name'     => 'Linen',
                'material_color'    => 'Ivory',
                'size_name'         => '10x10',
                'engraving_text'    => '',
                'engraving_font'    => '',
                'engraving_method'  => '',
                'base_price'        => 350.00,
                'material_upcharge' => 0.00,
                'size_upcharge'     => 0.00,
                'engraving_upcharge'=> 0.00,
                'applied_credits'   => 0.00,
                'total'             => 350.00,
                'shipping_name'     => 'Robert Johnson',
                'shipping_address1' => '456 Oak Avenue',
                'shipping_address2' => '',
                'shipping_city'     => 'Seattle',
                'shipping_state'    => 'WA',
                'shipping_zip'      => '98101',
            ),
        );

        $sample_total = 1024.00;

        switch ( $email_type ) {
            case 'order_confirmation':
                $subject = $this->get_setting( 'order_confirmation_subject', __( 'Your Album Order Confirmation', 'easy-album-orders' ) );
                $subject = $this->replace_placeholders( $subject, array(
                    'customer_name' => $sample_customer_name,
                    'album_title'   => $sample_album_title,
                ) );
                $html = $this->get_order_confirmation_template( $sample_orders, $sample_total, $sample_customer_name, $sample_album_title );
                break;

            case 'new_order_alert':
                $subject = $this->get_setting( 'new_order_alert_subject', __( 'New Album Order Received', 'easy-album-orders' ) );
                $subject = $this->replace_placeholders( $subject, array(
                    'customer_name' => $sample_customer_name,
                    'album_title'   => $sample_album_title,
                ) );
                $admin_url = admin_url( 'edit.php?post_type=album_order' );
                $html = $this->get_new_order_alert_template( $sample_orders, $sample_total, $sample_customer_name, $sample_customer_email, $sample_customer_phone, $sample_album_title, $admin_url );
                break;

            case 'shipped_notification':
                $subject = $this->get_setting( 'shipped_notification_subject', __( 'Your Album Has Shipped!', 'easy-album-orders' ) );
                $subject = $this->replace_placeholders( $subject, array(
                    'customer_name' => $sample_customer_name,
                    'album_name'    => $sample_orders[0]['album_name'],
                ) );
                $html = $this->get_shipped_notification_template(
                    $sample_orders[0],
                    $sample_customer_name,
                    'USPS1234567890',
                    'USPS Priority Mail',
                    'https://tools.usps.com/go/TrackConfirmAction?tLabels=USPS1234567890'
                );
                break;

            case 'cart_reminder':
                $subject = $this->get_setting( 'cart_reminder_subject', __( 'Don\'t forget your album order!', 'easy-album-orders' ) );
                $subject = $this->replace_placeholders( $subject, array(
                    'customer_name' => $sample_customer_name,
                    'album_title'   => $sample_album_title,
                ) );
                $html = $this->get_cart_reminder_template( array( $sample_orders[0] ), 674.00, $sample_customer_name, 'https://example.com/album/johnson-wedding/' );
                break;

            default:
                return false;
        }

        return array(
            'subject' => $subject,
            'html'    => $html,
        );
    }

    /**
     * Get cart reminder email template.
     *
     * @since 1.0.0
     *
     * @param array  $orders        Orders data (items in cart).
     * @param float  $total         Total price.
     * @param string $customer_name Customer name.
     * @param string $album_url     URL to the album order page.
     * @return string HTML email.
     */
    private function get_cart_reminder_template( $orders, $total, $customer_name, $album_url ) {
        $accent_color = $this->get_setting( 'accent_color', '#e67e22' );

        ob_start();
        ?>
        <h2 style="margin: 0 0 20px; color: #2c3e50; font-size: 24px;">Don't Forget Your Album!</h2>
        <p style="margin: 0 0 20px; color: #495057; font-size: 16px; line-height: 1.6;">
            Hi <?php echo esc_html( $customer_name ); ?>,
        </p>
        <p style="margin: 0 0 30px; color: #495057; font-size: 16px; line-height: 1.6;">
            We noticed you have <?php echo count( $orders ) === 1 ? 'an album' : 'some albums'; ?> waiting in your cart. Your selections look amazing! Complete your order to bring your memories to life.
        </p>

        <!-- Cart Items -->
        <?php foreach ( $orders as $order ) : ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
                <tr>
                    <td style="padding: 20px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="vertical-align: top;">
                                    <p style="margin: 0 0 10px; color: #2c3e50; font-size: 16px; font-weight: 600;"><?php echo esc_html( $order['album_name'] ); ?></p>
                                    <p style="margin: 0; color: #6c757d; font-size: 13px; line-height: 1.6;">
                                        <?php echo esc_html( $order['design_name'] ); ?> • 
                                        <?php echo esc_html( $order['material_name'] ); ?>
                                        <?php if ( $order['material_color'] ) : ?>(<?php echo esc_html( $order['material_color'] ); ?>)<?php endif; ?> • 
                                        <?php echo esc_html( $order['size_name'] ); ?>
                                    </p>
                                </td>
                                <td style="vertical-align: top; text-align: right; width: 100px;">
                                    <p style="margin: 0; color: <?php echo esc_attr( $accent_color ); ?>; font-size: 16px; font-weight: 600;"><?php echo esc_html( eao_format_price( $order['total'] ) ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        <?php endforeach; ?>

        <!-- Cart Total -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #2c3e50; border-radius: 8px; margin-bottom: 30px;">
            <tr>
                <td style="padding: 20px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="color: #ffffff; font-size: 18px;">Cart Total:</td>
                            <td style="color: #ffffff; font-size: 24px; font-weight: 700; text-align: right;"><?php echo esc_html( eao_format_price( $total ) ); ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- CTA Button -->
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">
                    <a href="<?php echo esc_url( $album_url ); ?>" style="display: inline-block; padding: 16px 40px; background-color: <?php echo esc_attr( $accent_color ); ?>; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 18px; font-weight: 600;">Complete Your Order</a>
                </td>
            </tr>
        </table>

        <p style="margin: 30px 0 0; color: #6c757d; font-size: 14px; line-height: 1.6; text-align: center;">
            If you have any questions or need help, feel free to reply to this email. We're here to help!
        </p>
        <?php
        $content = ob_get_clean();

        return $this->get_email_template( $content, __( 'Complete Your Album Order', 'easy-album-orders' ) );
    }

    /**
     * Set HTML content type for wp_mail.
     *
     * @since 1.0.0
     *
     * @return string HTML content type.
     */
    public function set_html_content_type() {
        return 'text/html';
    }

    /**
     * Get email setting with default.
     *
     * @since 1.0.0
     *
     * @param string $key     Setting key.
     * @param mixed  $default Default value.
     * @return mixed Setting value or default.
     */
    private function get_setting( $key, $default = '' ) {
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
    }

    /**
     * Get the from name for emails.
     *
     * @since 1.0.0
     *
     * @return string From name.
     */
    private function get_from_name() {
        $from_name = $this->get_setting( 'from_name', '' );
        return ! empty( $from_name ) ? $from_name : get_bloginfo( 'name' );
    }

    /**
     * Get the from email address.
     *
     * @since 1.0.0
     *
     * @return string From email.
     */
    private function get_from_email() {
        $from_email = $this->get_setting( 'from_email', '' );
        return ! empty( $from_email ) ? $from_email : get_option( 'admin_email' );
    }

    /**
     * Get the admin email for notifications.
     *
     * @since 1.0.0
     *
     * @return string Admin email.
     */
    private function get_admin_email() {
        $admin_email = $this->get_setting( 'admin_email', '' );
        return ! empty( $admin_email ) ? $admin_email : get_option( 'admin_email' );
    }

    /**
     * Check if a specific email type is enabled.
     *
     * @since 1.0.0
     *
     * @param string $type Email type.
     * @return bool True if enabled.
     */
    private function is_email_enabled( $type ) {
        // Check master toggle first (defaults to enabled if not set).
        if ( isset( $this->settings['email_notifications'] ) && empty( $this->settings['email_notifications'] ) ) {
            return false;
        }

        // Check individual email type.
        $key = 'enable_' . $type;
        return ! isset( $this->settings[ $key ] ) || ! empty( $this->settings[ $key ] );
    }

    /**
     * Get email headers.
     *
     * @since 1.0.0
     *
     * @return array Email headers.
     */
    private function get_headers() {
        $from_name  = $this->get_from_name();
        $from_email = $this->get_from_email();

        return array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', $from_name, $from_email ),
        );
    }

    /**
     * Send order confirmation email to customer.
     *
     * @since 1.0.0
     *
     * @param array $order_ids      Array of order IDs that were checked out.
     * @param int   $client_album_id Client album ID.
     */
    public function send_order_confirmation( $order_ids, $client_album_id ) {
        if ( ! $this->is_email_enabled( 'order_confirmation' ) ) {
            return;
        }

        if ( empty( $order_ids ) ) {
            return;
        }

        // Get customer email from the first order.
        $first_order_id = $order_ids[0];
        $customer_email = get_post_meta( $first_order_id, '_eao_customer_email', true );
        $customer_name  = get_post_meta( $first_order_id, '_eao_customer_name', true );

        if ( empty( $customer_email ) ) {
            return;
        }

        // Build order data.
        $orders_data = $this->get_orders_data( $order_ids );
        $total       = $this->calculate_orders_total( $order_ids );

        // Get client album title.
        $album_title = get_the_title( $client_album_id );

        // Build email content.
        $subject = $this->get_setting( 'order_confirmation_subject', __( 'Your Album Order Confirmation', 'easy-album-orders' ) );
        $subject = $this->replace_placeholders( $subject, array(
            'customer_name' => $customer_name,
            'album_title'   => $album_title,
        ) );

        $body = $this->get_order_confirmation_template( $orders_data, $total, $customer_name, $album_title );

        // Send email.
        wp_mail( $customer_email, $subject, $body, $this->get_headers() );
    }

    /**
     * Send new order alert to photographer/admin.
     *
     * @since 1.0.0
     *
     * @param array $order_ids      Array of order IDs that were checked out.
     * @param int   $client_album_id Client album ID.
     */
    public function send_new_order_alert( $order_ids, $client_album_id ) {
        if ( ! $this->is_email_enabled( 'new_order_alert' ) ) {
            return;
        }

        if ( empty( $order_ids ) ) {
            return;
        }

        $admin_email = $this->get_admin_email();

        // Get customer info from the first order.
        $first_order_id = $order_ids[0];
        $customer_email = get_post_meta( $first_order_id, '_eao_customer_email', true );
        $customer_name  = get_post_meta( $first_order_id, '_eao_customer_name', true );
        $customer_phone = get_post_meta( $first_order_id, '_eao_customer_phone', true );

        // Build order data.
        $orders_data = $this->get_orders_data( $order_ids );
        $total       = $this->calculate_orders_total( $order_ids );

        // Get client album title.
        $album_title = get_the_title( $client_album_id );

        // Admin URL to view orders.
        $admin_url = admin_url( 'edit.php?post_type=album_order' );

        // Build email content.
        $subject = $this->get_setting( 'new_order_alert_subject', __( 'New Album Order Received', 'easy-album-orders' ) );
        $subject = $this->replace_placeholders( $subject, array(
            'customer_name' => $customer_name,
            'album_title'   => $album_title,
        ) );

        $body = $this->get_new_order_alert_template( $orders_data, $total, $customer_name, $customer_email, $customer_phone, $album_title, $admin_url );

        // Send email.
        wp_mail( $admin_email, $subject, $body, $this->get_headers() );
    }

    /**
     * Send shipped notification to customer.
     *
     * @since 1.0.0
     *
     * @param int    $order_id   The order ID.
     * @param string $old_status Previous status.
     * @param string $new_status New status.
     */
    public function send_shipped_notification( $order_id, $old_status, $new_status ) {
        // Only send if status changed TO shipped.
        if ( 'shipped' !== $new_status ) {
            return;
        }

        if ( ! $this->is_email_enabled( 'shipped_notification' ) ) {
            return;
        }

        // Get customer email.
        $customer_email = get_post_meta( $order_id, '_eao_customer_email', true );
        $customer_name  = get_post_meta( $order_id, '_eao_customer_name', true );

        if ( empty( $customer_email ) ) {
            return;
        }

        // Get order data.
        $order_data = $this->get_single_order_data( $order_id );

        // Get tracking info if available.
        $tracking_number  = get_post_meta( $order_id, '_eao_tracking_number', true );
        $tracking_carrier = get_post_meta( $order_id, '_eao_tracking_carrier', true );
        $tracking_url     = get_post_meta( $order_id, '_eao_tracking_url', true );

        // Build email content.
        $subject = $this->get_setting( 'shipped_notification_subject', __( 'Your Album Has Shipped!', 'easy-album-orders' ) );
        $subject = $this->replace_placeholders( $subject, array(
            'customer_name' => $customer_name,
            'album_name'    => $order_data['album_name'],
        ) );

        $body = $this->get_shipped_notification_template( $order_data, $customer_name, $tracking_number, $tracking_carrier, $tracking_url );

        // Send email.
        wp_mail( $customer_email, $subject, $body, $this->get_headers() );
    }

    /**
     * Get orders data for email.
     *
     * @since 1.0.0
     *
     * @param array $order_ids Array of order IDs.
     * @return array Orders data.
     */
    private function get_orders_data( $order_ids ) {
        $orders = array();

        foreach ( $order_ids as $order_id ) {
            $orders[] = $this->get_single_order_data( $order_id );
        }

        return $orders;
    }

    /**
     * Get single order data.
     *
     * @since 1.0.0
     *
     * @param int $order_id Order ID.
     * @return array Order data.
     */
    private function get_single_order_data( $order_id ) {
        return array(
            'id'               => $order_id,
            'order_number'     => EAO_Helpers::generate_order_number( $order_id ),
            'album_name'       => get_post_meta( $order_id, '_eao_album_name', true ),
            'design_name'      => get_post_meta( $order_id, '_eao_design_name', true ),
            'material_name'    => get_post_meta( $order_id, '_eao_material_name', true ),
            'material_color'   => get_post_meta( $order_id, '_eao_material_color', true ),
            'size_name'        => get_post_meta( $order_id, '_eao_size_name', true ),
            'engraving_text'   => get_post_meta( $order_id, '_eao_engraving_text', true ),
            'engraving_font'   => get_post_meta( $order_id, '_eao_engraving_font', true ),
            'engraving_method' => get_post_meta( $order_id, '_eao_engraving_method_name', true ),
            'base_price'       => floatval( get_post_meta( $order_id, '_eao_base_price', true ) ),
            'material_upcharge'=> floatval( get_post_meta( $order_id, '_eao_material_upcharge', true ) ),
            'size_upcharge'    => floatval( get_post_meta( $order_id, '_eao_size_upcharge', true ) ),
            'engraving_upcharge'=> floatval( get_post_meta( $order_id, '_eao_engraving_upcharge', true ) ),
            'applied_credits'  => floatval( get_post_meta( $order_id, '_eao_applied_credits', true ) ),
            'total'            => EAO_Album_Order::calculate_total( $order_id ),
            'shipping_name'    => get_post_meta( $order_id, '_eao_shipping_name', true ),
            'shipping_address1'=> get_post_meta( $order_id, '_eao_shipping_address1', true ),
            'shipping_address2'=> get_post_meta( $order_id, '_eao_shipping_address2', true ),
            'shipping_city'    => get_post_meta( $order_id, '_eao_shipping_city', true ),
            'shipping_state'   => get_post_meta( $order_id, '_eao_shipping_state', true ),
            'shipping_zip'     => get_post_meta( $order_id, '_eao_shipping_zip', true ),
        );
    }

    /**
     * Calculate total for multiple orders.
     *
     * @since 1.0.0
     *
     * @param array $order_ids Array of order IDs.
     * @return float Total price.
     */
    private function calculate_orders_total( $order_ids ) {
        $total = 0;

        foreach ( $order_ids as $order_id ) {
            $total += EAO_Album_Order::calculate_total( $order_id );
        }

        return $total;
    }

    /**
     * Replace placeholders in text.
     *
     * @since 1.0.0
     *
     * @param string $text        Text with placeholders.
     * @param array  $replacements Key-value pairs for replacement.
     * @return string Text with placeholders replaced.
     */
    private function replace_placeholders( $text, $replacements ) {
        foreach ( $replacements as $key => $value ) {
            $text = str_replace( '{' . $key . '}', $value, $text );
        }
        return $text;
    }

    /**
     * Get base email template wrapper.
     *
     * @since 1.0.0
     *
     * @param string $content Email content.
     * @param string $title   Email title.
     * @return string HTML email.
     */
    private function get_email_template( $content, $title = '' ) {
        $logo_url    = $this->get_setting( 'logo_url', '' );
        $accent_color = $this->get_setting( 'accent_color', '#e67e22' );
        $site_name   = get_bloginfo( 'name' );

        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $title ); ?></title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: <?php echo esc_attr( $accent_color ); ?>; padding: 30px; text-align: center;">
                            <?php if ( $logo_url ) : ?>
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $site_name ); ?>" style="max-height: 60px; max-width: 200px;">
                            <?php else : ?>
                                <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600;"><?php echo esc_html( $site_name ); ?></h1>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0; color: #6c757d; font-size: 14px;">
                                <?php echo esc_html( $site_name ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get order confirmation email template.
     *
     * @since 1.0.0
     *
     * @param array  $orders        Orders data.
     * @param float  $total         Total price.
     * @param string $customer_name Customer name.
     * @param string $album_title   Album title.
     * @return string HTML email.
     */
    private function get_order_confirmation_template( $orders, $total, $customer_name, $album_title ) {
        $accent_color = $this->get_setting( 'accent_color', '#e67e22' );

        ob_start();
        ?>
        <h2 style="margin: 0 0 20px; color: #2c3e50; font-size: 24px;">Thank You for Your Order!</h2>
        <p style="margin: 0 0 20px; color: #495057; font-size: 16px; line-height: 1.6;">
            Hi <?php echo esc_html( $customer_name ); ?>,
        </p>
        <p style="margin: 0 0 30px; color: #495057; font-size: 16px; line-height: 1.6;">
            We've received your album order and are excited to get started! Here's a summary of what you ordered:
        </p>

        <!-- Order Items -->
        <?php foreach ( $orders as $order ) : ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 20px;">
                        <h3 style="margin: 0 0 15px; color: #2c3e50; font-size: 18px;"><?php echo esc_html( $order['album_name'] ); ?></h3>
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding: 5px 0; color: #6c757d; font-size: 14px;">Design:</td>
                                <td style="padding: 5px 0; color: #2c3e50; font-size: 14px; text-align: right;"><?php echo esc_html( $order['design_name'] ); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; color: #6c757d; font-size: 14px;">Material:</td>
                                <td style="padding: 5px 0; color: #2c3e50; font-size: 14px; text-align: right;">
                                    <?php echo esc_html( $order['material_name'] ); ?>
                                    <?php if ( $order['material_color'] ) : ?>
                                        (<?php echo esc_html( $order['material_color'] ); ?>)
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; color: #6c757d; font-size: 14px;">Size:</td>
                                <td style="padding: 5px 0; color: #2c3e50; font-size: 14px; text-align: right;"><?php echo esc_html( $order['size_name'] ); ?></td>
                            </tr>
                            <?php if ( $order['engraving_text'] ) : ?>
                                <tr>
                                    <td style="padding: 5px 0; color: #6c757d; font-size: 14px;">Engraving:</td>
                                    <td style="padding: 5px 0; color: #2c3e50; font-size: 14px; text-align: right;">"<?php echo esc_html( $order['engraving_text'] ); ?>"</td>
                                </tr>
                            <?php endif; ?>
                        </table>

                        <!-- Shipping Address -->
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
                            <p style="margin: 0 0 5px; color: #6c757d; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Shipping To:</p>
                            <p style="margin: 0; color: #2c3e50; font-size: 14px; line-height: 1.5;">
                                <?php echo esc_html( $order['shipping_name'] ); ?><br>
                                <?php echo esc_html( $order['shipping_address1'] ); ?>
                                <?php if ( $order['shipping_address2'] ) : ?>
                                    <br><?php echo esc_html( $order['shipping_address2'] ); ?>
                                <?php endif; ?>
                                <br><?php echo esc_html( $order['shipping_city'] . ', ' . $order['shipping_state'] . ' ' . $order['shipping_zip'] ); ?>
                            </p>
                        </div>

                        <!-- Item Total -->
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6; text-align: right;">
                            <?php if ( $order['applied_credits'] > 0 ) : ?>
                                <p style="margin: 0 0 5px; color: #27ae60; font-size: 14px;">
                                    Credit Applied: -<?php echo esc_html( eao_format_price( $order['applied_credits'] ) ); ?>
                                </p>
                            <?php endif; ?>
                            <p style="margin: 0; color: <?php echo esc_attr( $accent_color ); ?>; font-size: 18px; font-weight: 600;">
                                <?php echo esc_html( eao_format_price( $order['total'] ) ); ?>
                            </p>
                        </div>
                    </td>
                </tr>
            </table>
        <?php endforeach; ?>

        <!-- Order Total -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #2c3e50; border-radius: 8px;">
            <tr>
                <td style="padding: 20px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="color: #ffffff; font-size: 18px;">Order Total:</td>
                            <td style="color: #ffffff; font-size: 24px; font-weight: 700; text-align: right;"><?php echo esc_html( eao_format_price( $total ) ); ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <p style="margin: 30px 0 0; color: #495057; font-size: 16px; line-height: 1.6;">
            We'll send you another email when your album ships. If you have any questions, please don't hesitate to reach out!
        </p>
        <?php
        $content = ob_get_clean();

        return $this->get_email_template( $content, __( 'Order Confirmation', 'easy-album-orders' ) );
    }

    /**
     * Get new order alert email template (for admin/photographer).
     *
     * @since 1.0.0
     *
     * @param array  $orders         Orders data.
     * @param float  $total          Total price.
     * @param string $customer_name  Customer name.
     * @param string $customer_email Customer email.
     * @param string $customer_phone Customer phone.
     * @param string $album_title    Album title.
     * @param string $admin_url      Admin URL to view orders.
     * @return string HTML email.
     */
    private function get_new_order_alert_template( $orders, $total, $customer_name, $customer_email, $customer_phone, $album_title, $admin_url ) {
        $accent_color = $this->get_setting( 'accent_color', '#e67e22' );

        ob_start();
        ?>
        <h2 style="margin: 0 0 20px; color: #2c3e50; font-size: 24px;">🎉 New Album Order!</h2>
        <p style="margin: 0 0 30px; color: #495057; font-size: 16px; line-height: 1.6;">
            You've received a new order from <strong><?php echo esc_html( $customer_name ); ?></strong> for <strong><?php echo esc_html( $album_title ); ?></strong>.
        </p>

        <!-- Customer Info -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #e8f4fd; border-radius: 8px; margin-bottom: 20px;">
            <tr>
                <td style="padding: 20px;">
                    <h3 style="margin: 0 0 15px; color: #2c3e50; font-size: 16px;">Customer Information</h3>
                    <p style="margin: 0 0 5px; color: #2c3e50; font-size: 14px;"><strong>Name:</strong> <?php echo esc_html( $customer_name ); ?></p>
                    <p style="margin: 0 0 5px; color: #2c3e50; font-size: 14px;"><strong>Email:</strong> <a href="mailto:<?php echo esc_attr( $customer_email ); ?>" style="color: <?php echo esc_attr( $accent_color ); ?>;"><?php echo esc_html( $customer_email ); ?></a></p>
                    <?php if ( $customer_phone ) : ?>
                        <p style="margin: 0; color: #2c3e50; font-size: 14px;"><strong>Phone:</strong> <?php echo esc_html( $customer_phone ); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <!-- Order Items -->
        <h3 style="margin: 0 0 15px; color: #2c3e50; font-size: 16px;">Order Details (<?php echo count( $orders ); ?> <?php echo count( $orders ) === 1 ? 'album' : 'albums'; ?>)</h3>
        <?php foreach ( $orders as $order ) : ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
                <tr>
                    <td style="padding: 15px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="vertical-align: top;">
                                    <p style="margin: 0 0 10px; color: #2c3e50; font-size: 16px; font-weight: 600;"><?php echo esc_html( $order['album_name'] ); ?></p>
                                    <p style="margin: 0; color: #6c757d; font-size: 13px; line-height: 1.6;">
                                        <?php echo esc_html( $order['design_name'] ); ?> • 
                                        <?php echo esc_html( $order['material_name'] ); ?>
                                        <?php if ( $order['material_color'] ) : ?>(<?php echo esc_html( $order['material_color'] ); ?>)<?php endif; ?> • 
                                        <?php echo esc_html( $order['size_name'] ); ?>
                                        <?php if ( $order['engraving_text'] ) : ?><br>Engraving: "<?php echo esc_html( $order['engraving_text'] ); ?>"<?php endif; ?>
                                    </p>
                                    <p style="margin: 10px 0 0; color: #6c757d; font-size: 12px;">
                                        Ship to: <?php echo esc_html( $order['shipping_name'] ); ?>, <?php echo esc_html( $order['shipping_city'] . ', ' . $order['shipping_state'] ); ?>
                                    </p>
                                </td>
                                <td style="vertical-align: top; text-align: right; width: 100px;">
                                    <p style="margin: 0; color: <?php echo esc_attr( $accent_color ); ?>; font-size: 16px; font-weight: 600;"><?php echo esc_html( eao_format_price( $order['total'] ) ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        <?php endforeach; ?>

        <!-- Order Total -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #2c3e50; border-radius: 8px; margin-bottom: 30px;">
            <tr>
                <td style="padding: 20px;">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="color: #ffffff; font-size: 18px;">Order Total:</td>
                            <td style="color: #ffffff; font-size: 24px; font-weight: 700; text-align: right;"><?php echo esc_html( eao_format_price( $total ) ); ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- View Order Button -->
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">
                    <a href="<?php echo esc_url( $admin_url ); ?>" style="display: inline-block; padding: 14px 30px; background-color: <?php echo esc_attr( $accent_color ); ?>; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: 600;">View Order in Admin</a>
                </td>
            </tr>
        </table>
        <?php
        $content = ob_get_clean();

        return $this->get_email_template( $content, __( 'New Order Alert', 'easy-album-orders' ) );
    }

    /**
     * Get shipped notification email template.
     *
     * @since 1.0.0
     *
     * @param array  $order            Order data.
     * @param string $customer_name    Customer name.
     * @param string $tracking_number  Tracking number.
     * @param string $tracking_carrier Tracking carrier.
     * @param string $tracking_url     Tracking URL.
     * @return string HTML email.
     */
    private function get_shipped_notification_template( $order, $customer_name, $tracking_number, $tracking_carrier, $tracking_url ) {
        $accent_color = $this->get_setting( 'accent_color', '#e67e22' );

        ob_start();
        ?>
        <h2 style="margin: 0 0 20px; color: #2c3e50; font-size: 24px;">📦 Your Album Has Shipped!</h2>
        <p style="margin: 0 0 20px; color: #495057; font-size: 16px; line-height: 1.6;">
            Hi <?php echo esc_html( $customer_name ); ?>,
        </p>
        <p style="margin: 0 0 30px; color: #495057; font-size: 16px; line-height: 1.6;">
            Great news! Your album <strong>"<?php echo esc_html( $order['album_name'] ); ?>"</strong> has been shipped and is on its way to you!
        </p>

        <!-- Order Details -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
            <tr>
                <td style="padding: 20px;">
                    <h3 style="margin: 0 0 15px; color: #2c3e50; font-size: 18px;"><?php echo esc_html( $order['album_name'] ); ?></h3>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding: 5px 0; color: #6c757d; font-size: 14px;">Design:</td>
                            <td style="padding: 5px 0; color: #2c3e50; font-size: 14px; text-align: right;"><?php echo esc_html( $order['design_name'] ); ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 5px 0; color: #6c757d; font-size: 14px;">Material:</td>
                            <td style="padding: 5px 0; color: #2c3e50; font-size: 14px; text-align: right;">
                                <?php echo esc_html( $order['material_name'] ); ?>
                                <?php if ( $order['material_color'] ) : ?>
                                    (<?php echo esc_html( $order['material_color'] ); ?>)
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 5px 0; color: #6c757d; font-size: 14px;">Size:</td>
                            <td style="padding: 5px 0; color: #2c3e50; font-size: 14px; text-align: right;"><?php echo esc_html( $order['size_name'] ); ?></td>
                        </tr>
                        <?php if ( $order['engraving_text'] ) : ?>
                            <tr>
                                <td style="padding: 5px 0; color: #6c757d; font-size: 14px;">Engraving:</td>
                                <td style="padding: 5px 0; color: #2c3e50; font-size: 14px; text-align: right;">"<?php echo esc_html( $order['engraving_text'] ); ?>"</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Tracking Info -->
        <?php if ( $tracking_number || $tracking_url ) : ?>
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #e8f4fd; border-radius: 8px; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 20px;">
                        <h3 style="margin: 0 0 15px; color: #2c3e50; font-size: 16px;">Tracking Information</h3>
                        <?php if ( $tracking_carrier ) : ?>
                            <p style="margin: 0 0 5px; color: #2c3e50; font-size: 14px;"><strong>Carrier:</strong> <?php echo esc_html( $tracking_carrier ); ?></p>
                        <?php endif; ?>
                        <?php if ( $tracking_number ) : ?>
                            <p style="margin: 0 0 10px; color: #2c3e50; font-size: 14px;"><strong>Tracking Number:</strong> <?php echo esc_html( $tracking_number ); ?></p>
                        <?php endif; ?>
                        <?php if ( $tracking_url ) : ?>
                            <a href="<?php echo esc_url( $tracking_url ); ?>" style="display: inline-block; padding: 10px 20px; background-color: <?php echo esc_attr( $accent_color ); ?>; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600;">Track Your Package</a>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <!-- Shipping Address -->
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
            <tr>
                <td style="padding: 20px;">
                    <h3 style="margin: 0 0 10px; color: #2c3e50; font-size: 16px;">Shipping To:</h3>
                    <p style="margin: 0; color: #2c3e50; font-size: 14px; line-height: 1.5;">
                        <?php echo esc_html( $order['shipping_name'] ); ?><br>
                        <?php echo esc_html( $order['shipping_address1'] ); ?>
                        <?php if ( $order['shipping_address2'] ) : ?>
                            <br><?php echo esc_html( $order['shipping_address2'] ); ?>
                        <?php endif; ?>
                        <br><?php echo esc_html( $order['shipping_city'] . ', ' . $order['shipping_state'] . ' ' . $order['shipping_zip'] ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <p style="margin: 0; color: #495057; font-size: 16px; line-height: 1.6;">
            We hope you love your new album! If you have any questions, please don't hesitate to reach out.
        </p>
        <?php
        $content = ob_get_clean();

        return $this->get_email_template( $content, __( 'Your Album Has Shipped', 'easy-album-orders' ) );
    }
}

