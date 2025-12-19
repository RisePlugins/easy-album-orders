/**
 * Easy Album Orders - Public JavaScript
 *
 * Handles the client-facing order form functionality including
 * material/color selection, price calculation, and cart operations.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

(function($) {
    'use strict';

    /**
     * Public handler object.
     */
    const EAOPublic = {

        // Current selections.
        selections: {
            design: null,
            material: null,
            color: null,
            size: null,
            engraving: null
        },

        // Edit mode.
        editingOrderId: null,

        /**
         * Initialize public functionality.
         */
        init: function() {
            // Only run on order pages.
            if (!$('#eao-order-page').length) {
                return;
            }

            this.bindDesignSelection();
            this.bindMaterialSelection();
            this.bindSizeSelection();
            this.bindEngravingOptions();
            this.bindFormSubmit();
            this.bindCartActions();
            this.bindCheckout();
            this.checkOrderComplete();
        },

        /**
         * Format price with currency.
         *
         * @param {number} price The price to format.
         * @return {string} Formatted price.
         */
        formatPrice: function(price) {
            const currency = eaoPublic.currency || { symbol: '$', position: 'before' };
            const formatted = parseFloat(price).toFixed(2);

            if (currency.position === 'after') {
                return formatted + currency.symbol;
            }
            return currency.symbol + formatted;
        },

        /**
         * Bind design selection.
         */
        bindDesignSelection: function() {
            const self = this;

            $(document).on('click', '.eao-design-card', function() {
                const $card = $(this);
                const $input = $card.find('input[type="radio"]');
                
                // Update selection.
                $('.eao-design-card').removeClass('is-selected');
                $card.addClass('is-selected');
                $input.prop('checked', true);

                // Store selection.
                self.selections.design = {
                    index: $input.val(),
                    basePrice: parseFloat($card.data('base-price')) || 0
                };

                self.updatePriceCalculation();
            });
        },

        /**
         * Bind material selection.
         */
        bindMaterialSelection: function() {
            const self = this;

            $(document).on('click', '.eao-material-card', function() {
                const $card = $(this);
                const $input = $card.find('input[type="radio"]');

                // Update selection.
                $('.eao-material-card').removeClass('is-selected');
                $card.addClass('is-selected');
                $input.prop('checked', true);

                // Store selection.
                self.selections.material = {
                    id: $card.data('material-id'),
                    upcharge: parseFloat($card.data('upcharge')) || 0,
                    allowEngraving: $card.data('allow-engraving') === 1 || $card.data('allow-engraving') === '1',
                    colors: $card.data('colors') || [],
                    restrictedSizes: $card.data('restricted-sizes') || []
                };

                // Reset color selection.
                self.selections.color = null;
                $('#eao-color-id').val('');
                $('#eao-color-name').val('');

                // Update color display.
                self.updateColorDisplay();

                // Update size availability.
                self.updateSizeAvailability();

                // Update engraving visibility.
                self.updateEngravingVisibility();

                // Update price.
                self.updatePriceCalculation();
            });
        },

        /**
         * Update color display based on selected material.
         */
        updateColorDisplay: function() {
            const self = this;
            const $section = $('#eao-color-section');
            const $grid = $('#eao-color-grid');

            if (!self.selections.material || !self.selections.material.colors || self.selections.material.colors.length === 0) {
                $section.hide();
                return;
            }

            // Build color swatches.
            let html = '';
            self.selections.material.colors.forEach(function(color) {
                const style = color.type === 'texture' && color.texture_id
                    ? 'background-image: url(' + (color.texture_url || '') + ');'
                    : 'background-color: ' + (color.color_value || '#ccc') + ';';

                html += '<label class="eao-color-swatch" data-color-id="' + color.id + '" data-color-name="' + color.name + '" style="' + style + '">';
                html += '<input type="radio" name="color_selection" value="' + color.id + '">';
                html += '<span class="eao-color-swatch__tooltip">' + color.name + '</span>';
                html += '</label>';
            });

            $grid.html(html);
            $section.show();

            // Bind color swatch clicks.
            $grid.find('.eao-color-swatch').on('click', function() {
                const $swatch = $(this);

                $('.eao-color-swatch').removeClass('is-selected');
                $swatch.addClass('is-selected');
                $swatch.find('input').prop('checked', true);

                self.selections.color = {
                    id: $swatch.data('color-id'),
                    name: $swatch.data('color-name')
                };

                $('#eao-color-id').val(self.selections.color.id);
                $('#eao-color-name').val(self.selections.color.name);
            });
        },

        /**
         * Update size availability based on material restrictions.
         */
        updateSizeAvailability: function() {
            const self = this;
            const restrictedSizes = self.selections.material ? self.selections.material.restrictedSizes : [];

            $('.eao-size-card').each(function() {
                const $card = $(this);
                const sizeId = $card.data('size-id');

                // If no restrictions, all sizes available.
                if (!restrictedSizes || restrictedSizes.length === 0) {
                    $card.removeClass('is-disabled').find('input').prop('disabled', false);
                    return;
                }

                // Check if this size is in the restricted list.
                if (restrictedSizes.includes(sizeId)) {
                    $card.removeClass('is-disabled').find('input').prop('disabled', false);
                } else {
                    $card.addClass('is-disabled').find('input').prop('disabled', true);
                    
                    // Deselect if currently selected.
                    if ($card.hasClass('is-selected')) {
                        $card.removeClass('is-selected').find('input').prop('checked', false);
                        self.selections.size = null;
                    }
                }
            });
        },

        /**
         * Bind size selection.
         */
        bindSizeSelection: function() {
            const self = this;

            $(document).on('click', '.eao-size-card:not(.is-disabled)', function() {
                const $card = $(this);
                const $input = $card.find('input[type="radio"]');

                // Update selection.
                $('.eao-size-card').removeClass('is-selected');
                $card.addClass('is-selected');
                $input.prop('checked', true);

                // Store selection.
                self.selections.size = {
                    id: $card.data('size-id'),
                    upcharge: parseFloat($card.data('upcharge')) || 0
                };

                self.updatePriceCalculation();
            });
        },

        /**
         * Update engraving section visibility.
         */
        updateEngravingVisibility: function() {
            const self = this;
            const $section = $('#eao-engraving-section');

            if (self.selections.material && self.selections.material.allowEngraving) {
                $section.show();
            } else {
                $section.hide();
                // Reset engraving selection.
                $('#eao-engraving-method').val('');
                $('#eao-engraving-fields').hide();
                self.selections.engraving = null;
                self.updatePriceCalculation();
            }
        },

        /**
         * Bind engraving options.
         */
        bindEngravingOptions: function() {
            const self = this;

            // Method selection.
            $('#eao-engraving-method').on('change', function() {
                const $selected = $(this).find('option:selected');
                const value = $(this).val();

                if (!value) {
                    $('#eao-engraving-fields').hide();
                    self.selections.engraving = null;
                    self.updatePriceCalculation();
                    return;
                }

                // Store selection.
                self.selections.engraving = {
                    id: value,
                    upcharge: parseFloat($selected.data('upcharge')) || 0,
                    charLimit: parseInt($selected.data('char-limit')) || 50,
                    fonts: $selected.data('fonts') || ''
                };

                // Update character limit.
                $('#eao-char-limit').text(self.selections.engraving.charLimit);
                $('#eao-engraving-text').attr('maxlength', self.selections.engraving.charLimit);

                // Update fonts.
                const $fontField = $('#eao-font-field');
                const $fontSelect = $('#eao-engraving-font');
                const fonts = self.selections.engraving.fonts.split('\n').filter(f => f.trim());

                if (fonts.length > 0) {
                    let fontOptions = '<option value="">' + (eaoPublic.i18n?.selectFont || 'Select Font') + '</option>';
                    fonts.forEach(function(font) {
                        fontOptions += '<option value="' + font.trim() + '">' + font.trim() + '</option>';
                    });
                    $fontSelect.html(fontOptions);
                    $fontField.show();
                } else {
                    $fontField.hide();
                }

                $('#eao-engraving-fields').show();
                self.updatePriceCalculation();
            });

            // Character count.
            $('#eao-engraving-text').on('input', function() {
                $('#eao-char-count').text($(this).val().length);
            });
        },

        /**
         * Update price calculation display.
         */
        updatePriceCalculation: function() {
            const self = this;

            // Get values.
            const basePrice = self.selections.design ? self.selections.design.basePrice : 0;
            const materialUpcharge = self.selections.material ? self.selections.material.upcharge : 0;
            const sizeUpcharge = self.selections.size ? self.selections.size.upcharge : 0;
            const engravingUpcharge = self.selections.engraving ? self.selections.engraving.upcharge : 0;
            const credits = typeof eaoOrderData !== 'undefined' ? parseFloat(eaoOrderData.credits) || 0 : 0;

            // Calculate total.
            const subtotal = basePrice + materialUpcharge + sizeUpcharge + engravingUpcharge;
            const total = Math.max(0, subtotal - credits);

            // Update display.
            $('#eao-price-base .eao-price-line__value').text(self.formatPrice(basePrice)).attr('data-value', basePrice);

            if (materialUpcharge > 0) {
                $('#eao-price-material').show().find('.eao-price-line__value').text('+ ' + self.formatPrice(materialUpcharge)).attr('data-value', materialUpcharge);
            } else {
                $('#eao-price-material').hide();
            }

            if (sizeUpcharge > 0) {
                $('#eao-price-size').show().find('.eao-price-line__value').text('+ ' + self.formatPrice(sizeUpcharge)).attr('data-value', sizeUpcharge);
            } else {
                $('#eao-price-size').hide();
            }

            if (engravingUpcharge > 0) {
                $('#eao-price-engraving').show().find('.eao-price-line__value').text('+ ' + self.formatPrice(engravingUpcharge)).attr('data-value', engravingUpcharge);
            } else {
                $('#eao-price-engraving').hide();
            }

            $('#eao-price-total .eao-price-line__value').text(self.formatPrice(total)).attr('data-value', total);
            $('#eao-calculated-total').val(total);
        },

        /**
         * Validate the order form.
         *
         * @return {boolean} True if valid, false otherwise.
         */
        validateForm: function() {
            const self = this;

            // Check album name.
            const albumName = $('#eao-album-name').val().trim();
            if (!albumName) {
                self.showMessage('error', eaoPublic.i18n?.enterAlbumName || 'Please enter an album name.');
                $('#eao-album-name').focus();
                return false;
            }

            // Check design selection.
            if (!self.selections.design) {
                self.showMessage('error', eaoPublic.i18n?.selectDesign || 'Please select a design.');
                return false;
            }

            // Check material selection.
            if (!self.selections.material) {
                self.showMessage('error', eaoPublic.i18n?.selectMaterial || 'Please select a material.');
                return false;
            }

            // Check size selection.
            if (!self.selections.size) {
                self.showMessage('error', eaoPublic.i18n?.selectSize || 'Please select a size.');
                return false;
            }

            return true;
        },

        /**
         * Bind form submission.
         */
        bindFormSubmit: function() {
            const self = this;

            $('#eao-order-form').on('submit', function(e) {
                e.preventDefault();

                // Validate.
                if (!self.validateForm()) {
                    return;
                }

                const $form = $(this);
                const $submitBtn = $('#eao-add-to-cart-btn');
                const $btnText = $submitBtn.find('.eao-btn-text');
                const $spinner = $submitBtn.find('.eao-spinner');

                // Disable button and show loading.
                $submitBtn.prop('disabled', true);
                $btnText.text(self.editingOrderId ? 'Updating...' : 'Adding...');
                $spinner.show();

                // Build data.
                const data = {
                    action: self.editingOrderId ? 'eao_update_cart_item' : 'eao_add_to_cart',
                    nonce: eaoPublic.nonce,
                    client_album_id: eaoPublic.clientAlbumId,
                    album_name: $('#eao-album-name').val(),
                    design_index: self.selections.design.index,
                    material_id: self.selections.material.id,
                    color_id: self.selections.color ? self.selections.color.id : '',
                    color_name: self.selections.color ? self.selections.color.name : '',
                    size_id: self.selections.size.id,
                    engraving_method: self.selections.engraving ? self.selections.engraving.id : '',
                    engraving_text: $('#eao-engraving-text').val(),
                    engraving_font: $('#eao-engraving-font').val()
                };

                if (self.editingOrderId) {
                    data.order_id = self.editingOrderId;
                }

                // Submit via AJAX.
                $.ajax({
                    url: eaoPublic.ajaxUrl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            self.updateCart(response.data);
                            self.resetForm();
                            self.showMessage('success', response.data.message);
                        } else {
                            self.showMessage('error', response.data.message);
                        }
                    },
                    error: function() {
                        self.showMessage('error', eaoPublic.i18n?.errorOccurred || 'An error occurred. Please try again.');
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                        $btnText.text(eaoPublic.i18n?.addToCart || 'Add to Cart');
                        $spinner.hide();
                        self.editingOrderId = null;
                    }
                });
            });
        },

        /**
         * Reset the form after submission.
         */
        resetForm: function() {
            const self = this;

            // Reset form fields.
            $('#eao-order-form')[0].reset();

            // Clear selections.
            self.selections = {
                design: null,
                material: null,
                color: null,
                size: null,
                engraving: null
            };

            // Clear UI.
            $('.eao-selection-card').removeClass('is-selected');
            $('.eao-color-swatch').removeClass('is-selected');
            $('#eao-color-section').hide();
            $('#eao-engraving-section').hide();
            $('#eao-engraving-fields').hide();

            // Reset price display.
            self.updatePriceCalculation();

            // Clear edit mode.
            self.editingOrderId = null;
            $('#eao-add-to-cart-btn .eao-btn-text').text(eaoPublic.i18n?.addToCart || 'Add to Cart');
        },

        /**
         * Update cart display.
         *
         * @param {Object} data Response data from server.
         */
        updateCart: function(data) {
            // Update cart HTML.
            $('#eao-cart-items').html(data.cart_html);

            // Update count.
            $('#eao-cart-count').text(data.cart_count);

            // Update total.
            $('#eao-cart-total').text(data.cart_total);

            // Show/hide footer.
            if (data.cart_count > 0) {
                $('#eao-cart-footer').show();
            } else {
                $('#eao-cart-footer').hide();
            }
        },

        /**
         * Bind cart action buttons.
         */
        bindCartActions: function() {
            const self = this;

            // Edit cart item.
            $(document).on('click', '.eao-cart__item-btn--edit', function() {
                const orderId = $(this).data('order-id');
                self.loadOrderForEdit(orderId);
            });

            // Remove cart item.
            $(document).on('click', '.eao-cart__item-btn--remove', function() {
                const $btn = $(this);
                const orderId = $btn.data('order-id');

                if (confirm(eaoPublic.i18n?.confirmRemove || 'Are you sure you want to remove this album?')) {
                    $btn.prop('disabled', true);

                    $.ajax({
                        url: eaoPublic.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'eao_remove_from_cart',
                            nonce: eaoPublic.nonce,
                            order_id: orderId
                        },
                        success: function(response) {
                            if (response.success) {
                                self.updateCart(response.data);
                                self.showMessage('info', response.data.message);
                            }
                        },
                        complete: function() {
                            $btn.prop('disabled', false);
                        }
                    });
                }
            });
        },

        /**
         * Load order data for editing.
         *
         * @param {number} orderId The order ID to edit.
         */
        loadOrderForEdit: function(orderId) {
            const self = this;

            $.ajax({
                url: eaoPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eao_get_order_for_edit',
                    nonce: eaoPublic.nonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        self.populateFormForEdit(response.data);
                    }
                }
            });
        },

        /**
         * Populate form with order data for editing.
         *
         * @param {Object} data Order data.
         */
        populateFormForEdit: function(data) {
            const self = this;

            // Set edit mode.
            self.editingOrderId = data.order_id;

            // Populate album name.
            $('#eao-album-name').val(data.album_name);

            // Select design.
            const $designCard = $('.eao-design-card').eq(data.design_index);
            if ($designCard.length) {
                $designCard.trigger('click');
            }

            // Select material.
            const $materialCard = $('.eao-material-card[data-material-id="' + data.material_id + '"]');
            if ($materialCard.length) {
                $materialCard.trigger('click');

                // Wait for colors to render, then select color.
                setTimeout(function() {
                    if (data.color_id) {
                        const $colorSwatch = $('.eao-color-swatch[data-color-id="' + data.color_id + '"]');
                        if ($colorSwatch.length) {
                            $colorSwatch.trigger('click');
                        }
                    }
                }, 100);
            }

            // Select size.
            const $sizeCard = $('.eao-size-card[data-size-id="' + data.size_id + '"]');
            if ($sizeCard.length) {
                $sizeCard.trigger('click');
            }

            // Select engraving.
            if (data.engraving_method) {
                $('#eao-engraving-method').val(data.engraving_method).trigger('change');
                $('#eao-engraving-text').val(data.engraving_text);
                $('#eao-engraving-font').val(data.engraving_font);
                $('#eao-char-count').text(data.engraving_text.length);
            }

            // Update button text.
            $('#eao-add-to-cart-btn .eao-btn-text').text(eaoPublic.i18n?.updateCart || 'Update Cart');

            // Scroll to form.
            $('html, body').animate({
                scrollTop: $('#eao-order-form').offset().top - 50
            }, 300);

            self.showMessage('info', 'Editing album. Make your changes and click "Update Cart".');
        },

        /**
         * Bind checkout button.
         */
        bindCheckout: function() {
            const self = this;

            $('#eao-checkout-btn').on('click', function() {
                if (!confirm(eaoPublic.i18n?.confirmCheckout || 'Are you sure you want to complete this order? You will not be able to make changes after checkout.')) {
                    return;
                }

                const $btn = $(this);
                $btn.prop('disabled', true).text('Processing...');

                $.ajax({
                    url: eaoPublic.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eao_checkout',
                        nonce: eaoPublic.nonce,
                        client_album_id: eaoPublic.clientAlbumId
                    },
                    success: function(response) {
                        if (response.success) {
                            // Redirect to confirmation.
                            window.location.href = response.data.redirect_url;
                        } else {
                            self.showMessage('error', response.data.message);
                            $btn.prop('disabled', false).text(eaoPublic.i18n?.checkout || 'Complete Order');
                        }
                    },
                    error: function() {
                        self.showMessage('error', eaoPublic.i18n?.errorOccurred || 'An error occurred. Please try again.');
                        $btn.prop('disabled', false).text(eaoPublic.i18n?.checkout || 'Complete Order');
                    }
                });
            });
        },

        /**
         * Check if order was just completed and show message.
         */
        checkOrderComplete: function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('order_complete') === '1') {
                this.showMessage('success', 'Thank you! Your order has been submitted successfully. You will receive a confirmation email shortly.');
                
                // Clean up URL.
                if (history.replaceState) {
                    const cleanUrl = window.location.href.split('?')[0];
                    history.replaceState(null, null, cleanUrl);
                }
            }
        },

        /**
         * Show a message to the user.
         *
         * @param {string} type    Message type (success, error, info).
         * @param {string} message The message text.
         */
        showMessage: function(type, message) {
            const $container = $('#eao-messages');
            const $message = $('<div class="eao-message eao-message--' + type + '">' + message + '</div>');

            // Remove existing messages.
            $container.empty();

            // Add new message.
            $container.append($message);

            // Scroll to message.
            $('html, body').animate({
                scrollTop: $container.offset().top - 100
            }, 300);

            // Auto-remove after 8 seconds.
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 8000);
        }
    };

    // Initialize on document ready.
    $(document).ready(function() {
        EAOPublic.init();
    });

})(jQuery);
