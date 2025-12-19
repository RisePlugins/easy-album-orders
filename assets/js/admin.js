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
         * Initialize admin functionality.
         */
        init: function() {
            this.bindTabs();
            this.bindRepeaters();
            this.bindImageUpload();
            this.bindColorTypeToggle();
            this.bindNameInputs();
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
         * Bind repeater functionality.
         */
        bindRepeaters: function() {
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
                if (confirm(eaoAdmin.confirmDelete)) {
                    $(this).closest('.eao-repeater__item').slideUp(200, function() {
                        $(this).remove();
                        self.reindexItems();
                    });
                }
            });

            // Add material.
            $('.eao-add-material').on('click', function() {
                self.addMaterial();
            });

            // Add size.
            $('.eao-add-size').on('click', function() {
                self.addSize();
            });

            // Add engraving.
            $('.eao-add-engraving').on('click', function() {
                self.addEngraving();
            });

            // Add color.
            $(document).on('click', '.eao-add-color', function() {
                self.addColor($(this).closest('.eao-material-item'));
            });

            // Remove color.
            $(document).on('click', '.eao-sub-repeater__remove', function() {
                $(this).closest('.eao-sub-repeater__item').fadeOut(200, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Add a new material item.
         */
        addMaterial: function() {
            const $container = $('#materials-repeater .eao-repeater__items');
            const index = $container.find('.eao-material-item').length;
            const template = wp.template('eao-material-item');

            const $newItem = $(template({
                index: index,
                id: this.generateId()
            }));

            $container.append($newItem);
            $newItem.addClass('is-open');
            $newItem.find('.eao-material-name-input').focus();
        },

        /**
         * Add a new size item.
         */
        addSize: function() {
            const $container = $('#sizes-repeater .eao-repeater__items');
            const index = $container.find('.eao-size-item').length;
            const template = wp.template('eao-size-item');

            const $newItem = $(template({
                index: index,
                id: this.generateId()
            }));

            $container.append($newItem);
            $newItem.find('.eao-size-name-input').focus();
        },

        /**
         * Add a new engraving item.
         */
        addEngraving: function() {
            const $container = $('#engraving-repeater .eao-repeater__items');
            const index = $container.find('.eao-engraving-item').length;
            const template = wp.template('eao-engraving-item');

            const $newItem = $(template({
                index: index,
                id: this.generateId()
            }));

            $container.append($newItem);
            $newItem.addClass('is-open');
            $newItem.find('.eao-engraving-name-input').focus();
        },

        /**
         * Add a new color to a material.
         *
         * @param {jQuery} $materialItem The material item element.
         */
        addColor: function($materialItem) {
            const $container = $materialItem.find('.eao-sub-repeater__items');
            const materialIndex = $materialItem.data('index');
            const colorIndex = $container.find('.eao-color-item').length;
            const template = wp.template('eao-color-item');

            const $newItem = $(template({
                materialIndex: materialIndex,
                colorIndex: colorIndex,
                id: this.generateId()
            }));

            $container.append($newItem);
            $newItem.find('input[type="text"]').first().focus();
        },

        /**
         * Reindex all items after removal.
         */
        reindexItems: function() {
            // Reindex materials.
            $('.eao-material-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name^="eao_materials"]').each(function() {
                    const name = $(this).attr('name');
                    $(this).attr('name', name.replace(/eao_materials\[\d+\]/, 'eao_materials[' + index + ']'));
                });
            });

            // Reindex sizes.
            $('.eao-size-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name^="eao_sizes"]').each(function() {
                    const name = $(this).attr('name');
                    $(this).attr('name', name.replace(/eao_sizes\[\d+\]/, 'eao_sizes[' + index + ']'));
                });
            });

            // Reindex engraving options.
            $('.eao-engraving-item').each(function(index) {
                $(this).attr('data-index', index);
                $(this).find('[name^="eao_engraving_options"]').each(function() {
                    const name = $(this).attr('name');
                    $(this).attr('name', name.replace(/eao_engraving_options\[\d+\]/, 'eao_engraving_options[' + index + ']'));
                });
            });
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
                    title: eaoAdmin.mediaTitle,
                    button: {
                        text: eaoAdmin.mediaButton
                    },
                    multiple: false
                });

                // When image is selected.
                mediaFrame.on('select', function() {
                    const attachment = mediaFrame.state().get('selection').first().toJSON();
                    const thumbUrl = attachment.sizes && attachment.sizes.thumbnail
                        ? attachment.sizes.thumbnail.url
                        : attachment.url;

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
         * Bind color type toggle (solid/texture).
         */
        bindColorTypeToggle: function() {
            $(document).on('change', '.eao-color-type-select', function() {
                const $colorPicker = $(this).siblings('.eao-color-picker');
                if ($(this).val() === 'texture') {
                    $colorPicker.hide();
                } else {
                    $colorPicker.show();
                }
            });
        },

        /**
         * Bind name input changes to update header titles.
         */
        bindNameInputs: function() {
            // Material name.
            $(document).on('input', '.eao-material-name-input', function() {
                const name = $(this).val() || 'New Material';
                $(this).closest('.eao-repeater__item').find('.eao-repeater__item-title').text(name);
            });

            // Size name.
            $(document).on('input', '.eao-size-name-input', function() {
                const name = $(this).val() || 'New Size';
                $(this).closest('.eao-repeater__item').find('.eao-repeater__item-title').text(name);
            });

            // Engraving name.
            $(document).on('input', '.eao-engraving-name-input', function() {
                const name = $(this).val() || 'New Engraving Method';
                $(this).closest('.eao-repeater__item').find('.eao-repeater__item-title').text(name);
            });
        }
    };

    // Initialize on document ready.
    $(document).ready(function() {
        EAOAdmin.init();
    });

})(jQuery);

