<?php
/**
 * The main plugin class.
 *
 * This class is responsible for loading all dependencies, defining hooks,
 * and coordinating the plugin's functionality.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class EAO_Plugin {

    /**
     * The loader responsible for maintaining and registering all hooks.
     *
     * @since  1.0.0
     * @access protected
     * @var    EAO_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Initialize the plugin.
     *
     * Set the plugin name and version, load dependencies, and define hooks.
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->version     = EAO_VERSION;
        $this->plugin_name = 'easy-album-orders';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load all required dependencies for the plugin.
     *
     * Include the following files that make up the plugin:
     * - EAO_Loader: Orchestrates the hooks of the plugin.
     * - EAO_Admin: Defines all hooks for the admin area.
     * - EAO_Public: Defines all hooks for the public side of the site.
     * - Post type classes for Client Album and Album Order.
     *
     * @since  1.0.0
     * @access private
     */
    private function load_dependencies() {
        // Core classes.
        require_once EAO_PLUGIN_DIR . 'includes/class-eao-loader.php';

        // Post type classes.
        require_once EAO_PLUGIN_DIR . 'includes/post-types/class-eao-client-album.php';
        require_once EAO_PLUGIN_DIR . 'includes/post-types/class-eao-album-order.php';

        // Admin classes.
        require_once EAO_PLUGIN_DIR . 'includes/admin/class-eao-admin.php';
        require_once EAO_PLUGIN_DIR . 'includes/admin/class-eao-admin-menus.php';
        require_once EAO_PLUGIN_DIR . 'includes/admin/class-eao-client-album-meta.php';
        require_once EAO_PLUGIN_DIR . 'includes/admin/class-eao-album-order-meta.php';
        require_once EAO_PLUGIN_DIR . 'includes/admin/class-eao-admin-columns.php';

        // Public classes.
        require_once EAO_PLUGIN_DIR . 'includes/public/class-eao-public.php';
        require_once EAO_PLUGIN_DIR . 'includes/public/class-eao-template-loader.php';
        require_once EAO_PLUGIN_DIR . 'includes/public/class-eao-ajax-handler.php';

        // Core utilities.
        require_once EAO_PLUGIN_DIR . 'includes/core/class-eao-helpers.php';

        $this->loader = new EAO_Loader();
    }

    /**
     * Register all hooks related to the admin area.
     *
     * @since  1.0.0
     * @access private
     */
    private function define_admin_hooks() {
        $admin       = new EAO_Admin( $this->get_plugin_name(), $this->get_version() );
        $admin_menus = new EAO_Admin_Menus( $this->get_plugin_name(), $this->get_version() );

        // Admin assets.
        $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );

        // Admin menus.
        $this->loader->add_action( 'admin_menu', $admin_menus, 'register_menus' );

        // Register post types.
        $client_album = new EAO_Client_Album();
        $album_order  = new EAO_Album_Order();

        $this->loader->add_action( 'init', $client_album, 'register_post_type' );
        $this->loader->add_action( 'init', $album_order, 'register_post_type' );

        // Meta boxes (hooks are registered in constructors).
        new EAO_Client_Album_Meta();
        new EAO_Album_Order_Meta();

        // Admin columns and filters.
        new EAO_Admin_Columns();
    }

    /**
     * Register all hooks related to the public-facing functionality.
     *
     * @since  1.0.0
     * @access private
     */
    private function define_public_hooks() {
        $public = new EAO_Public( $this->get_plugin_name(), $this->get_version() );

        // Public assets.
        $this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );

        // Template loader (hooks registered in constructor).
        new EAO_Template_Loader();

        // AJAX handlers (hooks registered in constructor).
        new EAO_Ajax_Handler();
    }

    /**
     * Run the loader to execute all hooks.
     *
     * @since 1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Get the plugin name.
     *
     * @since  1.0.0
     * @return string The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Get the loader instance.
     *
     * @since  1.0.0
     * @return EAO_Loader The loader instance.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Get the plugin version.
     *
     * @since  1.0.0
     * @return string The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}

