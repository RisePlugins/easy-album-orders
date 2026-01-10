<?php
/**
 * Easy Album Orders
 *
 * A WordPress plugin for professional photographers to streamline
 * the process of selling custom albums directly through their website.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Easy Album Orders
 * Plugin URI:        https://riseplugins.com/
 * Description:       Streamline custom album orders for professional photographers with client order forms, material options, and order management.
 * Version:           1.0.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Ryan Moreno
 * Author URI:        https://riseplugins.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       easy-album-orders
 * Domain Path:       /languages
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin version.
 *
 * @since 1.0.0
 */
define( 'EAO_VERSION', '1.0.2' );

/**
 * Plugin directory path.
 *
 * @since 1.0.0
 */
define( 'EAO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 *
 * @since 1.0.0
 */
define( 'EAO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 *
 * @since 1.0.0
 */
define( 'EAO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum PHP version required.
 *
 * @since 1.0.0
 */
define( 'EAO_MIN_PHP_VERSION', '7.4' );

/**
 * Minimum WordPress version required.
 *
 * @since 1.0.0
 */
define( 'EAO_MIN_WP_VERSION', '5.8' );

/**
 * GitHub repository for updates.
 *
 * Format: 'username/repository-name'
 * Set this to your GitHub repo to enable automatic updates.
 *
 * @since 1.0.0
 */
define( 'EAO_GITHUB_REPO', 'RisePlugins/easy-album-orders' );

/**
 * Check PHP version and display error if not met.
 *
 * @since 1.0.0
 *
 * @return bool True if PHP version is compatible, false otherwise.
 */
function eao_check_php_version() {
    if ( version_compare( PHP_VERSION, EAO_MIN_PHP_VERSION, '<' ) ) {
        add_action( 'admin_notices', 'eao_php_version_notice' );
        return false;
    }
    return true;
}

/**
 * Display PHP version error notice.
 *
 * @since 1.0.0
 */
function eao_php_version_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                /* translators: 1: Required PHP version, 2: Current PHP version */
                esc_html__( 'Easy Album Orders requires PHP version %1$s or higher. You are running PHP version %2$s. Please upgrade your PHP version.', 'easy-album-orders' ),
                esc_html( EAO_MIN_PHP_VERSION ),
                esc_html( PHP_VERSION )
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Check WordPress version and display error if not met.
 *
 * @since 1.0.0
 *
 * @return bool True if WordPress version is compatible, false otherwise.
 */
function eao_check_wp_version() {
    global $wp_version;
    if ( version_compare( $wp_version, EAO_MIN_WP_VERSION, '<' ) ) {
        add_action( 'admin_notices', 'eao_wp_version_notice' );
        return false;
    }
    return true;
}

/**
 * Display WordPress version error notice.
 *
 * @since 1.0.0
 */
function eao_wp_version_notice() {
    global $wp_version;
    ?>
    <div class="notice notice-error">
        <p>
            <?php
            printf(
                /* translators: 1: Required WordPress version, 2: Current WordPress version */
                esc_html__( 'Easy Album Orders requires WordPress version %1$s or higher. You are running WordPress version %2$s. Please upgrade WordPress.', 'easy-album-orders' ),
                esc_html( EAO_MIN_WP_VERSION ),
                esc_html( $wp_version )
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Run version checks before initializing the plugin.
 *
 * @since 1.0.0
 *
 * @return bool True if all checks pass, false otherwise.
 */
function eao_requirements_met() {
    return eao_check_php_version() && eao_check_wp_version();
}

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function eao_activate() {
    require_once EAO_PLUGIN_DIR . 'includes/class-eao-activator.php';
    EAO_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function eao_deactivate() {
    require_once EAO_PLUGIN_DIR . 'includes/class-eao-deactivator.php';
    EAO_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'eao_activate' );
register_deactivation_hook( __FILE__, 'eao_deactivate' );

/**
 * Load the plugin text domain for translations.
 *
 * @since 1.0.0
 */
function eao_load_textdomain() {
    load_plugin_textdomain(
        'easy-album-orders',
        false,
        dirname( EAO_PLUGIN_BASENAME ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'eao_load_textdomain' );

/**
 * Begin execution of the plugin.
 *
 * @since 1.0.0
 */
function eao_run() {
    // Check requirements before running.
    if ( ! eao_requirements_met() ) {
        return;
    }

    // Include the main plugin class.
    require_once EAO_PLUGIN_DIR . 'includes/class-eao-plugin.php';

    // Initialize and run the plugin.
    $plugin = new EAO_Plugin();
    $plugin->run();
}
add_action( 'plugins_loaded', 'eao_run' );

/**
 * Initialize the GitHub updater for automatic updates.
 *
 * @since 1.0.0
 */
function eao_init_updater() {
    // Only initialize if a GitHub repo is configured.
    if ( empty( EAO_GITHUB_REPO ) ) {
        return;
    }

    require_once EAO_PLUGIN_DIR . 'includes/core/class-eao-github-updater.php';

    new EAO_GitHub_Updater(
        __FILE__,
        EAO_GITHUB_REPO,
        '' // Optional: Add access token for private repos.
    );
}
add_action( 'admin_init', 'eao_init_updater' );

/**
 * Add action links to the plugins page.
 *
 * @since 1.0.2
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function eao_plugin_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . esc_url( admin_url( 'admin.php?page=eao-album-options' ) ) . '">' . esc_html__( 'Settings', 'easy-album-orders' ) . '</a>',
        '<a href="' . esc_url( admin_url( 'edit.php?post_type=album_order' ) ) . '">' . esc_html__( 'View Orders', 'easy-album-orders' ) . '</a>',
        '<a href="' . esc_url( admin_url( 'post-new.php?post_type=client_album' ) ) . '">' . esc_html__( 'Create Client Album', 'easy-album-orders' ) . '</a>',
    );

    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . EAO_PLUGIN_BASENAME, 'eao_plugin_action_links' );

/**
 * Add row meta links to the plugins page.
 *
 * @since 1.0.2
 *
 * @param array  $links Plugin row meta links.
 * @param string $file  Plugin file path.
 * @return array Modified row meta links.
 */
function eao_plugin_row_meta( $links, $file ) {
    if ( EAO_PLUGIN_BASENAME !== $file ) {
        return $links;
    }

    $check_updates_url = wp_nonce_url(
        admin_url( 'plugins.php?eao_check_updates=1' ),
        'eao_check_updates',
        'eao_nonce'
    );

    $links[] = '<a href="' . esc_url( $check_updates_url ) . '">' . esc_html__( 'Check for updates', 'easy-album-orders' ) . '</a>';

    return $links;
}
add_filter( 'plugin_row_meta', 'eao_plugin_row_meta', 10, 2 );

/**
 * Handle the check for updates action.
 *
 * @since 1.0.2
 */
function eao_handle_check_updates() {
    if ( ! isset( $_GET['eao_check_updates'] ) || ! isset( $_GET['eao_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['eao_nonce'] ) ), 'eao_check_updates' ) ) {
        return;
    }

    if ( ! current_user_can( 'update_plugins' ) ) {
        return;
    }

    // Clear the GitHub release cache.
    delete_transient( 'eao_github_release' );

    // Clear WordPress update cache.
    delete_site_transient( 'update_plugins' );

    // Force WordPress to check for updates.
    wp_update_plugins();

    // Redirect back to plugins page with a message.
    wp_safe_redirect( admin_url( 'plugins.php?eao_updated=1' ) );
    exit;
}
add_action( 'admin_init', 'eao_handle_check_updates' );

/**
 * Display admin notice after checking for updates.
 *
 * @since 1.0.2
 */
function eao_update_check_notice() {
    if ( ! isset( $_GET['eao_updated'] ) ) {
        return;
    }

    // Check if an update is available.
    $update_plugins = get_site_transient( 'update_plugins' );
    $has_update     = isset( $update_plugins->response[ EAO_PLUGIN_BASENAME ] );

    if ( $has_update ) {
        $new_version = $update_plugins->response[ EAO_PLUGIN_BASENAME ]->new_version;
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php
                printf(
                    /* translators: %s: New version number */
                    esc_html__( 'Easy Album Orders: Update available! Version %s is ready to install.', 'easy-album-orders' ),
                    esc_html( $new_version )
                );
                ?>
            </p>
        </div>
        <?php
    } else {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Easy Album Orders: You are running the latest version.', 'easy-album-orders' ); ?></p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'eao_update_check_notice' );
