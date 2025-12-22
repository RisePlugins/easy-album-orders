<?php
/**
 * Album Order meta boxes.
 *
 * Handles all meta boxes for the Album Order post type including
 * order details, pricing, customer information, and status management.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Album Order meta boxes class.
 *
 * @since 1.0.0
 */
class EAO_Album_Order_Meta {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_album_order', array( $this, 'save_meta_boxes' ), 10, 2 );
    }

    /**
     * Register meta boxes for Album Order.
     *
     * @since 1.0.0
     */
    public function register_meta_boxes() {
        // Order Status meta box.
        add_meta_box(
            'eao_order_status',
            __( 'Order Status', 'easy-album-orders' ),
            array( $this, 'render_status_meta_box' ),
            'album_order',
            'side',
            'high'
        );

        // Order Summary meta box.
        add_meta_box(
            'eao_order_summary',
            __( 'Order Summary', 'easy-album-orders' ),
            array( $this, 'render_summary_meta_box' ),
            'album_order',
            'normal',
            'high'
        );

        // Album Details meta box.
        add_meta_box(
            'eao_album_details',
            __( 'Album Configuration', 'easy-album-orders' ),
            array( $this, 'render_album_details_meta_box' ),
            'album_order',
            'normal',
            'default'
        );

        // Customer Information meta box.
        add_meta_box(
            'eao_customer_info',
            __( 'Customer Information', 'easy-album-orders' ),
            array( $this, 'render_customer_meta_box' ),
            'album_order',
            'normal',
            'default'
        );

        // Notes meta box.
        add_meta_box(
            'eao_order_notes',
            __( 'Order Notes', 'easy-album-orders' ),
            array( $this, 'render_notes_meta_box' ),
            'album_order',
            'normal',
            'default'
        );

        // Related Client Album meta box.
        add_meta_box(
            'eao_related_album',
            __( 'Related Client Album', 'easy-album-orders' ),
            array( $this, 'render_related_album_meta_box' ),
            'album_order',
            'side',
            'default'
        );

        // Payment Information meta box (only show if Stripe is enabled or payment data exists).
        $stripe          = new EAO_Stripe();
        $payment_status  = get_post_meta( get_the_ID(), '_eao_payment_status', true );
        $payment_intent  = get_post_meta( get_the_ID(), '_eao_payment_intent_id', true );

        if ( $stripe->is_enabled() || $payment_status || $payment_intent ) {
            add_meta_box(
                'eao_payment_info',
                __( 'Payment Information', 'easy-album-orders' ),
                array( $this, 'render_payment_meta_box' ),
                'album_order',
                'side',
                'default'
            );
        }
    }

    /**
     * Render Order Status meta box.
     *
     * @since 1.0.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_status_meta_box( $post ) {
        wp_nonce_field( 'eao_album_order_meta', 'eao_album_order_nonce' );

        $status   = EAO_Album_Order::get_order_status( $post->ID );
        $statuses = EAO_Album_Order::get_statuses();

        // Get dates.
        $submitted_date = get_post_meta( $post->ID, '_eao_submission_date', true );
        $ordered_date   = get_post_meta( $post->ID, '_eao_order_date', true );
        $shipped_date   = get_post_meta( $post->ID, '_eao_shipped_date', true );
        ?>
        <div class="eao-meta-box">
            <div class="eao-field">
                <label for="eao_order_status"><?php esc_html_e( 'Current status', 'easy-album-orders' ); ?></label>
                <select id="eao_order_status" name="eao_order_status" class="eao-status-select">
                    <?php foreach ( $statuses as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ( $submitted_date || $ordered_date || $shipped_date ) : ?>
            <div class="eao-status-dates">
                <?php if ( $submitted_date ) : ?>
                    <div class="eao-status-dates__item">
                        <span class="eao-status-dates__label"><?php esc_html_e( 'Submitted', 'easy-album-orders' ); ?></span>
                        <span class="eao-status-dates__value"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submitted_date ) ) ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( $ordered_date ) : ?>
                    <div class="eao-status-dates__item">
                        <span class="eao-status-dates__label"><?php esc_html_e( 'Ordered', 'easy-album-orders' ); ?></span>
                        <span class="eao-status-dates__value"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ordered_date ) ) ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( $shipped_date ) : ?>
                    <div class="eao-status-dates__item">
                        <span class="eao-status-dates__label"><?php esc_html_e( 'Shipped', 'easy-album-orders' ); ?></span>
                        <span class="eao-status-dates__value"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $shipped_date ) ) ); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Order Summary meta box.
     *
     * @since 1.0.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_summary_meta_box( $post ) {
        $album_name = get_post_meta( $post->ID, '_eao_album_name', true );
        $order_number = EAO_Helpers::generate_order_number( $post->ID );

        // Pricing.
        $base_price         = floatval( get_post_meta( $post->ID, '_eao_base_price', true ) );
        $material_upcharge  = floatval( get_post_meta( $post->ID, '_eao_material_upcharge', true ) );
        $size_upcharge      = floatval( get_post_meta( $post->ID, '_eao_size_upcharge', true ) );
        $engraving_upcharge = floatval( get_post_meta( $post->ID, '_eao_engraving_upcharge', true ) );
        $credit_type        = get_post_meta( $post->ID, '_eao_credit_type', true );
        $applied_credits    = floatval( get_post_meta( $post->ID, '_eao_applied_credits', true ) );
        $total              = EAO_Album_Order::calculate_total( $post->ID );
        ?>
        <div class="eao-order-header">
            <div class="eao-order-header__info">
                <span class="eao-order-header__number"><?php echo esc_html( $order_number ); ?></span>
                <?php if ( $album_name ) : ?>
                    <span class="eao-order-header__album"><?php echo esc_html( $album_name ); ?></span>
                <?php endif; ?>
            </div>
            <div class="eao-order-header__total">
                <div class="eao-order-header__total-label"><?php esc_html_e( 'Order total', 'easy-album-orders' ); ?></div>
                <div class="eao-order-header__total-value"><?php echo esc_html( eao_format_price( $total ) ); ?></div>
            </div>
        </div>

        <div class="eao-price-table">
            <div class="eao-price-row">
                <span class="eao-price-row__label"><?php esc_html_e( 'Base price', 'easy-album-orders' ); ?></span>
                <span class="eao-price-row__value"><?php echo esc_html( eao_format_price( $base_price ) ); ?></span>
            </div>

            <?php if ( $material_upcharge > 0 ) : ?>
            <div class="eao-price-row eao-price-row--upcharge">
                <span class="eao-price-row__label"><?php esc_html_e( 'Material upcharge', 'easy-album-orders' ); ?></span>
                <span class="eao-price-row__value"><?php echo esc_html( eao_format_price( $material_upcharge ) ); ?></span>
            </div>
            <?php endif; ?>

            <?php if ( $size_upcharge > 0 ) : ?>
            <div class="eao-price-row eao-price-row--upcharge">
                <span class="eao-price-row__label"><?php esc_html_e( 'Size upcharge', 'easy-album-orders' ); ?></span>
                <span class="eao-price-row__value"><?php echo esc_html( eao_format_price( $size_upcharge ) ); ?></span>
            </div>
            <?php endif; ?>

            <?php if ( $engraving_upcharge > 0 ) : ?>
            <div class="eao-price-row eao-price-row--upcharge">
                <span class="eao-price-row__label"><?php esc_html_e( 'Engraving', 'easy-album-orders' ); ?></span>
                <span class="eao-price-row__value"><?php echo esc_html( eao_format_price( $engraving_upcharge ) ); ?></span>
            </div>
            <?php endif; ?>

            <?php if ( $applied_credits > 0 ) : ?>
            <div class="eao-price-row eao-price-row--credit">
                <span class="eao-price-row__label">
                    <?php 
                    if ( 'free_album' === $credit_type ) {
                        esc_html_e( 'Free album credit', 'easy-album-orders' );
                    } else {
                        esc_html_e( 'Album credit', 'easy-album-orders' );
                    }
                    ?>
                </span>
                <span class="eao-price-row__value"><?php echo esc_html( eao_format_price( $applied_credits ) ); ?></span>
            </div>
            <?php endif; ?>

            <div class="eao-price-row eao-price-row--total">
                <span class="eao-price-row__label"><?php esc_html_e( 'Total', 'easy-album-orders' ); ?></span>
                <span class="eao-price-row__value"><?php echo esc_html( eao_format_price( $total ) ); ?></span>
            </div>
        </div>
        <?php
    }

    /**
     * Render Album Details meta box.
     *
     * @since 1.0.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_album_details_meta_box( $post ) {
        // Get saved values.
        $album_name        = get_post_meta( $post->ID, '_eao_album_name', true );
        $design_name       = get_post_meta( $post->ID, '_eao_design_name', true );
        $material_name     = get_post_meta( $post->ID, '_eao_material_name', true );
        $material_color    = get_post_meta( $post->ID, '_eao_material_color', true );
        $size_name         = get_post_meta( $post->ID, '_eao_size_name', true );
        $engraving_method  = get_post_meta( $post->ID, '_eao_engraving_method', true );
        $engraving_text    = get_post_meta( $post->ID, '_eao_engraving_text', true );
        $engraving_font    = get_post_meta( $post->ID, '_eao_engraving_font', true );

        // Get options for dropdowns.
        $materials         = get_option( 'eao_materials', array() );
        $sizes             = get_option( 'eao_sizes', array() );
        $engraving_options = get_option( 'eao_engraving_options', array() );
        ?>
        <div class="eao-meta-box">
            <!-- Album Details Section -->
            <div class="eao-config-section">
                <div class="eao-field">
                    <label for="eao_album_name"><?php esc_html_e( 'Album name', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_album_name" name="eao_album_name" value="<?php echo esc_attr( $album_name ); ?>">
                </div>

                <div class="eao-field">
                    <label for="eao_design_name"><?php esc_html_e( 'Design', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_design_name" name="eao_design_name" value="<?php echo esc_attr( $design_name ); ?>">
                    <p class="description"><?php esc_html_e( 'The selected album design.', 'easy-album-orders' ); ?></p>
                </div>
            </div>

            <!-- Material & Size Section -->
            <div class="eao-config-section">
                <h4 class="eao-config-section__title">
                    <span class="dashicons dashicons-art"></span>
                    <?php esc_html_e( 'Material & Size', 'easy-album-orders' ); ?>
                </h4>

                <div class="eao-field-row">
                    <div class="eao-field">
                        <label for="eao_material_name"><?php esc_html_e( 'Material', 'easy-album-orders' ); ?></label>
                        <select id="eao_material_name" name="eao_material_name">
                            <option value=""><?php esc_html_e( '— Select Material —', 'easy-album-orders' ); ?></option>
                            <?php foreach ( $materials as $material ) : ?>
                                <option value="<?php echo esc_attr( $material['name'] ); ?>" <?php selected( $material_name, $material['name'] ); ?>>
                                    <?php echo esc_html( $material['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="eao-field">
                        <label for="eao_material_color"><?php esc_html_e( 'Color', 'easy-album-orders' ); ?></label>
                        <input type="text" id="eao_material_color" name="eao_material_color" value="<?php echo esc_attr( $material_color ); ?>">
                    </div>
                </div>

                <div class="eao-field">
                    <label for="eao_size_name"><?php esc_html_e( 'Size', 'easy-album-orders' ); ?></label>
                    <select id="eao_size_name" name="eao_size_name">
                        <option value=""><?php esc_html_e( '— Select Size —', 'easy-album-orders' ); ?></option>
                        <?php foreach ( $sizes as $size ) : ?>
                            <option value="<?php echo esc_attr( $size['name'] ); ?>" <?php selected( $size_name, $size['name'] ); ?>>
                                <?php echo esc_html( $size['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Engraving Section -->
            <div class="eao-config-section">
                <h4 class="eao-config-section__title">
                    <span class="dashicons dashicons-edit"></span>
                    <?php esc_html_e( 'Engraving', 'easy-album-orders' ); ?>
                </h4>

                <div class="eao-field-row">
                    <div class="eao-field">
                        <label for="eao_engraving_method"><?php esc_html_e( 'Method', 'easy-album-orders' ); ?></label>
                        <select id="eao_engraving_method" name="eao_engraving_method">
                            <option value=""><?php esc_html_e( 'No engraving', 'easy-album-orders' ); ?></option>
                            <?php foreach ( $engraving_options as $option ) : ?>
                                <option value="<?php echo esc_attr( $option['name'] ); ?>" <?php selected( $engraving_method, $option['name'] ); ?>>
                                    <?php echo esc_html( $option['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="eao-field">
                        <label for="eao_engraving_font"><?php esc_html_e( 'Font', 'easy-album-orders' ); ?></label>
                        <input type="text" id="eao_engraving_font" name="eao_engraving_font" value="<?php echo esc_attr( $engraving_font ); ?>">
                    </div>
                </div>

                <div class="eao-field">
                    <label for="eao_engraving_text"><?php esc_html_e( 'Engraving text', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_engraving_text" name="eao_engraving_text" value="<?php echo esc_attr( $engraving_text ); ?>">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Customer Information meta box.
     *
     * @since 1.0.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_customer_meta_box( $post ) {
        $customer_name  = get_post_meta( $post->ID, '_eao_customer_name', true );
        $customer_email = get_post_meta( $post->ID, '_eao_customer_email', true );
        $customer_phone = get_post_meta( $post->ID, '_eao_customer_phone', true );

        // Structured shipping address fields (matches front-end field names).
        $shipping_name      = get_post_meta( $post->ID, '_eao_shipping_name', true );
        $shipping_address1  = get_post_meta( $post->ID, '_eao_shipping_address1', true );
        $shipping_address2  = get_post_meta( $post->ID, '_eao_shipping_address2', true );
        $shipping_city      = get_post_meta( $post->ID, '_eao_shipping_city', true );
        $shipping_state     = get_post_meta( $post->ID, '_eao_shipping_state', true );
        $shipping_zip       = get_post_meta( $post->ID, '_eao_shipping_zip', true );

        $has_shipping = ! empty( $shipping_name ) || ! empty( $shipping_address1 );
        ?>
        <div class="eao-meta-box">
            <div class="eao-info-cards">
                <!-- Customer Contact Card -->
                <div class="eao-info-card" id="eao-customer-card">
                    <div class="eao-info-card__header">
                        <h4 class="eao-info-card__title"><?php esc_html_e( 'Customer', 'easy-album-orders' ); ?></h4>
                        <a class="eao-info-card__edit" onclick="eaoToggleEdit('customer')"><?php esc_html_e( 'Edit', 'easy-album-orders' ); ?></a>
                    </div>
                    <div class="eao-info-card__content" id="eao-customer-display">
                        <?php if ( $customer_name ) : ?>
                            <div class="eao-info-card__name"><?php echo esc_html( $customer_name ); ?></div>
                        <?php endif; ?>
                        <?php if ( $customer_email ) : ?>
                            <div class="eao-info-card__detail">
                                <a href="mailto:<?php echo esc_attr( $customer_email ); ?>"><?php echo esc_html( $customer_email ); ?></a>
                            </div>
                        <?php endif; ?>
                        <?php if ( $customer_phone ) : ?>
                            <div class="eao-info-card__detail">
                                <a href="tel:<?php echo esc_attr( $customer_phone ); ?>"><?php echo esc_html( $customer_phone ); ?></a>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! $customer_name && ! $customer_email && ! $customer_phone ) : ?>
                            <div class="eao-info-card__empty"><?php esc_html_e( 'No customer info', 'easy-album-orders' ); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="eao-edit-form" id="eao-customer-form">
                        <div class="eao-field">
                            <label for="eao_customer_name"><?php esc_html_e( 'Name', 'easy-album-orders' ); ?></label>
                            <input type="text" id="eao_customer_name" name="eao_customer_name" value="<?php echo esc_attr( $customer_name ); ?>">
                        </div>
                        <div class="eao-field">
                            <label for="eao_customer_email"><?php esc_html_e( 'Email', 'easy-album-orders' ); ?></label>
                            <input type="email" id="eao_customer_email" name="eao_customer_email" value="<?php echo esc_attr( $customer_email ); ?>">
                        </div>
                        <div class="eao-field">
                            <label for="eao_customer_phone"><?php esc_html_e( 'Phone', 'easy-album-orders' ); ?></label>
                            <input type="tel" id="eao_customer_phone" name="eao_customer_phone" value="<?php echo esc_attr( $customer_phone ); ?>">
                        </div>
                    </div>
                </div>

                <!-- Shipping Address Card -->
                <div class="eao-info-card" id="eao-shipping-card">
                    <div class="eao-info-card__header">
                        <h4 class="eao-info-card__title"><?php esc_html_e( 'Ship to', 'easy-album-orders' ); ?></h4>
                        <a class="eao-info-card__edit" onclick="eaoToggleEdit('shipping')"><?php esc_html_e( 'Edit', 'easy-album-orders' ); ?></a>
                    </div>
                    <div class="eao-info-card__content" id="eao-shipping-display">
                        <?php if ( $has_shipping ) : ?>
                            <?php if ( $shipping_name ) : ?>
                                <div class="eao-info-card__name"><?php echo esc_html( $shipping_name ); ?></div>
                            <?php endif; ?>
                            <div class="eao-info-card__address">
                                <?php echo esc_html( $shipping_address1 ); ?>
                                <?php if ( $shipping_address2 ) : ?>
                                    <br><?php echo esc_html( $shipping_address2 ); ?>
                                <?php endif; ?>
                                <?php if ( $shipping_city || $shipping_state || $shipping_zip ) : ?>
                                    <br><?php echo esc_html( trim( $shipping_city . ', ' . $shipping_state . ' ' . $shipping_zip, ', ' ) ); ?>
                                <?php endif; ?>
                            </div>
                        <?php else : ?>
                            <div class="eao-info-card__empty"><?php esc_html_e( 'No shipping address', 'easy-album-orders' ); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="eao-edit-form" id="eao-shipping-form">
                        <div class="eao-field">
                            <label for="eao_shipping_name"><?php esc_html_e( 'Recipient name', 'easy-album-orders' ); ?></label>
                            <input type="text" id="eao_shipping_name" name="eao_shipping_name" value="<?php echo esc_attr( $shipping_name ); ?>">
                        </div>
                        <div class="eao-field">
                            <label for="eao_shipping_address1"><?php esc_html_e( 'Street address', 'easy-album-orders' ); ?></label>
                            <input type="text" id="eao_shipping_address1" name="eao_shipping_address1" value="<?php echo esc_attr( $shipping_address1 ); ?>">
                        </div>
                        <div class="eao-field">
                            <label for="eao_shipping_address2"><?php esc_html_e( 'Apt, suite, etc.', 'easy-album-orders' ); ?></label>
                            <input type="text" id="eao_shipping_address2" name="eao_shipping_address2" value="<?php echo esc_attr( $shipping_address2 ); ?>">
                        </div>
                        <div class="eao-field-row">
                            <div class="eao-field eao-field--city">
                                <label for="eao_shipping_city"><?php esc_html_e( 'City', 'easy-album-orders' ); ?></label>
                                <input type="text" id="eao_shipping_city" name="eao_shipping_city" value="<?php echo esc_attr( $shipping_city ); ?>">
                            </div>
                            <div class="eao-field eao-field--state">
                                <label for="eao_shipping_state"><?php esc_html_e( 'State', 'easy-album-orders' ); ?></label>
                                <input type="text" id="eao_shipping_state" name="eao_shipping_state" value="<?php echo esc_attr( $shipping_state ); ?>">
                            </div>
                            <div class="eao-field eao-field--zip">
                                <label for="eao_shipping_zip"><?php esc_html_e( 'ZIP', 'easy-album-orders' ); ?></label>
                                <input type="text" id="eao_shipping_zip" name="eao_shipping_zip" value="<?php echo esc_attr( $shipping_zip ); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function eaoToggleEdit(type) {
            var form = document.getElementById('eao-' + type + '-form');
            var card = form.closest('.eao-info-card');
            var link = card.querySelector('.eao-info-card__edit');
            
            if (form.classList.contains('is-visible')) {
                form.classList.remove('is-visible');
                link.textContent = '<?php echo esc_js( __( 'Edit', 'easy-album-orders' ) ); ?>';
            } else {
                form.classList.add('is-visible');
                link.textContent = '<?php echo esc_js( __( 'Close', 'easy-album-orders' ) ); ?>';
            }
        }
        </script>
        <?php
    }

    /**
     * Render Order Notes meta box.
     *
     * @since 1.0.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_notes_meta_box( $post ) {
        $client_notes = get_post_meta( $post->ID, '_eao_client_notes', true );
        $photographer_notes = get_post_meta( $post->ID, '_eao_photographer_notes', true );
        ?>
        <div class="eao-meta-box">
            <div class="eao-notes-section">
                <label for="eao_client_notes" class="eao-notes-section__label">
                    <span class="dashicons dashicons-admin-comments"></span>
                    <?php esc_html_e( 'Client notes', 'easy-album-orders' ); ?>
                </label>
                <textarea id="eao_client_notes" name="eao_client_notes" rows="3" class="eao-notes-textarea eao-notes-textarea--readonly" readonly><?php echo esc_textarea( $client_notes ); ?></textarea>
                <p class="eao-notes-help"><?php esc_html_e( 'Notes from the client during checkout (read-only).', 'easy-album-orders' ); ?></p>
            </div>

            <div class="eao-notes-section">
                <label for="eao_photographer_notes" class="eao-notes-section__label eao-notes-section__label--internal">
                    <span class="dashicons dashicons-lock"></span>
                    <?php esc_html_e( 'Internal notes', 'easy-album-orders' ); ?>
                </label>
                <textarea id="eao_photographer_notes" name="eao_photographer_notes" rows="3" class="eao-notes-textarea"><?php echo esc_textarea( $photographer_notes ); ?></textarea>
                <p class="eao-notes-help"><?php esc_html_e( 'Private notes visible only to you.', 'easy-album-orders' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render Related Client Album meta box.
     *
     * @since 1.0.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_related_album_meta_box( $post ) {
        $client_album_id = get_post_meta( $post->ID, '_eao_client_album_id', true );
        $client_album    = $client_album_id ? get_post( $client_album_id ) : null;
        ?>
        <div class="eao-meta-box">
            <?php if ( $client_album ) : ?>
                <div class="eao-related-album">
                    <h5 class="eao-related-album__title"><?php echo esc_html( $client_album->post_title ); ?></h5>
                    <div class="eao-related-album__actions">
                        <a href="<?php echo esc_url( get_edit_post_link( $client_album_id ) ); ?>" class="eao-related-album__link">
                            <span class="dashicons dashicons-edit"></span>
                            <?php esc_html_e( 'Edit client album', 'easy-album-orders' ); ?>
                        </a>
                        <a href="<?php echo esc_url( get_permalink( $client_album_id ) ); ?>" target="_blank" class="eao-related-album__external">
                            <?php esc_html_e( 'View order form', 'easy-album-orders' ); ?> →
                        </a>
                    </div>
                </div>
            <?php else : ?>
                <div class="eao-related-album--empty">
                    <p><?php esc_html_e( 'This order is not linked to a client album.', 'easy-album-orders' ); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Payment Information meta box.
     *
     * Displays payment status, amount, and Stripe details
     * for orders processed through Stripe.
     *
     * @since 1.1.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_payment_meta_box( $post ) {
        $payment_status  = get_post_meta( $post->ID, '_eao_payment_status', true );
        $payment_amount  = get_post_meta( $post->ID, '_eao_payment_amount', true );
        $payment_intent  = get_post_meta( $post->ID, '_eao_payment_intent_id', true );
        $charge_id       = get_post_meta( $post->ID, '_eao_stripe_charge_id', true );
        $refund_amount   = get_post_meta( $post->ID, '_eao_refund_amount', true );
        $payment_date    = get_post_meta( $post->ID, '_eao_payment_date', true );
        $payment_error   = get_post_meta( $post->ID, '_eao_payment_error', true );

        // Status labels and badge classes.
        $status_config = array(
            'paid'           => array(
                'label' => __( 'Paid', 'easy-album-orders' ),
                'class' => 'eao-payment-badge--paid',
            ),
            'failed'         => array(
                'label' => __( 'Failed', 'easy-album-orders' ),
                'class' => 'eao-payment-badge--failed',
            ),
            'refunded'       => array(
                'label' => __( 'Refunded', 'easy-album-orders' ),
                'class' => 'eao-payment-badge--refunded',
            ),
            'partial_refund' => array(
                'label' => __( 'Partial Refund', 'easy-album-orders' ),
                'class' => 'eao-payment-badge--refunded',
            ),
            'pending'        => array(
                'label' => __( 'Pending', 'easy-album-orders' ),
                'class' => 'eao-payment-badge--pending',
            ),
            'free'           => array(
                'label' => __( 'Free', 'easy-album-orders' ),
                'class' => 'eao-payment-badge--free',
            ),
        );

        $status_label = isset( $status_config[ $payment_status ]['label'] )
            ? $status_config[ $payment_status ]['label']
            : __( 'No Payment', 'easy-album-orders' );

        $status_class = isset( $status_config[ $payment_status ]['class'] )
            ? $status_config[ $payment_status ]['class']
            : 'eao-payment-badge--none';

        // Determine Stripe dashboard URL (test vs live).
        $stripe          = new EAO_Stripe();
        $stripe_base_url = $stripe->is_test_mode()
            ? 'https://dashboard.stripe.com/test'
            : 'https://dashboard.stripe.com';
        ?>
        <div class="eao-meta-box eao-payment-meta-box">
            <table class="eao-meta-table">
                <tr>
                    <th><?php esc_html_e( 'Status', 'easy-album-orders' ); ?></th>
                    <td>
                        <span class="eao-payment-badge <?php echo esc_attr( $status_class ); ?>">
                            <?php echo esc_html( $status_label ); ?>
                        </span>
                    </td>
                </tr>

                <?php if ( $payment_amount ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Amount', 'easy-album-orders' ); ?></th>
                        <td><?php echo esc_html( eao_format_price( $payment_amount ) ); ?></td>
                    </tr>
                <?php endif; ?>

                <?php if ( $refund_amount ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Refunded', 'easy-album-orders' ); ?></th>
                        <td class="eao-payment-refund">
                            <?php echo esc_html( eao_format_price( $refund_amount ) ); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if ( $payment_date ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Date', 'easy-album-orders' ); ?></th>
                        <td>
                            <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $payment_date ) ) ); ?>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if ( $charge_id ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Stripe', 'easy-album-orders' ); ?></th>
                        <td>
                            <a href="<?php echo esc_url( $stripe_base_url . '/payments/' . $charge_id ); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="eao-stripe-link">
                                <?php esc_html_e( 'View in Stripe', 'easy-album-orders' ); ?>
                                <?php echo EAO_Icons::render( 'external-link', array( 'size' => 14 ) ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>

                <?php if ( 'failed' === $payment_status && $payment_error ) : ?>
                    <tr>
                        <th><?php esc_html_e( 'Error', 'easy-album-orders' ); ?></th>
                        <td class="eao-payment-error">
                            <?php echo esc_html( $payment_error ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

            <?php if ( ! $payment_status && ! $payment_intent ) : ?>
                <p class="eao-payment-empty">
                    <?php esc_html_e( 'No payment information recorded for this order.', 'easy-album-orders' ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save meta box data.
     *
     * @since 1.0.0
     *
     * @param int     $post_id The post ID.
     * @param WP_Post $post    The post object.
     */
    public function save_meta_boxes( $post_id, $post ) {
        // Verify nonce.
        if ( ! isset( $_POST['eao_album_order_nonce'] ) || ! wp_verify_nonce( $_POST['eao_album_order_nonce'], 'eao_album_order_meta' ) ) {
            return;
        }

        // Check autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save order status.
        if ( isset( $_POST['eao_order_status'] ) ) {
            $old_status = EAO_Album_Order::get_order_status( $post_id );
            $new_status = sanitize_key( $_POST['eao_order_status'] );

            if ( $old_status !== $new_status ) {
                EAO_Album_Order::update_status( $post_id, $new_status );
            }
        }

        // Save album details.
        $text_fields = array(
            'eao_album_name'      => '_eao_album_name',
            'eao_design_name'     => '_eao_design_name',
            'eao_material_name'   => '_eao_material_name',
            'eao_material_color'  => '_eao_material_color',
            'eao_size_name'       => '_eao_size_name',
            'eao_engraving_method'=> '_eao_engraving_method',
            'eao_engraving_text'  => '_eao_engraving_text',
            'eao_engraving_font'  => '_eao_engraving_font',
            'eao_customer_name'   => '_eao_customer_name',
            'eao_customer_phone'  => '_eao_customer_phone',
        );

        foreach ( $text_fields as $post_key => $meta_key ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
            }
        }

        // Save email.
        if ( isset( $_POST['eao_customer_email'] ) ) {
            update_post_meta( $post_id, '_eao_customer_email', sanitize_email( $_POST['eao_customer_email'] ) );
        }

        // Save structured shipping address fields (matches front-end field names).
        $shipping_fields = array(
            'eao_shipping_name'     => '_eao_shipping_name',
            'eao_shipping_address1' => '_eao_shipping_address1',
            'eao_shipping_address2' => '_eao_shipping_address2',
            'eao_shipping_city'     => '_eao_shipping_city',
            'eao_shipping_state'    => '_eao_shipping_state',
            'eao_shipping_zip'      => '_eao_shipping_zip',
        );

        foreach ( $shipping_fields as $post_key => $meta_key ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
            }
        }

        if ( isset( $_POST['eao_client_notes'] ) ) {
            update_post_meta( $post_id, '_eao_client_notes', sanitize_textarea_field( $_POST['eao_client_notes'] ) );
        }

        if ( isset( $_POST['eao_photographer_notes'] ) ) {
            update_post_meta( $post_id, '_eao_photographer_notes', sanitize_textarea_field( $_POST['eao_photographer_notes'] ) );
        }
    }
}

