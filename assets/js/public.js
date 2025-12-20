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

        // Selected address.
        selectedAddressId: 'new',

        // Cart token for identifying this browser's cart.
        cartToken: null,

        /**
         * Initialize public functionality.
         */
        init: function() {
            // Only run on order pages.
            if (!$('#eao-order-page').length) {
                return;
            }

            // Initialize cart token first.
            this.initCartToken();

            this.bindDesignSelection();
            this.bindMaterialSelection();
            this.bindSizeSelection();
            this.bindEngravingOptions();
            this.bindAddressSelection();
            this.bindFormSubmit();
            this.bindCartActions();
            this.bindCheckout();
            this.checkOrderComplete();

            // Load cart with token.
            this.refreshCart();
        },

        /**
         * Initialize or retrieve cart token from localStorage.
         * Each browser gets a unique token to keep carts separate.
         */
        initCartToken: function() {
            const storageKey = 'eao_cart_token_' + eaoPublic.clientAlbumId;

            // Try to get existing token.
            let token = localStorage.getItem(storageKey);

            // Generate new token if none exists.
            if (!token) {
                token = this.generateUUID();
                localStorage.setItem(storageKey, token);
            }

            this.cartToken = token;
        },

        /**
         * Generate a UUID v4.
         *
         * @return {string} UUID string.
         */
        generateUUID: function() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },

        /**
         * Get the cart token.
         *
         * @return {string} Cart token.
         */
        getCartToken: function() {
            return this.cartToken || '';
        },

        /**
         * Refresh cart display from server.
         */
        refreshCart: function() {
            const self = this;

            $.ajax({
                url: eaoPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eao_get_cart',
                    nonce: eaoPublic.nonce,
                    client_album_id: eaoPublic.clientAlbumId,
                    cart_token: self.getCartToken()
                },
                success: function(response) {
                    if (response.success) {
                        self.updateCart(response.data);
                    }
                }
            });
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

                // Store selection with credit info.
                self.selections.design = {
                    index: $input.val(),
                    basePrice: parseFloat($card.data('base-price')) || 0,
                    freeCredits: parseInt($card.data('free-credits')) || 0,
                    dollarCredit: parseFloat($card.data('dollar-credit')) || 0
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
         * Bind address selection.
         */
        bindAddressSelection: function() {
            const self = this;

            // Address card selection.
            $(document).on('click', '.eao-address-card', function(e) {
                // Don't select if clicking delete button.
                if ($(e.target).closest('.eao-address-card__delete').length) {
                    return;
                }

                const $card = $(this);
                const addressId = $card.data('address-id');

                // Update selection.
                $('.eao-address-card').removeClass('is-selected');
                $card.addClass('is-selected');

                self.selectedAddressId = addressId;
                $('#eao-selected-address-id').val(addressId);

                // Handle form display.
                const $form = $('#eao-address-form');

                if (addressId === 'new') {
                    // Show form for new address.
                    $form.removeClass('is-hidden using-saved').show();
                    self.clearAddressForm();
                } else {
                    // Hide form and store address data for submission.
                    const addressData = $card.data('address');
                    if (addressData) {
                        self.fillAddressForm(addressData);
                        $form.addClass('is-hidden using-saved').hide();
                    }
                }
            });

            // Delete saved address.
            $(document).on('click', '.eao-address-card__delete', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $card = $(this).closest('.eao-address-card');
                const addressId = $card.data('address-id');

                if (confirm(eaoPublic.i18n?.confirmDeleteAddress || 'Are you sure you want to delete this address?')) {
                    self.deleteAddress(addressId, $card);
                }
            });
        },

        /**
         * Fill address form with saved address data.
         *
         * @param {Object} address Address data.
         */
        fillAddressForm: function(address) {
            $('#eao-shipping-name').val(address.name || '');
            $('#eao-shipping-address1').val(address.address1 || '');
            $('#eao-shipping-address2').val(address.address2 || '');
            $('#eao-shipping-city').val(address.city || '');
            $('#eao-shipping-state').val(address.state || '');
            $('#eao-shipping-zip').val(address.zip || '');
        },

        /**
         * Clear address form fields.
         */
        clearAddressForm: function() {
            $('#eao-shipping-name').val('');
            $('#eao-shipping-address1').val('');
            $('#eao-shipping-address2').val('');
            $('#eao-shipping-city').val('');
            $('#eao-shipping-state').val('');
            $('#eao-shipping-zip').val('');
            $('#eao-save-address').prop('checked', false);
        },

        /**
         * Delete a saved address.
         *
         * @param {string} addressId The address ID to delete.
         * @param {jQuery} $card     The card element.
         */
        deleteAddress: function(addressId, $card) {
            const self = this;

            $.ajax({
                url: eaoPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eao_delete_address',
                    nonce: eaoPublic.nonce,
                    client_album_id: eaoPublic.clientAlbumId,
                    address_id: addressId
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the card.
                        $card.fadeOut(200, function() {
                            $(this).remove();

                            // If the deleted card was selected, select "New Address".
                            if (self.selectedAddressId === addressId) {
                                $('.eao-address-card--new').trigger('click');
                            }
                        });
                    }
                }
            });
        },

        /**
         * Save a new address.
         *
         * @param {Function} callback Callback after save.
         */
        saveAddress: function(callback) {
            const self = this;

            $.ajax({
                url: eaoPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eao_save_address',
                    nonce: eaoPublic.nonce,
                    client_album_id: eaoPublic.clientAlbumId,
                    shipping_name: $('#eao-shipping-name').val(),
                    shipping_address1: $('#eao-shipping-address1').val(),
                    shipping_address2: $('#eao-shipping-address2').val(),
                    shipping_city: $('#eao-shipping-city').val(),
                    shipping_state: $('#eao-shipping-state').val(),
                    shipping_zip: $('#eao-shipping-zip').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Add the new address card.
                        self.addAddressCard(response.data.address);

                        if (callback) {
                            callback();
                        }
                    }
                }
            });
        },

        /**
         * Add a new address card to the grid.
         *
         * @param {Object} address Address data.
         */
        addAddressCard: function(address) {
            const $grid = $('#eao-address-grid');
            const addressJson = JSON.stringify(address).replace(/"/g, '&quot;');

            let addressHtml = address.address1;
            if (address.address2) {
                addressHtml += '<br>' + address.address2;
            }
            addressHtml += '<br>' + address.city + ', ' + address.state + ' ' + address.zip;

            const $card = $(`
                <div class="eao-address-card" 
                     data-address-id="${address.id}"
                     data-address='${addressJson}'>
                    <button type="button" class="eao-address-card__delete" title="${eaoPublic.i18n?.deleteAddress || 'Delete address'}">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    <div class="eao-address-card__name">${address.name}</div>
                    <div class="eao-address-card__address">${addressHtml}</div>
                </div>
            `);

            $grid.append($card);
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

            // Calculate subtotal first.
            const subtotal = basePrice + materialUpcharge + sizeUpcharge + engravingUpcharge;

            // Determine design-specific credits.
            let creditAmount = 0;
            let creditLabel = '';

            if (self.selections.design) {
                // Free album credits take priority - they cover the base price.
                if (self.selections.design.freeCredits > 0) {
                    creditAmount = basePrice;
                    creditLabel = eaoPublic.i18n?.freeAlbumCredit || 'Free Album Credit';
                } else if (self.selections.design.dollarCredit > 0) {
                    // Dollar credit applies up to available amount, but not more than subtotal.
                    creditAmount = Math.min(self.selections.design.dollarCredit, subtotal);
                    creditLabel = eaoPublic.i18n?.albumCredit || 'Album Credit';
                }
            }

            // Calculate total.
            const total = Math.max(0, subtotal - creditAmount);

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

            // Show/hide credit line based on design selection.
            if (creditAmount > 0) {
                $('#eao-price-credit').show()
                    .find('.eao-price-line__label').text(creditLabel).end()
                    .find('.eao-price-line__value').text('- ' + self.formatPrice(creditAmount)).attr('data-value', creditAmount);
            } else {
                $('#eao-price-credit').hide();
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

            // Check shipping address fields.
            if (!$('#eao-shipping-name').val().trim()) {
                self.showMessage('error', eaoPublic.i18n?.enterShippingName || 'Please enter the recipient name.');
                $('#eao-shipping-name').focus();
                return false;
            }

            if (!$('#eao-shipping-address1').val().trim()) {
                self.showMessage('error', eaoPublic.i18n?.enterShippingAddress || 'Please enter a street address.');
                $('#eao-shipping-address1').focus();
                return false;
            }

            if (!$('#eao-shipping-city').val().trim()) {
                self.showMessage('error', eaoPublic.i18n?.enterShippingCity || 'Please enter a city.');
                $('#eao-shipping-city').focus();
                return false;
            }

            if (!$('#eao-shipping-state').val().trim()) {
                self.showMessage('error', eaoPublic.i18n?.enterShippingState || 'Please enter a state.');
                $('#eao-shipping-state').focus();
                return false;
            }

            if (!$('#eao-shipping-zip').val().trim()) {
                self.showMessage('error', eaoPublic.i18n?.enterShippingZip || 'Please enter a ZIP code.');
                $('#eao-shipping-zip').focus();
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
                    cart_token: self.getCartToken(),
                    album_name: $('#eao-album-name').val(),
                    design_index: self.selections.design.index,
                    material_id: self.selections.material.id,
                    color_id: self.selections.color ? self.selections.color.id : '',
                    color_name: self.selections.color ? self.selections.color.name : '',
                    size_id: self.selections.size.id,
                    engraving_method: self.selections.engraving ? self.selections.engraving.id : '',
                    engraving_text: $('#eao-engraving-text').val(),
                    engraving_font: $('#eao-engraving-font').val(),
                    shipping_name: $('#eao-shipping-name').val(),
                    shipping_address1: $('#eao-shipping-address1').val(),
                    shipping_address2: $('#eao-shipping-address2').val(),
                    shipping_city: $('#eao-shipping-city').val(),
                    shipping_state: $('#eao-shipping-state').val(),
                    shipping_zip: $('#eao-shipping-zip').val()
                };

                if (self.editingOrderId) {
                    data.order_id = self.editingOrderId;
                }

                // Function to submit the order.
                const submitOrder = function() {
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
                };

                // Check if we need to save the address first.
                const shouldSaveAddress = $('#eao-save-address').is(':checked') && self.selectedAddressId === 'new';

                if (shouldSaveAddress) {
                    self.saveAddress(submitOrder);
                } else {
                    submitOrder();
                }
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

            // Reset address selector to "New Address".
            $('.eao-address-card').removeClass('is-selected');
            $('.eao-address-card--new').addClass('is-selected');
            self.selectedAddressId = 'new';
            $('#eao-selected-address-id').val('new');
            $('#eao-address-form').removeClass('is-hidden using-saved').show();
            self.clearAddressForm();

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
                            order_id: orderId,
                            cart_token: self.getCartToken()
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
                    order_id: orderId,
                    cart_token: self.getCartToken()
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

            // Populate shipping address fields.
            // Try to find a matching saved address, otherwise use "new" with filled data.
            let matchingAddressId = null;
            
            $('.eao-address-card:not(.eao-address-card--new)').each(function() {
                const addressData = $(this).data('address');
                if (addressData && 
                    addressData.name === data.shipping_name &&
                    addressData.address1 === data.shipping_address1 &&
                    addressData.city === data.shipping_city &&
                    addressData.state === data.shipping_state &&
                    addressData.zip === data.shipping_zip) {
                    matchingAddressId = addressData.id;
                    return false; // Break the loop.
                }
            });

            if (matchingAddressId) {
                // Select the matching saved address (form will be hidden).
                const $matchingCard = $('.eao-address-card[data-address-id="' + matchingAddressId + '"]');
                $('.eao-address-card').removeClass('is-selected');
                $matchingCard.addClass('is-selected');
                self.selectedAddressId = matchingAddressId;
                $('#eao-selected-address-id').val(matchingAddressId);
                
                // Fill and hide the form.
                const addressData = $matchingCard.data('address');
                if (addressData) {
                    self.fillAddressForm(addressData);
                }
                $('#eao-address-form').addClass('is-hidden using-saved').hide();
            } else {
                // Select "New Address" and fill the form (form visible).
                $('.eao-address-card').removeClass('is-selected');
                $('.eao-address-card--new').addClass('is-selected');
                self.selectedAddressId = 'new';
                $('#eao-selected-address-id').val('new');
                $('#eao-address-form').removeClass('is-hidden using-saved').show();
                
                if (data.shipping_name) {
                    $('#eao-shipping-name').val(data.shipping_name);
                }
                if (data.shipping_address1) {
                    $('#eao-shipping-address1').val(data.shipping_address1);
                }
                if (data.shipping_address2) {
                    $('#eao-shipping-address2').val(data.shipping_address2);
                }
                if (data.shipping_city) {
                    $('#eao-shipping-city').val(data.shipping_city);
                }
                if (data.shipping_state) {
                    $('#eao-shipping-state').val(data.shipping_state);
                }
                if (data.shipping_zip) {
                    $('#eao-shipping-zip').val(data.shipping_zip);
                }
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

            // Open checkout modal.
            $('#eao-checkout-btn').on('click', function() {
                self.openCheckoutModal();
            });

            // Close modal handlers.
            $('#eao-modal-close, .eao-modal__backdrop').on('click', function() {
                self.closeCheckoutModal();
            });

            // Close on escape key.
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#eao-checkout-modal').is(':visible')) {
                    self.closeCheckoutModal();
                }
            });

            // Submit order from modal.
            $('#eao-submit-order-btn').on('click', function() {
                self.submitCheckout();
            });

            // Also submit on form enter.
            $('#eao-checkout-form').on('submit', function(e) {
                e.preventDefault();
                self.submitCheckout();
            });
        },

        /**
         * Open the checkout modal.
         */
        openCheckoutModal: function() {
            // Update modal total.
            $('#eao-modal-total').text($('#eao-cart-total').text());
            
            // Show modal.
            $('#eao-checkout-modal').fadeIn(200);
            $('body').css('overflow', 'hidden');
            
            // Focus first input.
            setTimeout(function() {
                $('#eao-customer-name').focus();
            }, 200);
        },

        /**
         * Close the checkout modal.
         */
        closeCheckoutModal: function() {
            $('#eao-checkout-modal').fadeOut(200);
            $('body').css('overflow', '');
        },

        /**
         * Submit checkout with customer info.
         */
        submitCheckout: function() {
            const self = this;

            // Validate required fields.
            const customerName = $('#eao-customer-name').val().trim();
            const customerEmail = $('#eao-customer-email').val().trim();

            if (!customerName) {
                self.showMessage('error', eaoPublic.i18n?.enterCustomerName || 'Please enter your name.');
                $('#eao-customer-name').focus();
                return;
            }

            if (!customerEmail) {
                self.showMessage('error', eaoPublic.i18n?.enterCustomerEmail || 'Please enter your email address.');
                $('#eao-customer-email').focus();
                return;
            }

            // Basic email validation.
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(customerEmail)) {
                self.showMessage('error', eaoPublic.i18n?.invalidEmail || 'Please enter a valid email address.');
                $('#eao-customer-email').focus();
                return;
            }

            const $btn = $('#eao-submit-order-btn');
            const $btnText = $btn.find('.eao-btn-text');
            const $spinner = $btn.find('.eao-spinner');

            $btn.prop('disabled', true);
            $btnText.text(eaoPublic.i18n?.processing || 'Processing...');
            $spinner.show();

            $.ajax({
                url: eaoPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eao_checkout',
                    nonce: eaoPublic.nonce,
                    client_album_id: eaoPublic.clientAlbumId,
                    cart_token: self.getCartToken(),
                    customer_name: customerName,
                    customer_email: customerEmail,
                    customer_phone: $('#eao-customer-phone').val().trim(),
                    client_notes: $('#eao-client-notes').val().trim()
                },
                success: function(response) {
                    if (response.success) {
                        // Redirect to confirmation.
                        window.location.href = response.data.redirect_url;
                    } else {
                        self.closeCheckoutModal();
                        self.showMessage('error', response.data.message);
                        $btn.prop('disabled', false);
                        $btnText.text(eaoPublic.i18n?.submitOrder || 'Submit Order');
                        $spinner.hide();
                    }
                },
                error: function() {
                    self.closeCheckoutModal();
                    self.showMessage('error', eaoPublic.i18n?.errorOccurred || 'An error occurred. Please try again.');
                    $btn.prop('disabled', false);
                    $btnText.text(eaoPublic.i18n?.submitOrder || 'Submit Order');
                    $spinner.hide();
                }
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

    /**
     * PDF Proof Viewer handler.
     */
    const EAOProofViewer = {
        // PDF document instance.
        pdfDoc: null,

        // Current page number.
        currentPage: 1,

        // Total pages.
        totalPages: 0,

        // Current view mode: 'slide' or 'grid'.
        viewMode: 'slide',

        // Grid thumbnails cache.
        gridThumbnails: [],

        // Current PDF URL.
        currentPdfUrl: null,

        // Current design name.
        currentDesignName: '',

        /**
         * Initialize the proof viewer.
         */
        init: function() {
            // Only run if viewer exists.
            if (!$('#eao-proof-viewer').length) {
                return;
            }

            // Set PDF.js worker.
            if (typeof pdfjsLib !== 'undefined' && eaoPublic && eaoPublic.pdfWorkerUrl) {
                pdfjsLib.GlobalWorkerOptions.workerSrc = eaoPublic.pdfWorkerUrl;
            }

            this.bindEvents();
        },

        /**
         * Bind all event handlers.
         */
        bindEvents: function() {
            const self = this;

            // View proof buttons - use higher priority event handling.
            $(document).on('click.eaoProofViewer', '.eao-view-proof-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const $btn = $(this);
                const pdfUrl = $btn.data('pdf-url') || $btn.attr('data-pdf-url');
                const designName = $btn.data('design-name') || $btn.attr('data-design-name');

                if (pdfUrl) {
                    self.open(pdfUrl, designName);
                }

                return false;
            });

            // Close button.
            $('.eao-proof-viewer__close, .eao-proof-viewer__backdrop').on('click', function() {
                self.close();
            });

            // Navigation buttons.
            $('#eao-proof-prev').on('click', function() {
                self.goToPage(self.currentPage - 1);
            });

            $('#eao-proof-next').on('click', function() {
                self.goToPage(self.currentPage + 1);
            });

            // View mode toggle.
            $('.eao-proof-viewer__view-btn').on('click', function() {
                const mode = $(this).data('view');
                self.setViewMode(mode);
            });

            // Keyboard navigation.
            $(document).on('keydown', function(e) {
                if (!$('#eao-proof-viewer').is(':visible')) {
                    return;
                }

                switch(e.key) {
                    case 'Escape':
                        self.close();
                        break;
                    case 'ArrowLeft':
                        self.goToPage(self.currentPage - 1);
                        break;
                    case 'ArrowRight':
                        self.goToPage(self.currentPage + 1);
                        break;
                    case 'Home':
                        self.goToPage(1);
                        break;
                    case 'End':
                        self.goToPage(self.totalPages);
                        break;
                }
            });

            // Grid item click.
            $(document).on('click', '.eao-proof-viewer__grid-item', function() {
                const pageNum = parseInt($(this).data('page'));
                self.goToPage(pageNum);
                self.setViewMode('slide');
            });
        },

        /**
         * Open the proof viewer with a PDF.
         *
         * @param {string} pdfUrl     URL of the PDF file.
         * @param {string} designName Name of the design.
         */
        open: function(pdfUrl, designName) {
            const self = this;

            // Reset state.
            this.currentPage = 1;
            this.totalPages = 0;
            this.pdfDoc = null;
            this.gridThumbnails = [];
            this.currentPdfUrl = pdfUrl;
            this.currentDesignName = designName || '';

            // Update title.
            $('#eao-proof-viewer-title').text(designName || (eaoPublic.i18n?.proofViewer || 'Proof Viewer'));

            // Show viewer and loading state.
            $('#eao-proof-viewer').fadeIn(200);
            $('body').css('overflow', 'hidden');

            this.showLoading(true);
            this.setViewMode('slide');

            // Load PDF.
            if (typeof pdfjsLib !== 'undefined') {
                pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
                    self.pdfDoc = pdf;
                    self.totalPages = pdf.numPages;
                    self.updatePagination();
                    self.showLoading(false);
                    self.renderPage(1);
                }).catch(function(error) {
                    console.error('Error loading PDF:', error);
                    self.showLoading(false);
                    // Fallback: open PDF in new tab.
                    window.open(pdfUrl, '_blank');
                    self.close();
                });
            } else {
                // PDF.js not loaded, open in new tab.
                window.open(pdfUrl, '_blank');
                this.close();
            }
        },

        /**
         * Close the proof viewer.
         */
        close: function() {
            $('#eao-proof-viewer').fadeOut(200);
            $('body').css('overflow', '');

            // Clean up.
            this.pdfDoc = null;
            this.gridThumbnails = [];
            $('#eao-proof-grid').empty();
        },

        /**
         * Show or hide loading state.
         *
         * @param {boolean} show Whether to show loading.
         */
        showLoading: function(show) {
            if (show) {
                $('#eao-proof-loading').show();
                $('#eao-proof-slide-view').hide();
                $('#eao-proof-grid-view').hide();
            } else {
                $('#eao-proof-loading').hide();
                if (this.viewMode === 'slide') {
                    $('#eao-proof-slide-view').show();
                } else {
                    $('#eao-proof-grid-view').show();
                }
            }
        },

        /**
         * Set the view mode.
         *
         * @param {string} mode 'slide' or 'grid'.
         */
        setViewMode: function(mode) {
            this.viewMode = mode;

            // Update toggle buttons.
            $('.eao-proof-viewer__view-btn').removeClass('is-active');
            $('.eao-proof-viewer__view-btn[data-view="' + mode + '"]').addClass('is-active');

            // Show appropriate view.
            if (mode === 'slide') {
                $('#eao-proof-grid-view').hide();
                $('#eao-proof-slide-view').show();
                $('#eao-proof-pagination').show();
            } else {
                $('#eao-proof-slide-view').hide();
                $('#eao-proof-grid-view').show();
                $('#eao-proof-pagination').hide();

                // Render grid if not already done.
                if (this.gridThumbnails.length === 0 && this.pdfDoc) {
                    this.renderGrid();
                }
            }
        },

        /**
         * Go to a specific page.
         *
         * @param {number} pageNum Page number to go to.
         */
        goToPage: function(pageNum) {
            if (pageNum < 1 || pageNum > this.totalPages || pageNum === this.currentPage) {
                return;
            }

            this.currentPage = pageNum;
            this.updatePagination();
            this.renderPage(pageNum);
        },

        /**
         * Update pagination display.
         */
        updatePagination: function() {
            $('#eao-proof-current-page').text(this.currentPage);
            $('#eao-proof-total-pages').text(this.totalPages);

            // Update nav button states.
            $('#eao-proof-prev').prop('disabled', this.currentPage <= 1);
            $('#eao-proof-next').prop('disabled', this.currentPage >= this.totalPages);
        },

        /**
         * Render a page to the main canvas.
         *
         * @param {number} pageNum Page number to render.
         */
        renderPage: function(pageNum) {
            const self = this;

            if (!this.pdfDoc) {
                return;
            }

            this.pdfDoc.getPage(pageNum).then(function(page) {
                const canvas = document.getElementById('eao-proof-canvas');
                const ctx = canvas.getContext('2d');

                // Calculate scale to fit container.
                const containerWidth = window.innerWidth - 180;
                const containerHeight = window.innerHeight - 180;

                const viewport = page.getViewport({ scale: 1 });
                const scaleX = containerWidth / viewport.width;
                const scaleY = containerHeight / viewport.height;
                const scale = Math.min(scaleX, scaleY, 2); // Max scale of 2 for quality.

                const scaledViewport = page.getViewport({ scale: scale });

                canvas.width = scaledViewport.width;
                canvas.height = scaledViewport.height;

                const renderContext = {
                    canvasContext: ctx,
                    viewport: scaledViewport
                };

                page.render(renderContext);
            });
        },

        /**
         * Render the grid view with all pages as thumbnails.
         */
        renderGrid: function() {
            const self = this;
            const $grid = $('#eao-proof-grid');

            $grid.empty();

            if (!this.pdfDoc) {
                return;
            }

            // Render each page as a thumbnail.
            for (let i = 1; i <= this.totalPages; i++) {
                (function(pageNum) {
                    self.pdfDoc.getPage(pageNum).then(function(page) {
                        const $item = $('<div class="eao-proof-viewer__grid-item" data-page="' + pageNum + '"></div>');
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        // Fixed thumbnail scale.
                        const viewport = page.getViewport({ scale: 0.5 });

                        canvas.width = viewport.width;
                        canvas.height = viewport.height;

                        const renderContext = {
                            canvasContext: ctx,
                            viewport: viewport
                        };

                        page.render(renderContext).promise.then(function() {
                            $item.append(canvas);

                            const $overlay = $('<div class="eao-proof-viewer__grid-item-overlay">' +
                                (eaoPublic.i18n?.page || 'Page') + ' ' + pageNum +
                                '</div>');
                            $item.append($overlay);

                            // Insert in correct order.
                            const $items = $grid.find('.eao-proof-viewer__grid-item');
                            let inserted = false;

                            $items.each(function() {
                                if (parseInt($(this).data('page')) > pageNum) {
                                    $(this).before($item);
                                    inserted = true;
                                    return false;
                                }
                            });

                            if (!inserted) {
                                $grid.append($item);
                            }

                            self.gridThumbnails.push({
                                pageNum: pageNum,
                                canvas: canvas
                            });
                        });
                    });
                })(i);
            }
        }
    };

    // Initialize on document ready.
    $(document).ready(function() {
        EAOPublic.init();
        EAOProofViewer.init();
    });

})(jQuery);
