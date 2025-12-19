<?php
/**
 * Client Album meta boxes.
 *
 * Handles all meta boxes for the Client Album post type including
 * client information, Loom video URL, album credits, and album designs.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Client Album meta boxes class.
 *
 * @since 1.0.0
 */
class EAO_Client_Album_Meta {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_client_album', array( $this, 'save_meta_boxes' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Register meta boxes for Client Album.
     *
     * @since 1.0.0
     */
    public function register_meta_boxes() {
        // Client Information meta box.
        add_meta_box(
            'eao_client_info',
            __( 'Client Information', 'easy-album-orders' ),
            array( $this, 'render_client_info_meta_box' ),
            'client_album',
            'normal',
            'high'
        );

        // Loom Video meta box.
        add_meta_box(
            'eao_loom_video',
            __( 'Loom Video', 'easy-album-orders' ),
            array( $this, 'render_loom_video_meta_box' ),
            'client_album',
            'normal',
            'high'
        );

        // Album Designs meta box.
        add_meta_box(
            'eao_album_designs',
            __( 'Album Designs', 'easy-album-orders' ),
            array( $this, 'render_designs_meta_box' ),
            'client_album',
            'normal',
            'default'
        );

        // Album Link meta box.
        add_meta_box(
            'eao_album_link',
            __( 'Album Order Link', 'easy-album-orders' ),
            array( $this, 'render_album_link_meta_box' ),
            'client_album',
            'side',
            'high'
        );
    }

    /**
     * Enqueue scripts for Client Album edit screen.
     *
     * @since 1.0.0
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts( $hook ) {
        global $post_type;

        if ( 'client_album' !== $post_type ) {
            return;
        }

        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        // Enqueue media uploader.
        wp_enqueue_media();

        // Enqueue our admin script (already registered in EAO_Admin).
    }

    /**
     * Render Client Information meta box.
     *
     * @since 1.0.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_client_info_meta_box( $post ) {
        wp_nonce_field( 'eao_client_album_meta', 'eao_client_album_nonce' );

        $client_name  = get_post_meta( $post->ID, '_eao_client_name', true );
        $client_email = get_post_meta( $post->ID, '_eao_client_email', true );
        $client_phone = get_post_meta( $post->ID, '_eao_client_phone', true );
        ?>
        <div class="eao-meta-box">
            <div class="eao-field-row">
                <div class="eao-field">
                    <label for="eao_client_name"><?php esc_html_e( 'Client Name', 'easy-album-orders' ); ?></label>
                    <input type="text" id="eao_client_name" name="eao_client_name" value="<?php echo esc_attr( $client_name ); ?>" class="regular-text">
                </div>
            </div>
            <div class="eao-field-row">
                <div class="eao-field">
                    <label for="eao_client_email"><?php esc_html_e( 'Client Email', 'easy-album-orders' ); ?></label>
                    <input type="email" id="eao_client_email" name="eao_client_email" value="<?php echo esc_attr( $client_email ); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e( 'Used for order notifications.', 'easy-album-orders' ); ?></p>
                </div>
            </div>
            <div class="eao-field-row">
                <div class="eao-field">
                    <label for="eao_client_phone"><?php esc_html_e( 'Client Phone', 'easy-album-orders' ); ?></label>
                    <input type="tel" id="eao_client_phone" name="eao_client_phone" value="<?php echo esc_attr( $client_phone ); ?>" class="regular-text">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Loom Video meta box.
     *
     * @since 1.0.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_loom_video_meta_box( $post ) {
        $loom_url = get_post_meta( $post->ID, '_eao_loom_url', true );
        ?>
        <div class="eao-meta-box">
            <div class="eao-field">
                <label for="eao_loom_url"><?php esc_html_e( 'Loom Video URL', 'easy-album-orders' ); ?></label>
                <input type="url" id="eao_loom_url" name="eao_loom_url" value="<?php echo esc_url( $loom_url ); ?>" class="large-text" placeholder="https://www.loom.com/share/...">
                <p class="description"><?php esc_html_e( 'Paste a Loom video URL to display at the top of the order form. This is a great way to walk clients through their album options.', 'easy-album-orders' ); ?></p>
            </div>
            <?php if ( ! empty( $loom_url ) ) : ?>
                <div class="eao-loom-preview" style="margin-top: 15px;">
                    <p><strong><?php esc_html_e( 'Preview:', 'easy-album-orders' ); ?></strong></p>
                    <?php echo $this->get_loom_embed( $loom_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get Loom embed HTML from URL.
     *
     * @since 1.0.0
     *
     * @param string $url The Loom URL.
     * @return string The embed HTML.
     */
    private function get_loom_embed( $url ) {
        // Extract Loom video ID.
        $video_id = '';

        if ( preg_match( '/loom\.com\/share\/([a-zA-Z0-9]+)/', $url, $matches ) ) {
            $video_id = $matches[1];
        } elseif ( preg_match( '/loom\.com\/embed\/([a-zA-Z0-9]+)/', $url, $matches ) ) {
            $video_id = $matches[1];
        }

        if ( empty( $video_id ) ) {
            return '<p class="description">' . esc_html__( 'Invalid Loom URL.', 'easy-album-orders' ) . '</p>';
        }

        $embed_url = 'https://www.loom.com/embed/' . $video_id;

        return sprintf(
            '<div style="position: relative; padding-bottom: 56.25%%; height: 0; max-width: 560px;"><iframe src="%s" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%%; height: 100%%;"></iframe></div>',
            esc_url( $embed_url )
        );
    }

    /**
     * Render Album Link meta box.
     *
     * @since 1.0.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_album_link_meta_box( $post ) {
        $permalink = get_permalink( $post->ID );
        $status    = get_post_status( $post->ID );
        ?>
        <div class="eao-meta-box">
            <?php if ( 'publish' === $status ) : ?>
                <p><?php esc_html_e( 'Share this link with your client:', 'easy-album-orders' ); ?></p>
                <div class="eao-copy-link" style="display: flex; gap: 5px; margin-bottom: 10px;">
                    <input type="text" value="<?php echo esc_url( $permalink ); ?>" readonly class="regular-text" style="flex: 1;" id="eao-album-link">
                    <button type="button" class="button eao-copy-link-btn" data-clipboard-target="#eao-album-link">
                        <?php esc_html_e( 'Copy', 'easy-album-orders' ); ?>
                    </button>
                </div>
                <p>
                    <a href="<?php echo esc_url( $permalink ); ?>" target="_blank" class="button">
                        <?php esc_html_e( 'View Order Form', 'easy-album-orders' ); ?> →
                    </a>
                </p>
            <?php else : ?>
                <p class="description"><?php esc_html_e( 'Publish this album to generate a shareable order link.', 'easy-album-orders' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Album Designs meta box.
     *
     * @since 1.0.0
     *
     * @param WP_Post $post The current post object.
     */
    public function render_designs_meta_box( $post ) {
        $designs = get_post_meta( $post->ID, '_eao_designs', true );
        $designs = is_array( $designs ) ? $designs : array();
        ?>
        <div class="eao-meta-box">
            <p class="description" style="margin-bottom: 15px;">
                <?php esc_html_e( 'Add album designs for this client. Each design includes a name, PDF proof, cover image, base price, and optional credits.', 'easy-album-orders' ); ?>
            </p>

            <div class="eao-repeater eao-designs-repeater" id="eao-designs-repeater">
                <div class="eao-repeater__items">
                    <?php if ( ! empty( $designs ) ) : ?>
                        <?php foreach ( $designs as $index => $design ) : ?>
                            <?php $this->render_design_item( $index, $design ); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="button button-secondary eao-add-design">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e( 'Add Design', 'easy-album-orders' ); ?>
                </button>
            </div>
        </div>

        <!-- Design Item Template -->
        <script type="text/html" id="tmpl-eao-design-item">
            <?php $this->render_design_item( '{{data.index}}', array(), true ); ?>
        </script>
        <?php
    }

    /**
     * Render a single design item.
     *
     * @since 1.0.0
     *
     * @param int|string $index     The item index.
     * @param array      $design    The design data.
     * @param bool       $is_template Whether this is a JS template.
     */
    private function render_design_item( $index, $design = array(), $is_template = false ) {
        $defaults = array(
            'name'              => '',
            'pdf_id'            => '',
            'cover_id'          => '',
            'base_price'        => '',
            'free_album_credits'=> '',
            'dollar_credit'     => '',
        );

        $design = wp_parse_args( $design, $defaults );
        $name_field = $is_template ? 'eao_designs[{{data.index}}]' : "eao_designs[{$index}]";
        ?>
        <div class="eao-repeater__item eao-design-item" data-index="<?php echo esc_attr( $index ); ?>">
            <div class="eao-repeater__item-header">
                <span class="eao-repeater__item-title">
                    <?php echo ! empty( $design['name'] ) ? esc_html( $design['name'] ) : esc_html__( 'New Design', 'easy-album-orders' ); ?>
                </span>
                <button type="button" class="eao-repeater__toggle" aria-expanded="<?php echo $is_template ? 'true' : 'false'; ?>">
                    <span class="dashicons dashicons-arrow-<?php echo $is_template ? 'up' : 'down'; ?>-alt2"></span>
                </button>
                <button type="button" class="eao-repeater__remove" title="<?php esc_attr_e( 'Remove', 'easy-album-orders' ); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
            <div class="eao-repeater__item-content" <?php echo $is_template ? 'style="display: block;"' : ''; ?>>
                <div class="eao-field-row">
                    <div class="eao-field" style="flex: 2;">
                        <label><?php esc_html_e( 'Design Name', 'easy-album-orders' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $name_field ); ?>[name]" value="<?php echo esc_attr( $design['name'] ); ?>" class="regular-text eao-design-name-input" placeholder="<?php esc_attr_e( 'e.g., Classic Layout', 'easy-album-orders' ); ?>">
                    </div>
                    <div class="eao-field" style="flex: 1;">
                        <label><?php esc_html_e( 'Base Price ($)', 'easy-album-orders' ); ?></label>
                        <input type="number" name="<?php echo esc_attr( $name_field ); ?>[base_price]" value="<?php echo esc_attr( $design['base_price'] ); ?>" step="0.01" min="0" class="small-text" style="width: 100%;">
                    </div>
                </div>

                <div class="eao-field-row">
                    <!-- Cover Image -->
                    <div class="eao-field eao-field--media">
                        <label><?php esc_html_e( 'Cover Image', 'easy-album-orders' ); ?></label>
                        <div class="eao-media-upload eao-image-upload">
                            <input type="hidden" name="<?php echo esc_attr( $name_field ); ?>[cover_id]" value="<?php echo esc_attr( $design['cover_id'] ); ?>" class="eao-image-id">
                            <div class="eao-image-preview">
                                <?php if ( ! empty( $design['cover_id'] ) ) : ?>
                                    <?php echo wp_get_attachment_image( $design['cover_id'], 'thumbnail' ); ?>
                                <?php endif; ?>
                            </div>
                            <div class="eao-media-buttons">
                                <button type="button" class="button eao-upload-image"><?php esc_html_e( 'Select Image', 'easy-album-orders' ); ?></button>
                                <button type="button" class="button eao-remove-image" <?php echo empty( $design['cover_id'] ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove', 'easy-album-orders' ); ?></button>
                            </div>
                        </div>
                    </div>

                    <!-- PDF Proof -->
                    <div class="eao-field eao-field--media">
                        <label><?php esc_html_e( 'PDF Proof', 'easy-album-orders' ); ?></label>
                        <div class="eao-media-upload eao-pdf-upload">
                            <input type="hidden" name="<?php echo esc_attr( $name_field ); ?>[pdf_id]" value="<?php echo esc_attr( $design['pdf_id'] ); ?>" class="eao-pdf-id">
                            <div class="eao-pdf-preview">
                                <?php if ( ! empty( $design['pdf_id'] ) ) : ?>
                                    <?php
                                    $pdf_url  = wp_get_attachment_url( $design['pdf_id'] );
                                    $pdf_name = basename( get_attached_file( $design['pdf_id'] ) );
                                    ?>
                                    <span class="dashicons dashicons-pdf"></span>
                                    <a href="<?php echo esc_url( $pdf_url ); ?>" target="_blank"><?php echo esc_html( $pdf_name ); ?></a>
                                <?php else : ?>
                                    <span class="eao-no-pdf"><?php esc_html_e( 'No PDF selected', 'easy-album-orders' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="eao-media-buttons">
                                <button type="button" class="button eao-upload-pdf"><?php esc_html_e( 'Select PDF', 'easy-album-orders' ); ?></button>
                                <button type="button" class="button eao-remove-pdf" <?php echo empty( $design['pdf_id'] ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove', 'easy-album-orders' ); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credits Section -->
                <div class="eao-design-credits" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e4e7;">
                    <h4 style="margin: 0 0 10px; font-size: 13px; color: #1d2327;"><?php esc_html_e( 'Album Credits for This Design', 'easy-album-orders' ); ?></h4>
                    <div class="eao-field-row">
                        <div class="eao-field" style="flex: 1;">
                            <label><?php esc_html_e( 'Free Album Credits', 'easy-album-orders' ); ?></label>
                            <input type="number" name="<?php echo esc_attr( $name_field ); ?>[free_album_credits]" value="<?php echo esc_attr( $design['free_album_credits'] ); ?>" min="0" step="1" class="small-text" style="width: 100%;">
                            <p class="description"><?php esc_html_e( 'Number of free albums (base price covered). Client pays only for upgrades.', 'easy-album-orders' ); ?></p>
                        </div>
                        <div class="eao-field" style="flex: 1;">
                            <label><?php esc_html_e( 'Dollar Credit ($)', 'easy-album-orders' ); ?></label>
                            <input type="number" name="<?php echo esc_attr( $name_field ); ?>[dollar_credit]" value="<?php echo esc_attr( $design['dollar_credit'] ); ?>" min="0" step="0.01" class="small-text" style="width: 100%;">
                            <p class="description"><?php esc_html_e( 'Dollar amount off each album of this design.', 'easy-album-orders' ); ?></p>
                        </div>
                    </div>
                </div>
            </div>
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
        if ( ! isset( $_POST['eao_client_album_nonce'] ) || ! wp_verify_nonce( $_POST['eao_client_album_nonce'], 'eao_client_album_meta' ) ) {
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

        // Save client information.
        if ( isset( $_POST['eao_client_name'] ) ) {
            update_post_meta( $post_id, '_eao_client_name', sanitize_text_field( $_POST['eao_client_name'] ) );
        }

        if ( isset( $_POST['eao_client_email'] ) ) {
            update_post_meta( $post_id, '_eao_client_email', sanitize_email( $_POST['eao_client_email'] ) );
        }

        if ( isset( $_POST['eao_client_phone'] ) ) {
            update_post_meta( $post_id, '_eao_client_phone', EAO_Helpers::sanitize_phone( $_POST['eao_client_phone'] ) );
        }

        // Save Loom URL.
        if ( isset( $_POST['eao_loom_url'] ) ) {
            update_post_meta( $post_id, '_eao_loom_url', esc_url_raw( $_POST['eao_loom_url'] ) );
        }

        // Save designs.
        if ( isset( $_POST['eao_designs'] ) && is_array( $_POST['eao_designs'] ) ) {
            $designs = $this->sanitize_designs( $_POST['eao_designs'] );
            update_post_meta( $post_id, '_eao_designs', $designs );
        } else {
            update_post_meta( $post_id, '_eao_designs', array() );
        }
    }

    /**
     * Sanitize designs array.
     *
     * @since 1.0.0
     *
     * @param array $designs Raw designs data.
     * @return array Sanitized designs.
     */
    private function sanitize_designs( $designs ) {
        $sanitized = array();

        foreach ( $designs as $design ) {
            // Skip empty designs.
            if ( empty( $design['name'] ) && empty( $design['cover_id'] ) && empty( $design['pdf_id'] ) ) {
                continue;
            }

            $sanitized[] = array(
                'name'               => sanitize_text_field( $design['name'] ),
                'pdf_id'             => absint( $design['pdf_id'] ),
                'cover_id'           => absint( $design['cover_id'] ),
                'base_price'         => floatval( $design['base_price'] ),
                'free_album_credits' => ! empty( $design['free_album_credits'] ) ? absint( $design['free_album_credits'] ) : 0,
                'dollar_credit'      => ! empty( $design['dollar_credit'] ) ? floatval( $design['dollar_credit'] ) : 0,
            );
        }

        return $sanitized;
    }
}

