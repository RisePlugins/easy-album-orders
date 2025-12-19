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
         * Bind tab navigation.
         */
        bindTabs: function() {
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
            if (hash && $('.eao-tabs .nav-tab[data-tab="' + hash + '"]').length) {
                $('.eao-tabs .nav-tab[data-tab="' + hash + '"]').trigger('click');
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
                if ($(this).val() === 'solid') {
                    $('.eao-color-picker-field').show();
                } else {
                    $('.eao-color-picker-field').hide();
                }
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
         * Open the color modal.
         *
         * @param {jQuery} $swatch Optional existing swatch to edit.
         */
        openColorModal: function($swatch) {
            const $modal = $('#eao-color-modal');

            if ($swatch) {
                // Editing existing color.
                const name = $swatch.find('.eao-color-name-input').val();
                const type = $swatch.find('.eao-color-type-input').val();
                const colorValue = $swatch.find('.eao-color-value-input').val();

                $('#eao-modal-color-name').val(name);
                $('input[name="eao_modal_color_type"][value="' + type + '"]').prop('checked', true).trigger('change');
                $('#eao-modal-color-value').val(colorValue || '#000000');
                $('#eao-modal-color-hex').text((colorValue || '#000000').toUpperCase());
                $modal.find('.eao-modal__header h3').text(eaoAdmin.editColor || 'Edit Color');
            } else {
                // Adding new color.
                $('#eao-modal-color-name').val('');
                $('input[name="eao_modal_color_type"][value="solid"]').prop('checked', true).trigger('change');
                $('#eao-modal-color-value').val('#000000');
                $('#eao-modal-color-hex').text('#000000');
                $modal.find('.eao-modal__header h3').text(eaoAdmin.addColor || 'Add Color');
            }

            $modal.fadeIn(150);
            $('#eao-modal-color-name').focus();
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

            if (!name) {
                $('#eao-modal-color-name').focus();
                return;
            }

            if (this.currentColorSwatch) {
                // Update existing swatch.
                this.updateColorSwatch(this.currentColorSwatch, name, type, colorValue);
            } else {
                // Add new swatch.
                this.addColorSwatch(name, type, colorValue);
            }

            this.closeColorModal();
        },

        /**
         * Update an existing color swatch.
         */
        updateColorSwatch: function($swatch, name, type, colorValue) {
            $swatch.find('.eao-color-name-input').val(name);
            $swatch.find('.eao-color-type-input').val(type);
            $swatch.find('.eao-color-value-input').val(colorValue);
            $swatch.find('.eao-color-swatch__name').text(name);

            const $circle = $swatch.find('.eao-color-swatch__circle');
            $circle.attr('title', name);

            if (type === 'solid') {
                $circle.css('background', colorValue);
                $circle.html('');
            } else {
                $circle.css('background', 'linear-gradient(135deg, #ddd 25%, #999 50%, #ddd 75%)');
                $circle.html('<span class="eao-color-swatch__texture-icon"><span class="dashicons dashicons-format-image"></span></span>');
            }
        },

        /**
         * Add a new color swatch.
         */
        addColorSwatch: function(name, type, colorValue) {
            const $materialCard = this.currentMaterialCard;
            const materialIndex = $materialCard.data('index');
            const $colorsGrid = $materialCard.find('.eao-colors-grid');
            const colorIndex = $colorsGrid.find('.eao-color-swatch:not(.eao-color-swatch--add)').length;

            const template = wp.template('eao-color-swatch');
            const $newSwatch = $(template({
                materialIndex: materialIndex,
                colorIndex: colorIndex,
                id: this.generateId(),
                name: name,
                type: type,
                colorValue: colorValue
            }));

            // Update the circle style based on type.
            const $circle = $newSwatch.find('.eao-color-swatch__circle');
            if (type === 'solid') {
                $circle.css('background-color', colorValue);
            } else {
                $circle.css('background', 'linear-gradient(135deg, #ddd 25%, #999 50%, #ddd 75%)');
                $circle.html('<span class="eao-color-swatch__texture-icon"><span class="dashicons dashicons-format-image"></span></span>');
            }

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

            // Design name update.
            $(document).on('input', '.eao-design-name-input', function() {
                const name = $(this).val() || 'New Design';
                $(this).closest('.eao-repeater__item').find('.eao-repeater__item-title').text(name);
            });
        },

        /**
         * Add a new design item.
         */
        addDesign: function() {
            const $container = $('#eao-designs-repeater .eao-repeater__items');
            const index = $container.find('.eao-design-item').length;
            const template = wp.template('eao-design-item');

            const $newItem = $(template({
                index: index
            }));

            $container.append($newItem);
            $newItem.addClass('is-open');
            $newItem.find('.eao-design-name-input').focus();
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
        }
    };

    // Initialize on document ready.
    $(document).ready(function() {
        EAOAdmin.init();
    });

})(jQuery);
