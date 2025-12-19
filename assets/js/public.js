/**
 * Easy Album Orders - Public JavaScript
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

        /**
         * Initialize public functionality.
         */
        init: function() {
            this.bindSelectionCards();
            this.bindColorSwatches();
            this.bindFormSubmit();
            this.bindCartActions();
        },

        /**
         * Bind selection card clicks.
         */
        bindSelectionCards: function() {
            const self = this;

            $(document).on('click', '.eao-selection-card', function() {
                const $card = $(this);
                const $input = $card.find('input[type="radio"]');
                const $container = $card.closest('.eao-selection-grid');

                // Update selection.
                $container.find('.eao-selection-card').removeClass('is-selected');
                $card.addClass('is-selected');
                $input.prop('checked', true).trigger('change');

                // Update price calculation.
                self.updatePriceCalculation();
            });
        },

        /**
         * Bind color swatch clicks.
         */
        bindColorSwatches: function() {
            $(document).on('click', '.eao-color-swatch', function() {
                const $swatch = $(this);
                const $input = $swatch.find('input[type="radio"]');
                const $container = $swatch.closest('.eao-color-grid');

                // Update selection.
                $container.find('.eao-color-swatch').removeClass('is-selected');
                $swatch.addClass('is-selected');
                $input.prop('checked', true).trigger('change');
            });
        },

        /**
         * Update price calculation display.
         */
        updatePriceCalculation: function() {
            // This will be expanded in Phase 4.
            console.log('Price calculation updated');
        },

        /**
         * Bind form submission.
         */
        bindFormSubmit: function() {
            const self = this;

            $('.eao-form').on('submit', function(e) {
                e.preventDefault();

                const $form = $(this);
                const $submitBtn = $form.find('[type="submit"]');

                // Validate form.
                if (!self.validateForm($form)) {
                    return;
                }

                // Disable button and show loading.
                $submitBtn.prop('disabled', true);
                $submitBtn.find('.eao-btn-text').text(eaoPublic.i18n.addToCart + '...');

                // Submit via AJAX.
                $.ajax({
                    url: eaoPublic.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'eao_add_to_cart',
                        nonce: eaoPublic.nonce,
                        formData: $form.serialize()
                    },
                    success: function(response) {
                        if (response.success) {
                            self.updateCart(response.data.cart);
                            self.resetForm($form);
                            self.showMessage('success', response.data.message);
                        } else {
                            self.showMessage('error', response.data.message);
                        }
                    },
                    error: function() {
                        self.showMessage('error', eaoPublic.i18n.errorOccurred);
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                        $submitBtn.find('.eao-btn-text').text(eaoPublic.i18n.addToCart);
                    }
                });
            });
        },

        /**
         * Validate the order form.
         *
         * @param {jQuery} $form The form element.
         * @return {boolean} True if valid, false otherwise.
         */
        validateForm: function($form) {
            // Check album name.
            const albumName = $form.find('[name="album_name"]').val();
            if (!albumName) {
                this.showMessage('error', eaoPublic.i18n.enterAlbumName);
                return false;
            }

            // Check design selection.
            if (!$form.find('[name="design_id"]:checked').length) {
                this.showMessage('error', eaoPublic.i18n.selectDesign);
                return false;
            }

            // Check material selection.
            if (!$form.find('[name="material_id"]:checked').length) {
                this.showMessage('error', eaoPublic.i18n.selectMaterial);
                return false;
            }

            // Check size selection.
            if (!$form.find('[name="size_id"]:checked').length) {
                this.showMessage('error', eaoPublic.i18n.selectSize);
                return false;
            }

            return true;
        },

        /**
         * Reset the form after submission.
         *
         * @param {jQuery} $form The form element.
         */
        resetForm: function($form) {
            $form[0].reset();
            $form.find('.is-selected').removeClass('is-selected');
            this.updatePriceCalculation();
        },

        /**
         * Bind cart action buttons.
         */
        bindCartActions: function() {
            const self = this;

            // Edit cart item.
            $(document).on('click', '.eao-cart__item-btn--edit', function() {
                const orderId = $(this).closest('.eao-cart__item').data('order-id');
                self.editCartItem(orderId);
            });

            // Remove cart item.
            $(document).on('click', '.eao-cart__item-btn--remove', function() {
                if (confirm(eaoPublic.i18n.confirmRemove)) {
                    const orderId = $(this).closest('.eao-cart__item').data('order-id');
                    self.removeCartItem(orderId);
                }
            });

            // Checkout.
            $(document).on('click', '.eao-cart__checkout-btn', function() {
                if (confirm(eaoPublic.i18n.confirmCheckout)) {
                    self.processCheckout();
                }
            });
        },

        /**
         * Update cart display.
         *
         * @param {Object} cartData Cart data from server.
         */
        updateCart: function(cartData) {
            // This will be expanded in Phase 4.
            console.log('Cart updated:', cartData);
        },

        /**
         * Edit a cart item.
         *
         * @param {number} orderId The order ID to edit.
         */
        editCartItem: function(orderId) {
            // This will be expanded in Phase 4.
            console.log('Edit cart item:', orderId);
        },

        /**
         * Remove a cart item.
         *
         * @param {number} orderId The order ID to remove.
         */
        removeCartItem: function(orderId) {
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
                        EAOPublic.updateCart(response.data.cart);
                    }
                }
            });
        },

        /**
         * Process checkout.
         */
        processCheckout: function() {
            const $checkoutBtn = $('.eao-cart__checkout-btn');
            $checkoutBtn.prop('disabled', true);

            $.ajax({
                url: eaoPublic.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'eao_process_checkout',
                    nonce: eaoPublic.nonce,
                    client_album_id: eaoPublic.clientAlbumId
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.redirect;
                    } else {
                        EAOPublic.showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    EAOPublic.showMessage('error', eaoPublic.i18n.errorOccurred);
                },
                complete: function() {
                    $checkoutBtn.prop('disabled', false);
                }
            });
        },

        /**
         * Show a message to the user.
         *
         * @param {string} type    Message type (success, error, info).
         * @param {string} message The message text.
         */
        showMessage: function(type, message) {
            const $message = $('<div class="eao-message eao-message--' + type + '">' + message + '</div>');
            
            // Remove existing messages.
            $('.eao-message').remove();

            // Add new message.
            $('.eao-form').prepend($message);

            // Scroll to message.
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 300);

            // Auto-remove after 5 seconds.
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Initialize on document ready.
    $(document).ready(function() {
        EAOPublic.init();
    });

})(jQuery);

