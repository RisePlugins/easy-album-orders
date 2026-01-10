<?php
/**
 * Template for Client Album Order History view.
 *
 * Displays all completed orders for a client album in full detail
 * with a visual, card-based layout featuring design images.
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

// Get designs for cover images.
$designs = get_post_meta( $album_id, '_eao_designs', true );
$designs = is_array( $designs ) ? $designs : array();

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
        <h1 class="eao-order-history-page__title"><?php esc_html_e( 'Your Album Orders', 'easy-album-orders' ); ?></h1>
        <p class="eao-order-history-page__subtitle">
            <?php echo esc_html( sprintf( _n( '%d album ordered for %s', '%d albums ordered for %s', count( $completed_orders ), 'easy-album-orders' ), count( $completed_orders ), $album_title ) ); ?>
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
                    $design_index       = get_post_meta( $order->ID, '_eao_design_index', true );
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
                    
                    // Get design cover image (custom cover takes priority, then PDF thumbnail).
                    $cover_url = '';
                    if ( isset( $designs[ $design_index ] ) ) {
                        $design_data = $designs[ $design_index ];
                        if ( ! empty( $design_data['use_custom_cover'] ) && ! empty( $design_data['cover_id'] ) ) {
                            $cover_url = wp_get_attachment_image_url( $design_data['cover_id'], 'medium' );
                        } elseif ( ! empty( $design_data['pdf_id'] ) ) {
                            $cover_url = EAO_Helpers::get_pdf_thumbnail_url( $design_data['pdf_id'], 'medium' );
                        }
                    }
                    
                    // Get material color data for background.
                    $material_id       = get_post_meta( $order->ID, '_eao_material_id', true );
                    $color_id          = get_post_meta( $order->ID, '_eao_material_color_id', true );
                    $material_bg_style = '';
                    $materials_list    = get_option( 'eao_materials', array() );
                    
                    // Find the material and color to get texture/hex.
                    foreach ( $materials_list as $mat ) {
                        if ( isset( $mat['id'] ) && $mat['id'] === $material_id && ! empty( $mat['colors'] ) ) {
                            foreach ( $mat['colors'] as $color ) {
                                if ( isset( $color['id'] ) && $color['id'] === $color_id ) {
                                    // Prefer texture image, fall back to hex color.
                                    if ( ! empty( $color['texture_image_id'] ) ) {
                                        $texture_url = wp_get_attachment_url( $color['texture_image_id'] );
                                        if ( $texture_url ) {
                                            $material_bg_style = 'background-image: url(' . esc_url( $texture_url ) . '); background-size: cover; background-position: center;';
                                        }
                                    } elseif ( ! empty( $color['hex'] ) ) {
                                        $material_bg_style = 'background-color: ' . esc_attr( $color['hex'] ) . ';';
                                    }
                                    break 2;
                                }
                            }
                        }
                    }
                    
                    // Is shipped?
                    $is_shipped = ( $order_status === EAO_Album_Order::STATUS_SHIPPED );
                ?>
                    <article class="eao-order-card-v2<?php echo $is_shipped ? ' eao-order-card-v2--shipped' : ''; ?>">
                        <!-- Card Image -->
                        <div class="eao-order-card-v2__image"<?php echo $material_bg_style ? ' style="' . esc_attr( $material_bg_style ) . '"' : ''; ?>>
                            <?php if ( $cover_url ) : ?>
                                <img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php echo esc_attr( $design_name ); ?>">
                            <?php else : ?>
                                <div class="eao-order-card-v2__image-placeholder">
                                    <?php EAO_Icons::render( 'book', array( 'size' => 48 ) ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Status Badge Overlay -->
                            <div class="eao-order-card-v2__status-badge eao-order-card-v2__status-badge--<?php echo esc_attr( $order_status ); ?>">
                                <?php if ( $is_shipped ) : ?>
                                    <?php EAO_Icons::render( 'circle-check', array( 'size' => 14, 'variant' => 'filled' ) ); ?>
                                <?php else : ?>
                                    <?php EAO_Icons::render( 'truck', array( 'size' => 14 ) ); ?>
                                <?php endif; ?>
                                <?php echo esc_html( $status_label ); ?>
                            </div>
                        </div>
                        
                        <!-- Card Content -->
                        <div class="eao-order-card-v2__content">
                            <!-- Header with Album Name and Order Info -->
                            <header class="eao-order-card-v2__header">
                                <div class="eao-order-card-v2__title-group">
                                    <h2 class="eao-order-card-v2__title"><?php echo esc_html( $album_name ); ?></h2>
                                    <span class="eao-order-card-v2__order-number"><?php echo esc_html( $order_number ); ?></span>
                                </div>
                                
                                <!-- Timeline -->
                                <div class="eao-order-card-v2__timeline">
                                    <?php if ( $order_date ) : ?>
                                        <div class="eao-order-card-v2__timeline-item eao-order-card-v2__timeline-item--complete">
                                            <?php EAO_Icons::render( 'circle-check', array( 'size' => 16, 'variant' => 'filled' ) ); ?>
                                            <span class="eao-order-card-v2__timeline-label"><?php esc_html_e( 'Ordered', 'easy-album-orders' ); ?></span>
                                            <span class="eao-order-card-v2__timeline-date"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $order_date ) ) ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( $is_shipped && $shipped_date ) : ?>
                                        <div class="eao-order-card-v2__timeline-item eao-order-card-v2__timeline-item--complete eao-order-card-v2__timeline-item--shipped">
                                            <?php EAO_Icons::render( 'circle-check', array( 'size' => 16, 'variant' => 'filled' ) ); ?>
                                            <span class="eao-order-card-v2__timeline-label"><?php esc_html_e( 'Shipped', 'easy-album-orders' ); ?></span>
                                            <span class="eao-order-card-v2__timeline-date"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $shipped_date ) ) ); ?></span>
                                        </div>
                                    <?php elseif ( ! $is_shipped ) : ?>
                                        <div class="eao-order-card-v2__timeline-item eao-order-card-v2__timeline-item--pending">
                                            <?php EAO_Icons::render( 'truck', array( 'size' => 16 ) ); ?>
                                            <span class="eao-order-card-v2__timeline-label"><?php esc_html_e( 'Shipping Soon', 'easy-album-orders' ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </header>
                            
                            <!-- Order Details Grid -->
                            <div class="eao-order-card-v2__details">
                                <!-- Album Specifications -->
                                <div class="eao-order-card-v2__specs">
                                    <?php if ( $design_name ) : ?>
                                        <div class="eao-order-card-v2__spec">
                                            <span class="eao-order-card-v2__spec-label"><?php esc_html_e( 'Design', 'easy-album-orders' ); ?></span>
                                            <span class="eao-order-card-v2__spec-value"><?php echo esc_html( $design_name ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( $material_name ) : ?>
                                        <div class="eao-order-card-v2__spec">
                                            <span class="eao-order-card-v2__spec-label"><?php esc_html_e( 'Material', 'easy-album-orders' ); ?></span>
                                            <span class="eao-order-card-v2__spec-value">
                                                <?php echo esc_html( $material_name ); ?>
                                                <?php if ( $material_color ) : ?>
                                                    <span class="eao-order-card-v2__spec-sub">(<?php echo esc_html( $material_color ); ?>)</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( $size_name ) : ?>
                                        <div class="eao-order-card-v2__spec">
                                            <span class="eao-order-card-v2__spec-label"><?php esc_html_e( 'Size', 'easy-album-orders' ); ?></span>
                                            <span class="eao-order-card-v2__spec-value"><?php echo esc_html( $size_name ); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ( $engraving_method && $engraving_text ) : ?>
                                        <div class="eao-order-card-v2__spec">
                                            <span class="eao-order-card-v2__spec-label"><?php esc_html_e( 'Engraving', 'easy-album-orders' ); ?></span>
                                            <span class="eao-order-card-v2__spec-value">
                                                "<?php echo esc_html( $engraving_text ); ?>"
                                                <span class="eao-order-card-v2__spec-sub"><?php echo esc_html( $engraving_method ); ?></span>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Shipping Address -->
                                <?php if ( $shipping_name || $shipping_address1 ) : ?>
                                    <div class="eao-order-card-v2__shipping">
                                        <div class="eao-order-card-v2__shipping-header">
                                            <?php EAO_Icons::render( 'truck', array( 'size' => 16 ) ); ?>
                                            <span><?php esc_html_e( 'Shipping To', 'easy-album-orders' ); ?></span>
                                        </div>
                                        <address class="eao-order-card-v2__address">
                                            <?php if ( $shipping_name ) : ?>
                                                <strong><?php echo esc_html( $shipping_name ); ?></strong>
                                            <?php endif; ?>
                                            <?php if ( $shipping_address1 ) : ?>
                                                <br><?php echo esc_html( $shipping_address1 ); ?>
                                            <?php endif; ?>
                                            <?php if ( $shipping_address2 ) : ?>
                                                <br><?php echo esc_html( $shipping_address2 ); ?>
                                            <?php endif; ?>
                                            <?php if ( $shipping_city || $shipping_state || $shipping_zip ) : ?>
                                                <br>
                                                <?php 
                                                $city_state = array_filter( array( $shipping_city, $shipping_state ) );
                                                echo esc_html( implode( ', ', $city_state ) );
                                                if ( $shipping_zip ) {
                                                    echo ' ' . esc_html( $shipping_zip );
                                                }
                                                ?>
                                            <?php endif; ?>
                                        </address>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Footer with Price -->
                            <footer class="eao-order-card-v2__footer">
                                <?php if ( $applied_credits > 0 ) : ?>
                                    <div class="eao-order-card-v2__credit">
                                        <?php EAO_Icons::render( 'award', array( 'size' => 16, 'variant' => 'filled' ) ); ?>
                                        <?php 
                                        if ( 'free' === $credit_type || 'free_album' === $credit_type ) {
                                            esc_html_e( 'Free Album Credit Applied', 'easy-album-orders' );
                                        } else {
                                            echo esc_html( sprintf( __( '%s Credit Applied', 'easy-album-orders' ), eao_format_price( $applied_credits ) ) );
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="eao-order-card-v2__total">
                                    <span class="eao-order-card-v2__total-label"><?php esc_html_e( 'Total Paid', 'easy-album-orders' ); ?></span>
                                    <span class="eao-order-card-v2__total-amount"><?php echo esc_html( eao_format_price( $total ) ); ?></span>
                                </div>
                            </footer>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
