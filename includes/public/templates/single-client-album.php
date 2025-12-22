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
$designs     = get_post_meta( $album_id, '_eao_designs', true );
$designs     = is_array( $designs ) ? $designs : array();

// Add available credits info to each design.
foreach ( $designs as $index => &$design ) {
    $design['available_free_credits']  = EAO_Album_Order::get_available_free_credits( $album_id, $index );
    $design['available_dollar_credit'] = EAO_Album_Order::get_available_dollar_credits( $album_id, $index );
}
unset( $design ); // Break reference.

// Get global options.
$materials         = get_option( 'eao_materials', array() );
$sizes             = get_option( 'eao_sizes', array() );
$engraving_options = get_option( 'eao_engraving_options', array() );
$general_settings  = get_option( 'eao_general_settings', array() );

// Cart items are loaded via JavaScript using browser-specific cart token.
// This ensures each browser has its own cart (bride vs. parents, etc.).
$cart_items = array();
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
                                $cover_url             = ! empty( $design['cover_id'] ) ? wp_get_attachment_image_url( $design['cover_id'], 'medium' ) : '';
                                $pdf_url               = ! empty( $design['pdf_id'] ) ? wp_get_attachment_url( $design['pdf_id'] ) : '';
                                $free_credits          = isset( $design['available_free_credits'] ) ? intval( $design['available_free_credits'] ) : 0;
                                $available_dollar      = isset( $design['available_dollar_credit'] ) ? floatval( $design['available_dollar_credit'] ) : 0;
                                $has_credits           = $free_credits > 0 || $available_dollar > 0;
                                ?>
                                <label class="eao-selection-card eao-design-card<?php echo $has_credits ? ' has-credit' : ''; ?>" 
                                       data-base-price="<?php echo esc_attr( $design['base_price'] ); ?>"
                                       data-free-credits="<?php echo esc_attr( $free_credits ); ?>"
                                       data-dollar-credit="<?php echo esc_attr( $available_dollar ); ?>">
                                    <input type="radio" name="design_index" value="<?php echo esc_attr( $index ); ?>" required>
                                    <?php if ( $has_credits ) : ?>
                                        <div class="eao-selection-card__badge">
                                            <?php if ( $free_credits > 0 ) : ?>
                                                <?php echo esc_html( sprintf( _n( '%d Free Album', '%d Free Albums', $free_credits, 'easy-album-orders' ), $free_credits ) ); ?>
                                            <?php else : ?>
                                                <?php echo esc_html( eao_format_price( $available_dollar ) . ' ' . __( 'Credit', 'easy-album-orders' ) ); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
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
                                        <a href="#" class="eao-selection-card__link eao-view-proof-btn" data-pdf-url="<?php echo esc_url( $pdf_url ); ?>" data-design-name="<?php echo esc_attr( $design['name'] ); ?>">
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

                                // Add URLs to colors for front-end use.
                                $colors_with_urls = array();
                                if ( ! empty( $material['colors'] ) && is_array( $material['colors'] ) ) {
                                    foreach ( $material['colors'] as $color ) {
                                        // Add texture URL if available.
                                        if ( ! empty( $color['texture_image_id'] ) ) {
                                            $color['texture_url'] = wp_get_attachment_url( $color['texture_image_id'] );
                                        }
                                        // Add preview image URL if available.
                                        if ( ! empty( $color['preview_image_id'] ) ) {
                                            $color['preview_image_url'] = wp_get_attachment_image_url( $color['preview_image_id'], 'medium' );
                                        }
                                        $colors_with_urls[] = $color;
                                    }
                                }
                                $colors_json = wp_json_encode( $colors_with_urls );

                                $restricted = ! empty( $material['restricted_sizes'] ) ? wp_json_encode( $material['restricted_sizes'] ) : '[]';
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
                                <?php
                                $size_image = ! empty( $size['image_id'] ) ? wp_get_attachment_image_url( $size['image_id'], 'medium' ) : '';
                                ?>
                                <label class="eao-selection-card eao-size-card" 
                                       data-size-id="<?php echo esc_attr( $size['id'] ); ?>"
                                       data-upcharge="<?php echo esc_attr( $size['upcharge'] ); ?>">
                                    <input type="radio" name="size_id" value="<?php echo esc_attr( $size['id'] ); ?>" required>
                                    <?php if ( $size_image ) : ?>
                                        <img src="<?php echo esc_url( $size_image ); ?>" alt="<?php echo esc_attr( $size['name'] ); ?>" class="eao-selection-card__image">
                                    <?php endif; ?>
                                    <div class="eao-selection-card__name"><?php echo esc_html( $size['name'] ); ?></div>
                                    <?php if ( ! empty( $size['dimensions'] ) ) : ?>
                                        <div class="eao-selection-card__dimensions"><?php echo esc_html( $size['dimensions'] ); ?></div>
                                    <?php endif; ?>
                                    <?php if ( $size['upcharge'] > 0 ) : ?>
                                        <div class="eao-selection-card__price">+ <?php echo esc_html( eao_format_price( $size['upcharge'] ) ); ?></div>
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
                        
                        <!-- Engraving Method Selection Cards -->
                        <div class="eao-selection-grid eao-engraving-grid">
                            <!-- No Engraving Card -->
                            <label class="eao-selection-card eao-engraving-card eao-engraving-card--none is-selected" 
                                   data-engraving-id=""
                                   data-upcharge="0"
                                   data-char-limit="0"
                                   data-fonts="">
                                <input type="radio" name="engraving_method" value="" checked>
                                <div class="eao-engraving-card__icon">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </div>
                                <div class="eao-selection-card__name"><?php esc_html_e( 'No Engraving', 'easy-album-orders' ); ?></div>
                                <div class="eao-selection-card__price eao-selection-card__price--included"><?php esc_html_e( 'Included', 'easy-album-orders' ); ?></div>
                            </label>

                            <?php foreach ( $engraving_options as $option ) : ?>
                                <?php
                                $engraving_image = ! empty( $option['image_id'] ) ? wp_get_attachment_image_url( $option['image_id'], 'thumbnail' ) : '';
                                ?>
                                <label class="eao-selection-card eao-engraving-card" 
                                       data-engraving-id="<?php echo esc_attr( $option['id'] ); ?>"
                                       data-upcharge="<?php echo esc_attr( $option['upcharge'] ); ?>"
                                       data-char-limit="<?php echo esc_attr( $option['character_limit'] ); ?>"
                                       data-fonts="<?php echo esc_attr( $option['fonts'] ); ?>">
                                    <input type="radio" name="engraving_method" value="<?php echo esc_attr( $option['id'] ); ?>">
                                    <?php if ( $engraving_image ) : ?>
                                        <img src="<?php echo esc_url( $engraving_image ); ?>" alt="<?php echo esc_attr( $option['name'] ); ?>" class="eao-selection-card__image">
                                    <?php else : ?>
                                        <div class="eao-engraving-card__icon">
                                            <span class="dashicons dashicons-edit"></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="eao-selection-card__name"><?php echo esc_html( $option['name'] ); ?></div>
                                    <?php if ( $option['upcharge'] > 0 ) : ?>
                                        <div class="eao-selection-card__price">+ <?php echo esc_html( eao_format_price( $option['upcharge'] ) ); ?></div>
                                    <?php else : ?>
                                        <div class="eao-selection-card__price eao-selection-card__price--included"><?php esc_html_e( 'Included', 'easy-album-orders' ); ?></div>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $option['character_limit'] ) ) : ?>
                                        <div class="eao-engraving-card__limit"><?php echo esc_html( sprintf( __( 'Up to %d characters', 'easy-album-orders' ), $option['character_limit'] ) ); ?></div>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Hidden select for backwards compatibility with JS -->
                        <select id="eao-engraving-method" name="engraving_method_select" class="eao-field__select" style="display: none;">
                            <option value=""><?php esc_html_e( 'No Engraving', 'easy-album-orders' ); ?></option>
                            <?php foreach ( $engraving_options as $option ) : ?>
                                <option value="<?php echo esc_attr( $option['id'] ); ?>" 
                                        data-upcharge="<?php echo esc_attr( $option['upcharge'] ); ?>"
                                        data-char-limit="<?php echo esc_attr( $option['character_limit'] ); ?>"
                                        data-fonts="<?php echo esc_attr( $option['fonts'] ); ?>">
                                    <?php echo esc_html( $option['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Engraving Details (shown when an engraving method is selected) -->
                        <div class="eao-engraving-fields" id="eao-engraving-fields" style="display: none;">
                            <div class="eao-field">
                                <label for="eao-engraving-text" class="eao-field__label"><?php esc_html_e( 'Engraving Text', 'easy-album-orders' ); ?></label>
                                <input type="text" id="eao-engraving-text" name="engraving_text" class="eao-field__input eao-field__input--engraving" placeholder="<?php esc_attr_e( 'Enter your engraving text', 'easy-album-orders' ); ?>">
                                <p class="eao-field__help eao-char-counter">
                                    <span class="eao-char-counter__current" id="eao-char-count">0</span>
                                    <span class="eao-char-counter__separator">/</span>
                                    <span class="eao-char-counter__limit" id="eao-char-limit">50</span>
                                    <span class="eao-char-counter__label"><?php esc_html_e( 'characters', 'easy-album-orders' ); ?></span>
                                </p>
                            </div>

                            <div class="eao-field eao-font-field" id="eao-font-field" style="display: none;">
                                <label class="eao-field__label"><?php esc_html_e( 'Font Style', 'easy-album-orders' ); ?></label>
                                <div class="eao-font-grid" id="eao-font-grid">
                                    <!-- Font options populated by JavaScript -->
                                </div>
                                <select id="eao-engraving-font" name="engraving_font" class="eao-field__select" style="display: none;">
                                    <option value=""><?php esc_html_e( 'Select Font', 'easy-album-orders' ); ?></option>
                                </select>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Shipping Address -->
                <section class="eao-form__section">
                    <h2 class="eao-form__section-title"><?php esc_html_e( 'Shipping Address', 'easy-album-orders' ); ?></h2>
                    <p class="eao-field__help" style="margin-bottom: 20px;"><?php esc_html_e( 'Each album can be shipped to a different address. For example, ship a parents album directly to them!', 'easy-album-orders' ); ?></p>
                    
                    <!-- Saved Address Selector -->
                    <!-- Addresses are loaded from localStorage (browser-specific) via JavaScript -->
                    <div class="eao-address-selector" id="eao-address-selector">
                        <div class="eao-address-selector__grid" id="eao-address-grid">
                            <!-- Add New Address Card -->
                            <div class="eao-address-card eao-address-card--new is-selected" data-address-id="new">
                                <div class="eao-address-card__icon">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                </div>
                                <div class="eao-address-card__label"><?php esc_html_e( 'New Address', 'easy-album-orders' ); ?></div>
                            </div>
                            <!-- Saved address cards rendered by JavaScript from localStorage -->
                        </div>
                    </div>

                    <!-- Address Form Fields -->
                    <div class="eao-address-form" id="eao-address-form">
                        <div class="eao-field">
                            <label for="eao-shipping-name" class="eao-field__label"><?php esc_html_e( 'Full Name', 'easy-album-orders' ); ?> <span class="required">*</span></label>
                            <input type="text" id="eao-shipping-name" name="shipping_name" class="eao-field__input" placeholder="<?php esc_attr_e( 'John Smith', 'easy-album-orders' ); ?>" required>
                        </div>

                        <div class="eao-field">
                            <label for="eao-shipping-address1" class="eao-field__label"><?php esc_html_e( 'Street Address', 'easy-album-orders' ); ?> <span class="required">*</span></label>
                            <input type="text" id="eao-shipping-address1" name="shipping_address1" class="eao-field__input" placeholder="<?php esc_attr_e( '123 Main Street', 'easy-album-orders' ); ?>" required>
                        </div>

                        <div class="eao-field">
                            <label for="eao-shipping-address2" class="eao-field__label"><?php esc_html_e( 'Apartment, Suite, etc.', 'easy-album-orders' ); ?> <span class="optional">(<?php esc_html_e( 'optional', 'easy-album-orders' ); ?>)</span></label>
                            <input type="text" id="eao-shipping-address2" name="shipping_address2" class="eao-field__input" placeholder="<?php esc_attr_e( 'Apt 4B', 'easy-album-orders' ); ?>">
                        </div>

                        <div class="eao-field-row eao-field-row--shipping">
                            <div class="eao-field eao-field--city">
                                <label for="eao-shipping-city" class="eao-field__label"><?php esc_html_e( 'City', 'easy-album-orders' ); ?> <span class="required">*</span></label>
                                <input type="text" id="eao-shipping-city" name="shipping_city" class="eao-field__input" placeholder="<?php esc_attr_e( 'New York', 'easy-album-orders' ); ?>" required>
                            </div>
                            <div class="eao-field eao-field--state">
                                <label for="eao-shipping-state" class="eao-field__label"><?php esc_html_e( 'State', 'easy-album-orders' ); ?> <span class="required">*</span></label>
                                <input type="text" id="eao-shipping-state" name="shipping_state" class="eao-field__input" placeholder="<?php esc_attr_e( 'NY', 'easy-album-orders' ); ?>" required>
                            </div>
                            <div class="eao-field eao-field--zip">
                                <label for="eao-shipping-zip" class="eao-field__label"><?php esc_html_e( 'ZIP Code', 'easy-album-orders' ); ?> <span class="required">*</span></label>
                                <input type="text" id="eao-shipping-zip" name="shipping_zip" class="eao-field__input" placeholder="<?php esc_attr_e( '10001', 'easy-album-orders' ); ?>" required>
                            </div>
                        </div>

                        <!-- Save Address Checkbox (checked by default) -->
                        <div class="eao-field eao-field--checkbox" id="eao-save-address-field">
                            <label class="eao-checkbox">
                                <input type="checkbox" id="eao-save-address" name="save_address" value="1" checked>
                                <span class="eao-checkbox__label"><?php esc_html_e( 'Save this address for future orders', 'easy-album-orders' ); ?></span>
                            </label>
                        </div>
                    </div>

                    <input type="hidden" id="eao-selected-address-id" name="selected_address_id" value="new">
                </section>

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
                        <div class="eao-price-line eao-price-line--credit" id="eao-price-credit" style="display: none;">
                            <span class="eao-price-line__label"><?php esc_html_e( 'Album Credit', 'easy-album-orders' ); ?></span>
                            <span class="eao-price-line__value" data-value="0">- <?php echo esc_html( eao_format_price( 0 ) ); ?></span>
                        </div>
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

    <!-- Checkout Modal -->
    <div class="eao-modal" id="eao-checkout-modal" style="display: none;">
        <div class="eao-modal__backdrop"></div>
        <div class="eao-modal__container">
            <div class="eao-modal__header">
                <h2 class="eao-modal__title"><?php esc_html_e( 'Complete Your Order', 'easy-album-orders' ); ?></h2>
                <button type="button" class="eao-modal__close" id="eao-modal-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div class="eao-modal__body">
                <form id="eao-checkout-form">
                    <!-- Step 1: Customer Info -->
                    <div class="eao-checkout-step" id="eao-step-info">
                        <p class="eao-modal__intro"><?php esc_html_e( 'Please provide your contact information so we can reach you about your order.', 'easy-album-orders' ); ?></p>
                        
                        <div class="eao-field">
                            <label for="eao-customer-name" class="eao-field__label"><?php esc_html_e( 'Your Name', 'easy-album-orders' ); ?> <span class="required">*</span></label>
                            <input type="text" id="eao-customer-name" name="customer_name" class="eao-field__input" placeholder="<?php esc_attr_e( 'John Smith', 'easy-album-orders' ); ?>" required>
                        </div>
                        
                        <div class="eao-field">
                            <label for="eao-customer-email" class="eao-field__label"><?php esc_html_e( 'Email Address', 'easy-album-orders' ); ?> <span class="required">*</span></label>
                            <input type="email" id="eao-customer-email" name="customer_email" class="eao-field__input" placeholder="<?php esc_attr_e( 'john@example.com', 'easy-album-orders' ); ?>" required>
                            <p class="eao-field__help"><?php esc_html_e( 'We\'ll send order confirmation and receipt to this email.', 'easy-album-orders' ); ?></p>
                        </div>
                        
                        <div class="eao-field">
                            <label for="eao-customer-phone" class="eao-field__label"><?php esc_html_e( 'Phone Number', 'easy-album-orders' ); ?> <span class="optional">(<?php esc_html_e( 'optional', 'easy-album-orders' ); ?>)</span></label>
                            <input type="tel" id="eao-customer-phone" name="customer_phone" class="eao-field__input" placeholder="<?php esc_attr_e( '(555) 123-4567', 'easy-album-orders' ); ?>">
                        </div>
                        
                        <div class="eao-field">
                            <label for="eao-client-notes" class="eao-field__label"><?php esc_html_e( 'Order Notes', 'easy-album-orders' ); ?> <span class="optional">(<?php esc_html_e( 'optional', 'easy-album-orders' ); ?>)</span></label>
                            <textarea id="eao-client-notes" name="client_notes" class="eao-field__textarea" rows="3" placeholder="<?php esc_attr_e( 'Any special requests or notes about your order...', 'easy-album-orders' ); ?>"></textarea>
                        </div>
                    </div>

                    <!-- Step 2: Payment (Stripe Elements) -->
                    <div class="eao-checkout-step eao-checkout-step--payment" id="eao-step-payment" style="display: none;">
                        <h3 class="eao-checkout-step__title"><?php esc_html_e( 'Payment Details', 'easy-album-orders' ); ?></h3>
                        
                        <!-- Stripe Elements container -->
                        <div class="eao-stripe-element" id="eao-card-element">
                            <!-- Stripe Card Element will be mounted here -->
                        </div>
                        
                        <!-- Error display -->
                        <div class="eao-stripe-error" id="eao-card-errors" role="alert"></div>
                        
                        <!-- Secure payment badge -->
                        <div class="eao-payment-secure">
                            <?php EAO_Icons::render( 'lock' ); ?>
                            <?php esc_html_e( 'Payments are secure and encrypted', 'easy-album-orders' ); ?>
                        </div>
                        
                        <!-- Back button -->
                        <button type="button" class="eao-btn eao-btn--text" id="eao-payment-back">
                            <?php EAO_Icons::render( 'arrow-left' ); ?>
                            <?php esc_html_e( 'Back to contact info', 'easy-album-orders' ); ?>
                        </button>
                    </div>
                </form>
            </div>
            <div class="eao-modal__footer">
                <div class="eao-modal__total">
                    <span><?php esc_html_e( 'Order Total:', 'easy-album-orders' ); ?></span>
                    <span class="eao-modal__total-value" id="eao-modal-total"><?php echo esc_html( eao_format_price( $cart_total ) ); ?></span>
                </div>
                <button type="button" class="eao-btn eao-btn--primary eao-btn--full" id="eao-submit-order-btn">
                    <span class="eao-btn-text"><?php esc_html_e( 'Submit Order', 'easy-album-orders' ); ?></span>
                    <span class="eao-spinner" style="display: none;"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- PDF Proof Viewer Lightbox -->
    <div class="eao-proof-viewer" id="eao-proof-viewer" style="display: none;">
        <div class="eao-proof-viewer__backdrop"></div>
        <div class="eao-proof-viewer__container">
            <!-- Header -->
            <div class="eao-proof-viewer__header">
                <div class="eao-proof-viewer__title">
                    <span class="dashicons dashicons-pdf"></span>
                    <span id="eao-proof-viewer-title"><?php esc_html_e( 'Proof Viewer', 'easy-album-orders' ); ?></span>
                </div>
                <div class="eao-proof-viewer__controls">
                    <div class="eao-proof-viewer__view-toggle">
                        <button type="button" class="eao-proof-viewer__view-btn is-active" data-view="slide" title="<?php esc_attr_e( 'Slide View', 'easy-album-orders' ); ?>">
                            <span class="dashicons dashicons-slides"></span>
                        </button>
                        <button type="button" class="eao-proof-viewer__view-btn" data-view="grid" title="<?php esc_attr_e( 'Grid View', 'easy-album-orders' ); ?>">
                            <span class="dashicons dashicons-grid-view"></span>
                        </button>
                    </div>
                    <button type="button" class="eao-proof-viewer__close" title="<?php esc_attr_e( 'Close', 'easy-album-orders' ); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="eao-proof-viewer__content">
                <!-- Loading State -->
                <div class="eao-proof-viewer__loading" id="eao-proof-loading">
                    <div class="eao-proof-viewer__spinner"></div>
                    <p><?php esc_html_e( 'Loading proof...', 'easy-album-orders' ); ?></p>
                </div>

                <!-- Slide View -->
                <div class="eao-proof-viewer__slide-view" id="eao-proof-slide-view">
                    <button type="button" class="eao-proof-viewer__nav eao-proof-viewer__nav--prev" id="eao-proof-prev" title="<?php esc_attr_e( 'Previous', 'easy-album-orders' ); ?>">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                    </button>
                    <div class="eao-proof-viewer__canvas-wrapper">
                        <canvas id="eao-proof-canvas"></canvas>
                    </div>
                    <button type="button" class="eao-proof-viewer__nav eao-proof-viewer__nav--next" id="eao-proof-next" title="<?php esc_attr_e( 'Next', 'easy-album-orders' ); ?>">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>

                <!-- Grid View -->
                <div class="eao-proof-viewer__grid-view" id="eao-proof-grid-view" style="display: none;">
                    <div class="eao-proof-viewer__grid" id="eao-proof-grid">
                        <!-- Grid thumbnails rendered by JS -->
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="eao-proof-viewer__footer">
                <div class="eao-proof-viewer__pagination" id="eao-proof-pagination">
                    <span class="eao-proof-viewer__page-info">
                        <?php esc_html_e( 'Page', 'easy-album-orders' ); ?>
                        <span id="eao-proof-current-page">1</span>
                        <?php esc_html_e( 'of', 'easy-album-orders' ); ?>
                        <span id="eao-proof-total-pages">1</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php
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

    <?php if ( ! empty( $completed_orders ) ) : ?>
        <!-- Order History Section -->
        <section class="eao-order-history" id="eao-order-history">
            <div class="eao-order-history__header">
                <h2 class="eao-order-history__title">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e( 'Your Order History', 'easy-album-orders' ); ?>
                </h2>
                <p class="eao-order-history__subtitle"><?php esc_html_e( 'Albums you have ordered', 'easy-album-orders' ); ?></p>
            </div>

            <div class="eao-order-history__list">
                <?php foreach ( $completed_orders as $order ) : 
                    $order_status     = EAO_Album_Order::get_order_status( $order->ID );
                    $album_name       = get_post_meta( $order->ID, '_eao_album_name', true );
                    $design_name      = get_post_meta( $order->ID, '_eao_design_name', true );
                    $material_name    = get_post_meta( $order->ID, '_eao_material_name', true );
                    $material_color   = get_post_meta( $order->ID, '_eao_material_color', true );
                    $size_name        = get_post_meta( $order->ID, '_eao_size_name', true );
                    $engraving_text   = get_post_meta( $order->ID, '_eao_engraving_text', true );
                    $order_total      = EAO_Album_Order::calculate_total( $order->ID );
                    $order_date       = get_post_meta( $order->ID, '_eao_order_date', true );
                    $shipped_date     = get_post_meta( $order->ID, '_eao_shipped_date', true );
                    $credit_type      = get_post_meta( $order->ID, '_eao_credit_type', true );
                    $applied_credits  = floatval( get_post_meta( $order->ID, '_eao_applied_credits', true ) );

                    // Shipping address.
                    $shipping_name     = get_post_meta( $order->ID, '_eao_shipping_name', true );
                    $shipping_address1 = get_post_meta( $order->ID, '_eao_shipping_address1', true );
                    $shipping_address2 = get_post_meta( $order->ID, '_eao_shipping_address2', true );
                    $shipping_city     = get_post_meta( $order->ID, '_eao_shipping_city', true );
                    $shipping_state    = get_post_meta( $order->ID, '_eao_shipping_state', true );
                    $shipping_zip      = get_post_meta( $order->ID, '_eao_shipping_zip', true );

                    $status_labels = EAO_Album_Order::get_statuses();
                    $status_label  = isset( $status_labels[ $order_status ] ) ? $status_labels[ $order_status ] : $order_status;
                ?>
                    <div class="eao-order-history__item">
                        <div class="eao-order-history__item-header">
                            <div class="eao-order-history__item-info">
                                <h3 class="eao-order-history__item-name"><?php echo esc_html( $album_name ); ?></h3>
                                <span class="eao-order-history__item-date">
                                    <?php 
                                    if ( $order_date ) {
                                        echo esc_html( sprintf( __( 'Ordered %s', 'easy-album-orders' ), date_i18n( get_option( 'date_format' ), strtotime( $order_date ) ) ) );
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="eao-order-history__item-status">
                                <span class="eao-status-badge eao-status-badge--<?php echo esc_attr( $order_status ); ?>">
                                    <?php echo esc_html( $status_label ); ?>
                                </span>
                            </div>
                        </div>

                        <div class="eao-order-history__item-details">
                            <div class="eao-order-history__item-specs">
                                <div class="eao-order-history__spec">
                                    <span class="eao-order-history__spec-label"><?php esc_html_e( 'Design:', 'easy-album-orders' ); ?></span>
                                    <span class="eao-order-history__spec-value"><?php echo esc_html( $design_name ); ?></span>
                                </div>
                                <div class="eao-order-history__spec">
                                    <span class="eao-order-history__spec-label"><?php esc_html_e( 'Material:', 'easy-album-orders' ); ?></span>
                                    <span class="eao-order-history__spec-value">
                                        <?php echo esc_html( $material_name ); ?>
                                        <?php if ( $material_color ) : ?>
                                            (<?php echo esc_html( $material_color ); ?>)
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="eao-order-history__spec">
                                    <span class="eao-order-history__spec-label"><?php esc_html_e( 'Size:', 'easy-album-orders' ); ?></span>
                                    <span class="eao-order-history__spec-value"><?php echo esc_html( $size_name ); ?></span>
                                </div>
                                <?php if ( $engraving_text ) : ?>
                                    <div class="eao-order-history__spec">
                                        <span class="eao-order-history__spec-label"><?php esc_html_e( 'Engraving:', 'easy-album-orders' ); ?></span>
                                        <span class="eao-order-history__spec-value">"<?php echo esc_html( $engraving_text ); ?>"</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="eao-order-history__item-shipping">
                                <span class="eao-order-history__spec-label"><?php esc_html_e( 'Shipping to:', 'easy-album-orders' ); ?></span>
                                <div class="eao-order-history__address">
                                    <?php if ( $shipping_name ) : ?>
                                        <strong><?php echo esc_html( $shipping_name ); ?></strong><br>
                                    <?php endif; ?>
                                    <?php echo esc_html( $shipping_address1 ); ?>
                                    <?php if ( $shipping_address2 ) : ?>
                                        <br><?php echo esc_html( $shipping_address2 ); ?>
                                    <?php endif; ?>
                                    <br><?php echo esc_html( $shipping_city . ', ' . $shipping_state . ' ' . $shipping_zip ); ?>
                                </div>
                            </div>
                        </div>

                        <div class="eao-order-history__item-footer">
                            <?php if ( $applied_credits > 0 ) : ?>
                                <div class="eao-order-history__credit">
                                    <?php if ( 'free_album' === $credit_type ) : ?>
                                        <span class="dashicons dashicons-awards"></span>
                                        <?php echo esc_html( sprintf( __( 'Free Album Credit Applied: %s', 'easy-album-orders' ), eao_format_price( $applied_credits ) ) ); ?>
                                    <?php else : ?>
                                        <span class="dashicons dashicons-tag"></span>
                                        <?php echo esc_html( sprintf( __( 'Credit Applied: %s', 'easy-album-orders' ), eao_format_price( $applied_credits ) ) ); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="eao-order-history__total">
                                <span class="eao-order-history__total-label"><?php esc_html_e( 'Total:', 'easy-album-orders' ); ?></span>
                                <span class="eao-order-history__total-value"><?php echo esc_html( eao_format_price( $order_total ) ); ?></span>
                            </div>
                        </div>

                        <?php if ( $order_status === EAO_Album_Order::STATUS_SHIPPED && $shipped_date ) : ?>
                            <div class="eao-order-history__shipped-notice">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php echo esc_html( sprintf( __( 'Shipped on %s', 'easy-album-orders' ), date_i18n( get_option( 'date_format' ), strtotime( $shipped_date ) ) ) ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php
// Store data for JavaScript (designs now include available_free_credits and dollar_credit).
$js_data = array(
    'albumId'   => $album_id,
    'designs'   => $designs,
    'materials' => $materials,
    'sizes'     => $sizes,
    'engraving' => $engraving_options,
    'currency'  => array(
        'symbol'   => isset( $general_settings['currency_symbol'] ) ? $general_settings['currency_symbol'] : '$',
        'position' => isset( $general_settings['currency_position'] ) ? $general_settings['currency_position'] : 'before',
    ),
);
?>
<script>
    var eaoOrderData = <?php echo wp_json_encode( $js_data ); ?>;
</script>

<?php get_footer(); ?>

