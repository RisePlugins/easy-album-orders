<?php
/**
 * Template for single Client Album (Order Form).
 *
 * This is the main client-facing order form where clients
 * can configure and order their albums.
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
$loom_url    = get_post_meta( $album_id, '_eao_loom_url', true );
$credits     = floatval( get_post_meta( $album_id, '_eao_album_credits', true ) );
$designs     = get_post_meta( $album_id, '_eao_designs', true );
$designs     = is_array( $designs ) ? $designs : array();

// Get global options.
$materials         = get_option( 'eao_materials', array() );
$sizes             = get_option( 'eao_sizes', array() );
$engraving_options = get_option( 'eao_engraving_options', array() );
$general_settings  = get_option( 'eao_general_settings', array() );

// Get cart items for this album.
$cart_items = EAO_Album_Order::get_cart_items( $album_id );
?>

<div class="eao-order-page" id="eao-order-page" data-album-id="<?php echo esc_attr( $album_id ); ?>">
    <!-- Header -->
    <header class="eao-header">
        <h1 class="eao-header__title"><?php echo esc_html( $album_title ); ?></h1>
        <?php if ( $client_name ) : ?>
            <p class="eao-header__subtitle"><?php echo esc_html( sprintf( __( 'Album order form for %s', 'easy-album-orders' ), $client_name ) ); ?></p>
        <?php endif; ?>
    </header>

    <?php if ( $loom_url ) : ?>
        <!-- Loom Video Section -->
        <section class="eao-video-section">
            <?php echo EAO_Template_Loader::get_loom_embed( $loom_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </section>
    <?php endif; ?>

    <!-- Main Layout -->
    <div class="eao-layout">
        <!-- Order Form -->
        <main class="eao-form-wrapper">
            <form class="eao-form" id="eao-order-form" method="post">
                <?php wp_nonce_field( 'eao_order_form', 'eao_order_nonce' ); ?>
                <input type="hidden" name="action" value="eao_add_to_cart">
                <input type="hidden" name="client_album_id" value="<?php echo esc_attr( $album_id ); ?>">

                <!-- Messages Container -->
                <div class="eao-messages" id="eao-messages"></div>

                <!-- Album Name -->
                <section class="eao-form__section">
                    <h2 class="eao-form__section-title"><?php esc_html_e( 'Name Your Album', 'easy-album-orders' ); ?></h2>
                    <div class="eao-field">
                        <label for="eao-album-name" class="eao-field__label"><?php esc_html_e( 'Album Name', 'easy-album-orders' ); ?> <span class="required">*</span></label>
                        <input type="text" id="eao-album-name" name="album_name" class="eao-field__input" placeholder="<?php esc_attr_e( 'e.g., Our Wedding Album', 'easy-album-orders' ); ?>" required>
                        <p class="eao-field__help"><?php esc_html_e( 'Give your album a memorable name.', 'easy-album-orders' ); ?></p>
                    </div>
                </section>

                <?php if ( ! empty( $designs ) ) : ?>
                    <!-- Album Design Selection -->
                    <section class="eao-form__section">
                        <h2 class="eao-form__section-title"><?php esc_html_e( 'Choose Your Design', 'easy-album-orders' ); ?></h2>
                        <div class="eao-selection-grid eao-designs-grid">
                            <?php foreach ( $designs as $index => $design ) : ?>
                                <?php
                                $cover_url = ! empty( $design['cover_id'] ) ? wp_get_attachment_image_url( $design['cover_id'], 'medium' ) : '';
                                $pdf_url   = ! empty( $design['pdf_id'] ) ? wp_get_attachment_url( $design['pdf_id'] ) : '';
                                ?>
                                <label class="eao-selection-card eao-design-card" data-base-price="<?php echo esc_attr( $design['base_price'] ); ?>">
                                    <input type="radio" name="design_index" value="<?php echo esc_attr( $index ); ?>" required>
                                    <?php if ( $cover_url ) : ?>
                                        <img src="<?php echo esc_url( $cover_url ); ?>" alt="<?php echo esc_attr( $design['name'] ); ?>" class="eao-selection-card__image">
                                    <?php else : ?>
                                        <div class="eao-selection-card__image eao-selection-card__image--placeholder">
                                            <span class="dashicons dashicons-format-gallery"></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="eao-selection-card__name"><?php echo esc_html( $design['name'] ); ?></div>
                                    <div class="eao-selection-card__price"><?php echo esc_html( eao_format_price( $design['base_price'] ) ); ?></div>
                                    <?php if ( $pdf_url ) : ?>
                                        <a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" class="eao-selection-card__link" onclick="event.stopPropagation();">
                                            <?php esc_html_e( 'View Proof', 'easy-album-orders' ); ?> →
                                        </a>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ( ! empty( $materials ) ) : ?>
                    <!-- Material Selection -->
                    <section class="eao-form__section">
                        <h2 class="eao-form__section-title"><?php esc_html_e( 'Select Material', 'easy-album-orders' ); ?></h2>
                        <div class="eao-selection-grid eao-materials-grid">
                            <?php foreach ( $materials as $index => $material ) : ?>
                                <?php
                                $material_image = ! empty( $material['image_id'] ) ? wp_get_attachment_image_url( $material['image_id'], 'thumbnail' ) : '';
                                $colors_json    = ! empty( $material['colors'] ) ? wp_json_encode( $material['colors'] ) : '[]';
                                $restricted     = ! empty( $material['restricted_sizes'] ) ? wp_json_encode( $material['restricted_sizes'] ) : '[]';
                                ?>
                                <label class="eao-selection-card eao-material-card" 
                                       data-material-id="<?php echo esc_attr( $material['id'] ); ?>"
                                       data-upcharge="<?php echo esc_attr( $material['upcharge'] ); ?>"
                                       data-allow-engraving="<?php echo esc_attr( ! empty( $material['allow_engraving'] ) ? '1' : '0' ); ?>"
                                       data-colors="<?php echo esc_attr( $colors_json ); ?>"
                                       data-restricted-sizes="<?php echo esc_attr( $restricted ); ?>">
                                    <input type="radio" name="material_id" value="<?php echo esc_attr( $material['id'] ); ?>" required>
                                    <?php if ( $material_image ) : ?>
                                        <img src="<?php echo esc_url( $material_image ); ?>" alt="<?php echo esc_attr( $material['name'] ); ?>" class="eao-selection-card__image">
                                    <?php else : ?>
                                        <div class="eao-selection-card__image eao-selection-card__image--placeholder">
                                            <span class="dashicons dashicons-art"></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="eao-selection-card__name"><?php echo esc_html( $material['name'] ); ?></div>
                                    <?php if ( $material['upcharge'] > 0 ) : ?>
                                        <div class="eao-selection-card__price">+ <?php echo esc_html( eao_format_price( $material['upcharge'] ) ); ?></div>
                                    <?php else : ?>
                                        <div class="eao-selection-card__price eao-selection-card__price--included"><?php esc_html_e( 'Included', 'easy-album-orders' ); ?></div>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <!-- Color Selection (Dynamic) -->
                    <section class="eao-form__section eao-color-section" id="eao-color-section" style="display: none;">
                        <h2 class="eao-form__section-title"><?php esc_html_e( 'Choose Color', 'easy-album-orders' ); ?></h2>
                        <div class="eao-color-grid" id="eao-color-grid">
                            <!-- Colors populated by JavaScript -->
                        </div>
                        <input type="hidden" name="color_id" id="eao-color-id" value="">
                        <input type="hidden" name="color_name" id="eao-color-name" value="">
                    </section>
                <?php endif; ?>

                <?php if ( ! empty( $sizes ) ) : ?>
                    <!-- Size Selection -->
                    <section class="eao-form__section eao-size-section" id="eao-size-section">
                        <h2 class="eao-form__section-title"><?php esc_html_e( 'Select Size', 'easy-album-orders' ); ?></h2>
                        <div class="eao-selection-grid eao-sizes-grid" id="eao-sizes-grid">
                            <?php foreach ( $sizes as $size ) : ?>
                                <label class="eao-selection-card eao-size-card" 
                                       data-size-id="<?php echo esc_attr( $size['id'] ); ?>"
                                       data-upcharge="<?php echo esc_attr( $size['upcharge'] ); ?>">
                                    <input type="radio" name="size_id" value="<?php echo esc_attr( $size['id'] ); ?>" required>
                                    <div class="eao-selection-card__name"><?php echo esc_html( $size['name'] ); ?></div>
                                    <?php if ( ! empty( $size['dimensions'] ) ) : ?>
                                        <div class="eao-selection-card__dimensions"><?php echo esc_html( $size['dimensions'] ); ?></div>
                                    <?php endif; ?>
                                    <?php if ( $size['upcharge'] > 0 ) : ?>
                                        <div class="eao-selection-card__price">+ <?php echo esc_html( eao_format_price( $size['upcharge'] ) ); ?></div>
                                    <?php else : ?>
                                        <div class="eao-selection-card__price eao-selection-card__price--included"><?php esc_html_e( 'Included', 'easy-album-orders' ); ?></div>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ( ! empty( $engraving_options ) ) : ?>
                    <!-- Engraving Section (Conditional) -->
                    <section class="eao-form__section eao-engraving-section" id="eao-engraving-section" style="display: none;">
                        <h2 class="eao-form__section-title"><?php esc_html_e( 'Add Engraving', 'easy-album-orders' ); ?> <span class="optional">(<?php esc_html_e( 'Optional', 'easy-album-orders' ); ?>)</span></h2>
                        
                        <div class="eao-field">
                            <label for="eao-engraving-method" class="eao-field__label"><?php esc_html_e( 'Engraving Method', 'easy-album-orders' ); ?></label>
                            <select id="eao-engraving-method" name="engraving_method" class="eao-field__select">
                                <option value=""><?php esc_html_e( 'No Engraving', 'easy-album-orders' ); ?></option>
                                <?php foreach ( $engraving_options as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option['id'] ); ?>" 
                                            data-upcharge="<?php echo esc_attr( $option['upcharge'] ); ?>"
                                            data-char-limit="<?php echo esc_attr( $option['character_limit'] ); ?>"
                                            data-fonts="<?php echo esc_attr( $option['fonts'] ); ?>">
                                        <?php 
                                        echo esc_html( $option['name'] );
                                        if ( $option['upcharge'] > 0 ) {
                                            echo ' (+' . esc_html( eao_format_price( $option['upcharge'] ) ) . ')';
                                        }
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="eao-engraving-fields" id="eao-engraving-fields" style="display: none;">
                            <div class="eao-field">
                                <label for="eao-engraving-text" class="eao-field__label"><?php esc_html_e( 'Engraving Text', 'easy-album-orders' ); ?></label>
                                <input type="text" id="eao-engraving-text" name="engraving_text" class="eao-field__input" placeholder="<?php esc_attr_e( 'Enter your engraving text', 'easy-album-orders' ); ?>">
                                <p class="eao-field__help">
                                    <span id="eao-char-count">0</span> / <span id="eao-char-limit">50</span> <?php esc_html_e( 'characters', 'easy-album-orders' ); ?>
                                </p>
                            </div>

                            <div class="eao-field" id="eao-font-field" style="display: none;">
                                <label for="eao-engraving-font" class="eao-field__label"><?php esc_html_e( 'Font Style', 'easy-album-orders' ); ?></label>
                                <select id="eao-engraving-font" name="engraving_font" class="eao-field__select">
                                    <option value=""><?php esc_html_e( 'Select Font', 'easy-album-orders' ); ?></option>
                                </select>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Price Summary -->
                <section class="eao-form__section eao-price-section">
                    <h2 class="eao-form__section-title"><?php esc_html_e( 'Price Summary', 'easy-album-orders' ); ?></h2>
                    <div class="eao-price-breakdown" id="eao-price-breakdown">
                        <div class="eao-price-line" id="eao-price-base">
                            <span class="eao-price-line__label"><?php esc_html_e( 'Base Price', 'easy-album-orders' ); ?></span>
                            <span class="eao-price-line__value" data-value="0"><?php echo esc_html( eao_format_price( 0 ) ); ?></span>
                        </div>
                        <div class="eao-price-line" id="eao-price-material" style="display: none;">
                            <span class="eao-price-line__label"><?php esc_html_e( 'Material', 'easy-album-orders' ); ?></span>
                            <span class="eao-price-line__value" data-value="0">+ <?php echo esc_html( eao_format_price( 0 ) ); ?></span>
                        </div>
                        <div class="eao-price-line" id="eao-price-size" style="display: none;">
                            <span class="eao-price-line__label"><?php esc_html_e( 'Size', 'easy-album-orders' ); ?></span>
                            <span class="eao-price-line__value" data-value="0">+ <?php echo esc_html( eao_format_price( 0 ) ); ?></span>
                        </div>
                        <div class="eao-price-line" id="eao-price-engraving" style="display: none;">
                            <span class="eao-price-line__label"><?php esc_html_e( 'Engraving', 'easy-album-orders' ); ?></span>
                            <span class="eao-price-line__value" data-value="0">+ <?php echo esc_html( eao_format_price( 0 ) ); ?></span>
                        </div>
                        <?php if ( $credits > 0 ) : ?>
                            <div class="eao-price-line eao-price-line--credit" id="eao-price-credit">
                                <span class="eao-price-line__label"><?php esc_html_e( 'Album Credit', 'easy-album-orders' ); ?></span>
                                <span class="eao-price-line__value" data-value="<?php echo esc_attr( $credits ); ?>">- <?php echo esc_html( eao_format_price( $credits ) ); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="eao-price-line eao-price-line--total" id="eao-price-total">
                            <span class="eao-price-line__label"><?php esc_html_e( 'Total', 'easy-album-orders' ); ?></span>
                            <span class="eao-price-line__value" data-value="0"><?php echo esc_html( eao_format_price( 0 ) ); ?></span>
                        </div>
                    </div>
                    <input type="hidden" name="calculated_total" id="eao-calculated-total" value="0">
                </section>

                <!-- Submit Button -->
                <div class="eao-form__submit">
                    <button type="submit" class="eao-btn eao-btn--primary eao-btn--full" id="eao-add-to-cart-btn">
                        <span class="eao-btn-text"><?php esc_html_e( 'Add to Cart', 'easy-album-orders' ); ?></span>
                        <span class="eao-spinner" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </main>

        <!-- Cart Sidebar -->
        <aside class="eao-cart" id="eao-cart">
            <div class="eao-cart__header">
                <h3 class="eao-cart__title">
                    <?php esc_html_e( 'Your Cart', 'easy-album-orders' ); ?>
                    <span class="eao-cart__count" id="eao-cart-count"><?php echo count( $cart_items ); ?></span>
                </h3>
            </div>

            <div class="eao-cart__items" id="eao-cart-items">
                <?php if ( empty( $cart_items ) ) : ?>
                    <div class="eao-cart__empty" id="eao-cart-empty">
                        <p><?php esc_html_e( 'Your cart is empty.', 'easy-album-orders' ); ?></p>
                        <p class="eao-cart__empty-hint"><?php esc_html_e( 'Configure an album above and click "Add to Cart" to get started.', 'easy-album-orders' ); ?></p>
                    </div>
                <?php else : ?>
                    <?php foreach ( $cart_items as $item ) : ?>
                        <?php EAO_Template_Loader::get_template_part( 'cart-item', '', array( 'order' => $item ) ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="eao-cart__footer" id="eao-cart-footer" <?php echo empty( $cart_items ) ? 'style="display: none;"' : ''; ?>>
                <div class="eao-cart__total">
                    <span><?php esc_html_e( 'Total:', 'easy-album-orders' ); ?></span>
                    <span id="eao-cart-total">
                        <?php
                        $cart_total = 0;
                        foreach ( $cart_items as $item ) {
                            $cart_total += EAO_Album_Order::calculate_total( $item->ID );
                        }
                        echo esc_html( eao_format_price( $cart_total ) );
                        ?>
                    </span>
                </div>
                <button type="button" class="eao-btn eao-btn--primary eao-btn--full" id="eao-checkout-btn">
                    <?php esc_html_e( 'Complete Order', 'easy-album-orders' ); ?>
                </button>
                <p class="eao-cart__notice">
                    <?php esc_html_e( 'You can edit or remove items until you complete your order.', 'easy-album-orders' ); ?>
                </p>
            </div>
        </aside>
    </div>
</div>

<?php
// Store data for JavaScript.
$js_data = array(
    'albumId'    => $album_id,
    'credits'    => $credits,
    'designs'    => $designs,
    'materials'  => $materials,
    'sizes'      => $sizes,
    'engraving'  => $engraving_options,
    'currency'   => array(
        'symbol'   => isset( $general_settings['currency_symbol'] ) ? $general_settings['currency_symbol'] : '$',
        'position' => isset( $general_settings['currency_position'] ) ? $general_settings['currency_position'] : 'before',
    ),
);
?>
<script>
    var eaoOrderData = <?php echo wp_json_encode( $js_data ); ?>;
</script>

<?php get_footer(); ?>

