<?php
/**
 * Admin list table columns.
 *
 * Handles custom columns for Client Album and Album Order list tables.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin columns class.
 *
 * @since 1.0.0
 */
class EAO_Admin_Columns {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Client Album columns.
        add_filter( 'manage_client_album_posts_columns', array( $this, 'client_album_columns' ) );
        add_action( 'manage_client_album_posts_custom_column', array( $this, 'client_album_column_content' ), 10, 2 );
        add_filter( 'manage_edit-client_album_sortable_columns', array( $this, 'client_album_sortable_columns' ) );

        // Album Order columns.
        add_filter( 'manage_album_order_posts_columns', array( $this, 'album_order_columns' ) );
        add_action( 'manage_album_order_posts_custom_column', array( $this, 'album_order_column_content' ), 10, 2 );
        add_filter( 'manage_edit-album_order_sortable_columns', array( $this, 'album_order_sortable_columns' ) );

        // Album Order status views (tabs).
        add_filter( 'views_edit-album_order', array( $this, 'album_order_views' ) );

        // Album Order row actions.
        add_filter( 'post_row_actions', array( $this, 'album_order_row_actions' ), 10, 2 );

        // Album Order filters.
        add_action( 'restrict_manage_posts', array( $this, 'album_order_filters' ) );
        add_filter( 'parse_query', array( $this, 'filter_album_orders' ) );

        // Sorting.
        add_action( 'pre_get_posts', array( $this, 'sort_columns' ) );

        // Add body class for our styling.
        add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );

        // Default hidden columns.
        add_filter( 'default_hidden_columns', array( $this, 'album_order_default_hidden_columns' ), 10, 2 );
    }

    /**
     * Add body class for album order pages.
     *
     * @since 1.0.0
     *
     * @param string $classes Existing body classes.
     * @return string Modified body classes.
     */
    public function admin_body_class( $classes ) {
        global $post_type;

        if ( 'album_order' === $post_type ) {
            $classes .= ' eao-album-orders-page';
        }

        if ( 'client_album' === $post_type ) {
            $classes .= ' eao-client-albums-page';
        }

        return $classes;
    }

    /**
     * Set default hidden columns for Album Orders list table.
     *
     * @since 1.0.0
     *
     * @param array     $hidden Array of hidden columns.
     * @param WP_Screen $screen The current screen object.
     * @return array Modified hidden columns.
     */
    public function album_order_default_hidden_columns( $hidden, $screen ) {
        if ( 'edit-album_order' === $screen->id ) {
            $hidden[] = 'date';
        }

        return $hidden;
    }

    /**
     * Define Client Album columns.
     *
     * @since 1.0.0
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function client_album_columns( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $value ) {
            if ( 'title' === $key ) {
                $new_columns[ $key ] = $value;
                $new_columns['client_name'] = __( 'Client', 'easy-album-orders' );
                $new_columns['designs']     = __( 'Designs', 'easy-album-orders' );
                $new_columns['credits']     = __( 'Credits', 'easy-album-orders' );
                $new_columns['orders']      = __( 'Orders', 'easy-album-orders' );
                $new_columns['link']        = __( 'Order Link', 'easy-album-orders' );
            } elseif ( 'date' === $key ) {
                $new_columns[ $key ] = $value;
            }
        }

        // Remove default date if we want it at the end.
        if ( ! isset( $new_columns['date'] ) && isset( $columns['date'] ) ) {
            $new_columns['date'] = $columns['date'];
        }

        return $new_columns;
    }

    /**
     * Output Client Album column content.
     *
     * @since 1.0.0
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function client_album_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'client_name':
                $name = get_post_meta( $post_id, '_eao_client_name', true );
                $email = get_post_meta( $post_id, '_eao_client_email', true );
                echo esc_html( $name ? $name : '—' );
                if ( $email ) {
                    echo '<br><small style="color: #666;">' . esc_html( $email ) . '</small>';
                }
                break;

            case 'designs':
                $designs = get_post_meta( $post_id, '_eao_designs', true );
                $count = is_array( $designs ) ? count( $designs ) : 0;
                echo esc_html( $count );
                break;

            case 'credits':
                $credits = get_post_meta( $post_id, '_eao_album_credits', true );
                if ( '' !== $credits && $credits > 0 ) {
                    echo '<span style="color: #00a32a;">' . esc_html( eao_format_price( $credits ) ) . '</span>';
                } else {
                    echo '—';
                }
                break;

            case 'orders':
                $orders = EAO_Album_Order::get_by_client_album( $post_id );
                $count = count( $orders );
                if ( $count > 0 ) {
                    $url = add_query_arg( array(
                        'post_type'       => 'album_order',
                        'client_album_id' => $post_id,
                    ), admin_url( 'edit.php' ) );
                    echo '<a href="' . esc_url( $url ) . '">' . esc_html( $count ) . '</a>';
                } else {
                    echo '0';
                }
                break;

            case 'link':
                if ( 'publish' === get_post_status( $post_id ) ) {
                    $permalink = get_permalink( $post_id );
                    echo '<a href="' . esc_url( $permalink ) . '" target="_blank" class="button button-small">' . esc_html__( 'View', 'easy-album-orders' ) . '</a>';
                } else {
                    echo '<span class="description">' . esc_html__( 'Not published', 'easy-album-orders' ) . '</span>';
                }
                break;
        }
    }

    /**
     * Define sortable Client Album columns.
     *
     * @since 1.0.0
     *
     * @param array $columns Existing sortable columns.
     * @return array Modified sortable columns.
     */
    public function client_album_sortable_columns( $columns ) {
        $columns['client_name'] = 'client_name';
        $columns['credits']     = 'credits';
        return $columns;
    }

    /**
     * Define Album Order columns.
     *
     * @since 1.0.0
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function album_order_columns( $columns ) {
        $new_columns = array(
            'cb'            => $columns['cb'],
            'order_number'  => __( 'Order', 'easy-album-orders' ),
            'client_album'  => __( 'Client Album', 'easy-album-orders' ),
            'customer'      => __( 'Customer', 'easy-album-orders' ),
            'album_name'    => __( 'Album', 'easy-album-orders' ),
            'configuration' => __( 'Configuration', 'easy-album-orders' ),
            'total'         => __( 'Total', 'easy-album-orders' ),
            'payment'       => __( 'Payment', 'easy-album-orders' ),
            'order_status'  => __( 'Status', 'easy-album-orders' ),
            'date'          => __( 'Date', 'easy-album-orders' ),
            'actions'       => __( 'Actions', 'easy-album-orders' ),
        );

        return $new_columns;
    }

    /**
     * Output Album Order column content.
     *
     * @since 1.0.0
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function album_order_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'order_number':
                $order_number = EAO_Helpers::generate_order_number( $post_id );
                $edit_link    = get_edit_post_link( $post_id );
                $status       = EAO_Album_Order::get_order_status( $post_id );
                
                echo '<a href="' . esc_url( $edit_link ) . '" class="eao-order-link">';
                echo '<strong class="eao-order-number">' . esc_html( $order_number ) . '</strong>';
                echo '</a>';
                
                // Show order date for ordered/shipped.
                if ( in_array( $status, array( 'ordered', 'shipped' ), true ) ) {
                    $order_date = get_post_meta( $post_id, '_eao_order_date', true );
                    if ( $order_date ) {
                        echo '<span class="eao-order-date">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order_date ) ) ) . '</span>';
                    }
                }
                break;

            case 'album_name':
                $name        = get_post_meta( $post_id, '_eao_album_name', true );
                $design_name = get_post_meta( $post_id, '_eao_design_name', true );
                
                if ( $name ) {
                    echo '<span class="eao-album-name">' . esc_html( $name ) . '</span>';
                } else {
                    echo '<span class="eao-empty">—</span>';
                }
                
                if ( $design_name ) {
                    echo '<span class="eao-album-design">' . esc_html( $design_name ) . '</span>';
                }
                break;

            case 'client_album':
                $client_album_id = get_post_meta( $post_id, '_eao_client_album_id', true );
                if ( $client_album_id ) {
                    $client_album = get_post( $client_album_id );
                    if ( $client_album ) {
                        $edit_link   = get_edit_post_link( $client_album_id );
                        $client_name = get_post_meta( $client_album_id, '_eao_client_name', true );
                        echo '<a href="' . esc_url( $edit_link ) . '" class="eao-client-album-link">';
                        echo esc_html( $client_name ? $client_name : $client_album->post_title );
                        echo '</a>';
                    } else {
                        echo '<span class="eao-empty">—</span>';
                    }
                } else {
                    echo '<span class="eao-empty">—</span>';
                }
                break;

            case 'customer':
                $name  = get_post_meta( $post_id, '_eao_customer_name', true );
                $email = get_post_meta( $post_id, '_eao_customer_email', true );
                $phone = get_post_meta( $post_id, '_eao_customer_phone', true );
                
                if ( $name ) {
                    echo '<span class="eao-customer-name">' . esc_html( $name ) . '</span>';
                } else {
                    echo '<span class="eao-empty">—</span>';
                }
                
                if ( $email ) {
                    echo '<a href="mailto:' . esc_attr( $email ) . '" class="eao-customer-email">' . esc_html( $email ) . '</a>';
                }
                break;

            case 'configuration':
                $material = get_post_meta( $post_id, '_eao_material_name', true );
                $color    = get_post_meta( $post_id, '_eao_material_color', true );
                $size     = get_post_meta( $post_id, '_eao_size_name', true );
                $engraving_text = get_post_meta( $post_id, '_eao_engraving_text', true );

                if ( $material || $size ) {
                    echo '<span class="eao-config-line">';
                    if ( $material ) {
                        echo '<span class="eao-config-material">' . esc_html( $material );
                        if ( $color ) {
                            echo ' <span class="eao-config-color">(' . esc_html( $color ) . ')</span>';
                        }
                        echo '</span>';
                    }
                    echo '</span>';
                    
                    if ( $size ) {
                        echo '<span class="eao-config-size">' . esc_html( $size ) . '</span>';
                    }
                    
                    if ( $engraving_text ) {
                        echo '<span class="eao-config-engraving" title="' . esc_attr( $engraving_text ) . '">✦ Engraved</span>';
                    }
                } else {
                    echo '<span class="eao-empty">—</span>';
                }
                break;

            case 'total':
                $total          = EAO_Album_Order::calculate_total( $post_id );
                $applied_credits = floatval( get_post_meta( $post_id, '_eao_applied_credits', true ) );
                
                echo '<span class="eao-total-amount">' . esc_html( eao_format_price( $total ) ) . '</span>';
                
                if ( $applied_credits > 0 ) {
                    echo '<span class="eao-total-credit">-' . esc_html( eao_format_price( $applied_credits ) ) . ' credit</span>';
                }
                break;

            case 'payment':
                $payment_status = get_post_meta( $post_id, '_eao_payment_status', true );
                $payment_amount = get_post_meta( $post_id, '_eao_payment_amount', true );

                if ( 'paid' === $payment_status ) {
                    echo '<span class="eao-payment-badge eao-payment-badge--paid">';
                    echo esc_html( eao_format_price( $payment_amount ) );
                    echo '</span>';
                } elseif ( 'refunded' === $payment_status || 'partial_refund' === $payment_status ) {
                    $refund_label = 'partial_refund' === $payment_status
                        ? __( 'Partial Refund', 'easy-album-orders' )
                        : __( 'Refunded', 'easy-album-orders' );
                    echo '<span class="eao-payment-badge eao-payment-badge--refunded">';
                    echo esc_html( $refund_label );
                    echo '</span>';
                } elseif ( 'failed' === $payment_status ) {
                    echo '<span class="eao-payment-badge eao-payment-badge--failed">';
                    esc_html_e( 'Failed', 'easy-album-orders' );
                    echo '</span>';
                } elseif ( 'pending' === $payment_status ) {
                    echo '<span class="eao-payment-badge eao-payment-badge--pending">';
                    esc_html_e( 'Pending', 'easy-album-orders' );
                    echo '</span>';
                } elseif ( 'free' === $payment_status ) {
                    echo '<span class="eao-payment-badge eao-payment-badge--free">';
                    esc_html_e( 'Free', 'easy-album-orders' );
                    echo '</span>';
                } else {
                    echo '<span class="eao-payment-badge eao-payment-badge--none">—</span>';
                }
                break;

            case 'order_status':
                $status = EAO_Album_Order::get_order_status( $post_id );
                $label  = EAO_Album_Order::get_status_label( $status );
                $class  = EAO_Helpers::get_status_color_class( $status );
                echo '<span class="eao-status ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
                break;

            case 'actions':
                $edit_link  = get_edit_post_link( $post_id );
                $trash_link = get_delete_post_link( $post_id );

                echo '<div class="eao-row-actions">';
                
                // Edit/View icon.
                echo '<a href="' . esc_url( $edit_link ) . '" class="eao-row-action eao-row-action--edit" title="' . esc_attr__( 'View', 'easy-album-orders' ) . '">';
                echo '<span class="dashicons dashicons-visibility"></span>';
                echo '</a>';

                // Trash icon.
                if ( $trash_link ) {
                    echo '<a href="' . esc_url( $trash_link ) . '" class="eao-row-action eao-row-action--trash" title="' . esc_attr__( 'Trash', 'easy-album-orders' ) . '">';
                    echo '<span class="dashicons dashicons-trash"></span>';
                    echo '</a>';
                }

                echo '</div>';
                break;
        }
    }

    /**
     * Define sortable Album Order columns.
     *
     * @since 1.0.0
     *
     * @param array $columns Existing sortable columns.
     * @return array Modified sortable columns.
     */
    public function album_order_sortable_columns( $columns ) {
        $columns['order_number'] = 'ID';
        $columns['album_name']   = 'album_name';
        $columns['customer']     = 'customer';
        $columns['total']        = 'total';
        $columns['order_status'] = 'order_status';
        return $columns;
    }

    /**
     * Add status views (tabs) to Album Order list table.
     *
     * @since 1.0.0
     *
     * @param array $views Existing views.
     * @return array Modified views.
     */
    public function album_order_views( $views ) {
        global $wpdb;

        // Get counts for each status.
        $counts = array(
            'all'       => 0,
            'submitted' => 0,
            'ordered'   => 0,
            'shipped'   => 0,
        );

        // Get total count.
        $counts['all'] = wp_count_posts( 'album_order' )->publish;

        // Get status counts.
        $status_counts = $wpdb->get_results(
            "SELECT pm.meta_value as status, COUNT(*) as count 
             FROM {$wpdb->postmeta} pm 
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = '_eao_order_status' 
             AND p.post_type = 'album_order' 
             AND p.post_status = 'publish' 
             GROUP BY pm.meta_value"
        );

        if ( $status_counts ) {
            foreach ( $status_counts as $row ) {
                if ( isset( $counts[ $row->status ] ) ) {
                    $counts[ $row->status ] = intval( $row->count );
                }
            }
        }

        // Get current filter.
        $current_status = isset( $_GET['order_status'] ) ? sanitize_key( $_GET['order_status'] ) : '';
        $base_url       = admin_url( 'edit.php?post_type=album_order' );

        // Build views.
        $new_views = array();

        // All orders.
        $class = empty( $current_status ) ? 'current' : '';
        $new_views['all'] = sprintf(
            '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
            esc_url( $base_url ),
            esc_attr( $class ),
            esc_html__( 'All', 'easy-album-orders' ),
            number_format_i18n( $counts['all'] )
        );

        // Submitted (In Cart).
        $class = ( 'submitted' === $current_status ) ? 'current' : '';
        $new_views['submitted'] = sprintf(
            '<a href="%s" class="%s eao-view-submitted">%s <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'order_status', 'submitted', $base_url ) ),
            esc_attr( $class ),
            esc_html__( 'In Cart', 'easy-album-orders' ),
            number_format_i18n( $counts['submitted'] )
        );

        // Ordered.
        $class = ( 'ordered' === $current_status ) ? 'current' : '';
        $new_views['ordered'] = sprintf(
            '<a href="%s" class="%s eao-view-ordered">%s <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'order_status', 'ordered', $base_url ) ),
            esc_attr( $class ),
            esc_html__( 'Ordered', 'easy-album-orders' ),
            number_format_i18n( $counts['ordered'] )
        );

        // Shipped.
        $class = ( 'shipped' === $current_status ) ? 'current' : '';
        $new_views['shipped'] = sprintf(
            '<a href="%s" class="%s eao-view-shipped">%s <span class="count">(%s)</span></a>',
            esc_url( add_query_arg( 'order_status', 'shipped', $base_url ) ),
            esc_attr( $class ),
            esc_html__( 'Shipped', 'easy-album-orders' ),
            number_format_i18n( $counts['shipped'] )
        );

        return $new_views;
    }

    /**
     * Remove row actions for Album Orders (using Actions column instead).
     *
     * @since 1.0.0
     *
     * @param array   $actions Existing row actions.
     * @param WP_Post $post    The post object.
     * @return array Empty array to hide row actions.
     */
    public function album_order_row_actions( $actions, $post ) {
        if ( 'album_order' !== $post->post_type ) {
            return $actions;
        }

        // Return empty array - actions are shown in the Actions column instead.
        return array();
    }

    /**
     * Add filters to Album Order list table.
     *
     * @since 1.0.0
     *
     * @param string $post_type The post type.
     */
    public function album_order_filters( $post_type ) {
        if ( 'album_order' !== $post_type ) {
            return;
        }

        // Status filter.
        $statuses = EAO_Album_Order::get_statuses();
        $current_status = isset( $_GET['order_status'] ) ? sanitize_key( $_GET['order_status'] ) : '';

        echo '<select name="order_status">';
        echo '<option value="">' . esc_html__( 'All Statuses', 'easy-album-orders' ) . '</option>';
        foreach ( $statuses as $value => $label ) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $value ),
                selected( $current_status, $value, false ),
                esc_html( $label )
            );
        }
        echo '</select>';

        // Client Album filter.
        $client_albums = EAO_Client_Album::get_all();
        $current_album = isset( $_GET['client_album_id'] ) ? absint( $_GET['client_album_id'] ) : '';

        echo '<select name="client_album_id">';
        echo '<option value="">' . esc_html__( 'All Client Albums', 'easy-album-orders' ) . '</option>';
        foreach ( $client_albums as $album ) {
            printf(
                '<option value="%d" %s>%s</option>',
                esc_attr( $album->ID ),
                selected( $current_album, $album->ID, false ),
                esc_html( $album->post_title )
            );
        }
        echo '</select>';
    }

    /**
     * Filter Album Orders based on dropdown selection.
     *
     * @since 1.0.0
     *
     * @param WP_Query $query The query object.
     */
    public function filter_album_orders( $query ) {
        global $pagenow;

        if ( ! is_admin() || 'edit.php' !== $pagenow || 'album_order' !== $query->get( 'post_type' ) ) {
            return;
        }

        $meta_query = $query->get( 'meta_query' );
        if ( ! is_array( $meta_query ) ) {
            $meta_query = array();
        }

        // Filter by status.
        if ( ! empty( $_GET['order_status'] ) ) {
            $meta_query[] = array(
                'key'   => '_eao_order_status',
                'value' => sanitize_key( $_GET['order_status'] ),
            );
        }

        // Filter by Client Album.
        if ( ! empty( $_GET['client_album_id'] ) ) {
            $meta_query[] = array(
                'key'   => '_eao_client_album_id',
                'value' => absint( $_GET['client_album_id'] ),
            );
        }

        if ( ! empty( $meta_query ) ) {
            $query->set( 'meta_query', $meta_query );
        }
    }

    /**
     * Handle sorting for custom columns.
     *
     * @since 1.0.0
     *
     * @param WP_Query $query The query object.
     */
    public function sort_columns( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        // Client Album sorting.
        if ( 'client_album' === $query->get( 'post_type' ) ) {
            switch ( $orderby ) {
                case 'client_name':
                    $query->set( 'meta_key', '_eao_client_name' );
                    $query->set( 'orderby', 'meta_value' );
                    break;
                case 'credits':
                    $query->set( 'meta_key', '_eao_album_credits' );
                    $query->set( 'orderby', 'meta_value_num' );
                    break;
            }
        }

        // Album Order sorting.
        if ( 'album_order' === $query->get( 'post_type' ) ) {
            switch ( $orderby ) {
                case 'album_name':
                    $query->set( 'meta_key', '_eao_album_name' );
                    $query->set( 'orderby', 'meta_value' );
                    break;
                case 'customer':
                    $query->set( 'meta_key', '_eao_customer_name' );
                    $query->set( 'orderby', 'meta_value' );
                    break;
                case 'order_status':
                    $query->set( 'meta_key', '_eao_order_status' );
                    $query->set( 'orderby', 'meta_value' );
                    break;
            }
        }
    }
}

