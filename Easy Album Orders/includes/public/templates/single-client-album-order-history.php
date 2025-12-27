<?php
/**
 * Template for Client Album Order History view.
 *
 * Displays all completed orders for a client album in full detail.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// Get album data.
$album_id    = get_the_ID();
$album_title = get_the_title();
$client_name = get_post_meta( $album_id, '_eao_client_name', true );

// Get general settings for currency.
$general_settings = get_option( 'eao_general_settings', array() );

// Get completed orders (status = ordered or shipped) for this client album.
$completed_orders = get_posts( array(
    'post_type'      => 'album_order',
    'posts_per_page' => -1,
    'meta_query'     => array(
        'relation' => 'AND',
        array(
            'key'   => '_eao_client_album_id',
            'value' => $album_id,
        ),
        array(
            'key'     => '_eao_order_status',
            'value'   => array( EAO_Album_Order::STATUS_ORDERED, EAO_Album_Order::STATUS_SHIPPED ),
            'compare' => 'IN',
        ),
    ),
    'orderby'        => 'date',
    'order'          => 'DESC',
) );
?>

<div class="eao-order-history-page" id="eao-order-history-page">
    <!-- Header -->
    <header class="eao-order-history-page__header">
        <a href="<?php echo esc_url( get_permalink() ); ?>" class="eao-order-history-page__back">
            <?php EAO_Icons::render( 'arrow-left', array( 'size' => 18 ) ); ?>
            <?php esc_html_e( 'Back to Order Form', 'easy-album-orders' ); ?>
        </a>
        <h1 class="eao-order-history-page__title"><?php esc_html_e( 'Order History', 'easy-album-orders' ); ?></h1>
        <p class="eao-order-history-page__subtitle">
            <?php echo esc_html( sprintf( __( '%d order(s) for %s', 'easy-album-orders' ), count( $completed_orders ), $album_title ) ); ?>
        </p>
    </header>

    <!-- Orders List -->
    <div class="eao-order-history-page__content">
        <?php if ( empty( $completed_orders ) ) : ?>
            <div class="eao-order-history-page__empty">
                <div class="eao-order-history-page__empty-icon">
                    <?php EAO_Icons::render( 'clipboard-list', array( 'size' => 64 ) ); ?>
                </div>
                <h2><?php esc_html_e( 'No Orders Yet', 'easy-album-orders' ); ?></h2>
                <p><?php esc_html_e( 'You haven\'t completed any orders for this album yet.', 'easy-album-orders' ); ?></p>
                <a href="<?php echo esc_url( get_permalink() ); ?>" class="eao-btn eao-btn--primary">
                    <?php esc_html_e( 'Start Your Order', 'easy-album-orders' ); ?>
                </a>
            </div>
        <?php else : ?>
            <div class="eao-order-history-page__orders">
                <?php foreach ( $completed_orders as $order ) :
                    // Get order meta data.
                    $order_status       = EAO_Album_Order::get_order_status( $order->ID );
                    $album_name         = get_post_meta( $order->ID, '_eao_album_name', true );
                    $design_name        = get_post_meta( $order->ID, '_eao_design_name', true );
                    $material_name      = get_post_meta( $order->ID, '_eao_material_name', true );
                    $material_color     = get_post_meta( $order->ID, '_eao_material_color', true );
                    $size_name          = get_post_meta( $order->ID, '_eao_size_name', true );
                    $engraving_method   = get_post_meta( $order->ID, '_eao_engraving_method', true );
                    $engraving_text     = get_post_meta( $order->ID, '_eao_engraving_text', true );
                    $engraving_font     = get_post_meta( $order->ID, '_eao_engraving_font', true );
                    $order_date         = get_post_meta( $order->ID, '_eao_order_date', true );
                    $shipped_date       = get_post_meta( $order->ID, '_eao_shipped_date', true );
                    
                    // Shipping address.
                    $shipping_name      = get_post_meta( $order->ID, '_eao_shipping_name', true );
                    $shipping_address1  = get_post_meta( $order->ID, '_eao_shipping_address1', true );
                    $shipping_address2  = get_post_meta( $order->ID, '_eao_shipping_address2', true );
                    $shipping_city      = get_post_meta( $order->ID, '_eao_shipping_city', true );
                    $shipping_state     = get_post_meta( $order->ID, '_eao_shipping_state', true );
                    $shipping_zip       = get_post_meta( $order->ID, '_eao_shipping_zip', true );
                    
                    // Pricing.
                    $base_price         = floatval( get_post_meta( $order->ID, '_eao_base_price', true ) );
                    $material_upcharge  = floatval( get_post_meta( $order->ID, '_eao_material_upcharge', true ) );
                    $size_upcharge      = floatval( get_post_meta( $order->ID, '_eao_size_upcharge', true ) );
                    $engraving_upcharge = floatval( get_post_meta( $order->ID, '_eao_engraving_upcharge', true ) );
                    $credit_type        = get_post_meta( $order->ID, '_eao_credit_type', true );
                    $applied_credits    = floatval( get_post_meta( $order->ID, '_eao_applied_credits', true ) );
                    $total              = EAO_Album_Order::calculate_total( $order->ID );
                    
                    // Status label.
                    $status_labels = EAO_Album_Order::get_statuses();
                    $status_label  = isset( $status_labels[ $order_status ] ) ? $status_labels[ $order_status ] : $order_status;
                    
                    // Order number.
                    $order_number = EAO_Helpers::generate_order_number( $order->ID );
                ?>
                    <article class="eao-order-card">
                        <!-- Order Header -->
                        <header class="eao-order-card__header">
                            <div class="eao-order-card__title-row">
                                <h2 class="eao-order-card__title"><?php echo esc_html( $album_name ); ?></h2>
                                <span class="eao-status-badge eao-status-badge--<?php echo esc_attr( $order_status ); ?> eao-status-badge--large">
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </div>
                            <div class="eao-order-card__meta">
                                <span class="eao-order-card__number">
                                    <?php EAO_Icons::render( 'hash', array( 'size' => 14 ) ); ?>
                                    <?php echo esc_html( $order_number ); ?>
                                </span>
                                <span class="eao-order-card__date">
                                    <?php EAO_Icons::render( 'calendar', array( 'size' => 14 ) ); ?>
                                    <?php 
                                    if ( $order_status === EAO_Album_Order::STATUS_SHIPPED && $shipped_date ) {
                                        echo esc_html( sprintf( __( 'Shipped %s', 'easy-album-orders' ), date_i18n( get_option( 'date_format' ), strtotime( $shipped_date ) ) ) );
                                    } elseif ( $order_date ) {
                                        echo esc_html( sprintf( __( 'Ordered %s', 'easy-album-orders' ), date_i18n( get_option( 'date_format' ), strtotime( $order_date ) ) ) );
                                    }
                                    ?>
                                </span>
                            </div>
                        </header>

                        <!-- Order Details -->
                        <div class="eao-order-card__body">
                            <div class="eao-order-card__details">
                                <!-- Album Details Section -->
                                <div class="eao-order-card__section">
                                    <h3 class="eao-order-card__section-title">
                                        <?php EAO_Icons::render( 'book', array( 'size' => 16 ) ); ?>
                                        <?php esc_html_e( 'Album Details', 'easy-album-orders' ); ?>
                                    </h3>
                                    <dl class="eao-order-card__dl">
                                        <?php if ( $design_name ) : ?>
                                            <div class="eao-order-card__dl-item">
                                                <dt><?php esc_html_e( 'Design', 'easy-album-orders' ); ?></dt>
                                                <dd><?php echo esc_html( $design_name ); ?></dd>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ( $material_name ) : ?>
                                            <div class="eao-order-card__dl-item">
                                                <dt><?php esc_html_e( 'Material', 'easy-album-orders' ); ?></dt>
                                                <dd>
                                                    <?php echo esc_html( $material_name ); ?>
                                                    <?php if ( $material_color ) : ?>
                                                        <span class="eao-order-card__color">(<?php echo esc_html( $material_color ); ?>)</span>
                                                    <?php endif; ?>
                                                </dd>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ( $size_name ) : ?>
                                            <div class="eao-order-card__dl-item">
                                                <dt><?php esc_html_e( 'Size', 'easy-album-orders' ); ?></dt>
                                                <dd><?php echo esc_html( $size_name ); ?></dd>
                                            </div>
                                        <?php endif; ?>
                                    </dl>
                                </div>

                                <?php if ( $engraving_method ) : ?>
                                    <!-- Engraving Section -->
                                    <div class="eao-order-card__section">
                                        <h3 class="eao-order-card__section-title">
                                            <?php EAO_Icons::render( 'writing', array( 'size' => 16 ) ); ?>
                                            <?php esc_html_e( 'Engraving', 'easy-album-orders' ); ?>
                                        </h3>
                                        <dl class="eao-order-card__dl">
                                            <div class="eao-order-card__dl-item">
                                                <dt><?php esc_html_e( 'Method', 'easy-album-orders' ); ?></dt>
                                                <dd><?php echo esc_html( $engraving_method ); ?></dd>
                                            </div>
                                            <?php if ( $engraving_text ) : ?>
                                                <div class="eao-order-card__dl-item">
                                                    <dt><?php esc_html_e( 'Text', 'easy-album-orders' ); ?></dt>
                                                    <dd class="eao-order-card__engraving-text"><?php echo esc_html( $engraving_text ); ?></dd>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ( $engraving_font ) : ?>
                                                <div class="eao-order-card__dl-item">
                                                    <dt><?php esc_html_e( 'Font', 'easy-album-orders' ); ?></dt>
                                                    <dd><?php echo esc_html( $engraving_font ); ?></dd>
                                                </div>
                                            <?php endif; ?>
                                        </dl>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $shipping_name || $shipping_address1 ) : ?>
                                    <!-- Shipping Section -->
                                    <div class="eao-order-card__section">
                                        <h3 class="eao-order-card__section-title">
                                            <?php EAO_Icons::render( 'truck', array( 'size' => 16 ) ); ?>
                                            <?php esc_html_e( 'Shipping To', 'easy-album-orders' ); ?>
                                        </h3>
                                        <address class="eao-order-card__address">
                                            <?php if ( $shipping_name ) : ?>
                                                <strong><?php echo esc_html( $shipping_name ); ?></strong><br>
                                            <?php endif; ?>
                                            <?php if ( $shipping_address1 ) : ?>
                                                <?php echo esc_html( $shipping_address1 ); ?><br>
                                            <?php endif; ?>
                                            <?php if ( $shipping_address2 ) : ?>
                                                <?php echo esc_html( $shipping_address2 ); ?><br>
                                            <?php endif; ?>
                                            <?php if ( $shipping_city || $shipping_state || $shipping_zip ) : ?>
                                                <?php 
                                                $city_state_zip = array_filter( array( $shipping_city, $shipping_state, $shipping_zip ) );
                                                echo esc_html( implode( ', ', array_slice( $city_state_zip, 0, 2 ) ) );
                                                if ( $shipping_zip ) {
                                                    echo ' ' . esc_html( $shipping_zip );
                                                }
                                                ?>
                                            <?php endif; ?>
                                        </address>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Price Breakdown -->
                            <div class="eao-order-card__pricing">
                                <h3 class="eao-order-card__section-title">
                                    <?php EAO_Icons::render( 'receipt', array( 'size' => 16 ) ); ?>
                                    <?php esc_html_e( 'Price Breakdown', 'easy-album-orders' ); ?>
                                </h3>
                                <div class="eao-order-card__price-lines">
                                    <div class="eao-order-card__price-line">
                                        <span><?php esc_html_e( 'Base Price', 'easy-album-orders' ); ?></span>
                                        <span><?php echo esc_html( eao_format_price( $base_price ) ); ?></span>
                                    </div>
                                    <?php if ( $material_upcharge > 0 ) : ?>
                                        <div class="eao-order-card__price-line">
                                            <span><?php esc_html_e( 'Material', 'easy-album-orders' ); ?></span>
                                            <span>+ <?php echo esc_html( eao_format_price( $material_upcharge ) ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( $size_upcharge > 0 ) : ?>
                                        <div class="eao-order-card__price-line">
                                            <span><?php esc_html_e( 'Size', 'easy-album-orders' ); ?></span>
                                            <span>+ <?php echo esc_html( eao_format_price( $size_upcharge ) ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( $engraving_upcharge > 0 ) : ?>
                                        <div class="eao-order-card__price-line">
                                            <span><?php esc_html_e( 'Engraving', 'easy-album-orders' ); ?></span>
                                            <span>+ <?php echo esc_html( eao_format_price( $engraving_upcharge ) ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ( $applied_credits > 0 ) : ?>
                                        <div class="eao-order-card__price-line eao-order-card__price-line--credit">
                                            <span>
                                                <?php 
                                                if ( 'free' === $credit_type ) {
                                                    esc_html_e( 'Free Album Credit', 'easy-album-orders' );
                                                } else {
                                                    esc_html_e( 'Album Credit', 'easy-album-orders' );
                                                }
                                                ?>
                                            </span>
                                            <span>- <?php echo esc_html( eao_format_price( $applied_credits ) ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="eao-order-card__price-line eao-order-card__price-line--total">
                                        <span><?php esc_html_e( 'Total Paid', 'easy-album-orders' ); ?></span>
                                        <span><?php echo esc_html( eao_format_price( $total ) ); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>

