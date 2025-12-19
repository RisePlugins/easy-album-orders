<?php
/**
 * Admin functionality.
 *
 * Handles all admin-specific functionality including
 * enqueuing styles and scripts for the admin area.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class.
 *
 * @since 1.0.0
 */
class EAO_Admin {

    /**
     * The ID of this plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since  1.0.0
     * @access private
     * @var    string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        $this->init_hooks();
    }

    /**
     * Initialize admin hooks.
     *
     * @since  1.0.0
     * @access private
     */
    private function init_hooks() {
        // Display activation notice.
        add_action( 'admin_notices', array( $this, 'activation_notice' ) );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since 1.0.0
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_styles( $hook ) {
        // Only load on our plugin pages.
        if ( ! $this->is_plugin_page( $hook ) ) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name . '-admin',
            EAO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since 1.0.0
     *
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts( $hook ) {
        // Only load on our plugin pages.
        if ( ! $this->is_plugin_page( $hook ) ) {
            return;
        }

        // Enqueue WordPress media uploader.
        wp_enqueue_media();

        wp_enqueue_script(
            $this->plugin_name . '-admin',
            EAO_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'jquery-ui-sortable' ),
            $this->version,
            true
        );

        // Localize script with data.
        wp_localize_script(
            $this->plugin_name . '-admin',
            'eaoAdmin',
            array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( 'eao_admin_nonce' ),
                'confirmDelete'  => __( 'Are you sure you want to delete this item?', 'easy-album-orders' ),
                'mediaTitle'     => __( 'Select or Upload Media', 'easy-album-orders' ),
                'mediaButton'    => __( 'Use this media', 'easy-album-orders' ),
                'pdfMediaTitle'  => __( 'Select or Upload PDF', 'easy-album-orders' ),
                'pdfMediaButton' => __( 'Use this PDF', 'easy-album-orders' ),
            )
        );
    }

    /**
     * Check if current page is a plugin admin page.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $hook The current admin page hook.
     * @return bool True if on a plugin page, false otherwise.
     */
    private function is_plugin_page( $hook ) {
        $plugin_pages = array(
            'toplevel_page_eao-client-albums',
            'client-albums_page_eao-album-options',
            'edit.php',
            'post.php',
            'post-new.php',
        );

        // Check if we're on one of our custom pages.
        if ( in_array( $hook, $plugin_pages, true ) ) {
            return true;
        }

        // Check if we're editing our post types.
        global $post_type;
        $our_post_types = array( 'client_album', 'album_order' );

        if ( in_array( $post_type, $our_post_types, true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Display activation notice.
     *
     * @since 1.0.0
     */
    public function activation_notice() {
        // Check if transient is set.
        if ( ! get_transient( 'eao_activation_notice' ) ) {
            return;
        }

        // Delete transient so it only shows once.
        delete_transient( 'eao_activation_notice' );

        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                printf(
                    /* translators: %s: Link to settings page */
                    esc_html__( 'Thank you for installing Easy Album Orders! Get started by %s.', 'easy-album-orders' ),
                    '<a href="' . esc_url( admin_url( 'admin.php?page=eao-album-options' ) ) . '">' . esc_html__( 'configuring your album options', 'easy-album-orders' ) . '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
}

