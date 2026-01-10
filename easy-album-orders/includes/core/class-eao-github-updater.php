<?php
/**
 * GitHub-based Plugin Updater
 *
 * Enables automatic updates from a GitHub repository.
 * Checks for new releases and integrates with WordPress update system.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EAO_GitHub_Updater
 *
 * Handles checking for updates from GitHub releases and
 * integrating with the WordPress plugin update mechanism.
 *
 * @since 1.0.0
 */
class EAO_GitHub_Updater {

	/**
	 * Plugin slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $slug;

	/**
	 * Plugin data from get_plugin_data().
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $plugin_data;

	/**
	 * Plugin basename.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $basename;

	/**
	 * GitHub username/repo.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $github_repo;

	/**
	 * GitHub API URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $github_api_url;

	/**
	 * GitHub response data (cached).
	 *
	 * @since 1.0.0
	 * @var object|null
	 */
	private $github_response;

	/**
	 * Access token for private repos (optional).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $access_token;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file   Full path to the main plugin file.
	 * @param string $github_repo   GitHub username/repository (e.g., 'username/repo-name').
	 * @param string $access_token  Optional. Access token for private repositories.
	 */
	public function __construct( $plugin_file, $github_repo, $access_token = '' ) {
		$this->slug         = plugin_basename( $plugin_file );
		$this->basename     = $plugin_file;
		$this->github_repo  = $github_repo;
		$this->access_token = $access_token;

		$this->github_api_url = sprintf(
			'https://api.github.com/repos/%s/releases/latest',
			$this->github_repo
		);

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );

		// Clear update cache when plugin is activated/deactivated
		add_action( 'activated_plugin', array( $this, 'clear_update_cache' ) );
		add_action( 'deactivated_plugin', array( $this, 'clear_update_cache' ) );
	}

	/**
	 * Get plugin data.
	 *
	 * @since 1.0.0
	 *
	 * @return array Plugin data.
	 */
	private function get_plugin_data() {
		if ( empty( $this->plugin_data ) ) {
			$this->plugin_data = get_plugin_data( $this->basename );
		}
		return $this->plugin_data;
	}

	/**
	 * Get repository info from GitHub.
	 *
	 * @since 1.0.0
	 *
	 * @return object|false GitHub release data or false on failure.
	 */
	private function get_github_release() {
		if ( ! empty( $this->github_response ) ) {
			return $this->github_response;
		}

		// Check transient cache first.
		$cached = get_transient( 'eao_github_release' );
		if ( false !== $cached ) {
			$this->github_response = $cached;
			return $cached;
		}

		// Build request args.
		$args = array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/vnd.github.v3+json',
			),
		);

		// Add authorization for private repos.
		if ( ! empty( $this->access_token ) ) {
			$args['headers']['Authorization'] = 'token ' . $this->access_token;
		}

		// Make the request.
		$response = wp_remote_get( $this->github_api_url, $args );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( empty( $data ) || ! isset( $data->tag_name ) ) {
			return false;
		}

		$this->github_response = $data;

		// Cache for 6 hours.
		set_transient( 'eao_github_release', $data, 6 * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Check for plugin update.
	 *
	 * @since 1.0.0
	 *
	 * @param object $transient Update transient data.
	 * @return object Modified transient data.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_github_release();
		if ( ! $release ) {
			return $transient;
		}

		$plugin_data     = $this->get_plugin_data();
		$current_version = $plugin_data['Version'];

		// Get version from tag (remove 'v' prefix if present).
		$github_version = ltrim( $release->tag_name, 'v' );

		// Compare versions.
		if ( version_compare( $github_version, $current_version, '>' ) ) {
			// Find the zip asset.
			$download_url = $this->get_download_url( $release );

			if ( $download_url ) {
				$transient->response[ $this->slug ] = (object) array(
					'slug'        => dirname( $this->slug ),
					'plugin'      => $this->slug,
					'new_version' => $github_version,
					'url'         => $plugin_data['PluginURI'],
					'package'     => $download_url,
					'icons'       => array(),
					'banners'     => array(),
					'tested'      => '',
					'requires'    => $plugin_data['RequiresWP'] ?? '5.8',
					'requires_php'=> $plugin_data['RequiresPHP'] ?? '7.4',
				);
			}
		}

		return $transient;
	}

	/**
	 * Get download URL from release.
	 *
	 * Looks for a .zip asset attachment, falls back to zipball_url.
	 *
	 * @since 1.0.0
	 *
	 * @param object $release GitHub release data.
	 * @return string|false Download URL or false if not found.
	 */
	private function get_download_url( $release ) {
		// First, look for an uploaded .zip asset (preferred).
		if ( ! empty( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( isset( $asset->content_type ) && $asset->content_type === 'application/zip' ) {
					$url = $asset->browser_download_url;
					// Add access token for private repos.
					if ( ! empty( $this->access_token ) ) {
						$url = add_query_arg( 'access_token', $this->access_token, $url );
					}
					return $url;
				}
			}
		}

		// Fallback to GitHub's auto-generated source zipball.
		// Note: This requires the zip to have the right folder structure.
		if ( ! empty( $release->zipball_url ) ) {
			$url = $release->zipball_url;
			if ( ! empty( $this->access_token ) ) {
				$url = add_query_arg( 'access_token', $this->access_token, $url );
			}
			return $url;
		}

		return false;
	}

	/**
	 * Provide plugin info for the WordPress plugins API.
	 *
	 * @since 1.0.0
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The API action being performed.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object Plugin info or false.
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		if ( dirname( $this->slug ) !== $args->slug ) {
			return $result;
		}

		$release = $this->get_github_release();
		if ( ! $release ) {
			return $result;
		}

		$plugin_data = $this->get_plugin_data();

		return (object) array(
			'name'              => $plugin_data['Name'],
			'slug'              => dirname( $this->slug ),
			'version'           => ltrim( $release->tag_name, 'v' ),
			'author'            => $plugin_data['AuthorName'],
			'author_profile'    => $plugin_data['AuthorURI'],
			'homepage'          => $plugin_data['PluginURI'],
			'requires'          => $plugin_data['RequiresWP'] ?? '5.8',
			'tested'            => '',
			'requires_php'      => $plugin_data['RequiresPHP'] ?? '7.4',
			'downloaded'        => 0,
			'last_updated'      => $release->published_at ?? '',
			'sections'          => array(
				'description'  => $plugin_data['Description'],
				'changelog'    => $this->format_changelog( $release->body ?? '' ),
			),
			'download_link'     => $this->get_download_url( $release ),
		);
	}

	/**
	 * Format changelog from markdown.
	 *
	 * @since 1.0.0
	 *
	 * @param string $markdown Release body markdown.
	 * @return string Formatted changelog HTML.
	 */
	private function format_changelog( $markdown ) {
		if ( empty( $markdown ) ) {
			return '<p>' . esc_html__( 'See release notes on GitHub.', 'easy-album-orders' ) . '</p>';
		}

		// Basic markdown to HTML conversion.
		$html = $markdown;

		// Convert headers.
		$html = preg_replace( '/^### (.+)$/m', '<h4>$1</h4>', $html );
		$html = preg_replace( '/^## (.+)$/m', '<h3>$1</h3>', $html );

		// Convert lists.
		$html = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $html );
		$html = preg_replace( '/(<li>.+<\/li>\n?)+/', '<ul>$0</ul>', $html );

		// Convert line breaks.
		$html = nl2br( $html );

		return wp_kses_post( $html );
	}

	/**
	 * Rename the plugin folder after installation.
	 *
	 * GitHub's zipball creates folders like 'username-repo-hash'.
	 * This renames it to the correct plugin folder name.
	 *
	 * @since 1.0.0
	 *
	 * @param bool  $response   Installation response.
	 * @param array $hook_extra Extra arguments passed to the upgrader.
	 * @param array $result     Installation result.
	 * @return array Modified result.
	 */
	public function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		// Only process our plugin.
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->slug ) {
			return $result;
		}

		$plugin_folder = WP_PLUGIN_DIR . '/' . dirname( $this->slug );
		$wp_filesystem->move( $result['destination'], $plugin_folder );
		$result['destination'] = $plugin_folder;

		// Clear update cache.
		$this->clear_update_cache();

		// Re-activate the plugin if it was active.
		if ( is_plugin_active( $this->slug ) ) {
			activate_plugin( $this->slug );
		}

		return $result;
	}

	/**
	 * Clear update cache.
	 *
	 * @since 1.0.0
	 */
	public function clear_update_cache() {
		delete_transient( 'eao_github_release' );
		$this->github_response = null;
	}

	/**
	 * Force check for updates.
	 *
	 * @since 1.0.0
	 */
	public function force_update_check() {
		$this->clear_update_cache();
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();
	}
}
