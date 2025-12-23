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
     * Stripe Payment Handler.
     *
     * Manages Stripe.js integration for payment processing.
     *
     * @since 1.1.0
     */
    const EAOStripe = {
        // Stripe instance.
        stripe: null,

        // Elements instance.
        elements: null,

        // Card element.
        cardElement: null,

        // Payment Intent client secret.
        clientSecret: null,

        // Current step: 'info' or 'payment'.
        currentStep: 'info',

        // Whether Stripe is initialized.
        initialized: false,

        /**
         * Initialize Stripe if enabled.
         *
         * @return {boolean} True if Stripe was initialized.
         */
        init: function() {
            if (!eaoPublic.stripe || !eaoPublic.stripe.enabled || !eaoPublic.stripe.publishableKey) {
                return false;
            }

            // Check if Stripe.js is loaded.
            if (typeof Stripe === 'undefined') {
                console.error('Stripe.js not loaded');
                return false;
            }

            try {
                this.stripe = Stripe(eaoPublic.stripe.publishableKey);
                this.elements = this.stripe.elements();

                // Create card element with custom styling.
                const style = {
                    base: {
                        color: '#333',
                        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                        fontSmoothing: 'antialiased',
                        fontSize: '16px',
                        '::placeholder': {
                            color: '#aab7c4'
                        }
                    },
                    invalid: {
                        color: '#dc3545',
                        iconColor: '#dc3545'
                    }
                };

                this.cardElement = this.elements.create('card', { style: style });

                // Handle real-time validation errors.
                this.cardElement.on('change', function(event) {
                    const displayError = document.getElementById('eao-card-errors');
                    if (displayError) {
                        displayError.textContent = event.error ? event.error.message : '';
                    }
                });

                this.initialized = true;
                return true;

            } catch (error) {
                console.error('Error initializing Stripe:', error);
                return false;
            }
        },

        /**
         * Check if Stripe is enabled and initialized.
         *
         * @return {boolean} True if Stripe is ready.
         */
        isEnabled: function() {
            return this.initialized && this.stripe !== null;
        },

        /**
         * Mount card element to DOM.
         */
        mountCard: function() {
            const container = document.getElementById('eao-card-element');
            if (container && this.cardElement) {
                this.cardElement.mount('#eao-card-element');
            }
        },

        /**
         * Create Payment Intent via AJAX.
         *
         * @param {Object} customerData Customer info.
         * @return {Promise}
         */
        createPaymentIntent: function(customerData) {
            const self = this;

            return new Promise((resolve, reject) => {
                $.ajax({
                    url: eaoPublic.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eao_create_payment_intent',
                        nonce: eaoPublic.nonce,
                        client_album_id: eaoPublic.clientAlbumId,
                        cart_token: EAOPublic.getCartToken(),
                        customer_name: customerData.name,
                        customer_email: customerData.email
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data.message || 'Payment initialization failed'));
                        }
                    },
                    error: function() {
                        reject(new Error(eaoPublic.i18n?.errorOccurred || 'An error occurred'));
                    }
                });
            });
        },

        /**
         * Confirm the payment with Stripe.
         *
         * @param {Object} customerData Customer info.
         * @return {Promise}
         */
        confirmPayment: function(customerData) {
            const self = this;

            return this.stripe.confirmCardPayment(this.clientSecret, {
                payment_method: {
                    card: this.cardElement,
                    billing_details: {
                        name: customerData.name,
                        email: customerData.email
                    }
                }
            }).then(function(result) {
                if (result.error) {
                    throw new Error(result.error.message);
                }
                return result.paymentIntent;
            });
        },

        /**
         * Complete the checkout after successful payment.
         *
         * @param {string} paymentIntentId Payment Intent ID.
         * @param {Object} customerData    Customer info.
         * @return {Promise}
         */
        completeCheckout: function(paymentIntentId, customerData) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: eaoPublic.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eao_confirm_payment',
                        nonce: eaoPublic.nonce,
                        payment_intent_id: paymentIntentId,
                        client_album_id: eaoPublic.clientAlbumId,
                        cart_token: EAOPublic.getCartToken(),
                        customer_name: customerData.name,
                        customer_email: customerData.email,
                        customer_phone: customerData.phone,
                        client_notes: customerData.notes
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data.message || 'Checkout failed'));
                        }
                    },
                    error: function() {
                        reject(new Error(eaoPublic.i18n?.errorOccurred || 'An error occurred'));
                    }
                });
            });
        },

        /**
         * Show the payment step.
         */
        showPaymentStep: function() {
            this.currentStep = 'payment';
            $('#eao-step-info').slideUp(200);
            $('#eao-step-payment').slideDown(200);
            
            // Focus card element after animation.
            setTimeout(() => {
                if (this.cardElement) {
                    this.cardElement.focus();
                }
            }, 250);
        },

        /**
         * Show the info step.
         */
        showInfoStep: function() {
            this.currentStep = 'info';
            $('#eao-step-payment').slideUp(200);
            $('#eao-step-info').slideDown(200);
            
            // Clear any card errors.
            $('#eao-card-errors').text('');
        },

        /**
         * Reset for new checkout.
         */
        reset: function() {
            this.clientSecret = null;
            this.currentStep = 'info';
            
            if (this.cardElement) {
                this.cardElement.clear();
            }
            
            // Reset step visibility.
            $('#eao-step-info').show();
            $('#eao-step-payment').hide();
            $('#eao-card-errors').text('');
        }
    };

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
            this.bindOrderHistoryAccordion();
            this.checkOrderComplete();

            // Load saved addresses from localStorage (browser-specific).
            this.loadSavedAddresses();

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
         * Get the localStorage key for saved addresses.
         * Uses cart token to make addresses browser-specific.
         *
         * @return {string} Storage key.
         */
        getAddressStorageKey: function() {
            return 'eao_saved_addresses_' + eaoPublic.clientAlbumId + '_' + this.getCartToken();
        },

        /**
         * Get saved addresses from localStorage.
         *
         * @return {Array} Array of saved addresses.
         */
        getSavedAddresses: function() {
            try {
                const stored = localStorage.getItem(this.getAddressStorageKey());
                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.error('Error reading saved addresses:', e);
                return [];
            }
        },

        /**
         * Save addresses to localStorage.
         *
         * @param {Array} addresses Array of address objects.
         */
        setSavedAddresses: function(addresses) {
            try {
                localStorage.setItem(this.getAddressStorageKey(), JSON.stringify(addresses));
            } catch (e) {
                console.error('Error saving addresses:', e);
            }
        },

        /**
         * Load saved addresses from localStorage and render them.
         */
        loadSavedAddresses: function() {
            const self = this;
            const addresses = this.getSavedAddresses();

            // Render each saved address card.
            addresses.forEach(function(address) {
                self.addAddressCard(address);
            });
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

            // Build color cards with preview images.
            let html = '';
            self.selections.material.colors.forEach(function(color) {
                // Build swatch style (for the overlay circle).
                let swatchStyle = '';
                if (color.type === 'texture' && color.texture_image_id) {
                    // Use texture region if available.
                    if (color.texture_region) {
                        let region;
                        try {
                            region = typeof color.texture_region === 'string' 
                                ? JSON.parse(color.texture_region) 
                                : color.texture_region;
                        } catch (e) {
                            region = null;
                        }
                        if (region && color.texture_url) {
                            swatchStyle = 'background-image: url(' + color.texture_url + '); ' +
                                'background-position: ' + region.x + '% ' + region.y + '%; ' +
                                'background-size: ' + region.zoom + '%;';
                        } else if (color.texture_url) {
                            swatchStyle = 'background-image: url(' + color.texture_url + '); background-size: cover;';
                        }
                    } else if (color.texture_url) {
                        swatchStyle = 'background-image: url(' + color.texture_url + '); background-size: cover;';
                    }
                } else {
                    swatchStyle = 'background-color: ' + (color.color_value || '#ccc') + ';';
                }

                // Check if color has preview image.
                const hasPreview = color.preview_image_url && color.preview_image_url.length > 0;

                html += '<label class="eao-color-card' + (hasPreview ? ' eao-color-card--has-preview' : '') + '" ';
                html += 'data-color-id="' + color.id + '" data-color-name="' + self.escapeHtml(color.name) + '">';
                html += '<input type="radio" name="color_selection" value="' + color.id + '">';

                if (hasPreview) {
                    // Show preview image with swatch overlay.
                    html += '<div class="eao-color-card__preview">';
                    html += '<img src="' + color.preview_image_url + '" alt="' + self.escapeHtml(color.name) + '" class="eao-color-card__image">';
                    html += '<div class="eao-color-card__swatch" style="' + swatchStyle + '"></div>';
                    html += '</div>';
                } else {
                    // Fallback: show just the swatch circle.
                    html += '<div class="eao-color-card__swatch-only" style="' + swatchStyle + '"></div>';
                }

                html += '<span class="eao-color-card__name">' + self.escapeHtml(color.name) + '</span>';
                html += '</label>';
            });

            $grid.html(html);
            $section.show();

            // Bind color card clicks.
            $grid.find('.eao-color-card').on('click', function() {
                const $card = $(this);

                $('.eao-color-card').removeClass('is-selected');
                $card.addClass('is-selected');
                $card.find('input').prop('checked', true);

                self.selections.color = {
                    id: $card.data('color-id'),
                    name: $card.data('color-name')
                };

                $('#eao-color-id').val(self.selections.color.id);
                $('#eao-color-name').val(self.selections.color.name);
            });
        },

        /**
         * Escape HTML for safe insertion.
         *
         * @param {string} text The text to escape.
         * @return {string} Escaped text.
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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

            // Engraving card selection.
            $(document).on('click', '.eao-engraving-card', function() {
                const $card = $(this);
                const $input = $card.find('input[type="radio"]');
                const engravingId = $card.data('engraving-id');

                // Update selection UI.
                $('.eao-engraving-card').removeClass('is-selected');
                $card.addClass('is-selected');
                $input.prop('checked', true);

                // Sync with hidden select for backwards compatibility.
                $('#eao-engraving-method').val(engravingId || '');

                if (!engravingId) {
                    // No engraving selected.
                    $('#eao-engraving-fields').hide();
                    self.selections.engraving = null;
                    self.updatePriceCalculation();
                    return;
                }

                // Store selection.
                self.selections.engraving = {
                    id: engravingId,
                    upcharge: parseFloat($card.data('upcharge')) || 0,
                    charLimit: parseInt($card.data('char-limit')) || 50,
                    fonts: $card.data('fonts') || ''
                };

                // Update character limit.
                $('#eao-char-limit').text(self.selections.engraving.charLimit);
                $('#eao-engraving-text').attr('maxlength', self.selections.engraving.charLimit);

                // Update fonts - now using card-based selection.
                self.updateFontDisplay();

                // Show engraving fields with animation.
                $('#eao-engraving-fields').slideDown(200);
                self.updatePriceCalculation();
            });

            // Character count with visual feedback.
            $('#eao-engraving-text').on('input', function() {
                const length = $(this).val().length;
                const limit = parseInt($('#eao-char-limit').text()) || 50;
                const $counter = $(this).closest('.eao-field').find('.eao-char-counter');
                
                $('#eao-char-count').text(length);
                
                // Update counter state.
                $counter.removeClass('eao-char-counter--warning eao-char-counter--error');
                if (length >= limit) {
                    $counter.addClass('eao-char-counter--error');
                } else if (length >= limit * 0.8) {
                    $counter.addClass('eao-char-counter--warning');
                }
            });

            // Legacy: Also support the hidden select change (for edit mode).
            $('#eao-engraving-method').on('change', function() {
                const value = $(this).val();
                const $card = value 
                    ? $('.eao-engraving-card[data-engraving-id="' + value + '"]')
                    : $('.eao-engraving-card--none');
                
                if ($card.length && !$card.hasClass('is-selected')) {
                    $card.trigger('click');
                }
            });
        },

        /**
         * Update font display based on selected engraving method.
         */
        updateFontDisplay: function() {
            const self = this;
            const $fontField = $('#eao-font-field');
            const $fontGrid = $('#eao-font-grid');
            const $fontSelect = $('#eao-engraving-font');

            if (!self.selections.engraving || !self.selections.engraving.fonts) {
                $fontField.hide();
                return;
            }

            const fonts = self.selections.engraving.fonts.split('\n').filter(f => f.trim());

            if (fonts.length === 0) {
                $fontField.hide();
                return;
            }

            // Build font cards.
            let fontHtml = '';
            let fontOptions = '<option value="">' + (eaoPublic.i18n?.selectFont || 'Select Font') + '</option>';

            fonts.forEach(function(font, index) {
                const fontName = font.trim();
                const fontClass = self.getFontClass(fontName);
                const isFirst = index === 0;
                
                fontHtml += '<label class="eao-font-card ' + fontClass + (isFirst ? ' is-selected' : '') + '" data-font="' + self.escapeHtml(fontName) + '">';
                fontHtml += '<input type="radio" name="font_selection" value="' + self.escapeHtml(fontName) + '"' + (isFirst ? ' checked' : '') + '>';
                fontHtml += '<div class="eao-font-card__preview">Abc</div>';
                fontHtml += '<div class="eao-font-card__name">' + self.escapeHtml(fontName) + '</div>';
                fontHtml += '</label>';

                fontOptions += '<option value="' + fontName + '"' + (isFirst ? ' selected' : '') + '>' + fontName + '</option>';
            });

            $fontGrid.html(fontHtml);
            $fontSelect.html(fontOptions);

            // Auto-select first font.
            if (fonts.length > 0) {
                $fontSelect.val(fonts[0].trim());
            }

            // Bind font card clicks.
            $fontGrid.find('.eao-font-card').on('click', function() {
                const $card = $(this);
                const fontName = $card.data('font');

                $('.eao-font-card').removeClass('is-selected');
                $card.addClass('is-selected');
                $card.find('input').prop('checked', true);

                // Sync with hidden select.
                $fontSelect.val(fontName);
            });

            $fontField.slideDown(200);
        },

        /**
         * Get CSS class for font card based on font name.
         *
         * @param {string} fontName The font name.
         * @return {string} CSS class for the font style.
         */
        getFontClass: function(fontName) {
            const name = fontName.toLowerCase();
            
            if (name.includes('script') || name.includes('cursive') || name.includes('italic') || name.includes('calligraphy')) {
                return 'eao-font-card--script';
            }
            if (name.includes('serif') && !name.includes('sans')) {
                return 'eao-font-card--serif';
            }
            if (name.includes('block') || name.includes('bold') || name.includes('caps')) {
                return 'eao-font-card--block';
            }
            return 'eao-font-card--sans';
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
            // Re-check save address checkbox (checked by default).
            $('#eao-save-address').prop('checked', true);
        },

        /**
         * Delete a saved address from localStorage (browser-specific).
         *
         * @param {string} addressId The address ID to delete.
         * @param {jQuery} $card     The card element.
         */
        deleteAddress: function(addressId, $card) {
            const self = this;

            try {
                // Get existing addresses and filter out the deleted one.
                let addresses = self.getSavedAddresses();
                addresses = addresses.filter(function(addr) {
                    return addr.id !== addressId;
                });
                self.setSavedAddresses(addresses);

                // Remove the card from UI.
                $card.fadeOut(200, function() {
                    $(this).remove();

                    // If the deleted card was selected, select "New Address".
                    if (self.selectedAddressId === addressId) {
                        $('.eao-address-card--new').trigger('click');
                    }
                });
            } catch (e) {
                console.error('Error deleting address from localStorage:', e);
            }
        },

        /**
         * Save a new address to localStorage (browser-specific).
         *
         * @param {Function} callback Callback after save.
         */
        saveAddress: function(callback) {
            const self = this;

            // Create address object.
            const address = {
                id: 'addr_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
                name: $('#eao-shipping-name').val().trim(),
                address1: $('#eao-shipping-address1').val().trim(),
                address2: $('#eao-shipping-address2').val().trim(),
                city: $('#eao-shipping-city').val().trim(),
                state: $('#eao-shipping-state').val().trim(),
                zip: $('#eao-shipping-zip').val().trim()
            };

            // Validate required fields.
            if (!address.name || !address.address1 || !address.city || !address.state || !address.zip) {
                // Skip saving but proceed with order.
                if (callback) {
                    callback();
                }
                return;
            }

            try {
                // Get existing addresses and add the new one.
                const addresses = self.getSavedAddresses();
                addresses.push(address);
                self.setSavedAddresses(addresses);

                // Add the address card to the UI.
                self.addAddressCard(address);

                // Uncheck the save address checkbox since it's now saved.
                $('#eao-save-address').prop('checked', false);

            } catch (e) {
                console.error('Error saving address to localStorage:', e);
            }

            // Proceed with the callback (order submission).
            if (callback) {
                callback();
            }
        },

        /**
         * Add a new address card to the grid.
         *
         * @param {Object} address Address data.
         */
        addAddressCard: function(address) {
            const self = this;
            const $grid = $('#eao-address-grid');
            
            // Safely encode JSON for HTML attribute (escape both double and single quotes).
            const addressJson = JSON.stringify(address)
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            // Build address HTML with proper escaping.
            let addressHtml = self.escapeHtml(address.address1);
            if (address.address2) {
                addressHtml += '<br>' + self.escapeHtml(address.address2);
            }
            addressHtml += '<br>' + self.escapeHtml(address.city) + ', ' + self.escapeHtml(address.state) + ' ' + self.escapeHtml(address.zip);

            const $card = $(`
                <div class="eao-address-card" 
                     data-address-id="${self.escapeHtml(address.id)}"
                     data-address='${addressJson}'>
                    <button type="button" class="eao-address-card__delete" title="${eaoPublic.i18n?.deleteAddress || 'Delete address'}">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                    <div class="eao-address-card__name">${self.escapeHtml(address.name)}</div>
                    <div class="eao-address-card__address">${addressHtml}</div>
                </div>
            `);

            $grid.append($card);
            
            // Store the address data in jQuery data cache for reliable retrieval.
            $card.data('address', address);
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
            $('.eao-color-card').removeClass('is-selected');
            $('.eao-font-card').removeClass('is-selected');
            $('#eao-color-section').hide();
            $('#eao-engraving-section').hide();
            $('#eao-engraving-fields').hide();
            $('#eao-font-field').hide();
            $('#eao-font-grid').empty();

            // Reset engraving to "No Engraving".
            $('.eao-engraving-card--none').addClass('is-selected');
            $('#eao-engraving-method').val('');
            $('#eao-engraving-text').val('');
            $('#eao-char-count').text('0');
            $('.eao-char-counter').removeClass('eao-char-counter--warning eao-char-counter--error');

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
                        const $colorCard = $('.eao-color-card[data-color-id="' + data.color_id + '"]');
                        if ($colorCard.length) {
                            $colorCard.trigger('click');
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
                // Use card-based selection.
                const $engravingCard = $('.eao-engraving-card[data-engraving-id="' + data.engraving_method + '"]');
                if ($engravingCard.length) {
                    $engravingCard.trigger('click');
                    
                    // Wait for fonts to render, then select font and set text.
                    setTimeout(function() {
                        if (data.engraving_text) {
                            $('#eao-engraving-text').val(data.engraving_text);
                            $('#eao-char-count').text(data.engraving_text.length);
                        }
                        if (data.engraving_font) {
                            const $fontCard = $('.eao-font-card[data-font="' + data.engraving_font + '"]');
                            if ($fontCard.length) {
                                $fontCard.trigger('click');
                            }
                            $('#eao-engraving-font').val(data.engraving_font);
                        }
                    }, 150);
                }
            } else {
                // Select "No Engraving".
                $('.eao-engraving-card--none').trigger('click');
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

            // Initialize Stripe.
            const stripeEnabled = EAOStripe.init();

            // Open checkout modal.
            $('#eao-checkout-btn').on('click', function() {
                self.openCheckoutModal();
                
                // Mount Stripe card element if enabled.
                if (stripeEnabled) {
                    setTimeout(function() {
                        EAOStripe.mountCard();
                    }, 200);
                }
            });

            // Close modal handlers.
            $('#eao-modal-close, .eao-modal__backdrop').on('click', function() {
                self.closeCheckoutModal();
                EAOStripe.reset();
            });

            // Close on escape key.
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#eao-checkout-modal').is(':visible')) {
                    self.closeCheckoutModal();
                    EAOStripe.reset();
                }
            });

            // Payment back button (return to info step).
            $('#eao-payment-back').on('click', function() {
                EAOStripe.showInfoStep();
                self.updateCheckoutButton();
            });

            // Submit order from modal.
            $('#eao-submit-order-btn').on('click', function() {
                if (stripeEnabled) {
                    self.handleStripeCheckout();
                } else {
                    self.submitCheckout();
                }
            });

            // Also submit on form enter.
            $('#eao-checkout-form').on('submit', function(e) {
                e.preventDefault();
                if (stripeEnabled) {
                    self.handleStripeCheckout();
                } else {
                    self.submitCheckout();
                }
            });
        },

        /**
         * Open the checkout modal.
         */
        openCheckoutModal: function() {
            const self = this;
            
            // Update modal total.
            $('#eao-modal-total').text($('#eao-cart-total').text());
            
            // Reset Stripe state if enabled.
            if (EAOStripe.isEnabled()) {
                EAOStripe.reset();
            }
            
            // Update button text based on Stripe status.
            self.updateCheckoutButton();
            
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
         * Submit checkout with customer info (no payment).
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
                    } else if (response.data && response.data.payment_required) {
                        // Payment is required - switch to Stripe flow.
                        self.handleStripeCheckout();
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
         * Handle Stripe checkout flow.
         *
         * @since 1.1.0
         */
        handleStripeCheckout: function() {
            const self = this;
            const $btn = $('#eao-submit-order-btn');
            const $btnText = $btn.find('.eao-btn-text');
            const $spinner = $btn.find('.eao-spinner');

            // Get customer data.
            const customerData = {
                name: $('#eao-customer-name').val().trim(),
                email: $('#eao-customer-email').val().trim(),
                phone: $('#eao-customer-phone').val().trim(),
                notes: $('#eao-client-notes').val().trim()
            };

            // Validate customer info.
            if (!customerData.name) {
                self.showCheckoutError(eaoPublic.i18n?.enterCustomerName || 'Please enter your name.');
                $('#eao-customer-name').focus();
                return;
            }

            if (!customerData.email || !self.isValidEmail(customerData.email)) {
                self.showCheckoutError(eaoPublic.i18n?.invalidEmail || 'Please enter a valid email.');
                $('#eao-customer-email').focus();
                return;
            }

            // Step 1: Info step - create Payment Intent and show payment.
            if (EAOStripe.currentStep === 'info') {
                $btn.prop('disabled', true);
                $btnText.text(eaoPublic.i18n?.processing || 'Processing...');
                $spinner.show();

                // Create Payment Intent.
                EAOStripe.createPaymentIntent(customerData)
                    .then(function(data) {
                        if (data.skip_payment) {
                            // No payment required, complete checkout directly.
                            return self.submitCheckout();
                        }

                        // Store client secret and show payment step.
                        EAOStripe.clientSecret = data.client_secret;
                        EAOStripe.showPaymentStep();

                        // Update button text.
                        $btnText.text(eaoPublic.i18n?.payNow || 'Pay Now');
                        $btn.prop('disabled', false);
                        $spinner.hide();
                    })
                    .catch(function(error) {
                        self.showCheckoutError(error.message);
                        $btn.prop('disabled', false);
                        $btnText.text(eaoPublic.i18n?.continueToPayment || 'Continue to Payment');
                        $spinner.hide();
                    });

                return;
            }

            // Step 2: Payment step - confirm payment.
            if (EAOStripe.currentStep === 'payment') {
                $btn.prop('disabled', true);
                $btnText.text(eaoPublic.i18n?.processing || 'Processing...');
                $spinner.show();

                // Clear any previous errors.
                $('#eao-card-errors').text('');

                EAOStripe.confirmPayment(customerData)
                    .then(function(paymentIntent) {
                        // Payment successful, complete checkout.
                        $btnText.text(eaoPublic.i18n?.processing || 'Completing order...');
                        return EAOStripe.completeCheckout(paymentIntent.id, customerData);
                    })
                    .then(function(data) {
                        // Redirect to confirmation.
                        window.location.href = data.redirect_url;
                    })
                    .catch(function(error) {
                        // Show error.
                        $('#eao-card-errors').text(error.message);
                        self.showCheckoutError(error.message);
                        
                        $btn.prop('disabled', false);
                        $btnText.text(eaoPublic.i18n?.payNow || 'Pay Now');
                        $spinner.hide();
                    });
            }
        },

        /**
         * Update checkout button text based on current step.
         *
         * @since 1.1.0
         */
        updateCheckoutButton: function() {
            const $btnText = $('#eao-submit-order-btn .eao-btn-text');
            
            if (EAOStripe.isEnabled()) {
                if (EAOStripe.currentStep === 'payment') {
                    $btnText.text(eaoPublic.i18n?.payNow || 'Pay Now');
                } else {
                    $btnText.text(eaoPublic.i18n?.continueToPayment || 'Continue to Payment');
                }
            } else {
                $btnText.text(eaoPublic.i18n?.submitOrder || 'Submit Order');
            }
        },

        /**
         * Show error in checkout modal.
         *
         * @since 1.1.0
         *
         * @param {string} message Error message.
         */
        showCheckoutError: function(message) {
            // Show error in card errors element if on payment step.
            if (EAOStripe.currentStep === 'payment') {
                $('#eao-card-errors').text(message);
            }
            
            // Also show toast message.
            this.showMessage('error', message);
        },

        /**
         * Simple email validation.
         *
         * @since 1.1.0
         *
         * @param {string} email Email address.
         * @return {boolean} True if valid.
         */
        isValidEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
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
         * Bind Order History sidebar accordion toggle.
         */
        bindOrderHistoryAccordion: function() {
            const $toggle = $('#eao-order-history-toggle');
            const $content = $('#eao-order-history-content');
            const $sidebar = $('#eao-order-history-sidebar');

            if (!$toggle.length || !$content.length) {
                return;
            }

            // Set initial expanded state based on aria-expanded.
            if ($toggle.attr('aria-expanded') === 'true') {
                $sidebar.addClass('is-expanded');
            }

            $toggle.on('click', function() {
                const isExpanded = $(this).attr('aria-expanded') === 'true';
                
                // Toggle aria-expanded.
                $(this).attr('aria-expanded', !isExpanded);
                
                // Toggle content visibility.
                $content.toggleClass('is-collapsed');
                
                // Toggle sidebar expansion.
                $sidebar.toggleClass('is-expanded');
            });
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
