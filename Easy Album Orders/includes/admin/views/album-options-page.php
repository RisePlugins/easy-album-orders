<?php
/**
 * Album Options settings page template.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap eao-admin-wrap eao-options-page">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <?php settings_errors( 'eao_messages' ); ?>

    <form method="post" action="" class="eao-options-form">
        <?php wp_nonce_field( 'eao_album_options_nonce', 'eao_nonce' ); ?>

        <div class="eao-options-layout">
            <!-- Sidebar Navigation -->
            <nav class="eao-options-nav" role="navigation" aria-label="<?php esc_attr_e( 'Album Options Navigation', 'easy-album-orders' ); ?>">
                <div class="eao-options-nav__header">
                    <h2 class="eao-options-nav__title"><?php esc_html_e( 'Settings', 'easy-album-orders' ); ?></h2>
                </div>
                <div class="eao-options-nav__inner">
                    <a href="#materials" class="eao-options-nav__item eao-options-nav__item--active" data-tab="materials">
                        <span class="eao-options-nav__icon"><?php EAO_Icons::render( 'palette', array( 'size' => 20 ) ); ?></span>
                        <span class="eao-options-nav__label"><?php esc_html_e( 'Materials', 'easy-album-orders' ); ?></span>
                    </a>
                    <a href="#sizes" class="eao-options-nav__item" data-tab="sizes">
                        <span class="eao-options-nav__icon"><?php EAO_Icons::render( 'ruler-2', array( 'size' => 20 ) ); ?></span>
                        <span class="eao-options-nav__label"><?php esc_html_e( 'Sizes', 'easy-album-orders' ); ?></span>
                    </a>
                    <a href="#engraving" class="eao-options-nav__item" data-tab="engraving">
                        <span class="eao-options-nav__icon"><?php EAO_Icons::render( 'writing', array( 'size' => 20 ) ); ?></span>
                        <span class="eao-options-nav__label"><?php esc_html_e( 'Engraving', 'easy-album-orders' ); ?></span>
                    </a>
                    <a href="#emails" class="eao-options-nav__item" data-tab="emails">
                        <span class="eao-options-nav__icon"><?php EAO_Icons::render( 'mail', array( 'size' => 20 ) ); ?></span>
                        <span class="eao-options-nav__label"><?php esc_html_e( 'Emails', 'easy-album-orders' ); ?></span>
                    </a>
                    <a href="#payments" class="eao-options-nav__item" data-tab="payments">
                        <span class="eao-options-nav__icon"><?php EAO_Icons::render( 'credit-card', array( 'size' => 20 ) ); ?></span>
                        <span class="eao-options-nav__label"><?php esc_html_e( 'Payments', 'easy-album-orders' ); ?></span>
                    </a>
                    <a href="#general" class="eao-options-nav__item" data-tab="general">
                        <span class="eao-options-nav__icon"><?php EAO_Icons::render( 'settings', array( 'size' => 20 ) ); ?></span>
                        <span class="eao-options-nav__label"><?php esc_html_e( 'General', 'easy-album-orders' ); ?></span>
                    </a>
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="eao-options-content">

        <!-- Materials Tab -->
        <div id="materials" class="eao-tab-content active">
            <div class="eao-tab-header">
                <div class="eao-tab-header__text">
                    <h2><?php esc_html_e( 'Materials', 'easy-album-orders' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Configure the materials available for albums. Each material can have its own colors, upcharges, and engraving settings.', 'easy-album-orders' ); ?>
                    </p>
                </div>
                <button type="button" class="button button-primary eao-add-material">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e( 'Add Material', 'easy-album-orders' ); ?>
                </button>
            </div>

            <div class="eao-materials-grid" id="materials-repeater">
                <?php if ( ! empty( $materials ) ) : ?>
                    <?php foreach ( $materials as $index => $material ) : ?>
                        <div class="eao-material-card" data-index="<?php echo esc_attr( $index ); ?>">
                            <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $material['id'] ); ?>">
                            
                            <div class="eao-sortable-handle" title="<?php esc_attr_e( 'Drag to reorder', 'easy-album-orders' ); ?>">
                                <?php EAO_Icons::render( 'grip-vertical', array( 'size' => 20 ) ); ?>
                            </div>
                            <div class="eao-material-card__body">
                                <!-- Column 1: Image -->
                                <div class="eao-material-card__image">
                                    <div class="eao-image-upload">
                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][image_id]" value="<?php echo esc_attr( $material['image_id'] ); ?>" class="eao-image-id">
                                        <div class="eao-image-preview eao-image-preview--large">
                                            <?php if ( ! empty( $material['image_id'] ) ) : ?>
                                                <?php echo wp_get_attachment_image( $material['image_id'], 'medium' ); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="eao-image-actions">
                                            <button type="button" class="button eao-upload-image">
                                                <?php echo empty( $material['image_id'] ) ? esc_html__( 'Upload', 'easy-album-orders' ) : esc_html__( 'Change', 'easy-album-orders' ); ?>
                                            </button>
                                            <button type="button" class="button eao-remove-image" <?php echo empty( $material['image_id'] ) ? 'style="display:none;"' : ''; ?>>
                                                <?php esc_html_e( 'Remove', 'easy-album-orders' ); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Column 2: Content -->
                                <div class="eao-material-card__content">
                                    <!-- Header: Stacked label fields -->
                                    <div class="eao-material-card__header">
                                        <!-- Row 1: Material Name + Delete -->
                                        <div class="eao-material-card__name-row">
                                            <div class="eao-material-card__field eao-material-card__field--name">
                                                <label><?php esc_html_e( 'Material Name', 'easy-album-orders' ); ?></label>
                                                <input type="text" name="eao_materials[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $material['name'] ); ?>" class="eao-material-name-input" required>
                                            </div>
                                            <button type="button" class="eao-material-card__delete" title="<?php esc_attr_e( 'Delete Material', 'easy-album-orders' ); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </div>
                                        <!-- Row 2: Upcharge + Allow Engraving -->
                                        <div class="eao-material-card__meta-row">
                                            <div class="eao-material-card__field">
                                                <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                                                <div class="eao-input-prefix">
                                                    <span>$</span>
                                                    <input type="number" name="eao_materials[<?php echo esc_attr( $index ); ?>][upcharge]" value="<?php echo esc_attr( $material['upcharge'] ); ?>" step="0.01" min="0">
                                                </div>
                                            </div>
                                            <div class="eao-material-card__field eao-material-card__field--toggle">
                                                <label class="eao-toggle">
                                                    <span class="eao-toggle__label"><?php esc_html_e( 'Allow Engraving', 'easy-album-orders' ); ?></span>
                                                    <input type="checkbox" name="eao_materials[<?php echo esc_attr( $index ); ?>][allow_engraving]" value="1" <?php checked( ! empty( $material['allow_engraving'] ) ); ?>>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Colors Section -->
                                    <div class="eao-material-card__colors">
                                        <div class="eao-colors-header">
                                            <span class="eao-colors-label"><?php esc_html_e( 'Colors', 'easy-album-orders' ); ?></span>
                                        </div>
                                        <div class="eao-colors-grid">
                                            <?php if ( ! empty( $material['colors'] ) ) : ?>
                                                <?php foreach ( $material['colors'] as $color_index => $color ) : ?>
                                                    <?php
                                                    // Get texture data.
                                                    $texture_image_id  = isset( $color['texture_image_id'] ) ? $color['texture_image_id'] : '';
                                                    $texture_image_url = '';
                                                    $texture_region    = isset( $color['texture_region'] ) ? $color['texture_region'] : '';
                                                    $preview_image_id  = isset( $color['preview_image_id'] ) ? $color['preview_image_id'] : '';

                                                    if ( $texture_image_id ) {
                                                        $texture_image_url = wp_get_attachment_url( $texture_image_id );
                                                    }

                                                    // Build swatch style.
                                                    $swatch_style = '';
                                                    if ( 'texture' === $color['type'] && $texture_image_url && $texture_region ) {
                                                        $region = json_decode( $texture_region, true );
                                                        if ( $region ) {
                                                            $swatch_style = sprintf(
                                                                'background-image: url(%s); background-position: %s%% %s%%; background-size: %s%%;',
                                                                esc_url( $texture_image_url ),
                                                                esc_attr( $region['x'] ),
                                                                esc_attr( $region['y'] ),
                                                                esc_attr( $region['zoom'] )
                                                            );
                                                        }
                                                    } elseif ( 'solid' === $color['type'] ) {
                                                        $swatch_style = 'background-color: ' . esc_attr( $color['color_value'] ) . ';';
                                                    } else {
                                                        $swatch_style = 'background: linear-gradient(135deg, #ddd 25%, #999 50%, #ddd 75%);';
                                                    }
                                                    ?>
                                                    <div class="eao-color-swatch" data-color-index="<?php echo esc_attr( $color_index ); ?>">
                                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][id]" value="<?php echo esc_attr( $color['id'] ); ?>">
                                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][type]" value="<?php echo esc_attr( $color['type'] ); ?>" class="eao-color-type-input">
                                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][color_value]" value="<?php echo esc_attr( $color['color_value'] ); ?>" class="eao-color-value-input">
                                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][name]" value="<?php echo esc_attr( $color['name'] ); ?>" class="eao-color-name-input">
                                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][texture_image_id]" value="<?php echo esc_attr( $texture_image_id ); ?>" class="eao-color-texture-image-id-input">
                                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][texture_image_url]" value="<?php echo esc_attr( $texture_image_url ); ?>" class="eao-color-texture-image-url-input">
                                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][texture_region]" value="<?php echo esc_attr( $texture_region ); ?>" class="eao-color-texture-region-input">
                                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][preview_image_id]" value="<?php echo esc_attr( $preview_image_id ); ?>" class="eao-color-preview-image-id-input">
                                                        
                                                        <div class="eao-color-swatch__circle" style="<?php echo $swatch_style; ?>" title="<?php echo esc_attr( $color['name'] ); ?>">
                                                            <?php if ( 'texture' === $color['type'] && ! $texture_image_url ) : ?>
                                                                <span class="eao-color-swatch__texture-icon">
                                                                    <span class="dashicons dashicons-format-image"></span>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="eao-color-swatch__name"><?php echo esc_html( $color['name'] ); ?></span>
                                                        <?php if ( $preview_image_id ) : ?>
                                                            <span class="eao-color-swatch__has-preview" title="<?php esc_attr_e( 'Has preview image', 'easy-album-orders' ); ?>">
                                                                <span class="dashicons dashicons-visibility"></span>
                                                            </span>
                                                        <?php endif; ?>
                                                        <button type="button" class="eao-color-swatch__edit" title="<?php esc_attr_e( 'Edit', 'easy-album-orders' ); ?>">
                                                            <span class="dashicons dashicons-edit"></span>
                                                        </button>
                                                        <button type="button" class="eao-color-swatch__delete" title="<?php esc_attr_e( 'Delete', 'easy-album-orders' ); ?>">
                                                            <span class="dashicons dashicons-no-alt"></span>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <!-- Add Color Button -->
                                            <button type="button" class="eao-color-swatch eao-color-swatch--add eao-add-color" title="<?php esc_attr_e( 'Add Color', 'easy-album-orders' ); ?>">
                                                <div class="eao-color-swatch__circle eao-color-swatch__circle--add">
                                                    <span class="dashicons dashicons-plus"></span>
                                                </div>
                                                <span class="eao-color-swatch__name"><?php esc_html_e( 'Add', 'easy-album-orders' ); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ( empty( $materials ) ) : ?>
                <div class="eao-empty-state">
                    <div class="eao-empty-state__icon">
                        <span class="dashicons dashicons-book"></span>
                    </div>
                    <p class="eao-empty-state__text"><?php esc_html_e( 'No materials added yet. Click "Add Material" to get started.', 'easy-album-orders' ); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sizes Tab -->
        <div id="sizes" class="eao-tab-content">
            <div class="eao-tab-header">
                <div class="eao-tab-header__text">
                    <h2><?php esc_html_e( 'Sizes', 'easy-album-orders' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Configure the available album sizes and their upcharges.', 'easy-album-orders' ); ?>
                    </p>
                </div>
                <button type="button" class="button button-primary eao-add-size">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e( 'Add Size', 'easy-album-orders' ); ?>
                </button>
            </div>

            <div class="eao-sizes-grid" id="sizes-repeater">
                <?php if ( ! empty( $sizes ) ) : ?>
                    <?php foreach ( $sizes as $index => $size ) : ?>
                        <div class="eao-size-card" data-index="<?php echo esc_attr( $index ); ?>">
                            <input type="hidden" name="eao_sizes[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $size['id'] ); ?>">
                            <div class="eao-sortable-handle" title="<?php esc_attr_e( 'Drag to reorder', 'easy-album-orders' ); ?>">
                                <?php EAO_Icons::render( 'grip-vertical', array( 'size' => 18 ) ); ?>
                            </div>
                            <div class="eao-size-card__image">
                                <div class="eao-image-upload">
                                    <input type="hidden" name="eao_sizes[<?php echo esc_attr( $index ); ?>][image_id]" value="<?php echo esc_attr( isset( $size['image_id'] ) ? $size['image_id'] : '' ); ?>" class="eao-image-id">
                                    <div class="eao-image-preview eao-image-preview--small">
                                        <?php if ( ! empty( $size['image_id'] ) ) : ?>
                                            <?php echo wp_get_attachment_image( $size['image_id'], 'thumbnail' ); ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="eao-upload-image eao-size-card__upload" title="<?php esc_attr_e( 'Upload Image', 'easy-album-orders' ); ?>">
                                        <span class="dashicons dashicons-camera"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="eao-size-card__main">
                                <div class="eao-size-card__field">
                                    <label><?php esc_html_e( 'Size Name', 'easy-album-orders' ); ?></label>
                                    <input type="text" name="eao_sizes[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $size['name'] ); ?>" class="eao-size-name-input" required>
                                </div>
                                <div class="eao-size-card__field">
                                    <label><?php esc_html_e( 'Dimensions', 'easy-album-orders' ); ?></label>
                                    <input type="text" name="eao_sizes[<?php echo esc_attr( $index ); ?>][dimensions]" value="<?php echo esc_attr( $size['dimensions'] ); ?>" placeholder="<?php esc_attr_e( 'e.g., 10" × 10"', 'easy-album-orders' ); ?>">
                                </div>
                                <div class="eao-size-card__field">
                                    <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                                    <div class="eao-input-prefix">
                                        <span>$</span>
                                        <input type="number" name="eao_sizes[<?php echo esc_attr( $index ); ?>][upcharge]" value="<?php echo esc_attr( $size['upcharge'] ); ?>" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="eao-size-card__delete" title="<?php esc_attr_e( 'Delete Size', 'easy-album-orders' ); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ( empty( $sizes ) ) : ?>
                <div class="eao-empty-state">
                    <div class="eao-empty-state__icon">
                        <span class="dashicons dashicons-image-crop"></span>
                    </div>
                    <p class="eao-empty-state__text"><?php esc_html_e( 'No sizes added yet. Click "Add Size" to get started.', 'easy-album-orders' ); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Engraving Tab -->
        <div id="engraving" class="eao-tab-content">
            <div class="eao-tab-header">
                <div class="eao-tab-header__text">
                    <h2><?php esc_html_e( 'Engraving Options', 'easy-album-orders' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Configure engraving methods, pricing, and font options.', 'easy-album-orders' ); ?>
                    </p>
                </div>
                <button type="button" class="button button-primary eao-add-engraving">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e( 'Add Method', 'easy-album-orders' ); ?>
                </button>
            </div>

            <div class="eao-engraving-grid" id="engraving-repeater">
                <?php if ( ! empty( $engraving_options ) ) : ?>
                    <?php foreach ( $engraving_options as $index => $option ) : ?>
                        <div class="eao-engraving-card" data-index="<?php echo esc_attr( $index ); ?>">
                            <input type="hidden" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $option['id'] ); ?>">
                            
                            <div class="eao-sortable-handle" title="<?php esc_attr_e( 'Drag to reorder', 'easy-album-orders' ); ?>">
                                <?php EAO_Icons::render( 'grip-vertical', array( 'size' => 18 ) ); ?>
                            </div>
                            <div class="eao-engraving-card__wrapper">
                                <div class="eao-engraving-card__content">
                                    <!-- Thumbnail Image -->
                                    <div class="eao-engraving-card__image">
                                        <div class="eao-image-upload">
                                            <input type="hidden" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][image_id]" value="<?php echo esc_attr( isset( $option['image_id'] ) ? $option['image_id'] : '' ); ?>" class="eao-image-id">
                                            <div class="eao-image-preview eao-image-preview--small">
                                                <?php if ( ! empty( $option['image_id'] ) ) : ?>
                                                    <?php echo wp_get_attachment_image( $option['image_id'], 'thumbnail' ); ?>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="eao-upload-image eao-engraving-card__upload" title="<?php esc_attr_e( 'Upload Image', 'easy-album-orders' ); ?>">
                                                <span class="dashicons dashicons-camera"></span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Name Field -->
                                    <div class="eao-engraving-card__name">
                                        <label><?php esc_html_e( 'Method Name', 'easy-album-orders' ); ?></label>
                                        <input type="text" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $option['name'] ); ?>" class="eao-engraving-name-input" placeholder="<?php esc_attr_e( 'e.g., Laser Engraving', 'easy-album-orders' ); ?>" required>
                                    </div>

                                    <!-- Upcharge Field -->
                                    <div class="eao-engraving-card__field">
                                        <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                                        <div class="eao-input-prefix">
                                            <span>$</span>
                                            <input type="number" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][upcharge]" value="<?php echo esc_attr( $option['upcharge'] ); ?>" step="0.01" min="0">
                                        </div>
                                    </div>

                                    <!-- Character Limit Field -->
                                    <div class="eao-engraving-card__field">
                                        <label><?php esc_html_e( 'Char Limit', 'easy-album-orders' ); ?></label>
                                        <input type="number" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][character_limit]" value="<?php echo esc_attr( $option['character_limit'] ); ?>" min="0" placeholder="0 = ∞">
                                    </div>

                                    <!-- Fonts Field -->
                                    <div class="eao-engraving-card__field eao-engraving-card__field--grow">
                                        <label><?php esc_html_e( 'Fonts', 'easy-album-orders' ); ?></label>
                                        <input type="text" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][fonts]" value="<?php echo esc_attr( $option['fonts'] ); ?>" placeholder="<?php esc_attr_e( 'Comma-separated font names', 'easy-album-orders' ); ?>">
                                    </div>

                                    <!-- Delete Button -->
                                    <button type="button" class="eao-engraving-card__delete" title="<?php esc_attr_e( 'Delete', 'easy-album-orders' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ( empty( $engraving_options ) ) : ?>
                <div class="eao-empty-state">
                    <div class="eao-empty-state__icon">
                        <span class="dashicons dashicons-edit"></span>
                    </div>
                    <p class="eao-empty-state__text"><?php esc_html_e( 'No engraving methods added yet. Click "Add Method" to get started.', 'easy-album-orders' ); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Emails Tab -->
        <?php $email_settings = get_option( 'eao_email_settings', array() ); ?>
        <div id="emails" class="eao-tab-content">
            <div class="eao-tab-header">
                <div class="eao-tab-header__text">
                    <h2><?php esc_html_e( 'Email Settings', 'easy-album-orders' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Configure email notifications for orders.', 'easy-album-orders' ); ?>
                    </p>
                </div>
            </div>

            <div class="eao-settings-grid">
                <!-- Master Email Toggle -->
                <div class="eao-settings-card">
                    <h3><?php esc_html_e( 'Notifications', 'easy-album-orders' ); ?></h3>
                    <div class="eao-settings-card__body">
                        <label class="eao-toggle eao-toggle--block">
                            <input type="checkbox" name="eao_email_settings[email_notifications]" value="1" <?php checked( ! isset( $email_settings['email_notifications'] ) || ! empty( $email_settings['email_notifications'] ) ); ?>>
                            <span class="eao-toggle__label"><?php esc_html_e( 'Enable email notifications', 'easy-album-orders' ); ?></span>
                        </label>
                        <p class="description"><?php esc_html_e( 'Master toggle for all email notifications. When disabled, no emails will be sent.', 'easy-album-orders' ); ?></p>
                        <div class="eao-field" style="margin-top: 16px;">
                            <label for="eao_admin_email"><?php esc_html_e( 'Admin Email', 'easy-album-orders' ); ?></label>
                            <input type="email" id="eao_admin_email" name="eao_email_settings[admin_email]" value="<?php echo esc_attr( isset( $email_settings['admin_email'] ) ? $email_settings['admin_email'] : get_option( 'admin_email' ) ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Email address for order notifications (New Order Alert).', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Sender Settings -->
                <div class="eao-settings-card">
                    <h3><?php esc_html_e( 'Email Sender', 'easy-album-orders' ); ?></h3>
                    <div class="eao-settings-card__body">
                        <div class="eao-field">
                            <label for="eao_from_name"><?php esc_html_e( 'From Name', 'easy-album-orders' ); ?></label>
                            <input type="text" id="eao_from_name" name="eao_email_settings[from_name]" value="<?php echo esc_attr( isset( $email_settings['from_name'] ) ? $email_settings['from_name'] : get_bloginfo( 'name' ) ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'The name that appears in the "From" field.', 'easy-album-orders' ); ?></p>
                        </div>
                        <div class="eao-field">
                            <label for="eao_from_email"><?php esc_html_e( 'From Email', 'easy-album-orders' ); ?></label>
                            <input type="email" id="eao_from_email" name="eao_email_settings[from_email]" value="<?php echo esc_attr( isset( $email_settings['from_email'] ) ? $email_settings['from_email'] : get_option( 'admin_email' ) ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'The email address that sends the notifications.', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Email Branding -->
                <div class="eao-settings-card">
                    <h3><?php esc_html_e( 'Email Branding', 'easy-album-orders' ); ?></h3>
                    <div class="eao-settings-card__body">
                        <div class="eao-field">
                            <label for="eao_email_logo"><?php esc_html_e( 'Logo URL', 'easy-album-orders' ); ?></label>
                            <input type="url" id="eao_email_logo" name="eao_email_settings[logo_url]" value="<?php echo esc_attr( isset( $email_settings['logo_url'] ) ? $email_settings['logo_url'] : '' ); ?>" placeholder="https://example.com/logo.png">
                            <p class="description"><?php esc_html_e( 'URL to your logo (appears in email header). Leave empty to show site name.', 'easy-album-orders' ); ?></p>
                        </div>
                        <div class="eao-field">
                            <label for="eao_accent_color"><?php esc_html_e( 'Accent Color', 'easy-album-orders' ); ?></label>
                            <div class="eao-color-picker-wrap">
                                <input type="color" id="eao_accent_color" name="eao_email_settings[accent_color]" value="<?php echo esc_attr( isset( $email_settings['accent_color'] ) ? $email_settings['accent_color'] : '#e67e22' ); ?>">
                                <span class="eao-color-hex"><?php echo esc_html( isset( $email_settings['accent_color'] ) ? $email_settings['accent_color'] : '#e67e22' ); ?></span>
                            </div>
                            <p class="description"><?php esc_html_e( 'Primary color used in email templates.', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Order Confirmation Email -->
                <div class="eao-settings-card eao-settings-card--full">
                    <h3>
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php esc_html_e( 'Order Confirmation Email', 'easy-album-orders' ); ?>
                        <span class="eao-badge eao-badge--customer"><?php esc_html_e( 'To Customer', 'easy-album-orders' ); ?></span>
                    </h3>
                    <div class="eao-settings-card__body">
                        <p class="eao-settings-card__description"><?php esc_html_e( 'Sent to the customer when they complete checkout.', 'easy-album-orders' ); ?></p>
                        <div class="eao-email-settings-row">
                            <label class="eao-toggle eao-toggle--block">
                                <input type="checkbox" name="eao_email_settings[enable_order_confirmation]" value="1" <?php checked( ! isset( $email_settings['enable_order_confirmation'] ) || ! empty( $email_settings['enable_order_confirmation'] ) ); ?>>
                                <span class="eao-toggle__label"><?php esc_html_e( 'Enable this email', 'easy-album-orders' ); ?></span>
                            </label>
                            <button type="button" class="button eao-email-preview-btn" data-email-type="order_confirmation">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php esc_html_e( 'Preview Email', 'easy-album-orders' ); ?>
                            </button>
                        </div>
                        <div class="eao-field">
                            <label><?php esc_html_e( 'Subject Line', 'easy-album-orders' ); ?></label>
                            <input type="text" name="eao_email_settings[order_confirmation_subject]" value="<?php echo esc_attr( isset( $email_settings['order_confirmation_subject'] ) ? $email_settings['order_confirmation_subject'] : __( 'Your Album Order Confirmation', 'easy-album-orders' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Available placeholders: {customer_name}, {album_title}', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- New Order Alert Email -->
                <div class="eao-settings-card eao-settings-card--full">
                    <h3>
                        <span class="dashicons dashicons-megaphone"></span>
                        <?php esc_html_e( 'New Order Alert', 'easy-album-orders' ); ?>
                        <span class="eao-badge eao-badge--admin"><?php esc_html_e( 'To Admin', 'easy-album-orders' ); ?></span>
                    </h3>
                    <div class="eao-settings-card__body">
                        <p class="eao-settings-card__description"><?php esc_html_e( 'Sent to you when a customer places an order.', 'easy-album-orders' ); ?></p>
                        <div class="eao-email-settings-row">
                            <label class="eao-toggle eao-toggle--block">
                                <input type="checkbox" name="eao_email_settings[enable_new_order_alert]" value="1" <?php checked( ! isset( $email_settings['enable_new_order_alert'] ) || ! empty( $email_settings['enable_new_order_alert'] ) ); ?>>
                                <span class="eao-toggle__label"><?php esc_html_e( 'Enable this email', 'easy-album-orders' ); ?></span>
                            </label>
                            <button type="button" class="button eao-email-preview-btn" data-email-type="new_order_alert">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php esc_html_e( 'Preview Email', 'easy-album-orders' ); ?>
                            </button>
                        </div>
                        <div class="eao-field">
                            <label><?php esc_html_e( 'Subject Line', 'easy-album-orders' ); ?></label>
                            <input type="text" name="eao_email_settings[new_order_alert_subject]" value="<?php echo esc_attr( isset( $email_settings['new_order_alert_subject'] ) ? $email_settings['new_order_alert_subject'] : __( 'New Album Order Received', 'easy-album-orders' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Available placeholders: {customer_name}, {album_title}', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Shipped Notification Email -->
                <div class="eao-settings-card eao-settings-card--full">
                    <h3>
                        <span class="dashicons dashicons-airplane"></span>
                        <?php esc_html_e( 'Shipped Notification', 'easy-album-orders' ); ?>
                        <span class="eao-badge eao-badge--customer"><?php esc_html_e( 'To Customer', 'easy-album-orders' ); ?></span>
                    </h3>
                    <div class="eao-settings-card__body">
                        <p class="eao-settings-card__description"><?php esc_html_e( 'Sent to the customer when you mark an album as shipped.', 'easy-album-orders' ); ?></p>
                        <div class="eao-email-settings-row">
                            <label class="eao-toggle eao-toggle--block">
                                <input type="checkbox" name="eao_email_settings[enable_shipped_notification]" value="1" <?php checked( ! isset( $email_settings['enable_shipped_notification'] ) || ! empty( $email_settings['enable_shipped_notification'] ) ); ?>>
                                <span class="eao-toggle__label"><?php esc_html_e( 'Enable this email', 'easy-album-orders' ); ?></span>
                            </label>
                            <button type="button" class="button eao-email-preview-btn" data-email-type="shipped_notification">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php esc_html_e( 'Preview Email', 'easy-album-orders' ); ?>
                            </button>
                        </div>
                        <div class="eao-field">
                            <label><?php esc_html_e( 'Subject Line', 'easy-album-orders' ); ?></label>
                            <input type="text" name="eao_email_settings[shipped_notification_subject]" value="<?php echo esc_attr( isset( $email_settings['shipped_notification_subject'] ) ? $email_settings['shipped_notification_subject'] : __( 'Your Album Has Shipped!', 'easy-album-orders' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Available placeholders: {customer_name}, {album_name}', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Cart Reminder Email -->
                <?php
                $next_scheduled = wp_next_scheduled( 'eao_cart_reminder_check' );
                $pending_count  = 0;
                $days_setting   = isset( $email_settings['cart_reminder_days'] ) ? absint( $email_settings['cart_reminder_days'] ) : 3;
                
                // Count pending reminders.
                if ( ! empty( $email_settings['enable_cart_reminder'] ) ) {
                    $date_threshold = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_setting} days" ) );
                    $pending_args   = array(
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
                    $pending_count = count( get_posts( $pending_args ) );
                }
                ?>
                <div class="eao-settings-card eao-settings-card--full">
                    <h3>
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e( 'Cart Reminder', 'easy-album-orders' ); ?>
                        <span class="eao-badge eao-badge--customer"><?php esc_html_e( 'To Customer', 'easy-album-orders' ); ?></span>
                    </h3>
                    <div class="eao-settings-card__body">
                        <p class="eao-settings-card__description"><?php esc_html_e( 'Sent when a customer has items in their cart but hasn\'t checked out.', 'easy-album-orders' ); ?></p>
                        <div class="eao-email-settings-row">
                            <label class="eao-toggle eao-toggle--block">
                                <input type="checkbox" name="eao_email_settings[enable_cart_reminder]" value="1" <?php checked( ! empty( $email_settings['enable_cart_reminder'] ) ); ?>>
                                <span class="eao-toggle__label"><?php esc_html_e( 'Enable this email', 'easy-album-orders' ); ?></span>
                            </label>
                            <button type="button" class="button eao-email-preview-btn" data-email-type="cart_reminder">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php esc_html_e( 'Preview Email', 'easy-album-orders' ); ?>
                            </button>
                        </div>
                        <div class="eao-field">
                            <label><?php esc_html_e( 'Send reminder after (days)', 'easy-album-orders' ); ?></label>
                            <input type="number" name="eao_email_settings[cart_reminder_days]" value="<?php echo esc_attr( $days_setting ); ?>" min="1" max="30" style="width: 80px;">
                            <p class="description"><?php esc_html_e( 'Number of days to wait before sending a reminder.', 'easy-album-orders' ); ?></p>
                        </div>
                        <div class="eao-field">
                            <label><?php esc_html_e( 'Subject Line', 'easy-album-orders' ); ?></label>
                            <input type="text" name="eao_email_settings[cart_reminder_subject]" value="<?php echo esc_attr( isset( $email_settings['cart_reminder_subject'] ) ? $email_settings['cart_reminder_subject'] : __( 'Don\'t forget your album order!', 'easy-album-orders' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Available placeholders: {customer_name}, {album_title}', 'easy-album-orders' ); ?></p>
                        </div>

                        <!-- Cron Status -->
                        <div class="eao-cron-status">
                            <h4><?php esc_html_e( 'Automated Reminders', 'easy-album-orders' ); ?></h4>
                            <div class="eao-cron-status__info">
                                <?php if ( $next_scheduled ) : ?>
                                    <p>
                                        <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                        <?php
                                        printf(
                                            /* translators: %s: next run time */
                                            esc_html__( 'Next automatic check: %s', 'easy-album-orders' ),
                                            '<strong>' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_scheduled + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ) . '</strong>'
                                        );
                                        ?>
                                    </p>
                                <?php else : ?>
                                    <p>
                                        <span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
                                        <?php esc_html_e( 'Cron not scheduled. Try deactivating and reactivating the plugin.', 'easy-album-orders' ); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ( $pending_count > 0 ) : ?>
                                    <p class="eao-cron-status__pending">
                                        <span class="dashicons dashicons-email"></span>
                                        <?php
                                        printf(
                                            /* translators: %d: number of pending reminders */
                                            esc_html( _n( '%d cart item eligible for reminder', '%d cart items eligible for reminders', $pending_count, 'easy-album-orders' ) ),
                                            esc_html( $pending_count )
                                        );
                                        ?>
                                    </p>
                                <?php elseif ( ! empty( $email_settings['enable_cart_reminder'] ) ) : ?>
                                    <p class="eao-cron-status__none">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php esc_html_e( 'No pending cart reminders at this time.', 'easy-album-orders' ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if ( $pending_count > 0 ) : ?>
                                <button type="button" class="button button-secondary eao-send-reminders-btn" id="eao-send-reminders-now">
                                    <span class="dashicons dashicons-email-alt"></span>
                                    <?php esc_html_e( 'Send Reminders Now', 'easy-album-orders' ); ?>
                                </button>
                                <span class="eao-send-reminders-status"></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Tab -->
        <?php
        $stripe_settings = get_option( 'eao_stripe_settings', array() );
        $stripe          = new EAO_Stripe();
        ?>
        <div id="payments" class="eao-tab-content">
            <div class="eao-tab-header">
                <div class="eao-tab-header__text">
                    <h2><?php esc_html_e( 'Payment Settings', 'easy-album-orders' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Configure Stripe payment processing for album orders. Customers will pay during checkout.', 'easy-album-orders' ); ?>
                    </p>
                </div>
            </div>

            <div class="eao-settings-grid">
                <!-- Enable Payments -->
                <div class="eao-settings-card eao-settings-card--full">
                    <h3>
                        <span class="dashicons dashicons-cart"></span>
                        <?php esc_html_e( 'Enable Payments', 'easy-album-orders' ); ?>
                    </h3>
                    <div class="eao-settings-card__body">
                        <label class="eao-toggle eao-toggle--block">
                            <input type="checkbox" name="eao_stripe_settings[enabled]" value="1" <?php checked( ! empty( $stripe_settings['enabled'] ) ); ?>>
                            <span class="eao-toggle__label"><?php esc_html_e( 'Require payment at checkout', 'easy-album-orders' ); ?></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'When enabled, customers must complete payment to finalize their album order. When disabled, orders can be placed without payment.', 'easy-album-orders' ); ?>
                        </p>
                    </div>
                </div>

                <!-- Setup Guide -->
                <div class="eao-settings-card eao-settings-card--full eao-settings-card--guide">
                    <h3>
                        <span class="dashicons dashicons-editor-help"></span>
                        <?php esc_html_e( 'How to Get Your Stripe API Keys', 'easy-album-orders' ); ?>
                    </h3>
                    <div class="eao-settings-card__body">
                        <div class="eao-setup-steps">
                            <div class="eao-setup-step">
                                <span class="eao-setup-step__number">1</span>
                                <div class="eao-setup-step__content">
                                    <strong><?php esc_html_e( 'Go to Stripe Dashboard', 'easy-album-orders' ); ?></strong>
                                    <p><?php esc_html_e( 'Log in to your Stripe account (or create one if you don\'t have one).', 'easy-album-orders' ); ?></p>
                                </div>
                            </div>
                            <div class="eao-setup-step">
                                <span class="eao-setup-step__number">2</span>
                                <div class="eao-setup-step__content">
                                    <strong><?php esc_html_e( 'Navigate to API Keys', 'easy-album-orders' ); ?></strong>
                                    <p><?php esc_html_e( 'Click Developers in the left menu, then API keys.', 'easy-album-orders' ); ?></p>
                                </div>
                            </div>
                            <div class="eao-setup-step">
                                <span class="eao-setup-step__number">3</span>
                                <div class="eao-setup-step__content">
                                    <strong><?php esc_html_e( 'Copy Your Standard Keys', 'easy-album-orders' ); ?></strong>
                                    <p><?php esc_html_e( 'Your Publishable key and Secret key are already displayed. Click to reveal the secret key, then copy both.', 'easy-album-orders' ); ?></p>
                                    <div class="eao-setup-step__note">
                                        <span class="dashicons dashicons-info"></span>
                                        <span><?php esc_html_e( 'You need the Standard keys (pk_test/sk_test or pk_live/sk_live), NOT restricted keys (rk_).', 'easy-album-orders' ); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="eao-setup-buttons">
                            <a href="https://dashboard.stripe.com/test/apikeys" target="_blank" rel="noopener" class="button">
                                <span class="dashicons dashicons-external"></span>
                                <?php esc_html_e( 'Get Test Keys', 'easy-album-orders' ); ?>
                            </a>
                            <a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener" class="button">
                                <span class="dashicons dashicons-external"></span>
                                <?php esc_html_e( 'Get Live Keys', 'easy-album-orders' ); ?>
                            </a>
                        </div>

                        <details class="eao-setup-faq">
                            <summary><?php esc_html_e( 'Why can\'t I use Restricted Keys?', 'easy-album-orders' ); ?></summary>
                            <div class="eao-setup-faq__content">
                                <p><?php esc_html_e( 'Restricted keys (starting with rk_) have limited permissions and may not work correctly with this plugin. The standard Secret key provides the necessary permissions for:', 'easy-album-orders' ); ?></p>
                                <ul>
                                    <li><?php esc_html_e( 'Creating Payment Intents', 'easy-album-orders' ); ?></li>
                                    <li><?php esc_html_e( 'Processing payments', 'easy-album-orders' ); ?></li>
                                    <li><?php esc_html_e( 'Handling refunds', 'easy-album-orders' ); ?></li>
                                    <li><?php esc_html_e( 'Verifying webhooks', 'easy-album-orders' ); ?></li>
                                </ul>
                                <p><strong><?php esc_html_e( 'Important:', 'easy-album-orders' ); ?></strong> <?php esc_html_e( 'If Stripe asks "Are you providing this key to another website?", select "Building your own integration" — because you\'re using the keys on YOUR OWN website, not sharing them with a third party.', 'easy-album-orders' ); ?></p>
                            </div>
                        </details>
                    </div>
                </div>

                <!-- Stripe API Mode -->
                <div class="eao-settings-card eao-settings-card--full">
                    <h3>
                        <span class="dashicons dashicons-admin-network"></span>
                        <?php esc_html_e( 'API Mode', 'easy-album-orders' ); ?>
                    </h3>
                    <div class="eao-settings-card__body">
                        <div class="eao-field">
                            <div class="eao-radio-group eao-radio-group--vertical">
                                <label class="eao-radio">
                                    <input type="radio" name="eao_stripe_settings[mode]" value="test" <?php checked( isset( $stripe_settings['mode'] ) ? $stripe_settings['mode'] : 'test', 'test' ); ?>>
                                    <span>
                                        <strong><?php esc_html_e( 'Test Mode', 'easy-album-orders' ); ?></strong>
                                        <small><?php esc_html_e( 'Use test API keys for development and testing. No real charges will be made.', 'easy-album-orders' ); ?></small>
                                    </span>
                                </label>
                                <label class="eao-radio">
                                    <input type="radio" name="eao_stripe_settings[mode]" value="live" <?php checked( isset( $stripe_settings['mode'] ) ? $stripe_settings['mode'] : 'test', 'live' ); ?>>
                                    <span>
                                        <strong><?php esc_html_e( 'Live Mode', 'easy-album-orders' ); ?></strong>
                                        <small><?php esc_html_e( 'Use live API keys to accept real payments from customers.', 'easy-album-orders' ); ?></small>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test API Keys -->
                <div class="eao-settings-card">
                    <h3>
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e( 'Test API Keys', 'easy-album-orders' ); ?>
                        <span class="eao-badge eao-badge--test"><?php esc_html_e( 'Test', 'easy-album-orders' ); ?></span>
                    </h3>
                    <div class="eao-settings-card__body">
                        <div class="eao-field">
                            <label for="eao_test_publishable_key"><?php esc_html_e( 'Publishable Key', 'easy-album-orders' ); ?></label>
                            <input type="text" id="eao_test_publishable_key" name="eao_stripe_settings[test_publishable_key]" value="<?php echo esc_attr( isset( $stripe_settings['test_publishable_key'] ) ? $stripe_settings['test_publishable_key'] : '' ); ?>" placeholder="pk_test_..." class="regular-text eao-key-input" data-key-type="pk_test">
                            <p class="description"><?php esc_html_e( 'Starts with pk_test_', 'easy-album-orders' ); ?></p>
                        </div>
                        <div class="eao-field">
                            <label for="eao_test_secret_key"><?php esc_html_e( 'Secret Key', 'easy-album-orders' ); ?></label>
                            <input type="password" id="eao_test_secret_key" name="eao_stripe_settings[test_secret_key]" value="<?php echo esc_attr( isset( $stripe_settings['test_secret_key'] ) ? $stripe_settings['test_secret_key'] : '' ); ?>" placeholder="sk_test_..." class="regular-text eao-key-input" autocomplete="off" data-key-type="sk_test">
                            <p class="description"><?php esc_html_e( 'Starts with sk_test_ — Keep this secret!', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Live API Keys -->
                <div class="eao-settings-card">
                    <h3>
                        <span class="dashicons dashicons-lock"></span>
                        <?php esc_html_e( 'Live API Keys', 'easy-album-orders' ); ?>
                        <span class="eao-badge eao-badge--live"><?php esc_html_e( 'Live', 'easy-album-orders' ); ?></span>
                    </h3>
                    <div class="eao-settings-card__body">
                        <div class="eao-field">
                            <label for="eao_live_publishable_key"><?php esc_html_e( 'Publishable Key', 'easy-album-orders' ); ?></label>
                            <input type="text" id="eao_live_publishable_key" name="eao_stripe_settings[live_publishable_key]" value="<?php echo esc_attr( isset( $stripe_settings['live_publishable_key'] ) ? $stripe_settings['live_publishable_key'] : '' ); ?>" placeholder="pk_live_..." class="regular-text eao-key-input" data-key-type="pk_live">
                            <p class="description"><?php esc_html_e( 'Starts with pk_live_', 'easy-album-orders' ); ?></p>
                        </div>
                        <div class="eao-field">
                            <label for="eao_live_secret_key"><?php esc_html_e( 'Secret Key', 'easy-album-orders' ); ?></label>
                            <input type="password" id="eao_live_secret_key" name="eao_stripe_settings[live_secret_key]" value="<?php echo esc_attr( isset( $stripe_settings['live_secret_key'] ) ? $stripe_settings['live_secret_key'] : '' ); ?>" placeholder="sk_live_..." class="regular-text eao-key-input" autocomplete="off" data-key-type="sk_live">
                            <p class="description"><?php esc_html_e( 'Starts with sk_live_ — Keep this secret!', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Webhook Configuration -->
                <div class="eao-settings-card eao-settings-card--full">
                    <h3>
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e( 'Webhook Configuration', 'easy-album-orders' ); ?>
                        <span class="eao-badge eao-badge--optional"><?php esc_html_e( 'Recommended', 'easy-album-orders' ); ?></span>
                    </h3>
                    <div class="eao-settings-card__body">
                        <p class="eao-settings-card__description">
                            <?php esc_html_e( 'Webhooks allow Stripe to notify your site about payment events (success, failure, refunds). Configure in Stripe Dashboard → Developers → Webhooks.', 'easy-album-orders' ); ?>
                        </p>

                        <div class="eao-field">
                            <label><?php esc_html_e( 'Webhook Endpoint URL', 'easy-album-orders' ); ?></label>
                            <div class="eao-webhook-url-field">
                                <input type="text" id="eao_webhook_url" value="<?php echo esc_attr( EAO_Stripe::get_webhook_url() ); ?>" readonly class="regular-text">
                                <button type="button" class="button eao-copy-webhook-url" data-copy-target="#eao_webhook_url">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    <?php esc_html_e( 'Copy', 'easy-album-orders' ); ?>
                                </button>
                            </div>
                            <p class="description"><?php esc_html_e( 'Add this URL as an endpoint in your Stripe Dashboard.', 'easy-album-orders' ); ?></p>
                        </div>

                        <div class="eao-field">
                            <label><?php esc_html_e( 'Webhook Setup Instructions', 'easy-album-orders' ); ?></label>
                            <ol class="eao-webhook-steps">
                                <li><?php esc_html_e( 'Select "Your account" (not Connected accounts)', 'easy-album-orders' ); ?></li>
                                <li><?php esc_html_e( 'For API version, select "your current version"', 'easy-album-orders' ); ?></li>
                                <li><?php esc_html_e( 'Add the events listed below', 'easy-album-orders' ); ?></li>
                                <li><?php esc_html_e( 'Paste the Endpoint URL above', 'easy-album-orders' ); ?></li>
                                <li><?php esc_html_e( 'Copy the Signing Secret and paste it below', 'easy-album-orders' ); ?></li>
                            </ol>
                        </div>

                        <div class="eao-field">
                            <label><?php esc_html_e( 'Required Events', 'easy-album-orders' ); ?></label>
                            <ul class="eao-webhook-events">
                                <li><code>payment_intent.succeeded</code> — <?php esc_html_e( 'Payment completed successfully', 'easy-album-orders' ); ?></li>
                                <li><code>payment_intent.payment_failed</code> — <?php esc_html_e( 'Payment failed', 'easy-album-orders' ); ?></li>
                                <li><code>charge.refunded</code> — <?php esc_html_e( 'Payment was refunded', 'easy-album-orders' ); ?></li>
                            </ul>
                        </div>

                        <div class="eao-field">
                            <label for="eao_webhook_secret"><?php esc_html_e( 'Webhook Signing Secret', 'easy-album-orders' ); ?></label>
                            <input type="password" id="eao_webhook_secret" name="eao_stripe_settings[webhook_secret]" value="<?php echo esc_attr( isset( $stripe_settings['webhook_secret'] ) ? $stripe_settings['webhook_secret'] : '' ); ?>" placeholder="whsec_..." class="regular-text" autocomplete="off">
                            <p class="description"><?php esc_html_e( 'Found in Stripe Dashboard after creating the webhook endpoint. Starts with whsec_', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Payment Settings -->
                <div class="eao-settings-card eao-settings-card--full">
                    <h3>
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php esc_html_e( 'Payment Settings', 'easy-album-orders' ); ?>
                    </h3>
                    <div class="eao-settings-card__body">
                        <div class="eao-field">
                            <label for="eao_statement_descriptor"><?php esc_html_e( 'Statement Descriptor', 'easy-album-orders' ); ?></label>
                            <input type="text" id="eao_statement_descriptor" name="eao_stripe_settings[statement_descriptor]" value="<?php echo esc_attr( isset( $stripe_settings['statement_descriptor'] ) ? $stripe_settings['statement_descriptor'] : 'Album Order' ); ?>" maxlength="22" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Appears on customer\'s bank/card statement (max 22 characters, letters, numbers, spaces, hyphens only).', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Connection Status -->
                <?php if ( ! empty( $stripe_settings['enabled'] ) ) : ?>
                <div class="eao-settings-card eao-settings-card--full">
                    <h3>
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e( 'Connection Status', 'easy-album-orders' ); ?>
                    </h3>
                    <div class="eao-settings-card__body">
                        <?php if ( $stripe->is_enabled() ) : ?>
                            <div class="eao-stripe-status eao-stripe-status--connected">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span>
                                    <?php
                                    printf(
                                        /* translators: %s: Test or Live */
                                        esc_html__( 'Stripe is connected in %s mode', 'easy-album-orders' ),
                                        $stripe->is_test_mode() ? '<strong>' . esc_html__( 'Test', 'easy-album-orders' ) . '</strong>' : '<strong>' . esc_html__( 'Live', 'easy-album-orders' ) . '</strong>'
                                    );
                                    ?>
                                </span>
                            </div>
                            <?php if ( $stripe->is_test_mode() ) : ?>
                                <p class="description eao-stripe-test-notice">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php esc_html_e( 'Test mode is active. Use test card 4242 4242 4242 4242 with any future date and CVC.', 'easy-album-orders' ); ?>
                                </p>
                            <?php endif; ?>
                        <?php else : ?>
                            <div class="eao-stripe-status eao-stripe-status--disconnected">
                                <span class="dashicons dashicons-warning"></span>
                                <span><?php esc_html_e( 'Stripe API keys are missing or invalid. Please enter your API keys above.', 'easy-album-orders' ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- General Tab -->
        <div id="general" class="eao-tab-content">
            <div class="eao-tab-header">
                <div class="eao-tab-header__text">
                    <h2><?php esc_html_e( 'General Settings', 'easy-album-orders' ); ?></h2>
                    <p class="description">
                        <?php esc_html_e( 'Configure global settings for the plugin.', 'easy-album-orders' ); ?>
                    </p>
                </div>
            </div>

            <div class="eao-settings-grid">
                <!-- Brand Settings Card -->
                <div class="eao-settings-card">
                    <h3><?php esc_html_e( 'Brand', 'easy-album-orders' ); ?></h3>
                    <div class="eao-settings-card__body">
                        <div class="eao-field">
                            <label for="eao_brand_color"><?php esc_html_e( 'Primary Brand Color', 'easy-album-orders' ); ?></label>
                            <div class="eao-color-field">
                                <input 
                                    type="color" 
                                    id="eao_brand_color" 
                                    name="eao_general_settings[brand_color]" 
                                    value="<?php echo esc_attr( isset( $general_settings['brand_color'] ) ? $general_settings['brand_color'] : '#e67e22' ); ?>"
                                    class="eao-color-input"
                                >
                                <input 
                                    type="text" 
                                    id="eao_brand_color_hex" 
                                    class="eao-color-hex-input" 
                                    value="<?php echo esc_attr( isset( $general_settings['brand_color'] ) ? $general_settings['brand_color'] : '#e67e22' ); ?>"
                                    pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$"
                                    placeholder="#e67e22"
                                >
                                <button type="button" class="button eao-color-reset" data-default="#e67e22">
                                    <?php esc_html_e( 'Reset', 'easy-album-orders' ); ?>
                                </button>
                            </div>
                            <p class="description"><?php esc_html_e( 'This color is used for buttons, links, and accent elements throughout the client album interface.', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>

                <div class="eao-settings-card">
                    <h3><?php esc_html_e( 'Currency', 'easy-album-orders' ); ?></h3>
                    <div class="eao-settings-card__body">
                        <div class="eao-field-row">
                            <div class="eao-field">
                                <label for="eao_currency"><?php esc_html_e( 'Currency Code', 'easy-album-orders' ); ?></label>
                                <input type="text" id="eao_currency" name="eao_general_settings[currency]" value="<?php echo esc_attr( isset( $general_settings['currency'] ) ? $general_settings['currency'] : 'USD' ); ?>" placeholder="USD">
                            </div>
                            <div class="eao-field">
                                <label for="eao_currency_symbol"><?php esc_html_e( 'Symbol', 'easy-album-orders' ); ?></label>
                                <input type="text" id="eao_currency_symbol" name="eao_general_settings[currency_symbol]" value="<?php echo esc_attr( isset( $general_settings['currency_symbol'] ) ? $general_settings['currency_symbol'] : '$' ); ?>" placeholder="$">
                            </div>
                        </div>
                        <div class="eao-field">
                            <label><?php esc_html_e( 'Symbol Position', 'easy-album-orders' ); ?></label>
                            <div class="eao-radio-group">
                                <label class="eao-radio">
                                    <input type="radio" name="eao_general_settings[currency_position]" value="before" <?php checked( isset( $general_settings['currency_position'] ) ? $general_settings['currency_position'] : 'before', 'before' ); ?>>
                                    <span><?php esc_html_e( 'Before ($100)', 'easy-album-orders' ); ?></span>
                                </label>
                                <label class="eao-radio">
                                    <input type="radio" name="eao_general_settings[currency_position]" value="after" <?php checked( isset( $general_settings['currency_position'] ) ? $general_settings['currency_position'] : 'before', 'after' ); ?>>
                                    <span><?php esc_html_e( 'After (100$)', 'easy-album-orders' ); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

            </div><!-- .eao-options-content -->
        </div><!-- .eao-options-layout -->

        <div class="eao-form-footer">
            <input type="submit" name="eao_save_options" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Changes', 'easy-album-orders' ); ?>">
        </div>
    </form>
</div>

<!-- Color Edit Modal -->
<div id="eao-color-modal" class="eao-modal eao-modal--medium" style="display: none;">
    <div class="eao-modal__backdrop"></div>
    <div class="eao-modal__content">
        <div class="eao-modal__header">
            <h3><?php esc_html_e( 'Edit Color', 'easy-album-orders' ); ?></h3>
            <button type="button" class="eao-modal__close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="eao-modal__body">
            <div class="eao-field">
                <label><?php esc_html_e( 'Color Name', 'easy-album-orders' ); ?></label>
                <input type="text" id="eao-modal-color-name" placeholder="<?php esc_attr_e( 'e.g., Navy Blue, Distressed Brown', 'easy-album-orders' ); ?>">
            </div>
            <div class="eao-field">
                <label><?php esc_html_e( 'Type', 'easy-album-orders' ); ?></label>
                <div class="eao-radio-group">
                    <label class="eao-radio">
                        <input type="radio" name="eao_modal_color_type" value="solid" checked>
                        <span><?php esc_html_e( 'Solid Color', 'easy-album-orders' ); ?></span>
                    </label>
                    <label class="eao-radio">
                        <input type="radio" name="eao_modal_color_type" value="texture">
                        <span><?php esc_html_e( 'Texture/Pattern', 'easy-album-orders' ); ?></span>
                    </label>
                </div>
            </div>

            <!-- Solid Color Picker -->
            <div class="eao-field eao-color-picker-field" id="eao-solid-color-section">
                <label><?php esc_html_e( 'Color', 'easy-album-orders' ); ?></label>
                <div class="eao-color-picker-wrap">
                    <input type="color" id="eao-modal-color-value" value="#000000">
                    <span class="eao-color-hex" id="eao-modal-color-hex">#000000</span>
                </div>
            </div>

            <!-- Texture/Pattern Section -->
            <div class="eao-texture-section" id="eao-texture-section" style="display: none;">
                <div class="eao-field">
                    <label><?php esc_html_e( 'Texture Image', 'easy-album-orders' ); ?></label>
                    <p class="description"><?php esc_html_e( 'Upload an image showing the material texture, then select a region for the color swatch.', 'easy-album-orders' ); ?></p>
                    <div class="eao-texture-upload">
                        <input type="hidden" id="eao-modal-texture-image-id" value="">
                        <input type="hidden" id="eao-modal-texture-image-url" value="">
                        <div class="eao-texture-upload__preview" id="eao-texture-preview">
                            <span class="eao-texture-upload__placeholder">
                                <span class="dashicons dashicons-format-image"></span>
                                <span><?php esc_html_e( 'Click to upload texture image', 'easy-album-orders' ); ?></span>
                            </span>
                        </div>
                        <div class="eao-texture-upload__actions">
                            <button type="button" class="button eao-upload-texture"><?php esc_html_e( 'Upload Image', 'easy-album-orders' ); ?></button>
                            <button type="button" class="button eao-remove-texture" style="display: none;"><?php esc_html_e( 'Remove', 'easy-album-orders' ); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Region Selector -->
                <div class="eao-field eao-region-selector-field" id="eao-region-selector-container" style="display: none;">
                    <label><?php esc_html_e( 'Select Swatch Region', 'easy-album-orders' ); ?></label>
                    <p class="description"><?php esc_html_e( 'Click on the image to place a circular selection. This area will be shown as the color swatch.', 'easy-album-orders' ); ?></p>
                    <div class="eao-region-selector">
                        <div class="eao-region-selector__image-container" id="eao-region-image-container">
                            <img id="eao-region-image" src="" alt="">
                            <div class="eao-region-selector__selection" id="eao-region-selection"></div>
                        </div>
                        <input type="hidden" id="eao-modal-texture-region" value="">
                    </div>
                    <div class="eao-region-preview">
                        <span class="eao-region-preview__label"><?php esc_html_e( 'Swatch Preview:', 'easy-album-orders' ); ?></span>
                        <div class="eao-region-preview__swatch" id="eao-region-preview-swatch"></div>
                    </div>
                </div>
            </div>

            <!-- Preview Image (for both solid and texture) -->
            <div class="eao-field eao-preview-image-field">
                <label><?php esc_html_e( 'Preview Image (Optional)', 'easy-album-orders' ); ?></label>
                <p class="description"><?php esc_html_e( 'Upload a larger preview image that clients will see when selecting this color on the front-end.', 'easy-album-orders' ); ?></p>
                <div class="eao-preview-image-upload">
                    <input type="hidden" id="eao-modal-preview-image-id" value="">
                    <div class="eao-preview-image-upload__preview" id="eao-preview-image-container">
                        <span class="eao-preview-image-upload__placeholder">
                            <span class="dashicons dashicons-format-image"></span>
                        </span>
                    </div>
                    <div class="eao-preview-image-upload__actions">
                        <button type="button" class="button eao-upload-preview-image"><?php esc_html_e( 'Upload Preview', 'easy-album-orders' ); ?></button>
                        <button type="button" class="button eao-remove-preview-image" style="display: none;"><?php esc_html_e( 'Remove', 'easy-album-orders' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="eao-modal__footer">
            <button type="button" class="button eao-modal__cancel"><?php esc_html_e( 'Cancel', 'easy-album-orders' ); ?></button>
            <button type="button" class="button button-primary eao-modal__save"><?php esc_html_e( 'Save Color', 'easy-album-orders' ); ?></button>
        </div>
    </div>
</div>

<!-- Templates for JavaScript -->
<script type="text/html" id="tmpl-eao-material-card">
    <div class="eao-material-card" data-index="{{data.index}}">
        <input type="hidden" name="eao_materials[{{data.index}}][id]" value="{{data.id}}">
        
        <div class="eao-sortable-handle" title="<?php esc_attr_e( 'Drag to reorder', 'easy-album-orders' ); ?>">
            <?php EAO_Icons::render( 'grip-vertical', array( 'size' => 20 ) ); ?>
        </div>
        <div class="eao-material-card__body">
            <!-- Column 1: Image -->
            <div class="eao-material-card__image">
                <div class="eao-image-upload">
                    <input type="hidden" name="eao_materials[{{data.index}}][image_id]" value="" class="eao-image-id">
                    <div class="eao-image-preview eao-image-preview--large"></div>
                    <div class="eao-image-actions">
                        <button type="button" class="button eao-upload-image"><?php esc_html_e( 'Upload', 'easy-album-orders' ); ?></button>
                        <button type="button" class="button eao-remove-image" style="display:none;"><?php esc_html_e( 'Remove', 'easy-album-orders' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- Column 2: Content -->
            <div class="eao-material-card__content">
                <!-- Header: Stacked label fields -->
                <div class="eao-material-card__header">
                    <!-- Row 1: Material Name + Delete -->
                    <div class="eao-material-card__name-row">
                        <div class="eao-material-card__field eao-material-card__field--name">
                            <label><?php esc_html_e( 'Material Name', 'easy-album-orders' ); ?></label>
                            <input type="text" name="eao_materials[{{data.index}}][name]" value="" class="eao-material-name-input" required>
                        </div>
                        <button type="button" class="eao-material-card__delete" title="<?php esc_attr_e( 'Delete Material', 'easy-album-orders' ); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                    <!-- Row 2: Upcharge + Allow Engraving -->
                    <div class="eao-material-card__meta-row">
                        <div class="eao-material-card__field">
                            <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                            <div class="eao-input-prefix">
                                <span>$</span>
                                <input type="number" name="eao_materials[{{data.index}}][upcharge]" value="0" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="eao-material-card__field eao-material-card__field--toggle">
                            <label class="eao-toggle">
                                <span class="eao-toggle__label"><?php esc_html_e( 'Allow Engraving', 'easy-album-orders' ); ?></span>
                                <input type="checkbox" name="eao_materials[{{data.index}}][allow_engraving]" value="1">
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Colors Section -->
                <div class="eao-material-card__colors">
                    <div class="eao-colors-header">
                        <span class="eao-colors-label"><?php esc_html_e( 'Colors', 'easy-album-orders' ); ?></span>
                    </div>
                    <div class="eao-colors-grid">
                        <button type="button" class="eao-color-swatch eao-color-swatch--add eao-add-color" title="<?php esc_attr_e( 'Add Color', 'easy-album-orders' ); ?>">
                            <div class="eao-color-swatch__circle eao-color-swatch__circle--add">
                                <span class="dashicons dashicons-plus"></span>
                            </div>
                            <span class="eao-color-swatch__name"><?php esc_html_e( 'Add', 'easy-album-orders' ); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="text/html" id="tmpl-eao-color-swatch">
    <div class="eao-color-swatch" data-color-index="{{data.colorIndex}}">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][id]" value="{{data.id}}">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][type]" value="{{data.type}}" class="eao-color-type-input">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][color_value]" value="{{data.colorValue}}" class="eao-color-value-input">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][name]" value="{{data.name}}" class="eao-color-name-input">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][texture_image_id]" value="{{data.textureImageId}}" class="eao-color-texture-image-id-input">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][texture_image_url]" value="{{data.textureImageUrl}}" class="eao-color-texture-image-url-input">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][texture_region]" value="{{data.textureRegion}}" class="eao-color-texture-region-input">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][preview_image_id]" value="{{data.previewImageId}}" class="eao-color-preview-image-id-input">
        
        <div class="eao-color-swatch__circle" style="{{data.swatchStyle}}" title="{{data.name}}">
            <# if (data.type === 'texture' && !data.textureImageUrl) { #>
                <span class="eao-color-swatch__texture-icon">
                    <span class="dashicons dashicons-format-image"></span>
                </span>
            <# } #>
        </div>
        <span class="eao-color-swatch__name">{{data.name}}</span>
        <# if (data.previewImageId) { #>
            <span class="eao-color-swatch__has-preview" title="<?php esc_attr_e( 'Has preview image', 'easy-album-orders' ); ?>">
                <span class="dashicons dashicons-visibility"></span>
            </span>
        <# } #>
        <button type="button" class="eao-color-swatch__edit" title="<?php esc_attr_e( 'Edit', 'easy-album-orders' ); ?>">
            <span class="dashicons dashicons-edit"></span>
        </button>
        <button type="button" class="eao-color-swatch__delete" title="<?php esc_attr_e( 'Delete', 'easy-album-orders' ); ?>">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
</script>

<script type="text/html" id="tmpl-eao-size-card">
    <div class="eao-size-card" data-index="{{data.index}}">
        <input type="hidden" name="eao_sizes[{{data.index}}][id]" value="{{data.id}}">
        <div class="eao-sortable-handle" title="<?php esc_attr_e( 'Drag to reorder', 'easy-album-orders' ); ?>">
            <?php EAO_Icons::render( 'grip-vertical', array( 'size' => 18 ) ); ?>
        </div>
        <div class="eao-size-card__image">
            <div class="eao-image-upload">
                <input type="hidden" name="eao_sizes[{{data.index}}][image_id]" value="" class="eao-image-id">
                <div class="eao-image-preview eao-image-preview--small"></div>
                <button type="button" class="eao-upload-image eao-size-card__upload" title="<?php esc_attr_e( 'Upload Image', 'easy-album-orders' ); ?>">
                    <span class="dashicons dashicons-camera"></span>
                </button>
            </div>
        </div>
        <div class="eao-size-card__main">
            <div class="eao-size-card__field">
                <label><?php esc_html_e( 'Size Name', 'easy-album-orders' ); ?></label>
                <input type="text" name="eao_sizes[{{data.index}}][name]" value="" class="eao-size-name-input" required>
            </div>
            <div class="eao-size-card__field">
                <label><?php esc_html_e( 'Dimensions', 'easy-album-orders' ); ?></label>
                <input type="text" name="eao_sizes[{{data.index}}][dimensions]" value="" placeholder="<?php esc_attr_e( 'e.g., 10" × 10"', 'easy-album-orders' ); ?>">
            </div>
            <div class="eao-size-card__field">
                <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                <div class="eao-input-prefix">
                    <span>$</span>
                    <input type="number" name="eao_sizes[{{data.index}}][upcharge]" value="0" step="0.01" min="0">
                </div>
            </div>
        </div>
        <button type="button" class="eao-size-card__delete" title="<?php esc_attr_e( 'Delete Size', 'easy-album-orders' ); ?>">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
</script>

<script type="text/html" id="tmpl-eao-engraving-card">
    <div class="eao-engraving-card" data-index="{{data.index}}">
        <input type="hidden" name="eao_engraving_options[{{data.index}}][id]" value="{{data.id}}">
        
        <div class="eao-sortable-handle" title="<?php esc_attr_e( 'Drag to reorder', 'easy-album-orders' ); ?>">
            <?php EAO_Icons::render( 'grip-vertical', array( 'size' => 18 ) ); ?>
        </div>
        <div class="eao-engraving-card__wrapper">
            <div class="eao-engraving-card__content">
                <!-- Thumbnail Image -->
                <div class="eao-engraving-card__image">
                    <div class="eao-image-upload">
                        <input type="hidden" name="eao_engraving_options[{{data.index}}][image_id]" value="" class="eao-image-id">
                        <div class="eao-image-preview eao-image-preview--small"></div>
                        <button type="button" class="eao-upload-image eao-engraving-card__upload" title="<?php esc_attr_e( 'Upload Image', 'easy-album-orders' ); ?>">
                            <span class="dashicons dashicons-camera"></span>
                        </button>
                    </div>
                </div>

                <!-- Name Field -->
                <div class="eao-engraving-card__name">
                    <label><?php esc_html_e( 'Method Name', 'easy-album-orders' ); ?></label>
                    <input type="text" name="eao_engraving_options[{{data.index}}][name]" value="" class="eao-engraving-name-input" placeholder="<?php esc_attr_e( 'e.g., Laser Engraving', 'easy-album-orders' ); ?>" required>
                </div>

                <!-- Upcharge Field -->
                <div class="eao-engraving-card__field">
                    <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                    <div class="eao-input-prefix">
                        <span>$</span>
                        <input type="number" name="eao_engraving_options[{{data.index}}][upcharge]" value="0" step="0.01" min="0">
                    </div>
                </div>

                <!-- Character Limit Field -->
                <div class="eao-engraving-card__field">
                    <label><?php esc_html_e( 'Char Limit', 'easy-album-orders' ); ?></label>
                    <input type="number" name="eao_engraving_options[{{data.index}}][character_limit]" value="" min="0" placeholder="0 = ∞">
                </div>

                <!-- Fonts Field -->
                <div class="eao-engraving-card__field eao-engraving-card__field--grow">
                    <label><?php esc_html_e( 'Fonts', 'easy-album-orders' ); ?></label>
                    <input type="text" name="eao_engraving_options[{{data.index}}][fonts]" value="" placeholder="<?php esc_attr_e( 'Comma-separated font names', 'easy-album-orders' ); ?>">
                </div>

                <!-- Delete Button -->
                <button type="button" class="eao-engraving-card__delete" title="<?php esc_attr_e( 'Delete', 'easy-album-orders' ); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
    </div>
</script>

<!-- Email Preview Modal -->
<div id="eao-email-preview-modal" class="eao-modal eao-modal--large" style="display: none;">
    <div class="eao-modal__backdrop"></div>
    <div class="eao-modal__content eao-modal__content--email-preview">
        <div class="eao-modal__header">
            <h3 id="eao-email-preview-title"><?php esc_html_e( 'Email Preview', 'easy-album-orders' ); ?></h3>
            <button type="button" class="eao-modal__close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="eao-modal__body">
            <div class="eao-email-preview-info">
                <p><strong><?php esc_html_e( 'Subject:', 'easy-album-orders' ); ?></strong> <span id="eao-email-preview-subject"></span></p>
                <p class="description"><?php esc_html_e( 'This preview uses sample data. Actual emails will contain real order information.', 'easy-album-orders' ); ?></p>
            </div>
            <div class="eao-email-preview-frame-container">
                <iframe id="eao-email-preview-frame" title="<?php esc_attr_e( 'Email Preview', 'easy-album-orders' ); ?>"></iframe>
            </div>
        </div>
        <div class="eao-modal__footer">
            <button type="button" class="button eao-modal__close"><?php esc_html_e( 'Close', 'easy-album-orders' ); ?></button>
        </div>
    </div>
</div>
