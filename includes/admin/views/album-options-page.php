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
<div class="wrap eao-admin-wrap">
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
            <h2><?php esc_html_e( 'Materials', 'easy-album-orders' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Configure the materials available for albums. Each material can have its own colors, upcharges, and engraving settings.', 'easy-album-orders' ); ?>
            </p>

            <div class="eao-repeater" id="materials-repeater">
                <div class="eao-repeater__items">
                    <?php if ( ! empty( $materials ) ) : ?>
                        <?php foreach ( $materials as $index => $material ) : ?>
                            <div class="eao-repeater__item eao-material-item" data-index="<?php echo esc_attr( $index ); ?>">
                                <div class="eao-repeater__item-header">
                                    <span class="eao-repeater__item-title">
                                        <?php echo esc_html( $material['name'] ); ?>
                                    </span>
                                    <button type="button" class="eao-repeater__toggle" aria-expanded="false">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </button>
                                    <button type="button" class="eao-repeater__remove" title="<?php esc_attr_e( 'Remove', 'easy-album-orders' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                                <div class="eao-repeater__item-content">
                                    <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $material['id'] ); ?>">

                                    <div class="eao-field-row">
                                        <div class="eao-field">
                                            <label><?php esc_html_e( 'Material Name', 'easy-album-orders' ); ?></label>
                                            <input type="text" name="eao_materials[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $material['name'] ); ?>" class="regular-text eao-material-name-input" required>
                                        </div>
                                        <div class="eao-field">
                                            <label><?php esc_html_e( 'Upcharge ($)', 'easy-album-orders' ); ?></label>
                                            <input type="number" name="eao_materials[<?php echo esc_attr( $index ); ?>][upcharge]" value="<?php echo esc_attr( $material['upcharge'] ); ?>" step="0.01" min="0" class="small-text">
                                        </div>
                                    </div>

                                    <div class="eao-field-row">
                                        <div class="eao-field eao-field--image">
                                            <label><?php esc_html_e( 'Material Image', 'easy-album-orders' ); ?></label>
                                            <div class="eao-image-upload">
                                                <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][image_id]" value="<?php echo esc_attr( $material['image_id'] ); ?>" class="eao-image-id">
                                                <div class="eao-image-preview">
                                                    <?php if ( ! empty( $material['image_id'] ) ) : ?>
                                                        <?php echo wp_get_attachment_image( $material['image_id'], 'thumbnail' ); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <button type="button" class="button eao-upload-image"><?php esc_html_e( 'Select Image', 'easy-album-orders' ); ?></button>
                                                <button type="button" class="button eao-remove-image" <?php echo empty( $material['image_id'] ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove', 'easy-album-orders' ); ?></button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="eao-field-row">
                                        <div class="eao-field">
                                            <label>
                                                <input type="checkbox" name="eao_materials[<?php echo esc_attr( $index ); ?>][allow_engraving]" value="1" <?php checked( ! empty( $material['allow_engraving'] ) ); ?>>
                                                <?php esc_html_e( 'Allow Engraving', 'easy-album-orders' ); ?>
                                            </label>
                                            <p class="description"><?php esc_html_e( 'Enable engraving options for this material.', 'easy-album-orders' ); ?></p>
                                        </div>
                                    </div>

                                    <!-- Colors Sub-repeater -->
                                    <div class="eao-sub-repeater eao-colors-repeater">
                                        <h4><?php esc_html_e( 'Colors', 'easy-album-orders' ); ?></h4>
                                        <div class="eao-sub-repeater__items">
                                            <?php if ( ! empty( $material['colors'] ) ) : ?>
                                                <?php foreach ( $material['colors'] as $color_index => $color ) : ?>
                                                    <div class="eao-sub-repeater__item eao-color-item">
                                                        <input type="hidden" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][id]" value="<?php echo esc_attr( $color['id'] ); ?>">
                                                        <div class="eao-color-item__fields">
                                                            <input type="text" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][name]" value="<?php echo esc_attr( $color['name'] ); ?>" placeholder="<?php esc_attr_e( 'Color Name', 'easy-album-orders' ); ?>" class="regular-text">
                                                            <select name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][type]" class="eao-color-type-select">
                                                                <option value="solid" <?php selected( $color['type'], 'solid' ); ?>><?php esc_html_e( 'Solid Color', 'easy-album-orders' ); ?></option>
                                                                <option value="texture" <?php selected( $color['type'], 'texture' ); ?>><?php esc_html_e( 'Texture', 'easy-album-orders' ); ?></option>
                                                            </select>
                                                            <input type="color" name="eao_materials[<?php echo esc_attr( $index ); ?>][colors][<?php echo esc_attr( $color_index ); ?>][color_value]" value="<?php echo esc_attr( $color['color_value'] ); ?>" class="eao-color-picker" <?php echo 'texture' === $color['type'] ? 'style="display:none;"' : ''; ?>>
                                                        </div>
                                                        <button type="button" class="button-link eao-sub-repeater__remove">
                                                            <span class="dashicons dashicons-no-alt"></span>
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="button eao-add-color"><?php esc_html_e( 'Add Color', 'easy-album-orders' ); ?></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="button button-secondary eao-add-material">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e( 'Add Material', 'easy-album-orders' ); ?>
                </button>
            </div>
        </div>

        <!-- Sizes Tab -->
        <div id="sizes" class="eao-tab-content">
            <h2><?php esc_html_e( 'Sizes', 'easy-album-orders' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Configure the available album sizes and their upcharges.', 'easy-album-orders' ); ?>
            </p>

            <div class="eao-repeater" id="sizes-repeater">
                <div class="eao-repeater__items">
                    <?php if ( ! empty( $sizes ) ) : ?>
                        <?php foreach ( $sizes as $index => $size ) : ?>
                            <div class="eao-repeater__item eao-size-item" data-index="<?php echo esc_attr( $index ); ?>">
                                <div class="eao-repeater__item-header">
                                    <span class="eao-repeater__item-title">
                                        <?php echo esc_html( $size['name'] ); ?>
                                    </span>
                                    <button type="button" class="eao-repeater__remove" title="<?php esc_attr_e( 'Remove', 'easy-album-orders' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                                <div class="eao-repeater__item-content eao-size-fields">
                                    <input type="hidden" name="eao_sizes[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $size['id'] ); ?>">
                                    <input type="text" name="eao_sizes[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $size['name'] ); ?>" placeholder="<?php esc_attr_e( 'Size Name (e.g., 10x10)', 'easy-album-orders' ); ?>" class="regular-text eao-size-name-input" required>
                                    <input type="text" name="eao_sizes[<?php echo esc_attr( $index ); ?>][dimensions]" value="<?php echo esc_attr( $size['dimensions'] ); ?>" placeholder="<?php esc_attr_e( 'Dimensions', 'easy-album-orders' ); ?>" class="regular-text">
                                    <input type="number" name="eao_sizes[<?php echo esc_attr( $index ); ?>][upcharge]" value="<?php echo esc_attr( $size['upcharge'] ); ?>" placeholder="<?php esc_attr_e( 'Upcharge', 'easy-album-orders' ); ?>" step="0.01" min="0" class="small-text">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="button button-secondary eao-add-size">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e( 'Add Size', 'easy-album-orders' ); ?>
                </button>
            </div>
        </div>

        <!-- Engraving Tab -->
        <div id="engraving" class="eao-tab-content">
            <h2><?php esc_html_e( 'Engraving Options', 'easy-album-orders' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Configure engraving methods, pricing, and font options.', 'easy-album-orders' ); ?>
            </p>

            <div class="eao-repeater" id="engraving-repeater">
                <div class="eao-repeater__items">
                    <?php if ( ! empty( $engraving_options ) ) : ?>
                        <?php foreach ( $engraving_options as $index => $option ) : ?>
                            <div class="eao-repeater__item eao-engraving-item" data-index="<?php echo esc_attr( $index ); ?>">
                                <div class="eao-repeater__item-header">
                                    <span class="eao-repeater__item-title">
                                        <?php echo esc_html( $option['name'] ); ?>
                                    </span>
                                    <button type="button" class="eao-repeater__toggle" aria-expanded="false">
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </button>
                                    <button type="button" class="eao-repeater__remove" title="<?php esc_attr_e( 'Remove', 'easy-album-orders' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                                <div class="eao-repeater__item-content">
                                    <input type="hidden" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $option['id'] ); ?>">

                                    <div class="eao-field-row">
                                        <div class="eao-field">
                                            <label><?php esc_html_e( 'Method Name', 'easy-album-orders' ); ?></label>
                                            <input type="text" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $option['name'] ); ?>" class="regular-text eao-engraving-name-input" required>
                                        </div>
                                        <div class="eao-field">
                                            <label><?php esc_html_e( 'Upcharge ($)', 'easy-album-orders' ); ?></label>
                                            <input type="number" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][upcharge]" value="<?php echo esc_attr( $option['upcharge'] ); ?>" step="0.01" min="0" class="small-text">
                                        </div>
                                        <div class="eao-field">
                                            <label><?php esc_html_e( 'Character Limit', 'easy-album-orders' ); ?></label>
                                            <input type="number" name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][character_limit]" value="<?php echo esc_attr( $option['character_limit'] ); ?>" min="0" class="small-text">
                                        </div>
                                    </div>

                                    <div class="eao-field">
                                        <label><?php esc_html_e( 'Available Fonts (one per line)', 'easy-album-orders' ); ?></label>
                                        <textarea name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][fonts]" rows="3" class="large-text"><?php echo esc_textarea( $option['fonts'] ); ?></textarea>
                                    </div>

                                    <div class="eao-field">
                                        <label><?php esc_html_e( 'Description', 'easy-album-orders' ); ?></label>
                                        <textarea name="eao_engraving_options[<?php echo esc_attr( $index ); ?>][description]" rows="2" class="large-text"><?php echo esc_textarea( $option['description'] ); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="button button-secondary eao-add-engraving">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e( 'Add Engraving Method', 'easy-album-orders' ); ?>
                </button>
            </div>
        </div>

        <!-- General Tab -->
        <div id="general" class="eao-tab-content">
            <h2><?php esc_html_e( 'General Settings', 'easy-album-orders' ); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="eao_currency"><?php esc_html_e( 'Currency', 'easy-album-orders' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="eao_currency" name="eao_general_settings[currency]" value="<?php echo esc_attr( isset( $general_settings['currency'] ) ? $general_settings['currency'] : 'USD' ); ?>" class="small-text">
                        <p class="description"><?php esc_html_e( 'Currency code (e.g., USD, EUR, GBP)', 'easy-album-orders' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="eao_currency_symbol"><?php esc_html_e( 'Currency Symbol', 'easy-album-orders' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="eao_currency_symbol" name="eao_general_settings[currency_symbol]" value="<?php echo esc_attr( isset( $general_settings['currency_symbol'] ) ? $general_settings['currency_symbol'] : '$' ); ?>" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Symbol Position', 'easy-album-orders' ); ?>
                    </th>
                    <td>
                        <label>
                            <input type="radio" name="eao_general_settings[currency_position]" value="before" <?php checked( isset( $general_settings['currency_position'] ) ? $general_settings['currency_position'] : 'before', 'before' ); ?>>
                            <?php esc_html_e( 'Before price ($100)', 'easy-album-orders' ); ?>
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="eao_general_settings[currency_position]" value="after" <?php checked( isset( $general_settings['currency_position'] ) ? $general_settings['currency_position'] : 'before', 'after' ); ?>>
                            <?php esc_html_e( 'After price (100$)', 'easy-album-orders' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Email Notifications', 'easy-album-orders' ); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="eao_general_settings[email_notifications]" value="1" <?php checked( ! empty( $general_settings['email_notifications'] ) ); ?>>
                            <?php esc_html_e( 'Send email notifications for new orders', 'easy-album-orders' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="eao_admin_email"><?php esc_html_e( 'Admin Email', 'easy-album-orders' ); ?></label>
                    </th>
                    <td>
                        <input type="email" id="eao_admin_email" name="eao_general_settings[admin_email]" value="<?php echo esc_attr( isset( $general_settings['admin_email'] ) ? $general_settings['admin_email'] : get_option( 'admin_email' ) ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Email address for order notifications.', 'easy-album-orders' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <input type="submit" name="eao_save_options" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'easy-album-orders' ); ?>">
        </p>
    </form>
</div>

<!-- Templates for JavaScript -->
<script type="text/html" id="tmpl-eao-material-item">
    <div class="eao-repeater__item eao-material-item" data-index="{{data.index}}">
        <div class="eao-repeater__item-header">
            <span class="eao-repeater__item-title"><?php esc_html_e( 'New Material', 'easy-album-orders' ); ?></span>
            <button type="button" class="eao-repeater__toggle" aria-expanded="true">
                <span class="dashicons dashicons-arrow-up-alt2"></span>
            </button>
            <button type="button" class="eao-repeater__remove" title="<?php esc_attr_e( 'Remove', 'easy-album-orders' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        <div class="eao-repeater__item-content" style="display: block;">
            <input type="hidden" name="eao_materials[{{data.index}}][id]" value="{{data.id}}">

            <div class="eao-field-row">
                <div class="eao-field">
                    <label><?php esc_html_e( 'Material Name', 'easy-album-orders' ); ?></label>
                    <input type="text" name="eao_materials[{{data.index}}][name]" value="" class="regular-text eao-material-name-input" required>
                </div>
                <div class="eao-field">
                    <label><?php esc_html_e( 'Upcharge ($)', 'easy-album-orders' ); ?></label>
                    <input type="number" name="eao_materials[{{data.index}}][upcharge]" value="0" step="0.01" min="0" class="small-text">
                </div>
            </div>

            <div class="eao-field-row">
                <div class="eao-field eao-field--image">
                    <label><?php esc_html_e( 'Material Image', 'easy-album-orders' ); ?></label>
                    <div class="eao-image-upload">
                        <input type="hidden" name="eao_materials[{{data.index}}][image_id]" value="" class="eao-image-id">
                        <div class="eao-image-preview"></div>
                        <button type="button" class="button eao-upload-image"><?php esc_html_e( 'Select Image', 'easy-album-orders' ); ?></button>
                        <button type="button" class="button eao-remove-image" style="display:none;"><?php esc_html_e( 'Remove', 'easy-album-orders' ); ?></button>
                    </div>
                </div>
            </div>

            <div class="eao-field-row">
                <div class="eao-field">
                    <label>
                        <input type="checkbox" name="eao_materials[{{data.index}}][allow_engraving]" value="1">
                        <?php esc_html_e( 'Allow Engraving', 'easy-album-orders' ); ?>
                    </label>
                    <p class="description"><?php esc_html_e( 'Enable engraving options for this material.', 'easy-album-orders' ); ?></p>
                </div>
            </div>

            <div class="eao-sub-repeater eao-colors-repeater">
                <h4><?php esc_html_e( 'Colors', 'easy-album-orders' ); ?></h4>
                <div class="eao-sub-repeater__items"></div>
                <button type="button" class="button eao-add-color"><?php esc_html_e( 'Add Color', 'easy-album-orders' ); ?></button>
            </div>
        </div>
    </div>
</script>

<script type="text/html" id="tmpl-eao-color-item">
    <div class="eao-sub-repeater__item eao-color-item">
        <input type="hidden" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][id]" value="{{data.id}}">
        <div class="eao-color-item__fields">
            <input type="text" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][name]" value="" placeholder="<?php esc_attr_e( 'Color Name', 'easy-album-orders' ); ?>" class="regular-text">
            <select name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][type]" class="eao-color-type-select">
                <option value="solid"><?php esc_html_e( 'Solid Color', 'easy-album-orders' ); ?></option>
                <option value="texture"><?php esc_html_e( 'Texture', 'easy-album-orders' ); ?></option>
            </select>
            <input type="color" name="eao_materials[{{data.materialIndex}}][colors][{{data.colorIndex}}][color_value]" value="#000000" class="eao-color-picker">
        </div>
        <button type="button" class="button-link eao-sub-repeater__remove">
            <span class="dashicons dashicons-no-alt"></span>
        </button>
    </div>
</script>

<script type="text/html" id="tmpl-eao-size-item">
    <div class="eao-repeater__item eao-size-item" data-index="{{data.index}}">
        <div class="eao-repeater__item-header">
            <span class="eao-repeater__item-title"><?php esc_html_e( 'New Size', 'easy-album-orders' ); ?></span>
            <button type="button" class="eao-repeater__remove" title="<?php esc_attr_e( 'Remove', 'easy-album-orders' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        <div class="eao-repeater__item-content eao-size-fields" style="display: block;">
            <input type="hidden" name="eao_sizes[{{data.index}}][id]" value="{{data.id}}">
            <input type="text" name="eao_sizes[{{data.index}}][name]" value="" placeholder="<?php esc_attr_e( 'Size Name (e.g., 10x10)', 'easy-album-orders' ); ?>" class="regular-text eao-size-name-input" required>
            <input type="text" name="eao_sizes[{{data.index}}][dimensions]" value="" placeholder="<?php esc_attr_e( 'Dimensions', 'easy-album-orders' ); ?>" class="regular-text">
            <input type="number" name="eao_sizes[{{data.index}}][upcharge]" value="0" placeholder="<?php esc_attr_e( 'Upcharge', 'easy-album-orders' ); ?>" step="0.01" min="0" class="small-text">
        </div>
    </div>
</script>

<script type="text/html" id="tmpl-eao-engraving-item">
    <div class="eao-repeater__item eao-engraving-item" data-index="{{data.index}}">
        <div class="eao-repeater__item-header">
            <span class="eao-repeater__item-title"><?php esc_html_e( 'New Engraving Method', 'easy-album-orders' ); ?></span>
            <button type="button" class="eao-repeater__toggle" aria-expanded="true">
                <span class="dashicons dashicons-arrow-up-alt2"></span>
            </button>
            <button type="button" class="eao-repeater__remove" title="<?php esc_attr_e( 'Remove', 'easy-album-orders' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        <div class="eao-repeater__item-content" style="display: block;">
            <input type="hidden" name="eao_engraving_options[{{data.index}}][id]" value="{{data.id}}">

            <div class="eao-field-row">
                <div class="eao-field">
                    <label><?php esc_html_e( 'Method Name', 'easy-album-orders' ); ?></label>
                    <input type="text" name="eao_engraving_options[{{data.index}}][name]" value="" class="regular-text eao-engraving-name-input" required>
                </div>
                <div class="eao-field">
                    <label><?php esc_html_e( 'Upcharge ($)', 'easy-album-orders' ); ?></label>
                    <input type="number" name="eao_engraving_options[{{data.index}}][upcharge]" value="0" step="0.01" min="0" class="small-text">
                </div>
                <div class="eao-field">
                    <label><?php esc_html_e( 'Character Limit', 'easy-album-orders' ); ?></label>
                    <input type="number" name="eao_engraving_options[{{data.index}}][character_limit]" value="" min="0" class="small-text">
                </div>
            </div>

            <div class="eao-field">
                <label><?php esc_html_e( 'Available Fonts (one per line)', 'easy-album-orders' ); ?></label>
                <textarea name="eao_engraving_options[{{data.index}}][fonts]" rows="3" class="large-text"></textarea>
            </div>

            <div class="eao-field">
                <label><?php esc_html_e( 'Description', 'easy-album-orders' ); ?></label>
                <textarea name="eao_engraving_options[{{data.index}}][description]" rows="2" class="large-text"></textarea>
            </div>
        </div>
    </div>
</script>

