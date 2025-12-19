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

        // Album Order filters.
        add_action( 'restrict_manage_posts', array( $this, 'album_order_filters' ) );
        add_filter( 'parse_query', array( $this, 'filter_album_orders' ) );

        // Sorting.
        add_action( 'pre_get_posts', array( $this, 'sort_columns' ) );
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
            'album_name'    => __( 'Album', 'easy-album-orders' ),
            'client_album'  => __( 'Client Album', 'easy-album-orders' ),
            'customer'      => __( 'Customer', 'easy-album-orders' ),
            'configuration' => __( 'Configuration', 'easy-album-orders' ),
            'total'         => __( 'Total', 'easy-album-orders' ),
            'order_status'  => __( 'Status', 'easy-album-orders' ),
            'date'          => __( 'Date', 'easy-album-orders' ),
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
                $edit_link = get_edit_post_link( $post_id );
                echo '<a href="' . esc_url( $edit_link ) . '"><strong>' . esc_html( $order_number ) . '</strong></a>';
                break;

            case 'album_name':
                $name = get_post_meta( $post_id, '_eao_album_name', true );
                echo esc_html( $name ? $name : '—' );
                break;

            case 'client_album':
                $client_album_id = get_post_meta( $post_id, '_eao_client_album_id', true );
                if ( $client_album_id ) {
                    $client_album = get_post( $client_album_id );
                    if ( $client_album ) {
                        $edit_link = get_edit_post_link( $client_album_id );
                        echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $client_album->post_title ) . '</a>';
                    } else {
                        echo '—';
                    }
                } else {
                    echo '—';
                }
                break;

            case 'customer':
                $name  = get_post_meta( $post_id, '_eao_customer_name', true );
                $email = get_post_meta( $post_id, '_eao_customer_email', true );
                echo esc_html( $name ? $name : '—' );
                if ( $email ) {
                    echo '<br><small><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></small>';
                }
                break;

            case 'configuration':
                $material = get_post_meta( $post_id, '_eao_material_name', true );
                $color    = get_post_meta( $post_id, '_eao_material_color', true );
                $size     = get_post_meta( $post_id, '_eao_size_name', true );

                $parts = array();
                if ( $material ) {
                    $parts[] = $material . ( $color ? ' (' . $color . ')' : '' );
                }
                if ( $size ) {
                    $parts[] = $size;
                }

                echo esc_html( ! empty( $parts ) ? implode( ', ', $parts ) : '—' );
                break;

            case 'total':
                $total = EAO_Album_Order::calculate_total( $post_id );
                echo '<strong>' . esc_html( eao_format_price( $total ) ) . '</strong>';
                break;

            case 'order_status':
                $status = EAO_Album_Order::get_order_status( $post_id );
                $label  = EAO_Album_Order::get_status_label( $status );
                $class  = EAO_Helpers::get_status_color_class( $status );
                echo '<span class="eao-status ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
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

