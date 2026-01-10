<?php
/**
 * Admin menu registration.
 *
 * Handles the registration and display of admin menus
 * for the Easy Album Orders plugin.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin menus class.
 *
 * @since 1.0.0
 */
class EAO_Admin_Menus {

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
     * Register all admin menus.
     *
     * @since 1.0.0
     */
    public function register_menus() {
        // Client Albums parent menu.
        add_menu_page(
            __( 'Client Albums', 'easy-album-orders' ),
            __( 'Client Albums', 'easy-album-orders' ),
            'manage_options',
            'edit.php?post_type=client_album',
            '',
            'dashicons-format-gallery',
            26
        );

        // Album Options submenu.
        add_submenu_page(
            'edit.php?post_type=client_album',
            __( 'Album Options', 'easy-album-orders' ),
            __( 'Album Options', 'easy-album-orders' ),
            'manage_options',
            'eao-album-options',
            array( $this, 'render_album_options_page' )
        );

        // Album Orders menu (standalone).
        add_menu_page(
            __( 'Album Orders', 'easy-album-orders' ),
            __( 'Album Orders', 'easy-album-orders' ),
            'manage_options',
            'edit.php?post_type=album_order',
            '',
            'dashicons-cart',
            27
        );

        // Reports submenu under Album Orders.
        add_submenu_page(
            'edit.php?post_type=album_order',
            __( 'Reports', 'easy-album-orders' ),
            __( 'Reports', 'easy-album-orders' ),
            'manage_options',
            'eao-reports',
            array( $this, 'render_reports_page' )
        );
    }

    /**
     * Render the Reports page.
     *
     * @since 1.0.0
     */
    public function render_reports_page() {
        // Check user capabilities.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Get date range from query params.
        $range = isset( $_GET['range'] ) ? sanitize_key( $_GET['range'] ) : '30days';
        
        // Calculate date boundaries based on range.
        $date_boundaries = $this->get_date_boundaries( $range );
        $start_date      = $date_boundaries['start'];
        $end_date        = $date_boundaries['end'];
        $range_label     = $date_boundaries['label'];

        // Get report data.
        $report_data = $this->get_report_data( $start_date, $end_date );

        // Include the view.
        include EAO_PLUGIN_DIR . 'includes/admin/views/reports-page.php';
    }

    /**
     * Get date boundaries for a given range.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $range The date range identifier.
     * @return array Array with start, end dates and label.
     */
    private function get_date_boundaries( $range ) {
        $today = current_time( 'Y-m-d' );
        
        switch ( $range ) {
            case 'today':
                return array(
                    'start' => $today,
                    'end'   => $today,
                    'label' => __( 'Today', 'easy-album-orders' ),
                );

            case '7days':
                return array(
                    'start' => date( 'Y-m-d', strtotime( '-6 days', strtotime( $today ) ) ),
                    'end'   => $today,
                    'label' => __( 'Last 7 Days', 'easy-album-orders' ),
                );

            case '30days':
                return array(
                    'start' => date( 'Y-m-d', strtotime( '-29 days', strtotime( $today ) ) ),
                    'end'   => $today,
                    'label' => __( 'Last 30 Days', 'easy-album-orders' ),
                );

            case 'this_month':
                return array(
                    'start' => date( 'Y-m-01', strtotime( $today ) ),
                    'end'   => $today,
                    'label' => __( 'This Month', 'easy-album-orders' ),
                );

            case 'last_month':
                $first_of_last = date( 'Y-m-01', strtotime( 'first day of last month' ) );
                $last_of_last  = date( 'Y-m-t', strtotime( 'last day of last month' ) );
                return array(
                    'start' => $first_of_last,
                    'end'   => $last_of_last,
                    'label' => __( 'Last Month', 'easy-album-orders' ),
                );

            case 'this_quarter':
                $month   = date( 'n', strtotime( $today ) );
                $quarter = ceil( $month / 3 );
                $start_month = ( ( $quarter - 1 ) * 3 ) + 1;
                return array(
                    'start' => date( 'Y-m-01', strtotime( date( 'Y' ) . '-' . $start_month . '-01' ) ),
                    'end'   => $today,
                    'label' => __( 'This Quarter', 'easy-album-orders' ),
                );

            case 'this_year':
                return array(
                    'start' => date( 'Y-01-01', strtotime( $today ) ),
                    'end'   => $today,
                    'label' => __( 'This Year', 'easy-album-orders' ),
                );

            case 'all_time':
                return array(
                    'start' => '2000-01-01',
                    'end'   => $today,
                    'label' => __( 'All Time', 'easy-album-orders' ),
                );

            default:
                return array(
                    'start' => date( 'Y-m-d', strtotime( '-29 days', strtotime( $today ) ) ),
                    'end'   => $today,
                    'label' => __( 'Last 30 Days', 'easy-album-orders' ),
                );
        }
    }

    /**
     * Get report data for the given date range.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $start_date Start date (Y-m-d).
     * @param string $end_date   End date (Y-m-d).
     * @return array Report data.
     */
    private function get_report_data( $start_date, $end_date ) {
        global $wpdb;

        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime   = $end_date . ' 23:59:59';

        // Get orders in range (only ordered/shipped - not submitted/cart items).
        $order_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_eao_order_status'
                WHERE p.post_type = 'album_order'
                AND p.post_status = 'publish'
                AND pm.meta_value IN ('ordered', 'shipped')
                AND p.post_date >= %s
                AND p.post_date <= %s",
                $start_datetime,
                $end_datetime
            )
        );

        // Calculate totals.
        $total_revenue = 0;
        $total_orders  = count( $order_ids );

        foreach ( $order_ids as $order_id ) {
            $total_revenue += EAO_Album_Order::calculate_total( $order_id );
        }

        $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

        // Orders by status (all time for status breakdown).
        $submitted_count = count( EAO_Album_Order::get_by_status( EAO_Album_Order::STATUS_SUBMITTED ) );
        $ordered_count   = count( EAO_Album_Order::get_by_status( EAO_Album_Order::STATUS_ORDERED ) );
        $shipped_count   = count( EAO_Album_Order::get_by_status( EAO_Album_Order::STATUS_SHIPPED ) );

        // Revenue by day for chart.
        $revenue_by_day = $this->get_revenue_by_day( $start_date, $end_date );

        // Top materials.
        $top_materials = $this->get_top_materials( $start_date, $end_date );

        // Top sizes.
        $top_sizes = $this->get_top_sizes( $start_date, $end_date );

        // Monthly comparison (current vs previous).
        $monthly_comparison = $this->get_monthly_comparison();

        return array(
            'total_revenue'      => $total_revenue,
            'total_orders'       => $total_orders,
            'avg_order_value'    => $avg_order_value,
            'submitted_count'    => $submitted_count,
            'ordered_count'      => $ordered_count,
            'shipped_count'      => $shipped_count,
            'revenue_by_day'     => $revenue_by_day,
            'top_materials'      => $top_materials,
            'top_sizes'          => $top_sizes,
            'monthly_comparison' => $monthly_comparison,
        );
    }

    /**
     * Get revenue by day for chart.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @return array Revenue by day.
     */
    private function get_revenue_by_day( $start_date, $end_date ) {
        global $wpdb;

        $data = array();
        $current = strtotime( $start_date );
        $end     = strtotime( $end_date );

        while ( $current <= $end ) {
            $day = date( 'Y-m-d', $current );
            $data[ $day ] = 0;
            $current = strtotime( '+1 day', $current );
        }

        // Get orders with their dates.
        $orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, DATE(p.post_date) as order_date FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_eao_order_status'
                WHERE p.post_type = 'album_order'
                AND p.post_status = 'publish'
                AND pm.meta_value IN ('ordered', 'shipped')
                AND p.post_date >= %s
                AND p.post_date <= %s",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            )
        );

        foreach ( $orders as $order ) {
            if ( isset( $data[ $order->order_date ] ) ) {
                $data[ $order->order_date ] += EAO_Album_Order::calculate_total( $order->ID );
            }
        }

        return $data;
    }

    /**
     * Get top materials by order count.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @return array Top materials.
     */
    private function get_top_materials( $start_date, $end_date ) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm2.meta_value as material, COUNT(*) as count
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_eao_order_status'
                INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_eao_material_name'
                WHERE p.post_type = 'album_order'
                AND p.post_status = 'publish'
                AND pm.meta_value IN ('ordered', 'shipped')
                AND p.post_date >= %s
                AND p.post_date <= %s
                AND pm2.meta_value != ''
                GROUP BY pm2.meta_value
                ORDER BY count DESC
                LIMIT 5",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );

        return $results ? $results : array();
    }

    /**
     * Get top sizes by order count.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @return array Top sizes.
     */
    private function get_top_sizes( $start_date, $end_date ) {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT pm2.meta_value as size, COUNT(*) as count
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_eao_order_status'
                INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_eao_size_name'
                WHERE p.post_type = 'album_order'
                AND p.post_status = 'publish'
                AND pm.meta_value IN ('ordered', 'shipped')
                AND p.post_date >= %s
                AND p.post_date <= %s
                AND pm2.meta_value != ''
                GROUP BY pm2.meta_value
                ORDER BY count DESC
                LIMIT 5",
                $start_date . ' 00:00:00',
                $end_date . ' 23:59:59'
            ),
            ARRAY_A
        );

        return $results ? $results : array();
    }

    /**
     * Get monthly comparison data.
     *
     * @since  1.0.0
     * @access private
     *
     * @return array Monthly comparison.
     */
    private function get_monthly_comparison() {
        global $wpdb;

        $data = array();

        // Get last 6 months.
        for ( $i = 5; $i >= 0; $i-- ) {
            $month_start = date( 'Y-m-01', strtotime( "-{$i} months" ) );
            $month_end   = date( 'Y-m-t', strtotime( "-{$i} months" ) );
            $month_label = date_i18n( 'M', strtotime( $month_start ) );

            $order_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_eao_order_status'
                    WHERE p.post_type = 'album_order'
                    AND p.post_status = 'publish'
                    AND pm.meta_value IN ('ordered', 'shipped')
                    AND p.post_date >= %s
                    AND p.post_date <= %s",
                    $month_start . ' 00:00:00',
                    $month_end . ' 23:59:59'
                )
            );

            $revenue = 0;
            foreach ( $order_ids as $order_id ) {
                $revenue += EAO_Album_Order::calculate_total( $order_id );
            }

            $data[] = array(
                'month'   => $month_label,
                'revenue' => $revenue,
                'orders'  => count( $order_ids ),
            );
        }

        return $data;
    }

    /**
     * Render the Album Options settings page.
     *
     * @since 1.0.0
     */
    public function render_album_options_page() {
        // Check user capabilities.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle form submission.
        if ( isset( $_POST['eao_save_options'] ) && check_admin_referer( 'eao_album_options_nonce', 'eao_nonce' ) ) {
            $this->save_album_options();
        }

        // Get current options.
        $materials         = get_option( 'eao_materials', array() );
        $sizes             = get_option( 'eao_sizes', array() );
        $engraving_options = get_option( 'eao_engraving_options', array() );
        $general_settings  = get_option( 'eao_general_settings', array() );

        // Include the view.
        include EAO_PLUGIN_DIR . 'includes/admin/views/album-options-page.php';
    }

    /**
     * Save album options from the settings form.
     *
     * @since  1.0.0
     * @access private
     */
    private function save_album_options() {
        // Sanitize and save materials.
        if ( isset( $_POST['eao_materials'] ) && is_array( $_POST['eao_materials'] ) ) {
            $materials = $this->sanitize_materials( $_POST['eao_materials'] );
            update_option( 'eao_materials', $materials );
        } else {
            update_option( 'eao_materials', array() );
        }

        // Sanitize and save sizes.
        if ( isset( $_POST['eao_sizes'] ) && is_array( $_POST['eao_sizes'] ) ) {
            $sizes = $this->sanitize_sizes( $_POST['eao_sizes'] );
            update_option( 'eao_sizes', $sizes );
        } else {
            update_option( 'eao_sizes', array() );
        }

        // Sanitize and save engraving options.
        if ( isset( $_POST['eao_engraving_options'] ) && is_array( $_POST['eao_engraving_options'] ) ) {
            $engraving = $this->sanitize_engraving_options( $_POST['eao_engraving_options'] );
            update_option( 'eao_engraving_options', $engraving );
        } else {
            update_option( 'eao_engraving_options', array() );
        }

        // Sanitize and save general settings.
        if ( isset( $_POST['eao_general_settings'] ) && is_array( $_POST['eao_general_settings'] ) ) {
            $general = $this->sanitize_general_settings( $_POST['eao_general_settings'] );
            update_option( 'eao_general_settings', $general );
        }

        // Sanitize and save email settings.
        if ( isset( $_POST['eao_email_settings'] ) && is_array( $_POST['eao_email_settings'] ) ) {
            $email = $this->sanitize_email_settings( $_POST['eao_email_settings'] );
            update_option( 'eao_email_settings', $email );
        }

        // Sanitize and save Stripe settings.
        if ( isset( $_POST['eao_stripe_settings'] ) && is_array( $_POST['eao_stripe_settings'] ) ) {
            $stripe = $this->sanitize_stripe_settings( $_POST['eao_stripe_settings'] );
            update_option( 'eao_stripe_settings', $stripe );
        }

        // Add success message.
        add_settings_error(
            'eao_messages',
            'eao_message',
            __( 'Settings saved successfully.', 'easy-album-orders' ),
            'updated'
        );
    }

    /**
     * Sanitize materials array.
     *
     * @since  1.0.0
     * @access private
     *
     * @param array $materials Raw materials data.
     * @return array Sanitized materials data.
     */
    private function sanitize_materials( $materials ) {
        $sanitized = array();

        foreach ( $materials as $material ) {
            if ( empty( $material['name'] ) ) {
                continue;
            }

            $sanitized_material = array(
                'id'              => isset( $material['id'] ) ? sanitize_key( $material['id'] ) : wp_generate_uuid4(),
                'name'            => sanitize_text_field( $material['name'] ),
                'image_id'        => isset( $material['image_id'] ) ? absint( $material['image_id'] ) : 0,
                'upcharge'        => isset( $material['upcharge'] ) ? floatval( $material['upcharge'] ) : 0,
                'allow_engraving' => isset( $material['allow_engraving'] ) ? true : false,
                'restricted_sizes'=> isset( $material['restricted_sizes'] ) && is_array( $material['restricted_sizes'] )
                    ? array_map( 'sanitize_key', $material['restricted_sizes'] )
                    : array(),
                'colors'          => array(),
            );

            // Sanitize colors.
            if ( isset( $material['colors'] ) && is_array( $material['colors'] ) ) {
                foreach ( $material['colors'] as $color ) {
                    if ( empty( $color['name'] ) ) {
                        continue;
                    }

                    // Sanitize texture_region as JSON - strip slashes and validate.
                    $texture_region = '';
                    if ( ! empty( $color['texture_region'] ) ) {
                        // Remove any accumulated slashes (can have many layers from repeated saves).
                        $region_value = $color['texture_region'];
                        
                        // Keep stripping slashes until we get valid JSON or no more changes.
                        // Use a high limit to handle deeply corrupted data.
                        for ( $attempt = 0; $attempt < 100; $attempt++ ) {
                            $decoded = json_decode( $region_value, true );
                            if ( $decoded !== null && isset( $decoded['x'] ) && isset( $decoded['y'] ) && isset( $decoded['zoom'] ) ) {
                                // Valid JSON with expected structure - re-encode cleanly.
                                $texture_region = wp_json_encode( array(
                                    'x'    => sanitize_text_field( $decoded['x'] ),
                                    'y'    => sanitize_text_field( $decoded['y'] ),
                                    'zoom' => sanitize_text_field( $decoded['zoom'] ),
                                ) );
                                break;
                            }
                            // Try stripping more slashes.
                            $new_value = stripslashes( $region_value );
                            if ( $new_value === $region_value ) {
                                // No more slashes to strip - data is unrecoverable.
                                break;
                            }
                            $region_value = $new_value;
                        }
                    }

                    $sanitized_material['colors'][] = array(
                        'id'               => isset( $color['id'] ) ? sanitize_key( $color['id'] ) : wp_generate_uuid4(),
                        'name'             => sanitize_text_field( $color['name'] ),
                        'type'             => isset( $color['type'] ) && in_array( $color['type'], array( 'solid', 'texture' ), true )
                            ? $color['type']
                            : 'solid',
                        'color_value'      => isset( $color['color_value'] ) ? sanitize_hex_color( $color['color_value'] ) : '#000000',
                        'texture_image_id' => isset( $color['texture_image_id'] ) ? absint( $color['texture_image_id'] ) : 0,
                        'texture_region'   => $texture_region,
                        'preview_image_id' => isset( $color['preview_image_id'] ) ? absint( $color['preview_image_id'] ) : 0,
                    );
                }
            }

            $sanitized[] = $sanitized_material;
        }

        return $sanitized;
    }

    /**
     * Sanitize sizes array.
     *
     * @since  1.0.0
     * @access private
     *
     * @param array $sizes Raw sizes data.
     * @return array Sanitized sizes data.
     */
    private function sanitize_sizes( $sizes ) {
        $sanitized = array();

        foreach ( $sizes as $size ) {
            if ( empty( $size['name'] ) ) {
                continue;
            }

            $sanitized[] = array(
                'id'         => isset( $size['id'] ) ? sanitize_key( $size['id'] ) : wp_generate_uuid4(),
                'name'       => sanitize_text_field( $size['name'] ),
                'dimensions' => isset( $size['dimensions'] ) ? sanitize_text_field( $size['dimensions'] ) : '',
                'upcharge'   => isset( $size['upcharge'] ) ? floatval( $size['upcharge'] ) : 0,
                'image_id'   => isset( $size['image_id'] ) ? absint( $size['image_id'] ) : 0,
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize engraving options array.
     *
     * @since  1.0.0
     * @access private
     *
     * @param array $options Raw engraving options data.
     * @return array Sanitized engraving options data.
     */
    private function sanitize_engraving_options( $options ) {
        $sanitized = array();

        foreach ( $options as $option ) {
            if ( empty( $option['name'] ) ) {
                continue;
            }

            $sanitized[] = array(
                'id'              => isset( $option['id'] ) ? sanitize_key( $option['id'] ) : wp_generate_uuid4(),
                'name'            => sanitize_text_field( $option['name'] ),
                'upcharge'        => isset( $option['upcharge'] ) ? floatval( $option['upcharge'] ) : 0,
                'character_limit' => isset( $option['character_limit'] ) ? absint( $option['character_limit'] ) : 0,
                'fonts'           => isset( $option['fonts'] ) ? sanitize_textarea_field( $option['fonts'] ) : '',
                'description'     => isset( $option['description'] ) ? sanitize_textarea_field( $option['description'] ) : '',
                'image_id'        => isset( $option['image_id'] ) ? absint( $option['image_id'] ) : 0,
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize general settings array.
     *
     * @since  1.0.0
     * @access private
     *
     * @param array $settings Raw settings data.
     * @return array Sanitized settings data.
     */
    private function sanitize_general_settings( $settings ) {
        return array(
            'currency'          => isset( $settings['currency'] ) ? sanitize_text_field( $settings['currency'] ) : 'USD',
            'currency_symbol'   => isset( $settings['currency_symbol'] ) ? sanitize_text_field( $settings['currency_symbol'] ) : '$',
            'currency_position' => isset( $settings['currency_position'] ) && in_array( $settings['currency_position'], array( 'before', 'after' ), true )
                ? $settings['currency_position']
                : 'before',
            'brand_color'       => isset( $settings['brand_color'] ) ? sanitize_hex_color( $settings['brand_color'] ) : '#1a1a1b',
        );
    }

    /**
     * Sanitize email settings.
     *
     * @since  1.0.0
     * @access private
     *
     * @param array $settings Raw email settings.
     * @return array Sanitized email settings.
     */
    private function sanitize_email_settings( $settings ) {
        return array(
            // Master toggle.
            'email_notifications'           => isset( $settings['email_notifications'] ) ? true : false,
            'admin_email'                   => isset( $settings['admin_email'] ) ? sanitize_email( $settings['admin_email'] ) : get_option( 'admin_email' ),

            // Sender settings.
            'from_name'                     => isset( $settings['from_name'] ) ? sanitize_text_field( $settings['from_name'] ) : '',
            'from_email'                    => isset( $settings['from_email'] ) ? sanitize_email( $settings['from_email'] ) : '',

            // Branding.
            'logo_url'                      => isset( $settings['logo_url'] ) ? esc_url_raw( $settings['logo_url'] ) : '',
            'accent_color'                  => isset( $settings['accent_color'] ) ? sanitize_hex_color( $settings['accent_color'] ) : '#e67e22',

            // Order Confirmation.
            'enable_order_confirmation'     => isset( $settings['enable_order_confirmation'] ) ? true : false,
            'order_confirmation_subject'    => isset( $settings['order_confirmation_subject'] ) ? sanitize_text_field( $this->clean_subject_line( $settings['order_confirmation_subject'] ) ) : '',

            // New Order Alert.
            'enable_new_order_alert'        => isset( $settings['enable_new_order_alert'] ) ? true : false,
            'new_order_alert_subject'       => isset( $settings['new_order_alert_subject'] ) ? sanitize_text_field( $this->clean_subject_line( $settings['new_order_alert_subject'] ) ) : '',

            // Shipped Notification.
            'enable_shipped_notification'   => isset( $settings['enable_shipped_notification'] ) ? true : false,
            'shipped_notification_subject'  => isset( $settings['shipped_notification_subject'] ) ? sanitize_text_field( $this->clean_subject_line( $settings['shipped_notification_subject'] ) ) : '',

            // Cart Reminder.
            'enable_cart_reminder'          => isset( $settings['enable_cart_reminder'] ) ? true : false,
            'cart_reminder_days'            => isset( $settings['cart_reminder_days'] ) ? absint( $settings['cart_reminder_days'] ) : 3,
            'cart_reminder_subject'         => isset( $settings['cart_reminder_subject'] ) ? sanitize_text_field( $this->clean_subject_line( $settings['cart_reminder_subject'] ) ) : '',
        );
    }

    /**
     * Clean subject line by stripping accumulated backslashes.
     *
     * Fixes a bug where backslashes could accumulate before apostrophes
     * and quotes through repeated form saves.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $subject Raw subject line value.
     * @return string Cleaned subject line.
     */
    private function clean_subject_line( $subject ) {
        // Strip any accumulated backslashes (can be deeply nested from bug).
        // Keep stripping until no more changes occur.
        $previous = '';
        $cleaned  = $subject;
        while ( $cleaned !== $previous ) {
            $previous = $cleaned;
            $cleaned  = stripslashes( $cleaned );
        }
        return $cleaned;
    }

    /**
     * Sanitize Stripe settings.
     *
     * @since  1.1.0
     * @access private
     *
     * @param array $settings Raw Stripe settings.
     * @return array Sanitized Stripe settings.
     */
    private function sanitize_stripe_settings( $settings ) {
        return array(
            // Enable/Disable.
            'enabled'              => isset( $settings['enabled'] ) ? true : false,

            // Mode (test or live).
            'mode'                 => isset( $settings['mode'] ) && in_array( $settings['mode'], array( 'test', 'live' ), true )
                ? $settings['mode']
                : 'test',

            // Test API Keys.
            'test_publishable_key' => isset( $settings['test_publishable_key'] )
                ? $this->sanitize_stripe_key( $settings['test_publishable_key'] )
                : '',
            'test_secret_key'      => isset( $settings['test_secret_key'] )
                ? $this->sanitize_stripe_key( $settings['test_secret_key'] )
                : '',

            // Live API Keys.
            'live_publishable_key' => isset( $settings['live_publishable_key'] )
                ? $this->sanitize_stripe_key( $settings['live_publishable_key'] )
                : '',
            'live_secret_key'      => isset( $settings['live_secret_key'] )
                ? $this->sanitize_stripe_key( $settings['live_secret_key'] )
                : '',

            // Webhook Secret.
            'webhook_secret'       => isset( $settings['webhook_secret'] )
                ? $this->sanitize_stripe_key( $settings['webhook_secret'] )
                : '',

            // Statement Descriptor (max 22 chars, alphanumeric + spaces/hyphens).
            'statement_descriptor' => isset( $settings['statement_descriptor'] )
                ? substr( preg_replace( '/[^a-zA-Z0-9 \-]/', '', sanitize_text_field( $settings['statement_descriptor'] ) ), 0, 22 )
                : 'Album Order',
        );
    }

    /**
     * Sanitize a Stripe API key.
     *
     * Removes whitespace and validates basic format.
     *
     * @since  1.1.0
     * @access private
     *
     * @param string $key Raw API key.
     * @return string Sanitized API key.
     */
    private function sanitize_stripe_key( $key ) {
        // Remove whitespace and sanitize.
        $key = trim( sanitize_text_field( $key ) );

        // Remove any accidental quotes or slashes.
        $key = stripslashes( $key );
        $key = str_replace( array( '"', "'" ), '', $key );

        return $key;
    }
}

