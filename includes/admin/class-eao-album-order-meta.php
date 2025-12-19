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
                <label for="eao_order_status"><?php esc_html_e( 'Current Status', 'easy-album-orders' ); ?></label>
                <select id="eao_order_status" name="eao_order_status" style="width: 100%;">
                    <?php foreach ( $statuses as $value => $label ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="eao-status-dates" style="margin-top: 15px; font-size: 12px; color: #666;">
                <?php if ( $submitted_date ) : ?>
                    <p><strong><?php esc_html_e( 'Submitted:', 'easy-album-orders' ); ?></strong><br>
                    <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $submitted_date ) ) ); ?></p>
                <?php endif; ?>

                <?php if ( $ordered_date ) : ?>
                    <p><strong><?php esc_html_e( 'Ordered:', 'easy-album-orders' ); ?></strong><br>
                    <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ordered_date ) ) ); ?></p>
                <?php endif; ?>

                <?php if ( $shipped_date ) : ?>
                    <p><strong><?php esc_html_e( 'Shipped:', 'easy-album-orders' ); ?></strong><br>
                    <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $shipped_date ) ) ); ?></p>
                <?php endif; ?>
            </div>
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
        <div class="eao-meta-box">
            <div class="eao-order-header" style="background: #f6f7f7; padding: 15px; margin: -12px -12px 20px; border-bottom: 1px solid #ddd;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="font-size: 14px;"><?php echo esc_html( $order_number ); ?></strong>
                        <?php if ( $album_name ) : ?>
                            <br><span style="color: #666;"><?php echo esc_html( $album_name ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 24px; font-weight: bold; color: #2271b1;"><?php echo esc_html( eao_format_price( $total ) ); ?></span>
                    </div>
                </div>
            </div>

            <table class="eao-price-table" style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?php esc_html_e( 'Base Price', 'easy-album-orders' ); ?></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;"><?php echo esc_html( eao_format_price( $base_price ) ); ?></td>
                </tr>
                <?php if ( $material_upcharge > 0 ) : ?>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?php esc_html_e( 'Material Upcharge', 'easy-album-orders' ); ?></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;">+ <?php echo esc_html( eao_format_price( $material_upcharge ) ); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ( $size_upcharge > 0 ) : ?>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?php esc_html_e( 'Size Upcharge', 'easy-album-orders' ); ?></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;">+ <?php echo esc_html( eao_format_price( $size_upcharge ) ); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ( $engraving_upcharge > 0 ) : ?>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;"><?php esc_html_e( 'Engraving', 'easy-album-orders' ); ?></td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;">+ <?php echo esc_html( eao_format_price( $engraving_upcharge ) ); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ( $applied_credits > 0 ) : ?>
                <tr style="color: #00a32a;">
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee;">
                        <?php 
                        if ( 'free_album' === $credit_type ) {
                            esc_html_e( 'Free Album Credit', 'easy-album-orders' );
                        } else {
                            esc_html_e( 'Album Credit', 'easy-album-orders' );
                        }
                        ?>
                    </td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #eee; text-align: right;">- <?php echo esc_html( eao_format_price( $applied_credits ) ); ?></td>
                </tr>
                <?php endif; ?>
                <tr style="font-weight: bold; font-size: 16px;">
                    <td style="padding: 12px 0;"><?php esc_html_e( 'Total', 'easy-album-orders' ); ?></td>
                    <td style="padding: 12px 0; text-align: right;"><?php echo esc_html( eao_format_price( $total ) ); ?></td>
                </tr>
            </table>
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
            <div class="eao-field-row">
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_album_name"><?php esc_html_e( 'Album Name', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_album_name" name="eao_album_name" value="<?php echo esc_attr( $album_name ); ?>" class="regular-text" style="width: 100%;">
                </div>
            </div>

            <div class="eao-field-row">
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_design_name"><?php esc_html_e( 'Design', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_design_name" name="eao_design_name" value="<?php echo esc_attr( $design_name ); ?>" class="regular-text" style="width: 100%;">
                    <p class="description"><?php esc_html_e( 'The selected album design.', 'easy-album-orders' ); ?></p>
                </div>
            </div>

            <div class="eao-field-row">
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_material_name"><?php esc_html_e( 'Material', 'easy-album-orders' ); ?></label>
                    <select id="eao_material_name" name="eao_material_name" style="width: 100%;">
                        <option value=""><?php esc_html_e( '— Select Material —', 'easy-album-orders' ); ?></option>
                        <?php foreach ( $materials as $material ) : ?>
                            <option value="<?php echo esc_attr( $material['name'] ); ?>" <?php selected( $material_name, $material['name'] ); ?>>
                                <?php echo esc_html( $material['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_material_color"><?php esc_html_e( 'Color', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_material_color" name="eao_material_color" value="<?php echo esc_attr( $material_color ); ?>" class="regular-text" style="width: 100%;">
                </div>
            </div>

            <div class="eao-field-row">
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_size_name"><?php esc_html_e( 'Size', 'easy-album-orders' ); ?></label>
                    <select id="eao_size_name" name="eao_size_name" style="width: 100%;">
                        <option value=""><?php esc_html_e( '— Select Size —', 'easy-album-orders' ); ?></option>
                        <?php foreach ( $sizes as $size ) : ?>
                            <option value="<?php echo esc_attr( $size['name'] ); ?>" <?php selected( $size_name, $size['name'] ); ?>>
                                <?php echo esc_html( $size['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr style="margin: 20px 0;">

            <h4 style="margin: 0 0 15px;"><?php esc_html_e( 'Engraving', 'easy-album-orders' ); ?></h4>

            <div class="eao-field-row">
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_engraving_method"><?php esc_html_e( 'Method', 'easy-album-orders' ); ?></label>
                    <select id="eao_engraving_method" name="eao_engraving_method" style="width: 100%;">
                        <option value=""><?php esc_html_e( 'No Engraving', 'easy-album-orders' ); ?></option>
                        <?php foreach ( $engraving_options as $option ) : ?>
                            <option value="<?php echo esc_attr( $option['name'] ); ?>" <?php selected( $engraving_method, $option['name'] ); ?>>
                                <?php echo esc_html( $option['name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_engraving_font"><?php esc_html_e( 'Font', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_engraving_font" name="eao_engraving_font" value="<?php echo esc_attr( $engraving_font ); ?>" class="regular-text" style="width: 100%;">
                </div>
            </div>

            <div class="eao-field">
                <label for="eao_engraving_text"><?php esc_html_e( 'Engraving Text', 'easy-album-orders' ); ?></label>
                <input type="text" id="eao_engraving_text" name="eao_engraving_text" value="<?php echo esc_attr( $engraving_text ); ?>" class="large-text">
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
        ?>
        <div class="eao-meta-box">
            <h4 style="margin: 0 0 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php esc_html_e( 'Customer Contact', 'easy-album-orders' ); ?></h4>
            <div class="eao-field-row">
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_customer_name"><?php esc_html_e( 'Name', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_customer_name" name="eao_customer_name" value="<?php echo esc_attr( $customer_name ); ?>" class="regular-text" style="width: 100%;">
                </div>
            </div>
            <div class="eao-field-row">
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_customer_email"><?php esc_html_e( 'Email', 'easy-album-orders' ); ?></label>
                    <input type="email" id="eao_customer_email" name="eao_customer_email" value="<?php echo esc_attr( $customer_email ); ?>" class="regular-text" style="width: 100%;">
                </div>
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_customer_phone"><?php esc_html_e( 'Phone', 'easy-album-orders' ); ?></label>
                    <input type="tel" id="eao_customer_phone" name="eao_customer_phone" value="<?php echo esc_attr( $customer_phone ); ?>" class="regular-text" style="width: 100%;">
                </div>
            </div>

            <h4 style="margin: 20px 0 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php esc_html_e( 'Shipping Address', 'easy-album-orders' ); ?></h4>
            
            <div class="eao-field">
                <label for="eao_shipping_name"><?php esc_html_e( 'Recipient Name', 'easy-album-orders' ); ?></label>
                <input type="text" id="eao_shipping_name" name="eao_shipping_name" value="<?php echo esc_attr( $shipping_name ); ?>" class="regular-text" style="width: 100%;">
            </div>

            <div class="eao-field">
                <label for="eao_shipping_address1"><?php esc_html_e( 'Street Address', 'easy-album-orders' ); ?></label>
                <input type="text" id="eao_shipping_address1" name="eao_shipping_address1" value="<?php echo esc_attr( $shipping_address1 ); ?>" class="regular-text" style="width: 100%;">
            </div>

            <div class="eao-field">
                <label for="eao_shipping_address2"><?php esc_html_e( 'Apartment, Suite, etc.', 'easy-album-orders' ); ?></label>
                <input type="text" id="eao_shipping_address2" name="eao_shipping_address2" value="<?php echo esc_attr( $shipping_address2 ); ?>" class="regular-text" style="width: 100%;">
            </div>

            <div class="eao-field-row">
                <div class="eao-field" style="flex: 2;">
                    <label for="eao_shipping_city"><?php esc_html_e( 'City', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_shipping_city" name="eao_shipping_city" value="<?php echo esc_attr( $shipping_city ); ?>" class="regular-text" style="width: 100%;">
                </div>
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_shipping_state"><?php esc_html_e( 'State', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_shipping_state" name="eao_shipping_state" value="<?php echo esc_attr( $shipping_state ); ?>" class="regular-text" style="width: 100%;">
                </div>
                <div class="eao-field" style="flex: 1;">
                    <label for="eao_shipping_zip"><?php esc_html_e( 'ZIP Code', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_shipping_zip" name="eao_shipping_zip" value="<?php echo esc_attr( $shipping_zip ); ?>" class="regular-text" style="width: 100%;">
                </div>
            </div>
        </div>
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
            <div class="eao-field">
                <label for="eao_client_notes"><?php esc_html_e( 'Client Notes', 'easy-album-orders' ); ?></label>
                <textarea id="eao_client_notes" name="eao_client_notes" rows="3" class="large-text"><?php echo esc_textarea( $client_notes ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Notes from the client during checkout.', 'easy-album-orders' ); ?></p>
            </div>
            <div class="eao-field" style="margin-top: 15px;">
                <label for="eao_photographer_notes"><?php esc_html_e( 'Internal Notes', 'easy-album-orders' ); ?></label>
                <textarea id="eao_photographer_notes" name="eao_photographer_notes" rows="3" class="large-text"><?php echo esc_textarea( $photographer_notes ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Private notes (not visible to client).', 'easy-album-orders' ); ?></p>
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
                <p>
                    <strong><?php echo esc_html( $client_album->post_title ); ?></strong>
                </p>
                <p>
                    <a href="<?php echo esc_url( get_edit_post_link( $client_album_id ) ); ?>" class="button">
                        <?php esc_html_e( 'View Client Album', 'easy-album-orders' ); ?>
                    </a>
                </p>
                <p>
                    <a href="<?php echo esc_url( get_permalink( $client_album_id ) ); ?>" target="_blank">
                        <?php esc_html_e( 'View Order Form', 'easy-album-orders' ); ?> →
                    </a>
                </p>
            <?php else : ?>
                <p class="description"><?php esc_html_e( 'This order is not linked to a client album.', 'easy-album-orders' ); ?></p>
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

