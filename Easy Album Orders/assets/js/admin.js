/**
 * Easy Album Orders - Admin JavaScript
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * Admin handler object.
     */
    const EAOAdmin = {

        /**
         * Current color being edited.
         */
        currentColorSwatch: null,
        currentMaterialCard: null,

        /**
         * Initialize admin functionality.
         */
        init: function() {
            this.bindTabs();
            this.bindMaterialCards();
            this.bindSizeCards();
            this.bindEngravingCards();
            this.bindColorModal();
            this.bindImageUpload();
            this.bindPdfUpload();
            this.bindDesignRepeater();
            this.bindCopyLink();
            this.bindLegacyRepeaters();
            this.bindEmailPreview();
            this.bindCartReminderSend();
            this.reorganizeAlbumOrdersTable();
            this.bindCopyWebhookUrl();
            this.bindStripeKeyValidation();
            this.bindRefund();
            this.bindBrandColorPicker();
            this.initSortable();
        },

        /**
         * Generate a unique ID.
         *
         * @return {string} Unique ID.
         */
        generateId: function() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },

        /**
         * Bind tab/section navigation.
         * Supports both old tab style (.eao-tabs .nav-tab) and new sidebar nav (.eao-options-nav__item).
         */
        bindTabs: function() {
            const self = this;

            // New sidebar navigation.
            $('.eao-options-nav__item').on('click', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');

                // Update active nav item.
                $('.eao-options-nav__item').removeClass('eao-options-nav__item--active');
                $(this).addClass('eao-options-nav__item--active');

                // Show corresponding content.
                $('.eao-tab-content').removeClass('active');
                $('#' + tab).addClass('active');

                // Update URL hash.
                if (history.pushState) {
                    history.pushState(null, null, '#' + tab);
                }

                // Scroll to top of content on mobile.
                if (window.innerWidth <= 960) {
                    $('html, body').animate({
                        scrollTop: $('.eao-options-content').offset().top - 50
                    }, 200);
                }
            });

            // Legacy tab navigation (for backward compatibility).
            $('.eao-tabs .nav-tab').on('click', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');

                // Update active tab.
                $('.eao-tabs .nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                // Show corresponding content.
                $('.eao-tab-content').removeClass('active');
                $('#' + tab).addClass('active');

                // Update URL hash.
                if (history.pushState) {
                    history.pushState(null, null, '#' + tab);
                }
            });

            // Check URL hash on load.
            const hash = window.location.hash.replace('#', '');
            if (hash) {
                // Try new nav first, then legacy tabs.
                if ($('.eao-options-nav__item[data-tab="' + hash + '"]').length) {
                    $('.eao-options-nav__item[data-tab="' + hash + '"]').trigger('click');
                } else if ($('.eao-tabs .nav-tab[data-tab="' + hash + '"]').length) {
                    $('.eao-tabs .nav-tab[data-tab="' + hash + '"]').trigger('click');
                }
            }
        },

        /**
         * Bind material card functionality.
         */
        bindMaterialCards: function() {
            const self = this;

            // Add material.
            $(document).on('click', '.eao-add-material', function() {
                self.addMaterial();
            });

            // Delete material.
            $(document).on('click', '.eao-material-card__delete', function() {
                if (confirm(eaoAdmin.confirmDelete || 'Are you sure you want to delete this item?')) {
                    $(this).closest('.eao-material-card').slideUp(200, function() {
                        $(this).remove();
                        self.reindexMaterials();
                        self.hideEmptyStateIfNeeded('#materials');
                    });
                }
            });

            // Add color - opens modal.
            $(document).on('click', '.eao-add-color', function() {
                self.currentMaterialCard = $(this).closest('.eao-material-card');
                self.currentColorSwatch = null;
                self.openColorModal();
            });

            // Edit color - opens modal with existing data.
            $(document).on('click', '.eao-color-swatch__edit', function(e) {
                e.stopPropagation();
                const $swatch = $(this).closest('.eao-color-swatch');
                self.currentMaterialCard = $swatch.closest('.eao-material-card');
                self.currentColorSwatch = $swatch;
                self.openColorModal($swatch);
            });

            // Delete color.
            $(document).on('click', '.eao-color-swatch__delete', function(e) {
                e.stopPropagation();
                $(this).closest('.eao-color-swatch').fadeOut(200, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Add a new material card.
         */
        addMaterial: function() {
            const $container = $('#materials-repeater');
            const index = $container.find('.eao-material-card').length;
            const template = wp.template('eao-material-card');

            const $newCard = $(template({
                index: index,
                id: this.generateId()
            }));

            // Hide empty state.
            $('#materials .eao-empty-state').hide();

            $container.append($newCard);
            $newCard.hide().slideDown(200, function() {
                $newCard.find('.eao-material-name-input').focus();
            });
        },

        /**
         * Reindex all material cards.
         */
        reindexMaterials: function() {
            $('.eao-material-card').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name^="eao_materials"]').each(function() {
                    const name = $(this).attr('name');
                    $(this).attr('name', name.replace(/eao_materials\[\d+\]/, 'eao_materials[' + index + ']'));
                });
            });
        },

        /**
         * Bind size card functionality.
         */
        bindSizeCards: function() {
            const self = this;

            // Add size.
            $(document).on('click', '.eao-add-size', function() {
                self.addSize();
            });

            // Delete size.
            $(document).on('click', '.eao-size-card__delete', function() {
                if (confirm(eaoAdmin.confirmDelete || 'Are you sure you want to delete this item?')) {
                    $(this).closest('.eao-size-card').slideUp(200, function() {
                        $(this).remove();
                        self.reindexSizes();
                        self.hideEmptyStateIfNeeded('#sizes');
                    });
                }
            });
        },

        /**
         * Add a new size card.
         */
        addSize: function() {
            const $container = $('#sizes-repeater');
            const index = $container.find('.eao-size-card').length;
            const template = wp.template('eao-size-card');

            const $newCard = $(template({
                index: index,
                id: this.generateId()
            }));

            // Hide empty state.
            $('#sizes .eao-empty-state').hide();

            $container.append($newCard);
            $newCard.hide().slideDown(200, function() {
                $newCard.find('.eao-size-name-input').focus();
            });
        },

        /**
         * Reindex all size cards.
         */
        reindexSizes: function() {
            $('.eao-size-card').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name^="eao_sizes"]').each(function() {
                    const name = $(this).attr('name');
                    $(this).attr('name', name.replace(/eao_sizes\[\d+\]/, 'eao_sizes[' + index + ']'));
                });
            });
        },

        /**
         * Bind engraving card functionality.
         */
        bindEngravingCards: function() {
            const self = this;

            // Add engraving.
            $(document).on('click', '.eao-add-engraving', function() {
                self.addEngraving();
            });

            // Delete engraving.
            $(document).on('click', '.eao-engraving-card__delete', function() {
                if (confirm(eaoAdmin.confirmDelete || 'Are you sure you want to delete this item?')) {
                    $(this).closest('.eao-engraving-card').slideUp(200, function() {
                        $(this).remove();
                        self.reindexEngraving();
                        self.hideEmptyStateIfNeeded('#engraving');
                    });
                }
            });
        },

        /**
         * Add a new engraving card.
         */
        addEngraving: function() {
            const $container = $('#engraving-repeater');
            const index = $container.find('.eao-engraving-card').length;
            const template = wp.template('eao-engraving-card');

            const $newCard = $(template({
                index: index,
                id: this.generateId()
            }));

            // Hide empty state.
            $('#engraving .eao-empty-state').hide();

            $container.append($newCard);
            $newCard.hide().slideDown(200, function() {
                $newCard.find('.eao-engraving-name-input').focus();
            });
        },

        /**
         * Reindex all engraving cards.
         */
        reindexEngraving: function() {
            $('.eao-engraving-card').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name^="eao_engraving_options"]').each(function() {
                    const name = $(this).attr('name');
                    $(this).attr('name', name.replace(/eao_engraving_options\[\d+\]/, 'eao_engraving_options[' + index + ']'));
                });
            });
        },

        /**
         * Hide empty state if there are items, show if there are none.
         */
        hideEmptyStateIfNeeded: function(tabId) {
            const $tab = $(tabId);
            const $emptyState = $tab.find('.eao-empty-state');
            const hasItems = $tab.find('.eao-material-card, .eao-size-card, .eao-engraving-card').length > 0;

            if (hasItems) {
                $emptyState.hide();
            } else {
                $emptyState.show();
            }
        },

        /**
         * Bind color modal functionality.
         */
        bindColorModal: function() {
            const self = this;
            const $modal = $('#eao-color-modal');

            // Close modal on backdrop click.
            $modal.on('click', '.eao-modal__backdrop, .eao-modal__close, .eao-modal__cancel', function() {
                self.closeColorModal();
            });

            // Update hex display when color changes.
            $('#eao-modal-color-value').on('input', function() {
                $('#eao-modal-color-hex').text($(this).val().toUpperCase());
            });

            // Toggle color picker visibility based on type.
            $('input[name="eao_modal_color_type"]').on('change', function() {
                self.toggleColorTypeUI($(this).val());
            });

            // Texture image upload.
            $modal.on('click', '.eao-upload-texture, .eao-texture-upload__preview', function() {
                self.openTextureUpload();
            });

            // Remove texture image.
            $modal.on('click', '.eao-remove-texture', function(e) {
                e.stopPropagation();
                self.removeTextureImage();
            });

            // Preview image upload.
            $modal.on('click', '.eao-upload-preview-image, .eao-preview-image-upload__preview', function() {
                self.openPreviewImageUpload();
            });

            // Remove preview image.
            $modal.on('click', '.eao-remove-preview-image', function(e) {
                e.stopPropagation();
                self.removePreviewImage();
            });

            // Region selection - click to place circle.
            $(document).on('click', '#eao-region-image-container', function(e) {
                self.placeRegionSelection(e);
            });

            // Save color.
            $modal.on('click', '.eao-modal__save', function() {
                self.saveColor();
            });

            // Handle Enter key in modal.
            $modal.on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    self.saveColor();
                }
                if (e.key === 'Escape') {
                    self.closeColorModal();
                }
            });
        },

        /**
         * Toggle UI between solid color and texture modes.
         */
        toggleColorTypeUI: function(type) {
            if (type === 'solid') {
                $('#eao-solid-color-section').show();
                $('#eao-texture-section').hide();
            } else {
                $('#eao-solid-color-section').hide();
                $('#eao-texture-section').show();
            }
        },

        /**
         * Open texture image upload dialog.
         */
        openTextureUpload: function() {
            const self = this;
            let textureFrame;

            textureFrame = wp.media({
                title: eaoAdmin.textureUploadTitle || 'Select Texture Image',
                button: {
                    text: eaoAdmin.textureUploadButton || 'Use this image'
                },
                multiple: false
            });

            textureFrame.on('select', function() {
                const attachment = textureFrame.state().get('selection').first().toJSON();
                self.setTextureImage(attachment);
            });

            textureFrame.open();
        },

        /**
         * Set the texture image in the modal.
         */
        setTextureImage: function(attachment) {
            const imageUrl = attachment.sizes && attachment.sizes.medium
                ? attachment.sizes.medium.url
                : attachment.url;

            $('#eao-modal-texture-image-id').val(attachment.id);
            $('#eao-modal-texture-image-url').val(attachment.url);

            // Show image in texture preview.
            $('#eao-texture-preview').html('<img src="' + imageUrl + '" alt="">');
            $('.eao-remove-texture').show();

            // Show region selector.
            $('#eao-region-selector-container').show();
            $('#eao-region-image').attr('src', attachment.url).on('load', function() {
                // Reset selection.
                $('#eao-region-selection').css({
                    display: 'none',
                    left: 0,
                    top: 0,
                    width: 0,
                    height: 0
                });
                $('#eao-modal-texture-region').val('');
                $('#eao-region-preview-swatch').css('background-image', 'none');
            });
        },

        /**
         * Remove texture image from modal.
         */
        removeTextureImage: function() {
            $('#eao-modal-texture-image-id').val('');
            $('#eao-modal-texture-image-url').val('');
            $('#eao-texture-preview').html(
                '<span class="eao-texture-upload__placeholder">' +
                    '<span class="dashicons dashicons-format-image"></span>' +
                    '<span>' + (eaoAdmin.uploadTexture || 'Click to upload texture image') + '</span>' +
                '</span>'
            );
            $('.eao-remove-texture').hide();
            $('#eao-region-selector-container').hide();
            $('#eao-modal-texture-region').val('');
            $('#eao-region-preview-swatch').css('background-image', 'none');
        },

        /**
         * Default swatch size (percentage of container width).
         */
        swatchSizePercent: 15,

        /**
         * Place region selection on click.
         * Creates a circular selection where the user clicks.
         */
        placeRegionSelection: function(e) {
            const $container = $('#eao-region-image-container');
            const $selection = $('#eao-region-selection');
            const offset = $container.offset();
            const containerWidth = $container.width();
            const containerHeight = $container.height();

            // Get click position relative to container.
            let clickX = e.pageX - offset.left;
            let clickY = e.pageY - offset.top;

            // Calculate selection circle size (fixed percentage of container).
            const selSize = containerWidth * (this.swatchSizePercent / 100);
            const halfSize = selSize / 2;

            // Constrain click position so circle stays within bounds.
            clickX = Math.max(halfSize, Math.min(clickX, containerWidth - halfSize));
            clickY = Math.max(halfSize, Math.min(clickY, containerHeight - halfSize));

            // Position the selection circle centered on click point.
            $selection.css({
                display: 'block',
                left: clickX - halfSize,
                top: clickY - halfSize,
                width: selSize,
                height: selSize
            });

            // Calculate percentage-based region for background-position.
            const region = {
                x: (clickX / containerWidth * 100).toFixed(2),
                y: (clickY / containerHeight * 100).toFixed(2),
                zoom: (100 / this.swatchSizePercent * 100).toFixed(0)
            };

            $('#eao-modal-texture-region').val(JSON.stringify(region));

            // Update preview swatch.
            const textureUrl = $('#eao-modal-texture-image-url').val();
            if (textureUrl) {
                $('#eao-region-preview-swatch').css({
                    'background-image': 'url(' + textureUrl + ')',
                    'background-position': region.x + '% ' + region.y + '%',
                    'background-size': region.zoom + '%'
                });
            }
        },

        /**
         * Open preview image upload dialog.
         */
        openPreviewImageUpload: function() {
            const self = this;
            let previewFrame;

            previewFrame = wp.media({
                title: eaoAdmin.previewUploadTitle || 'Select Preview Image',
                button: {
                    text: eaoAdmin.previewUploadButton || 'Use this image'
                },
                multiple: false
            });

            previewFrame.on('select', function() {
                const attachment = previewFrame.state().get('selection').first().toJSON();
                self.setPreviewImage(attachment);
            });

            previewFrame.open();
        },

        /**
         * Set the preview image in the modal.
         */
        setPreviewImage: function(attachment) {
            const thumbUrl = attachment.sizes && attachment.sizes.thumbnail
                ? attachment.sizes.thumbnail.url
                : attachment.url;

            $('#eao-modal-preview-image-id').val(attachment.id);
            $('#eao-preview-image-container').html('<img src="' + thumbUrl + '" alt="">');
            $('.eao-remove-preview-image').show();
        },

        /**
         * Remove preview image from modal.
         */
        removePreviewImage: function() {
            $('#eao-modal-preview-image-id').val('');
            $('#eao-preview-image-container').html(
                '<span class="eao-preview-image-upload__placeholder">' +
                    '<span class="dashicons dashicons-format-image"></span>' +
                '</span>'
            );
            $('.eao-remove-preview-image').hide();
        },

        /**
         * Open the color modal.
         *
         * @param {jQuery} $swatch Optional existing swatch to edit.
         */
        openColorModal: function($swatch) {
            const self = this;
            const $modal = $('#eao-color-modal');

            // Reset modal state.
            this.resetColorModal();

            if ($swatch) {
                // Editing existing color.
                const name = $swatch.find('.eao-color-name-input').val();
                const type = $swatch.find('.eao-color-type-input').val();
                const colorValue = $swatch.find('.eao-color-value-input').val();
                const textureImageId = $swatch.find('.eao-color-texture-image-id-input').val();
                const textureImageUrl = $swatch.find('.eao-color-texture-image-url-input').val();
                const textureRegion = $swatch.find('.eao-color-texture-region-input').val();
                const previewImageId = $swatch.find('.eao-color-preview-image-id-input').val();

                $('#eao-modal-color-name').val(name);
                $('input[name="eao_modal_color_type"][value="' + type + '"]').prop('checked', true);
                this.toggleColorTypeUI(type);

                // Solid color.
                $('#eao-modal-color-value').val(colorValue || '#000000');
                $('#eao-modal-color-hex').text((colorValue || '#000000').toUpperCase());

                // Texture data.
                if (type === 'texture' && textureImageId && textureImageUrl) {
                    $('#eao-modal-texture-image-id').val(textureImageId);
                    $('#eao-modal-texture-image-url').val(textureImageUrl);
                    $('#eao-texture-preview').html('<img src="' + textureImageUrl + '" alt="">');
                    $('.eao-remove-texture').show();

                    // Show region selector with existing image.
                    $('#eao-region-selector-container').show();
                    $('#eao-region-image').attr('src', textureImageUrl);

                    // Restore region selection if exists.
                    if (textureRegion) {
                        $('#eao-modal-texture-region').val(textureRegion);
                        try {
                            const region = JSON.parse(textureRegion);
                            $('#eao-region-preview-swatch').css({
                                'background-image': 'url(' + textureImageUrl + ')',
                                'background-position': region.x + '% ' + region.y + '%',
                                'background-size': region.zoom + '%'
                            });

                            // Show selection circle at saved position.
                            setTimeout(function() {
                                const $container = $('#eao-region-image-container');
                                const containerWidth = $container.width();
                                const containerHeight = $container.height();
                                const selSize = containerWidth * (self.swatchSizePercent / 100);
                                const halfSize = selSize / 2;
                                const centerX = (region.x / 100) * containerWidth;
                                const centerY = (region.y / 100) * containerHeight;

                                $('#eao-region-selection').css({
                                    display: 'block',
                                    left: centerX - halfSize,
                                    top: centerY - halfSize,
                                    width: selSize,
                                    height: selSize
                                });
                            }, 100);
                        } catch (e) {
                            console.error('Error parsing texture region:', e);
                        }
                    }
                }

                // Preview image.
                if (previewImageId) {
                    $('#eao-modal-preview-image-id').val(previewImageId);
                    // Fetch preview image URL via AJAX or use placeholder.
                    $.ajax({
                        url: eaoAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'eao_get_attachment_url',
                            nonce: eaoAdmin.nonce,
                            attachment_id: previewImageId
                        },
                        success: function(response) {
                            if (response.success && response.data.url) {
                                $('#eao-preview-image-container').html('<img src="' + response.data.url + '" alt="">');
                                $('.eao-remove-preview-image').show();
                            }
                        }
                    });
                }

                $modal.find('.eao-modal__header h3').text(eaoAdmin.editColor || 'Edit Color');
            } else {
                // Adding new color.
                $('input[name="eao_modal_color_type"][value="solid"]').prop('checked', true);
                this.toggleColorTypeUI('solid');
                $modal.find('.eao-modal__header h3').text(eaoAdmin.addColor || 'Add Color');
            }

            $modal.fadeIn(150);
            $('#eao-modal-color-name').focus();
        },

        /**
         * Reset color modal to initial state.
         */
        resetColorModal: function() {
            $('#eao-modal-color-name').val('');
            $('#eao-modal-color-value').val('#000000');
            $('#eao-modal-color-hex').text('#000000');

            // Reset texture fields.
            $('#eao-modal-texture-image-id').val('');
            $('#eao-modal-texture-image-url').val('');
            $('#eao-modal-texture-region').val('');
            $('#eao-texture-preview').html(
                '<span class="eao-texture-upload__placeholder">' +
                    '<span class="dashicons dashicons-format-image"></span>' +
                    '<span>' + (eaoAdmin.uploadTexture || 'Click to upload texture image') + '</span>' +
                '</span>'
            );
            $('.eao-remove-texture').hide();
            $('#eao-region-selector-container').hide();
            $('#eao-region-image').attr('src', '');
            $('#eao-region-selection').css({ display: 'none', width: 0, height: 0 });
            $('#eao-region-preview-swatch').css('background-image', 'none');

            // Reset preview image.
            $('#eao-modal-preview-image-id').val('');
            $('#eao-preview-image-container').html(
                '<span class="eao-preview-image-upload__placeholder">' +
                    '<span class="dashicons dashicons-format-image"></span>' +
                '</span>'
            );
            $('.eao-remove-preview-image').hide();
        },

        /**
         * Close the color modal.
         */
        closeColorModal: function() {
            $('#eao-color-modal').fadeOut(150);
            this.currentColorSwatch = null;
            this.currentMaterialCard = null;
        },

        /**
         * Save the color from the modal.
         */
        saveColor: function() {
            const name = $('#eao-modal-color-name').val().trim();
            const type = $('input[name="eao_modal_color_type"]:checked').val();
            const colorValue = $('#eao-modal-color-value').val();
            const textureImageId = $('#eao-modal-texture-image-id').val();
            const textureImageUrl = $('#eao-modal-texture-image-url').val();
            const textureRegion = $('#eao-modal-texture-region').val();
            const previewImageId = $('#eao-modal-preview-image-id').val();

            if (!name) {
                $('#eao-modal-color-name').focus();
                return;
            }

            // Validate texture data if texture type.
            if (type === 'texture' && !textureImageId) {
                alert(eaoAdmin.textureRequired || 'Please upload a texture image.');
                return;
            }

            const colorData = {
                name: name,
                type: type,
                colorValue: colorValue,
                textureImageId: textureImageId,
                textureImageUrl: textureImageUrl,
                textureRegion: textureRegion,
                previewImageId: previewImageId
            };

            if (this.currentColorSwatch) {
                // Update existing swatch.
                this.updateColorSwatch(this.currentColorSwatch, colorData);
            } else {
                // Add new swatch.
                this.addColorSwatch(colorData);
            }

            this.closeColorModal();
        },

        /**
         * Build swatch style from color data.
         */
        buildSwatchStyle: function(data) {
            if (data.type === 'texture' && data.textureImageUrl && data.textureRegion) {
                try {
                    const region = typeof data.textureRegion === 'string'
                        ? JSON.parse(data.textureRegion)
                        : data.textureRegion;
                    return 'background-image: url(' + data.textureImageUrl + '); ' +
                           'background-position: ' + region.x + '% ' + region.y + '%; ' +
                           'background-size: ' + region.zoom + '%;';
                } catch (e) {
                    return 'background: linear-gradient(135deg, #ddd 25%, #999 50%, #ddd 75%);';
                }
            } else if (data.type === 'solid') {
                return 'background-color: ' + data.colorValue + ';';
            }
            return 'background: linear-gradient(135deg, #ddd 25%, #999 50%, #ddd 75%);';
        },

        /**
         * Update an existing color swatch.
         */
        updateColorSwatch: function($swatch, data) {
            $swatch.find('.eao-color-name-input').val(data.name);
            $swatch.find('.eao-color-type-input').val(data.type);
            $swatch.find('.eao-color-value-input').val(data.colorValue);
            $swatch.find('.eao-color-texture-image-id-input').val(data.textureImageId);
            $swatch.find('.eao-color-texture-image-url-input').val(data.textureImageUrl);
            $swatch.find('.eao-color-texture-region-input').val(data.textureRegion);
            $swatch.find('.eao-color-preview-image-id-input').val(data.previewImageId);
            $swatch.find('.eao-color-swatch__name').text(data.name);

            const $circle = $swatch.find('.eao-color-swatch__circle');
            $circle.attr('title', data.name);
            $circle.attr('style', this.buildSwatchStyle(data));

            // Update texture icon visibility.
            if (data.type === 'texture' && !data.textureImageUrl) {
                $circle.html('<span class="eao-color-swatch__texture-icon"><span class="dashicons dashicons-format-image"></span></span>');
            } else {
                $circle.html('');
            }

            // Update preview indicator.
            const $previewIndicator = $swatch.find('.eao-color-swatch__has-preview');
            if (data.previewImageId) {
                if (!$previewIndicator.length) {
                    $swatch.find('.eao-color-swatch__name').after(
                        '<span class="eao-color-swatch__has-preview" title="Has preview image">' +
                            '<span class="dashicons dashicons-visibility"></span>' +
                        '</span>'
                    );
                }
            } else {
                $previewIndicator.remove();
            }
        },

        /**
         * HTML-escape a string for use in attributes.
         */
        escapeAttr: function(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        },

        /**
         * Add a new color swatch.
         */
        addColorSwatch: function(data) {
            const $materialCard = this.currentMaterialCard;
            const materialIndex = $materialCard.data('index');
            const $colorsGrid = $materialCard.find('.eao-colors-grid');
            const colorIndex = $colorsGrid.find('.eao-color-swatch:not(.eao-color-swatch--add)').length;

            const swatchStyle = this.buildSwatchStyle(data);

            const template = wp.template('eao-color-swatch');
            const $newSwatch = $(template({
                materialIndex: materialIndex,
                colorIndex: colorIndex,
                id: this.generateId(),
                name: data.name,
                type: data.type,
                colorValue: data.colorValue,
                textureImageId: data.textureImageId || '',
                textureImageUrl: data.textureImageUrl || '',
                textureRegion: '', // Set via .val() below to avoid escaping issues.
                previewImageId: data.previewImageId || '',
                swatchStyle: swatchStyle
            }));

            // Set values with jQuery to properly handle special characters.
            $newSwatch.find('.eao-color-texture-region-input').val(data.textureRegion || '');
            $newSwatch.find('.eao-color-texture-image-url-input').val(data.textureImageUrl || '');

            // Insert before the add button.
            $colorsGrid.find('.eao-color-swatch--add').before($newSwatch);
            $newSwatch.hide().fadeIn(200);
        },

        /**
         * Bind image upload functionality.
         */
        bindImageUpload: function() {
            let mediaFrame;

            $(document).on('click', '.eao-upload-image', function(e) {
                e.preventDefault();

                const $button = $(this);
                const $container = $button.closest('.eao-image-upload');
                const $input = $container.find('.eao-image-id');
                const $preview = $container.find('.eao-image-preview');
                const $removeBtn = $container.find('.eao-remove-image');

                // Create media frame if it doesn't exist.
                mediaFrame = wp.media({
                    title: eaoAdmin.mediaTitle || 'Select Image',
                    button: {
                        text: eaoAdmin.mediaButton || 'Use this image'
                    },
                    multiple: false
                });

                // When image is selected.
                mediaFrame.on('select', function() {
                    const attachment = mediaFrame.state().get('selection').first().toJSON();
                    const thumbUrl = attachment.sizes && attachment.sizes.medium
                        ? attachment.sizes.medium.url
                        : (attachment.sizes && attachment.sizes.thumbnail
                            ? attachment.sizes.thumbnail.url
                            : attachment.url);

                    $input.val(attachment.id);
                    $preview.html('<img src="' + thumbUrl + '" alt="">');
                    $removeBtn.show();
                });

                mediaFrame.open();
            });

            // Remove image.
            $(document).on('click', '.eao-remove-image', function(e) {
                e.preventDefault();

                const $container = $(this).closest('.eao-image-upload');
                $container.find('.eao-image-id').val('');
                $container.find('.eao-image-preview').empty();
                $(this).hide();
            });
        },

        /**
         * Bind PDF upload functionality.
         */
        bindPdfUpload: function() {
            let pdfFrame;

            $(document).on('click', '.eao-upload-pdf', function(e) {
                e.preventDefault();

                const $button = $(this);
                const $container = $button.closest('.eao-pdf-upload');
                const $input = $container.find('.eao-pdf-id');
                const $preview = $container.find('.eao-pdf-preview');
                const $removeBtn = $container.find('.eao-remove-pdf');

                // Create media frame for PDFs.
                pdfFrame = wp.media({
                    title: eaoAdmin.pdfMediaTitle || 'Select PDF',
                    button: {
                        text: eaoAdmin.pdfMediaButton || 'Use this PDF'
                    },
                    library: {
                        type: 'application/pdf'
                    },
                    multiple: false
                });

                // When PDF is selected.
                pdfFrame.on('select', function() {
                    const attachment = pdfFrame.state().get('selection').first().toJSON();

                    $input.val(attachment.id);
                    $preview.html('<span class="dashicons dashicons-pdf"></span> <a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>');
                    $removeBtn.show();
                });

                pdfFrame.open();
            });

            // Remove PDF.
            $(document).on('click', '.eao-remove-pdf', function(e) {
                e.preventDefault();

                const $container = $(this).closest('.eao-pdf-upload');
                $container.find('.eao-pdf-id').val('');
                $container.find('.eao-pdf-preview').html('<span class="eao-no-pdf">No PDF selected</span>');
                $(this).hide();
            });
        },

        /**
         * Bind design repeater functionality (Client Album edit screen).
         */
        bindDesignRepeater: function() {
            const self = this;

            // Add design.
            $(document).on('click', '.eao-add-design', function() {
                self.addDesign();
            });

            // Remove design card.
            $(document).on('click', '.eao-design-card__remove', function(e) {
                e.preventDefault();
                if (confirm(eaoAdmin.confirmDelete || 'Are you sure you want to delete this design?')) {
                    const $card = $(this).closest('.eao-design-card');
                    $card.slideUp(250, function() {
                        $(this).remove();
                        self.reindexDesignCards();
                    });
                }
            });
        },

        /**
         * Add a new design item.
         */
        addDesign: function() {
            const $container = $('#eao-designs-repeater .eao-repeater__items');
            const index = $container.find('.eao-design-card').length;
            const template = wp.template('eao-design-item');

            const $newItem = $(template({
                index: index
            }));

            $container.append($newItem);
            $newItem.hide().slideDown(250);
            $newItem.find('.eao-design-name-input').focus();
        },

        /**
         * Reindex design cards after removal.
         */
        reindexDesignCards: function() {
            $('.eao-design-card').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('.eao-design-card__index').text(index + 1);
                $(this).find('[name^="eao_designs"]').each(function() {
                    const name = $(this).attr('name');
                    $(this).attr('name', name.replace(/eao_designs\[\d+\]/, 'eao_designs[' + index + ']'));
                });
            });
        },

        /**
         * Bind copy link functionality.
         */
        bindCopyLink: function() {
            $(document).on('click', '.eao-copy-link-btn', function(e) {
                e.preventDefault();

                const $input = $('#eao-album-link');
                $input.select();

                try {
                    document.execCommand('copy');
                    const $btn = $(this);
                    const originalText = $btn.text();
                    $btn.text('Copied!');
                    setTimeout(function() {
                        $btn.text(originalText);
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy:', err);
                }
            });
        },

        /**
         * Bind legacy repeater functionality (for meta boxes).
         */
        bindLegacyRepeaters: function() {
            const self = this;

            // Toggle item content.
            $(document).on('click', '.eao-repeater__toggle', function(e) {
                e.stopPropagation();
                const $item = $(this).closest('.eao-repeater__item');
                $item.toggleClass('is-open');
            });

            // Click on header to toggle.
            $(document).on('click', '.eao-repeater__item-header', function(e) {
                if (!$(e.target).closest('.eao-repeater__toggle, .eao-repeater__remove').length) {
                    $(this).find('.eao-repeater__toggle').trigger('click');
                }
            });

            // Remove item.
            $(document).on('click', '.eao-repeater__remove', function(e) {
                e.stopPropagation();
                if (confirm(eaoAdmin.confirmDelete || 'Are you sure you want to delete this item?')) {
                    $(this).closest('.eao-repeater__item').slideUp(200, function() {
                        $(this).remove();
                        self.reindexLegacyItems();
                    });
                }
            });
        },

        /**
         * Reindex legacy repeater items.
         */
        reindexLegacyItems: function() {
            // Reindex designs.
            $('.eao-design-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name^="eao_designs"]').each(function() {
                    const name = $(this).attr('name');
                    $(this).attr('name', name.replace(/eao_designs\[\d+\]/, 'eao_designs[' + index + ']'));
                });
            });
        },

        /**
         * Bind email preview functionality.
         */
        bindEmailPreview: function() {
            const self = this;
            const $modal = $('#eao-email-preview-modal');

            // Preview button click.
            $(document).on('click', '.eao-email-preview-btn', function() {
                const emailType = $(this).data('email-type');
                self.loadEmailPreview(emailType);
            });

            // Close modal.
            $modal.on('click', '.eao-modal__backdrop, .eao-modal__close', function() {
                self.closeEmailPreviewModal();
            });

            // Handle Escape key.
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $modal.is(':visible')) {
                    self.closeEmailPreviewModal();
                }
            });
        },

        /**
         * Load email preview via AJAX.
         *
         * @param {string} emailType The type of email to preview.
         */
        loadEmailPreview: function(emailType) {
            const $modal = $('#eao-email-preview-modal');
            const $frame = $('#eao-email-preview-frame');
            const $title = $('#eao-email-preview-title');
            const $subject = $('#eao-email-preview-subject');

            // Set title based on email type.
            const titles = {
                'order_confirmation': eaoAdmin.emailTitles?.order_confirmation || 'Order Confirmation Email',
                'new_order_alert': eaoAdmin.emailTitles?.new_order_alert || 'New Order Alert Email',
                'shipped_notification': eaoAdmin.emailTitles?.shipped_notification || 'Shipped Notification Email',
                'cart_reminder': eaoAdmin.emailTitles?.cart_reminder || 'Cart Reminder Email'
            };
            $title.text(titles[emailType] || 'Email Preview');

            // Show loading state.
            $modal.fadeIn(150);
            $frame.attr('srcdoc', '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-family:sans-serif;color:#666;"><p>Loading preview...</p></div>');
            $subject.text('Loading...');

            // Fetch preview via AJAX.
            $.ajax({
                url: eaoAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eao_preview_email',
                    nonce: eaoAdmin.nonce,
                    email_type: emailType
                },
                success: function(response) {
                    if (response.success) {
                        $subject.text(response.data.subject);
                        $frame.attr('srcdoc', response.data.html);
                    } else {
                        $frame.attr('srcdoc', '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-family:sans-serif;color:#c00;"><p>Error loading preview: ' + (response.data || 'Unknown error') + '</p></div>');
                        $subject.text('Error');
                    }
                },
                error: function() {
                    $frame.attr('srcdoc', '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-family:sans-serif;color:#c00;"><p>Failed to load preview. Please try again.</p></div>');
                    $subject.text('Error');
                }
            });
        },

        /**
         * Close the email preview modal.
         */
        closeEmailPreviewModal: function() {
            const $modal = $('#eao-email-preview-modal');
            const $frame = $('#eao-email-preview-frame');

            $modal.fadeOut(150, function() {
                // Clear iframe to stop any loading.
                $frame.attr('srcdoc', '');
            });
        },

        /**
         * Bind cart reminder manual send functionality.
         */
        bindCartReminderSend: function() {
            const $btn = $('#eao-send-reminders-now');
            const $status = $('.eao-send-reminders-status');

            if (!$btn.length) {
                return;
            }

            $btn.on('click', function() {
                const $button = $(this);
                const originalText = $button.html();

                // Disable button and show loading.
                $button.prop('disabled', true).html(
                    '<span class="dashicons dashicons-update spin"></span> ' + 
                    (eaoAdmin.sendingReminders || 'Sending...')
                );
                $status.removeClass('success error').text('');

                // Send AJAX request.
                $.ajax({
                    url: eaoAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eao_send_cart_reminders',
                        nonce: eaoAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.addClass('success').text(response.data.message);
                            // Hide button after successful send.
                            setTimeout(function() {
                                $button.fadeOut();
                            }, 2000);
                        } else {
                            $status.addClass('error').text(response.data || 'Error sending reminders');
                            $button.prop('disabled', false).html(originalText);
                        }
                    },
                    error: function() {
                        $status.addClass('error').text('Failed to send reminders. Please try again.');
                        $button.prop('disabled', false).html(originalText);
                    }
                });
            });
        },

        /**
         * Bind copy webhook URL functionality for Stripe settings.
         */
        bindCopyWebhookUrl: function() {
            $(document).on('click', '.eao-copy-webhook-url', function(e) {
                e.preventDefault();

                const $btn = $(this);
                const targetSelector = $btn.data('copy-target');
                const $input = $(targetSelector);

                if (!$input.length) {
                    return;
                }

                // Select and copy the URL.
                $input.select();
                $input[0].setSelectionRange(0, 99999); // For mobile.

                try {
                    // Try modern clipboard API first.
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText($input.val()).then(function() {
                            showCopiedFeedback($btn);
                        }).catch(function() {
                            // Fallback to execCommand.
                            document.execCommand('copy');
                            showCopiedFeedback($btn);
                        });
                    } else {
                        // Fallback to execCommand.
                        document.execCommand('copy');
                        showCopiedFeedback($btn);
                    }
                } catch (err) {
                    console.error('Failed to copy:', err);
                }

                function showCopiedFeedback($button) {
                    const originalHtml = $button.html();
                    $button.addClass('copied').html(
                        '<span class="dashicons dashicons-yes"></span> ' +
                        (eaoAdmin.copied || 'Copied!')
                    );
                    setTimeout(function() {
                        $button.removeClass('copied').html(originalHtml);
                    }, 2000);
                }
            });
        },

        /**
         * Reorganize Album Orders list table layout.
         * Moves search box inside tablenav for better UX.
         */
        reorganizeAlbumOrdersTable: function() {
            // Only run on album_order list page.
            if (!$('body').hasClass('eao-album-orders-page')) {
                return;
            }

            const $searchBox = $('.search-box');
            const $tablenavTop = $('.tablenav.top');

            if (!$searchBox.length || !$tablenavTop.length) {
                return;
            }

            // Move search box inside tablenav, after tablenav-pages.
            const $tablenavPages = $tablenavTop.find('.tablenav-pages');
            
            if ($tablenavPages.length) {
                // Insert after pagination.
                $searchBox.insertAfter($tablenavPages);
            } else {
                // Insert at end of tablenav.
                $tablenavTop.append($searchBox);
            }

            // Add class for styling.
            $searchBox.addClass('eao-search-box-inline');
        },

        /**
         * Bind Stripe API key validation.
         * Validates key format and provides visual feedback.
         */
        bindStripeKeyValidation: function() {
            // Only run on album options page with payments tab.
            if (!$('.eao-options-page').length) {
                return;
            }

            const keyPatterns = {
                'pk_test': /^pk_test_[a-zA-Z0-9]+$/,
                'sk_test': /^sk_test_[a-zA-Z0-9]+$/,
                'pk_live': /^pk_live_[a-zA-Z0-9]+$/,
                'sk_live': /^sk_live_[a-zA-Z0-9]+$/
            };

            // Key format descriptions for errors.
            const keyDescriptions = {
                'pk_test': 'Test Publishable Key (starts with pk_test_)',
                'sk_test': 'Test Secret Key (starts with sk_test_)',
                'pk_live': 'Live Publishable Key (starts with pk_live_)',
                'sk_live': 'Live Secret Key (starts with sk_live_)'
            };

            // Invalid key formats (restricted keys).
            const invalidPatterns = [
                { pattern: /^rk_/, message: 'This appears to be a Restricted Key. Please use the standard Secret Key instead (sk_test_ or sk_live_).' },
                { pattern: /^sk_test_/, field: 'pk', message: 'This is a Secret Key. The Publishable Key should start with pk_test_ or pk_live_.' },
                { pattern: /^sk_live_/, field: 'pk', message: 'This is a Secret Key. The Publishable Key should start with pk_test_ or pk_live_.' },
                { pattern: /^pk_test_/, field: 'sk', message: 'This is a Publishable Key. The Secret Key should start with sk_test_ or sk_live_.' },
                { pattern: /^pk_live_/, field: 'sk', message: 'This is a Publishable Key. The Secret Key should start with sk_test_ or sk_live_.' }
            ];

            function validateKeyInput($input) {
                const value = $input.val().trim();
                const expectedType = $input.data('key-type');

                // Remove previous validation classes and error messages.
                $input.removeClass('eao-key-valid eao-key-invalid');
                $input.siblings('.eao-key-error').remove();

                if (!value) {
                    return; // Empty is OK (not required).
                }

                // Check for invalid patterns.
                for (const invalid of invalidPatterns) {
                    if (invalid.pattern.test(value)) {
                        // Check if this invalid pattern applies to this field type.
                        const fieldType = expectedType.startsWith('pk') ? 'pk' : 'sk';
                        if (!invalid.field || invalid.field === fieldType) {
                            $input.addClass('eao-key-invalid');
                            $input.after('<span class="eao-key-error description" style="color: #dc3232; display: block; margin-top: 4px;">' + invalid.message + '</span>');
                            return;
                        }
                    }
                }

                // Check for valid pattern.
                const pattern = keyPatterns[expectedType];
                if (pattern && pattern.test(value)) {
                    $input.addClass('eao-key-valid');
                } else if (pattern && value.length > 5) {
                    // Only show invalid if they've typed enough.
                    $input.addClass('eao-key-invalid');
                    $input.after('<span class="eao-key-error description" style="color: #dc3232; display: block; margin-top: 4px;">Invalid format. Expected: ' + keyDescriptions[expectedType] + '</span>');
                }
            }

            // Validate on input.
            $(document).on('input blur', '.eao-key-input', function() {
                validateKeyInput($(this));
            });

            // Validate existing values on page load.
            $('.eao-key-input').each(function() {
                validateKeyInput($(this));
            });
        },

        /**
         * Bind refund modal and actions.
         *
         * @since 1.2.0
         */
        bindRefund: function() {
            const $modal = $('#eao-refund-modal');
            const $btn = $('#eao-refund-btn');

            // Exit if no refund elements on page.
            if (!$modal.length || !$btn.length) {
                return;
            }

            const orderId = $btn.data('order-id');
            const chargeId = $btn.data('charge-id');
            const maxAmount = parseFloat($btn.data('amount'));
            const nonce = $btn.data('nonce');

            // Open modal.
            $btn.on('click', function() {
                $modal.fadeIn(200);
            });

            // Close modal.
            $('#eao-refund-modal-close, #eao-refund-cancel, .eao-refund-modal__backdrop').on('click', function() {
                $modal.fadeOut(200);
            });

            // Close on escape.
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $modal.is(':visible')) {
                    $modal.fadeOut(200);
                }
            });

            // Toggle partial refund input.
            $('input[name="eao_refund_type"]').on('change', function() {
                if ($(this).val() === 'partial') {
                    $('#eao-refund-partial').slideDown(200);
                    $('#eao-refund-amount').focus();
                } else {
                    $('#eao-refund-partial').slideUp(200);
                }
            });

            // Process refund.
            $('#eao-refund-confirm').on('click', function() {
                const $confirmBtn = $(this);
                const $text = $confirmBtn.find('.eao-refund-confirm__text');
                const $loading = $confirmBtn.find('.eao-refund-confirm__loading');
                const refundType = $('input[name="eao_refund_type"]:checked').val();
                const reason = $('#eao-refund-reason').val();

                let amount = maxAmount;
                if (refundType === 'partial') {
                    amount = parseFloat($('#eao-refund-amount').val());
                    if (isNaN(amount) || amount <= 0 || amount > maxAmount) {
                        alert('Please enter a valid refund amount between $0.01 and $' + maxAmount.toFixed(2));
                        return;
                    }
                }

                // Confirm action.
                const confirmMessage = refundType === 'full'
                    ? 'Are you sure you want to refund the full amount of $' + maxAmount.toFixed(2) + '?'
                    : 'Are you sure you want to refund $' + amount.toFixed(2) + '?';

                if (!confirm(confirmMessage)) {
                    return;
                }

                // Show loading state.
                $confirmBtn.prop('disabled', true);
                $text.hide();
                $loading.show();

                // Send AJAX request.
                $.ajax({
                    url: eaoAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eao_process_refund',
                        nonce: nonce,
                        order_id: orderId,
                        charge_id: chargeId,
                        amount: amount,
                        reason: reason,
                        is_full: refundType === 'full' ? 'true' : 'false'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message and reload page.
                            alert(response.data.message);
                            window.location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                            $confirmBtn.prop('disabled', false);
                            $text.show();
                            $loading.hide();
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        $confirmBtn.prop('disabled', false);
                        $text.show();
                        $loading.hide();
                    }
                });
            });
        },

        /**
         * Bind brand color picker functionality.
         * Syncs color input with hex text input and handles reset.
         */
        bindBrandColorPicker: function() {
            const $colorInput = $('#eao_brand_color');
            const $hexInput = $('#eao_brand_color_hex');
            const $resetBtn = $('.eao-color-reset');

            if (!$colorInput.length) {
                return;
            }

            // Sync color picker to hex input.
            $colorInput.on('input change', function() {
                $hexInput.val($(this).val().toUpperCase());
            });

            // Sync hex input to color picker.
            $hexInput.on('input', function() {
                let hex = $(this).val().trim();
                
                // Add # if missing.
                if (hex && !hex.startsWith('#')) {
                    hex = '#' + hex;
                }

                // Validate hex format.
                if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(hex)) {
                    // Expand 3-char hex to 6-char.
                    if (hex.length === 4) {
                        hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
                    }
                    $colorInput.val(hex.toLowerCase());
                    $(this).val(hex.toUpperCase());
                }
            });

            // Handle blur to format/validate.
            $hexInput.on('blur', function() {
                let hex = $(this).val().trim();
                
                // Add # if missing.
                if (hex && !hex.startsWith('#')) {
                    hex = '#' + hex;
                }

                // Validate and fix format.
                if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(hex)) {
                    // Expand 3-char hex to 6-char.
                    if (hex.length === 4) {
                        hex = '#' + hex[1] + hex[1] + hex[2] + hex[2] + hex[3] + hex[3];
                    }
                    $(this).val(hex.toUpperCase());
                    $colorInput.val(hex.toLowerCase());
                } else if (hex) {
                    // Invalid - reset to current color input value.
                    $(this).val($colorInput.val().toUpperCase());
                }
            });

            // Reset button.
            $resetBtn.on('click', function() {
                const defaultColor = $(this).data('default');
                $colorInput.val(defaultColor);
                $hexInput.val(defaultColor.toUpperCase());
            });
        },

        /**
         * Initialize sortable functionality for Materials, Sizes, and Engravings.
         * Uses jQuery UI Sortable for drag and drop reordering.
         *
         * @since 1.0.0
         */
        initSortable: function() {
            const self = this;

            // Only initialize if jQuery UI Sortable is available.
            if (typeof $.fn.sortable !== 'function') {
                return;
            }

            // Materials sortable.
            $('#materials-repeater').sortable({
                items: '.eao-material-card',
                handle: '.eao-sortable-handle',
                axis: 'y',
                cursor: 'grabbing',
                opacity: 0.8,
                placeholder: 'eao-sortable-placeholder eao-sortable-placeholder--material',
                tolerance: 'pointer',
                start: function(e, ui) {
                    ui.placeholder.height(ui.item.outerHeight());
                    ui.item.addClass('eao-is-dragging');
                },
                stop: function(e, ui) {
                    ui.item.removeClass('eao-is-dragging');
                    self.reindexMaterials();
                }
            });

            // Sizes sortable.
            $('#sizes-repeater').sortable({
                items: '.eao-size-card',
                handle: '.eao-sortable-handle',
                axis: 'y',
                cursor: 'grabbing',
                opacity: 0.8,
                placeholder: 'eao-sortable-placeholder eao-sortable-placeholder--size',
                tolerance: 'pointer',
                start: function(e, ui) {
                    ui.placeholder.height(ui.item.outerHeight());
                    ui.item.addClass('eao-is-dragging');
                },
                stop: function(e, ui) {
                    ui.item.removeClass('eao-is-dragging');
                    self.reindexSizes();
                }
            });

            // Engraving sortable.
            $('#engraving-repeater').sortable({
                items: '.eao-engraving-card',
                handle: '.eao-sortable-handle',
                axis: 'y',
                cursor: 'grabbing',
                opacity: 0.8,
                placeholder: 'eao-sortable-placeholder eao-sortable-placeholder--engraving',
                tolerance: 'pointer',
                start: function(e, ui) {
                    ui.placeholder.height(ui.item.outerHeight());
                    ui.item.addClass('eao-is-dragging');
                },
                stop: function(e, ui) {
                    ui.item.removeClass('eao-is-dragging');
                    self.reindexEngraving();
                }
            });
        }
    };

    // Initialize on document ready.
    $(document).ready(function() {
        EAOAdmin.init();
    });

})(jQuery);
