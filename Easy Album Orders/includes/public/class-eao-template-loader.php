<?php
/**
 * Template loader for front-end views.
 *
 * Handles loading custom templates for the Client Album post type.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Template loader class.
 *
 * @since 1.0.0
 */
class EAO_Template_Loader {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter( 'single_template', array( $this, 'load_client_album_template' ) );
    }

    /**
     * Load custom template for single Client Album.
     *
     * @since 1.0.0
     *
     * @param string $template The current template path.
     * @return string Modified template path.
     */
    public function load_client_album_template( $template ) {
        global $post;

        if ( 'client_album' === $post->post_type ) {
            // Check for order history view parameter.
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view parameter.
            $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';

            if ( 'order-history' === $view ) {
                $plugin_template = EAO_PLUGIN_DIR . 'includes/public/templates/single-client-album-order-history.php';
            } else {
                $plugin_template = EAO_PLUGIN_DIR . 'includes/public/templates/single-client-album.php';
            }

            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Get a template part.
     *
     * @since 1.0.0
     *
     * @param string $slug The template slug.
     * @param string $name Optional. The template name.
     * @param array  $args Optional. Arguments to pass to the template.
     */
    public static function get_template_part( $slug, $name = '', $args = array() ) {
        $template = '';

        // Look for slug-name.php.
        if ( $name ) {
            $template = EAO_PLUGIN_DIR . "includes/public/templates/{$slug}-{$name}.php";
        }

        // Fall back to slug.php.
        if ( ! $template || ! file_exists( $template ) ) {
            $template = EAO_PLUGIN_DIR . "includes/public/templates/{$slug}.php";
        }

        // Allow theme overrides.
        $theme_template = locate_template( array(
            "easy-album-orders/{$slug}-{$name}.php",
            "easy-album-orders/{$slug}.php",
        ) );

        if ( $theme_template ) {
            $template = $theme_template;
        }

        if ( $template && file_exists( $template ) ) {
            // Extract args for use in template.
            if ( ! empty( $args ) && is_array( $args ) ) {
                extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            }

            include $template;
        }
    }

    /**
     * Get Loom embed HTML from URL.
     *
     * @since 1.0.0
     *
     * @param string $url The Loom URL.
     * @return string The embed HTML.
     */
    public static function get_loom_embed( $url ) {
        if ( empty( $url ) ) {
            return '';
        }

        // Extract Loom video ID.
        $video_id = '';

        if ( preg_match( '/loom\.com\/share\/([a-zA-Z0-9]+)/', $url, $matches ) ) {
            $video_id = $matches[1];
        } elseif ( preg_match( '/loom\.com\/embed\/([a-zA-Z0-9]+)/', $url, $matches ) ) {
            $video_id = $matches[1];
        }

        if ( empty( $video_id ) ) {
            return '';
        }

        $embed_url = 'https://www.loom.com/embed/' . $video_id;

        return sprintf(
            '<div class="eao-video-wrapper"><iframe src="%s" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div>',
            esc_url( $embed_url )
        );
    }
}

