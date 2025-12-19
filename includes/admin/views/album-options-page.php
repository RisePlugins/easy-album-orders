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

        <nav class="nav-tab-wrapper eao-tabs">
            <a href="#materials" class="nav-tab nav-tab-active" data-tab="materials">
                <?php esc_html_e( 'Materials', 'easy-album-orders' ); ?>
            </a>
            <a href="#sizes" class="nav-tab" data-tab="sizes">
                <?php esc_html_e( 'Sizes', 'easy-album-orders' ); ?>
            </a>
            <a href="#engraving" class="nav-tab" data-tab="engraving">
                <?php esc_html_e( 'Engraving', 'easy-album-orders' ); ?>
            </a>
            <a href="#general" class="nav-tab" data-tab="general">
                <?php esc_html_e( 'General', 'easy-album-orders' ); ?>
            </a>
        </nav>

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
                            
                            <div class="eao-material-card__header">
                                <div class="eao-material-card__image">
                                    <div class="eao-image-upload">
                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][image_id]" value="<?php echo esc_attr( $material['image_id'] ); ?>" class="eao-image-id">
                                        <div class="eao-image-preview eao-image-preview--large">
                                            <?php if ( ! empty( $material['image_id'] ) ) : ?>
                                                <?php echo wp_get_attachment_image( $material['image_id'], 'medium' ); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="eao-image-actions">
                                            <button type="button" class="button-link eao-upload-image" title="<?php esc_attr_e( 'Upload Image', 'easy-album-orders' ); ?>">
                                                <span class="dashicons dashicons-camera"></span>
                                            </button>
                                            <button type="button" class="button-link eao-remove-image" title="<?php esc_attr_e( 'Remove Image', 'easy-album-orders' ); ?>" <?php echo empty( $material['image_id'] ) ? 'style="display:none;"' : ''; ?>>
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="eao-material-card__info">
                                    <input type="text" name="eao_materials[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $material['name'] ); ?>" class="eao-material-name-input" placeholder="<?php esc_attr_e( 'Material Name', 'easy-album-orders' ); ?>" required>
                                    <div class="eao-material-card__meta">
                                        <div class="eao-inline-field">
                                            <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                                            <div class="eao-input-prefix">
                                                <span>$</span>
                                                <input type="number" name="eao_materials[<?php echo esc_attr( $index ); ?>][upcharge]" value="<?php echo esc_attr( $material['upcharge'] ); ?>" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <label class="eao-toggle">
                                            <input type="checkbox" name="eao_materials[<?php echo esc_attr( $index ); ?>][allow_engraving]" value="1" <?php checked( ! empty( $material['allow_engraving'] ) ); ?>>
                                            <span class="eao-toggle__label"><?php esc_html_e( 'Allow Engraving', 'easy-album-orders' ); ?></span>
                                        </label>
                                    </div>
                                </div>
                                <button type="button" class="eao-material-card__delete" title="<?php esc_attr_e( 'Delete Material', 'easy-album-orders' ); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>

                            <!-- Colors Section -->
                            <div class="eao-material-card__colors">
                                <div class="eao-colors-header">
                                    <span class="eao-colors-label"><?php esc_html_e( 'Colors', 'easy-album-orders' ); ?></span>
                                </div>
                                <div class="eao-colors-grid">
                                    <?php if ( ! empty( $material['colors'] ) ) : ?>
                                        <?php foreach ( $material['colors'] as $color_index => $color ) : ?>
                                            <div class="eao-color-swatch" data-color-index="<?php echo esc_attr( $color_index ); ?>">
                                                <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][id]" value="<?php echo esc_attr( $color['id'] ); ?>">
                                                <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][type]" value="<?php echo esc_attr( $color['type'] ); ?>" class="eao-color-type-input">
                                                <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][color_value]" value="<?php echo esc_attr( $color['color_value'] ); ?>" class="eao-color-value-input">
                                                <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][name]" value="<?php echo esc_attr( $color['name'] ); ?>" class="eao-color-name-input">
                                                
                                                <div class="eao-color-swatch__circle" style="<?php echo 'solid' === $color['type'] ? 'background-color: ' . esc_attr( $color['color_value'] ) . ';' : 'background: linear-gradient(135deg, #ddd 25%, #999 50%, #ddd 75%);'; ?>" title="<?php echo esc_attr( $color['name'] ); ?>">
                                                    <?php if ( 'texture' === $color['type'] ) : ?>
                                                        <span class="eao-color-swatch__texture-icon">
                                                            <span class="dashicons dashicons-format-image"></span>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="eao-color-swatch__name"><?php echo esc_html( $color['name'] ); ?></span>
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
                            <div class="eao-size-card__main">
                                <input type="text" name="eao_sizes[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $size['name'] ); ?>" placeholder="<?php esc_attr_e( 'Size Name', 'easy-album-orders' ); ?>" class="eao-size-name-input" required>
                                <input type="text" name="eao_sizes[<?php echo esc_attr( $index ); ?>][dimensions]" value="<?php echo esc_attr( $size['dimensions'] ); ?>" placeholder="<?php esc_attr_e( 'e.g., 10" × 10"', 'easy-album-orders' ); ?>">
                            </div>
                            <div class="eao-size-card__upcharge">
                                <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                                <div class="eao-input-prefix">
                                    <span>$</span>
                                    <input type="number" name="eao_sizes[<?php echo esc_attr( $index ); ?>][upcharge]" value="<?php echo esc_attr( $size['upcharge'] ); ?>" step="0.01" min="0">
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
                            
                            <div class="eao-engraving-card__header">
                                <input type="text" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $option['name'] ); ?>" class="eao-engraving-name-input" placeholder="<?php esc_attr_e( 'Method Name', 'easy-album-orders' ); ?>" required>
                                <button type="button" class="eao-engraving-card__delete" title="<?php esc_attr_e( 'Delete', 'easy-album-orders' ); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>

                            <div class="eao-engraving-card__body">
                                <div class="eao-engraving-card__row">
                                    <div class="eao-field">
                                        <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                                        <div class="eao-input-prefix">
                                            <span>$</span>
                                            <input type="number" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][upcharge]" value="<?php echo esc_attr( $option['upcharge'] ); ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="eao-field">
                                        <label><?php esc_html_e( 'Character Limit', 'easy-album-orders' ); ?></label>
                                        <input type="number" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][character_limit]" value="<?php echo esc_attr( $option['character_limit'] ); ?>" min="0" placeholder="0 = unlimited">
                                    </div>
                                </div>
                                <div class="eao-field">
                                    <label><?php esc_html_e( 'Available Fonts', 'easy-album-orders' ); ?></label>
                                    <textarea name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][fonts]" rows="2" placeholder="<?php esc_attr_e( 'One font per line', 'easy-album-orders' ); ?>"><?php echo esc_textarea( $option['fonts'] ); ?></textarea>
                                </div>
                                <div class="eao-field">
                                    <label><?php esc_html_e( 'Description', 'easy-album-orders' ); ?></label>
                                    <textarea name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][description]" rows="2" placeholder="<?php esc_attr_e( 'Optional description for customers', 'easy-album-orders' ); ?>"><?php echo esc_textarea( $option['description'] ); ?></textarea>
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

                <div class="eao-settings-card">
                    <h3><?php esc_html_e( 'Notifications', 'easy-album-orders' ); ?></h3>
                    <div class="eao-settings-card__body">
                        <label class="eao-toggle eao-toggle--block">
                            <input type="checkbox" name="eao_general_settings[email_notifications]" value="1" <?php checked( ! empty( $general_settings['email_notifications'] ) ); ?>>
                            <span class="eao-toggle__label"><?php esc_html_e( 'Send email notifications for new orders', 'easy-album-orders' ); ?></span>
                        </label>
                        <div class="eao-field">
                            <label for="eao_admin_email"><?php esc_html_e( 'Admin Email', 'easy-album-orders' ); ?></label>
                            <input type="email" id="eao_admin_email" name="eao_general_settings[admin_email]" value="<?php echo esc_attr( isset( $general_settings['admin_email'] ) ? $general_settings['admin_email'] : get_option( 'admin_email' ) ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Email address for order notifications.', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="eao-form-footer">
            <input type="submit" name="eao_save_options" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Changes', 'easy-album-orders' ); ?>">
        </div>
    </form>
</div>

<!-- Color Edit Modal -->
<div id="eao-color-modal" class="eao-modal" style="display: none;">
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
                <input type="text" id="eao-modal-color-name" placeholder="<?php esc_attr_e( 'e.g., Navy Blue', 'easy-album-orders' ); ?>">
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
            <div class="eao-field eao-color-picker-field">
                <label><?php esc_html_e( 'Color', 'easy-album-orders' ); ?></label>
                <div class="eao-color-picker-wrap">
                    <input type="color" id="eao-modal-color-value" value="#000000">
                    <span class="eao-color-hex" id="eao-modal-color-hex">#000000</span>
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
        
        <div class="eao-material-card__header">
            <div class="eao-material-card__image">
                <div class="eao-image-upload">
                    <input type="hidden" name="eao_materials[{{data.index}}][image_id]" value="" class="eao-image-id">
                    <div class="eao-image-preview eao-image-preview--large"></div>
                    <div class="eao-image-actions">
                        <button type="button" class="button-link eao-upload-image" title="<?php esc_attr_e( 'Upload Image', 'easy-album-orders' ); ?>">
                            <span class="dashicons dashicons-camera"></span>
                        </button>
                        <button type="button" class="button-link eao-remove-image" title="<?php esc_attr_e( 'Remove Image', 'easy-album-orders' ); ?>" style="display:none;">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="eao-material-card__info">
                <input type="text" name="eao_materials[{{data.index}}][name]" value="" class="eao-material-name-input" placeholder="<?php esc_attr_e( 'Material Name', 'easy-album-orders' ); ?>" required>
                <div class="eao-material-card__meta">
                    <div class="eao-inline-field">
                        <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                        <div class="eao-input-prefix">
                            <span>$</span>
                            <input type="number" name="eao_materials[{{data.index}}][upcharge]" value="0" step="0.01" min="0">
                        </div>
                    </div>
                    <label class="eao-toggle">
                        <input type="checkbox" name="eao_materials[{{data.index}}][allow_engraving]" value="1">
                        <span class="eao-toggle__label"><?php esc_html_e( 'Allow Engraving', 'easy-album-orders' ); ?></span>
                    </label>
                </div>
            </div>
            <button type="button" class="eao-material-card__delete" title="<?php esc_attr_e( 'Delete Material', 'easy-album-orders' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>

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
</script>

<script type="text/html" id="tmpl-eao-color-swatch">
    <div class="eao-color-swatch" data-color-index="{{data.colorIndex}}">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][id]" value="{{data.id}}">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][type]" value="{{data.type}}" class="eao-color-type-input">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][color_value]" value="{{data.colorValue}}" class="eao-color-value-input">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][name]" value="{{data.name}}" class="eao-color-name-input">
        
        <div class="eao-color-swatch__circle" style="background-color: {{data.colorValue}};" title="{{data.name}}"></div>
        <span class="eao-color-swatch__name">{{data.name}}</span>
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
        <div class="eao-size-card__main">
            <input type="text" name="eao_sizes[{{data.index}}][name]" value="" placeholder="<?php esc_attr_e( 'Size Name', 'easy-album-orders' ); ?>" class="eao-size-name-input" required>
            <input type="text" name="eao_sizes[{{data.index}}][dimensions]" value="" placeholder="<?php esc_attr_e( 'e.g., 10" × 10"', 'easy-album-orders' ); ?>">
        </div>
        <div class="eao-size-card__upcharge">
            <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
            <div class="eao-input-prefix">
                <span>$</span>
                <input type="number" name="eao_sizes[{{data.index}}][upcharge]" value="0" step="0.01" min="0">
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
        
        <div class="eao-engraving-card__header">
            <input type="text" name="eao_engraving_options[{{data.index}}][name]" value="" class="eao-engraving-name-input" placeholder="<?php esc_attr_e( 'Method Name', 'easy-album-orders' ); ?>" required>
            <button type="button" class="eao-engraving-card__delete" title="<?php esc_attr_e( 'Delete', 'easy-album-orders' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>

        <div class="eao-engraving-card__body">
            <div class="eao-engraving-card__row">
                <div class="eao-field">
                    <label><?php esc_html_e( 'Upcharge', 'easy-album-orders' ); ?></label>
                    <div class="eao-input-prefix">
                        <span>$</span>
                        <input type="number" name="eao_engraving_options[{{data.index}}][upcharge]" value="0" step="0.01" min="0">
                    </div>
                </div>
                <div class="eao-field">
                    <label><?php esc_html_e( 'Character Limit', 'easy-album-orders' ); ?></label>
                    <input type="number" name="eao_engraving_options[{{data.index}}][character_limit]" value="" min="0" placeholder="0 = unlimited">
                </div>
            </div>
            <div class="eao-field">
                <label><?php esc_html_e( 'Available Fonts', 'easy-album-orders' ); ?></label>
                <textarea name="eao_engraving_options[{{data.index}}][fonts]" rows="2" placeholder="<?php esc_attr_e( 'One font per line', 'easy-album-orders' ); ?>"></textarea>
            </div>
            <div class="eao-field">
                <label><?php esc_html_e( 'Description', 'easy-album-orders' ); ?></label>
                <textarea name="eao_engraving_options[{{data.index}}][description]" rows="2" placeholder="<?php esc_attr_e( 'Optional description for customers', 'easy-album-orders' ); ?>"></textarea>
            </div>
        </div>
    </div>
</script>
