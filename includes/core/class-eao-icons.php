<?php
/**
 * Icon helper for Tabler Icons.
 *
 * Provides a clean API for rendering Tabler Icons Pro SVGs
 * throughout the plugin. Supports both outline and filled variants.
 *
 * @package Easy_Album_Orders
 * @since   1.0.0
 */

// If not WordPress, exit.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Icons helper class.
 *
 * @since 1.0.0
 */
class EAO_Icons {

    /**
     * Base path to icon files.
     *
     * @since  1.0.0
     * @access private
     * @var    string
     */
    private static $icons_path = '';

    /**
     * In-memory cache of loaded icons for the current request.
     *
     * @since  1.0.0
     * @access private
     * @var    array
     */
    private static $cache = array();

    /**
     * Get the base path to icon files.
     *
     * @since  1.0.0
     * @access private
     *
     * @return string Path to icons directory.
     */
    private static function get_icons_path() {
        if ( empty( self::$icons_path ) ) {
            self::$icons_path = EAO_PLUGIN_DIR . 'assets/icons/tabler/';
        }
        return self::$icons_path;
    }

    /**
     * Get an icon SVG.
     *
     * @since 1.0.0
     *
     * @param string $name Icon name (e.g., 'shopping-cart', 'plus', 'trash').
     * @param array  $args {
     *     Optional. Icon display arguments.
     *
     *     @type string $variant Icon variant: 'outline' or 'filled'. Default 'outline'.
     *     @type int    $size    Icon size in pixels. Default 24.
     *     @type string $class   Additional CSS classes.
     *     @type string $title   Accessible title for screen readers.
     *     @type float  $stroke  Stroke width for outline icons. Default 2.
     * }
     * @return string SVG markup or empty string if icon not found.
     */
    public static function get( $name, $args = array() ) {
        $defaults = array(
            'variant' => 'outline',
            'size'    => 24,
            'class'   => '',
            'title'   => '',
            'stroke'  => 2,
        );

        $args = wp_parse_args( $args, $defaults );

        // Sanitize the icon name.
        $name = sanitize_file_name( $name );

        // Build cache key.
        $cache_key = $args['variant'] . '/' . $name;

        // Check memory cache first.
        if ( ! isset( self::$cache[ $cache_key ] ) ) {
            self::$cache[ $cache_key ] = self::load_icon_file( $name, $args['variant'] );
        }

        $svg_content = self::$cache[ $cache_key ];

        if ( empty( $svg_content ) ) {
            return '';
        }

        return self::build_svg( $svg_content, $name, $args );
    }

    /**
     * Echo an icon SVG.
     *
     * @since 1.0.0
     *
     * @param string $name Icon name.
     * @param array  $args Optional. Icon display arguments.
     */
    public static function render( $name, $args = array() ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup is built safely.
        echo self::get( $name, $args );
    }

    /**
     * Load icon file contents.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $name    Icon name.
     * @param string $variant Icon variant (outline or filled).
     * @return string|false SVG inner content or false if not found.
     */
    private static function load_icon_file( $name, $variant ) {
        $file_path = self::get_icons_path() . $variant . '/' . $name . '.svg';

        if ( ! file_exists( $file_path ) ) {
            return false;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read.
        $svg = file_get_contents( $file_path );

        if ( empty( $svg ) ) {
            return false;
        }

        // Extract the inner content (paths) from the SVG.
        if ( preg_match( '/<svg[^>]*>(.*)<\/svg>/s', $svg, $matches ) ) {
            return trim( $matches[1] );
        }

        return false;
    }

    /**
     * Build the final SVG markup with custom attributes.
     *
     * @since  1.0.0
     * @access private
     *
     * @param string $content Inner SVG content (paths).
     * @param string $name    Icon name.
     * @param array  $args    Display arguments.
     * @return string Complete SVG markup.
     */
    private static function build_svg( $content, $name, $args ) {
        // Build CSS classes.
        $classes = 'eao-icon eao-icon--' . esc_attr( $name );
        if ( 'filled' === $args['variant'] ) {
            $classes .= ' eao-icon--filled';
        }
        if ( ! empty( $args['class'] ) ) {
            $classes .= ' ' . esc_attr( $args['class'] );
        }

        // Build accessibility attributes.
        $title_markup = '';
        $aria         = 'aria-hidden="true"';

        if ( ! empty( $args['title'] ) ) {
            $title_id     = 'eao-icon-title-' . wp_unique_id();
            $title_markup = '<title id="' . esc_attr( $title_id ) . '">' . esc_html( $args['title'] ) . '</title>';
            $aria         = 'aria-labelledby="' . esc_attr( $title_id ) . '" role="img"';
        }

        // Determine fill and stroke based on variant.
        if ( 'filled' === $args['variant'] ) {
            $fill   = 'currentColor';
            $stroke = 'none';
            $stroke_attrs = '';
        } else {
            $fill   = 'none';
            $stroke = 'currentColor';
            $stroke_attrs = sprintf(
                'stroke-width="%s" stroke-linecap="round" stroke-linejoin="round"',
                esc_attr( $args['stroke'] )
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="%2$s" stroke="%3$s" %4$s class="%5$s" %6$s>%7$s%8$s</svg>',
            absint( $args['size'] ),
            esc_attr( $fill ),
            esc_attr( $stroke ),
            $stroke_attrs,
            esc_attr( $classes ),
            $aria,
            $title_markup,
            $content
        );
    }

    /**
     * Check if an icon exists.
     *
     * @since 1.0.0
     *
     * @param string $name    Icon name.
     * @param string $variant Icon variant (outline or filled). Default 'outline'.
     * @return bool True if icon exists, false otherwise.
     */
    public static function exists( $name, $variant = 'outline' ) {
        $name      = sanitize_file_name( $name );
        $file_path = self::get_icons_path() . $variant . '/' . $name . '.svg';
        return file_exists( $file_path );
    }

    /**
     * Get all available icon names for a variant.
     *
     * Useful for admin interfaces that need to display icon pickers.
     *
     * @since 1.0.0
     *
     * @param string $variant Icon variant (outline or filled). Default 'outline'.
     * @return array Array of icon names (without .svg extension).
     */
    public static function get_available_icons( $variant = 'outline' ) {
        $icons_dir = self::get_icons_path() . $variant . '/';

        if ( ! is_dir( $icons_dir ) ) {
            return array();
        }

        $files = glob( $icons_dir . '*.svg' );

        if ( empty( $files ) ) {
            return array();
        }

        $icons = array();
        foreach ( $files as $file ) {
            $icons[] = basename( $file, '.svg' );
        }

        sort( $icons );

        return $icons;
    }

    /**
     * Get commonly used icons for this plugin.
     *
     * Returns a curated list of icons frequently used in album/e-commerce contexts.
     *
     * @since 1.0.0
     *
     * @return array Associative array of icon categories and their icons.
     */
    public static function get_common_icons() {
        return array(
            'navigation'  => array(
                'menu',
                'x',
                'chevron-down',
                'chevron-up',
                'chevron-left',
                'chevron-right',
                'arrow-left',
                'arrow-right',
            ),
            'actions'     => array(
                'plus',
                'minus',
                'trash',
                'edit',
                'check',
                'x',
                'upload',
                'download',
                'copy',
                'external-link',
            ),
            'ecommerce'   => array(
                'shopping-cart',
                'credit-card',
                'package',
                'truck',
                'receipt',
                'discount',
                'currency-dollar',
                'tag',
            ),
            'albums'      => array(
                'book',
                'book-2',
                'album',
                'photo',
                'photo-plus',
                'camera',
                'palette',
                'color-swatch',
                'ruler',
                'dimensions',
            ),
            'status'      => array(
                'info-circle',
                'alert-circle',
                'alert-triangle',
                'circle-check',
                'circle-x',
                'clock',
                'hourglass',
            ),
            'communication' => array(
                'mail',
                'send',
                'message',
                'phone',
                'at',
            ),
            'users'       => array(
                'user',
                'users',
                'user-plus',
                'user-check',
            ),
            'interface'   => array(
                'settings',
                'adjustments',
                'filter',
                'search',
                'eye',
                'eye-off',
                'grip-vertical',
                'dots-vertical',
                'maximize',
                'minimize',
            ),
            'files'       => array(
                'file',
                'file-text',
                'file-download',
                'pdf',
                'folder',
            ),
        );
    }
}

