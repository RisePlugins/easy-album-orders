<?php
/**
 * Cart item template.
 *
 * Displays a single item in the cart sidebar.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 *
 * @var WP_Post $order The order post object.
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! isset( $order ) || ! $order ) {
    return;
}

$order_id         = $order->ID;
$album_name       = get_post_meta( $order_id, '_eao_album_name', true );
$design_name      = get_post_meta( $order_id, '_eao_design_name', true );
$material_name    = get_post_meta( $order_id, '_eao_material_name', true );
$color_name       = get_post_meta( $order_id, '_eao_material_color', true );
$size_name        = get_post_meta( $order_id, '_eao_size_name', true );
$engraving        = get_post_meta( $order_id, '_eao_engraving_method', true );
$shipping_address = get_post_meta( $order_id, '_eao_shipping_address', true );
$credit_type      = get_post_meta( $order_id, '_eao_credit_type', true );
$applied_credits  = floatval( get_post_meta( $order_id, '_eao_applied_credits', true ) );
$total            = EAO_Album_Order::calculate_total( $order_id );
?>

<div class="eao-cart__item" data-order-id="<?php echo esc_attr( $order_id ); ?>">
    <div class="eao-cart__item-header">
        <span class="eao-cart__item-name"><?php echo esc_html( $album_name ? $album_name : __( 'Untitled Album', 'easy-album-orders' ) ); ?></span>
        <span class="eao-cart__item-price"><?php echo esc_html( eao_format_price( $total ) ); ?></span>
    </div>
    <div class="eao-cart__item-details">
        <?php if ( $design_name ) : ?>
            <div><?php echo esc_html( $design_name ); ?></div>
        <?php endif; ?>
        <?php if ( $material_name ) : ?>
            <div>
                <?php 
                echo esc_html( $material_name );
                if ( $color_name ) {
                    echo ' - ' . esc_html( $color_name );
                }
                ?>
            </div>
        <?php endif; ?>
        <?php if ( $size_name ) : ?>
            <div><?php echo esc_html( $size_name ); ?></div>
        <?php endif; ?>
        <?php if ( $engraving ) : ?>
            <div><?php echo esc_html( sprintf( __( 'Engraving: %s', 'easy-album-orders' ), $engraving ) ); ?></div>
        <?php endif; ?>
        <?php if ( $shipping_address ) : ?>
            <div class="eao-cart__item-shipping" style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed #e2e4e7;">
                <strong style="font-size: 11px; text-transform: uppercase; color: #999;"><?php esc_html_e( 'Ship to:', 'easy-album-orders' ); ?></strong>
                <div style="font-size: 12px; white-space: pre-line;"><?php echo esc_html( $shipping_address ); ?></div>
            </div>
        <?php endif; ?>
        <?php if ( $applied_credits > 0 ) : ?>
            <div class="eao-cart__item-credit">
                <?php
                if ( 'free_album' === $credit_type ) {
                    esc_html_e( '✓ Free Album Credit Applied', 'easy-album-orders' );
                } else {
                    echo esc_html( sprintf( __( '✓ %s credit applied', 'easy-album-orders' ), eao_format_price( $applied_credits ) ) );
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="eao-cart__item-actions">
        <button type="button" class="eao-cart__item-btn eao-cart__item-btn--edit" data-order-id="<?php echo esc_attr( $order_id ); ?>">
            <?php esc_html_e( 'Edit', 'easy-album-orders' ); ?>
        </button>
        <button type="button" class="eao-cart__item-btn eao-cart__item-btn--remove" data-order-id="<?php echo esc_attr( $order_id ); ?>">
            <?php esc_html_e( 'Remove', 'easy-album-orders' ); ?>
        </button>
    </div>
</div>

