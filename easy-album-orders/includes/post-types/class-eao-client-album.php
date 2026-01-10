<?php
/**
 * Client Album custom post type.
 *
 * Registers and manages the Client Album post type which represents
 * individual order forms for specific clients.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Client Album post type class.
 *
 * @since 1.0.0
 */
class EAO_Client_Album {

    /**
     * Post type slug.
     *
     * @since 1.0.0
     * @var   string
     */
    const POST_TYPE = 'client_album';

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Constructor can be used to set up additional hooks if needed.
    }

    /**
     * Register the Client Album custom post type.
     *
     * @since 1.0.0
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Client Albums', 'Post type general name', 'easy-album-orders' ),
            'singular_name'         => _x( 'Client Album', 'Post type singular name', 'easy-album-orders' ),
            'menu_name'             => _x( 'Client Albums', 'Admin Menu text', 'easy-album-orders' ),
            'name_admin_bar'        => _x( 'Client Album', 'Add New on Toolbar', 'easy-album-orders' ),
            'add_new'               => __( 'Add New', 'easy-album-orders' ),
            'add_new_item'          => __( 'Add New Client Album', 'easy-album-orders' ),
            'new_item'              => __( 'New Client Album', 'easy-album-orders' ),
            'edit_item'             => __( 'Edit Client Album', 'easy-album-orders' ),
            'view_item'             => __( 'View Client Album', 'easy-album-orders' ),
            'all_items'             => __( 'All Client Albums', 'easy-album-orders' ),
            'search_items'          => __( 'Search Client Albums', 'easy-album-orders' ),
            'parent_item_colon'     => __( 'Parent Client Albums:', 'easy-album-orders' ),
            'not_found'             => __( 'No client albums found.', 'easy-album-orders' ),
            'not_found_in_trash'    => __( 'No client albums found in Trash.', 'easy-album-orders' ),
            'featured_image'        => _x( 'Client Album Cover Image', 'Overrides the "Featured Image" phrase', 'easy-album-orders' ),
            'set_featured_image'    => _x( 'Set cover image', 'Overrides the "Set featured image" phrase', 'easy-album-orders' ),
            'remove_featured_image' => _x( 'Remove cover image', 'Overrides the "Remove featured image" phrase', 'easy-album-orders' ),
            'use_featured_image'    => _x( 'Use as cover image', 'Overrides the "Use as featured image" phrase', 'easy-album-orders' ),
            'archives'              => _x( 'Client Album archives', 'The post type archive label', 'easy-album-orders' ),
            'insert_into_item'      => _x( 'Insert into client album', 'Overrides the "Insert into post" phrase', 'easy-album-orders' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this client album', 'Overrides the "Uploaded to this post" phrase', 'easy-album-orders' ),
            'filter_items_list'     => _x( 'Filter client albums list', 'Screen reader text', 'easy-album-orders' ),
            'items_list_navigation' => _x( 'Client albums list navigation', 'Screen reader text', 'easy-album-orders' ),
            'items_list'            => _x( 'Client albums list', 'Screen reader text', 'easy-album-orders' ),
        );

        $args = array(
            'labels'              => $labels,
            'description'         => __( 'Individual album order forms for clients.', 'easy-album-orders' ),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false, // We'll add it manually to our custom menu.
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => 'album',
                'with_front' => false,
            ),
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-format-gallery',
            'supports'            => array( 'title' ),
            'show_in_rest'        => true,
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Get all client albums.
     *
     * @since 1.0.0
     *
     * @param array $args Optional. Additional query arguments.
     * @return WP_Post[] Array of client album posts.
     */
    public static function get_all( $args = array() ) {
        $defaults = array(
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        $query_args = wp_parse_args( $args, $defaults );

        return get_posts( $query_args );
    }

    /**
     * Get a client album by ID.
     *
     * @since 1.0.0
     *
     * @param int $album_id The album post ID.
     * @return WP_Post|null The client album post or null if not found.
     */
    public static function get( $album_id ) {
        $post = get_post( $album_id );

        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return null;
        }

        return $post;
    }

    /**
     * Get client album meta data.
     *
     * @since 1.0.0
     *
     * @param int    $album_id The album post ID.
     * @param string $key      Optional. Specific meta key to retrieve.
     * @return mixed The meta value(s).
     */
    public static function get_meta( $album_id, $key = '' ) {
        if ( empty( $key ) ) {
            return get_post_meta( $album_id );
        }

        return get_post_meta( $album_id, '_eao_' . $key, true );
    }

    /**
     * Update client album meta data.
     *
     * @since 1.0.0
     *
     * @param int    $album_id The album post ID.
     * @param string $key      The meta key (without prefix).
     * @param mixed  $value    The meta value.
     * @return int|bool Meta ID on success, false on failure.
     */
    public static function update_meta( $album_id, $key, $value ) {
        return update_post_meta( $album_id, '_eao_' . $key, $value );
    }

    /**
     * Get the front-end URL for a client album.
     *
     * @since 1.0.0
     *
     * @param int $album_id The album post ID.
     * @return string The permalink URL.
     */
    public static function get_url( $album_id ) {
        return get_permalink( $album_id );
    }

    /**
     * Get album designs for a client album.
     *
     * @since 1.0.0
     *
     * @param int $album_id The album post ID.
     * @return array Array of album designs.
     */
    public static function get_designs( $album_id ) {
        $designs = self::get_meta( $album_id, 'designs' );
        return is_array( $designs ) ? $designs : array();
    }

    /**
     * Get client information for a client album.
     *
     * @since 1.0.0
     *
     * @param int $album_id The album post ID.
     * @return array Client information array.
     */
    public static function get_client_info( $album_id ) {
        return array(
            'name'      => self::get_meta( $album_id, 'client_name' ),
            'email'     => self::get_meta( $album_id, 'client_email' ),
            'loom_url'  => self::get_meta( $album_id, 'loom_url' ),
        );
    }
}

