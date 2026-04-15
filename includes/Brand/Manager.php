<?php

namespace FooPlugins\FooConvert\Brand;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Manages extracted brand data for the AI popup builder.
 */
class Manager {

    /**
     * Registers hooks.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_option' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    /**
     * Registers the saved brand option.
     *
     * @return void
     */
    public function register_option(): void {
        register_setting(
            'fooconvert',
            FOOCONVERT_OPTION_AI_BRAND,
            array(
                'type'              => 'object',
                'sanitize_callback' => array( self::class, 'sanitize_brand' ),
                'show_in_rest'      => array(
                    'schema' => self::get_brand_schema(),
                ),
                'default'           => array(),
            )
        );
    }

    /**
     * Registers REST routes for brand load/save/extract flows.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route(
            'fooconvert/v1',
            '/ai-popup-builder/brand',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'handle_get_brand' ),
                    'permission_callback' => array( $this, 'can_manage_popups' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'handle_save_brand' ),
                    'permission_callback' => array( $this, 'can_manage_popups' ),
                    'args'                => array(
                        'brand' => array(
                            'type'     => 'object',
                            'required' => true,
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            'fooconvert/v1',
            '/ai-popup-builder/brand/extract',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_extract_brand' ),
                'permission_callback' => array( $this, 'can_manage_popups' ),
                'args'                => array(
                    'mode' => array(
                        'type'    => 'string',
                        'default' => 'local',
                    ),
                    'url'  => array(
                        'type' => 'string',
                    ),
                ),
            )
        );
    }

    /**
     * Returns the saved brand payload.
     *
     * @return WP_REST_Response
     */
    public function handle_get_brand(): WP_REST_Response {
        return new WP_REST_Response(
            array(
                'brand'        => self::get_saved_brand(),
                'hasSavedBrand' => self::has_saved_brand(),
            )
        );
    }

    /**
     * Saves the supplied brand payload.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response
     */
    public function handle_save_brand( WP_REST_Request $request ): WP_REST_Response {
        $brand = self::sanitize_brand( $request->get_param( 'brand' ) );
        update_option( FOOCONVERT_OPTION_AI_BRAND, $brand, false );

        return new WP_REST_Response(
            array(
                'brand'         => $brand,
                'hasSavedBrand' => self::has_meaningful_brand_data( $brand ),
                'savedAt'       => gmdate( 'c' ),
            )
        );
    }

    /**
     * Extracts a brand profile from the local site or a remote URL.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_Error|WP_REST_Response
     */
    public function handle_extract_brand( WP_REST_Request $request ) {
        $mode = sanitize_text_field( (string) $request->get_param( 'mode' ) );
        $mode = in_array( $mode, array( 'local', 'remote' ), true ) ? $mode : 'local';

        if ( 'remote' === $mode ) {
            $url = esc_url_raw( (string) $request->get_param( 'url' ) );
            if ( '' === $url ) {
                return new WP_Error( 'fooconvert_brand_missing_url', __( 'A remote URL is required for remote brand extraction.', 'fooconvert' ), array( 'status' => 400 ) );
            }

            $extractor = new RemoteExtractor();
            $result    = $extractor->extract( $url );
        } else {
            $extractor = new LocalExtractor();
            $result    = $extractor->extract();
        }

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $brand = self::sanitize_brand( self::enrich_brand( is_array( $result ) ? $result : array() ) );

        return new WP_REST_Response(
            array(
                'brand'         => $brand,
                'hasSavedBrand' => self::has_saved_brand(),
            )
        );
    }

    /**
     * Returns the saved brand, enriched with current site defaults.
     *
     * @return array<string,mixed>
     */
    public static function get_saved_brand(): array {
        $brand = get_option( FOOCONVERT_OPTION_AI_BRAND, null );

        if ( ! is_array( $brand ) || empty( $brand ) ) {
            return array();
        }

        $brand = self::sanitize_brand( $brand );

        if ( ! self::has_meaningful_brand_data( $brand ) ) {
            return array();
        }

        return self::enrich_brand( $brand );
    }

    /**
     * Returns a default brand payload for the current site.
     *
     * @return array<string,mixed>
     */
    public static function get_default_brand(): array {
        return self::enrich_brand( array() );
    }

    /**
     * Determines whether a brand has been saved already.
     *
     * @return bool
     */
    public static function has_saved_brand(): bool {
        $brand = get_option( FOOCONVERT_OPTION_AI_BRAND, null );

        if ( ! is_array( $brand ) || empty( $brand ) ) {
            return false;
        }

        return self::has_meaningful_brand_data( self::sanitize_brand( $brand ) );
    }

    /**
     * Normalizes and enriches a brand payload with site-level metadata.
     *
     * @param array<string,mixed> $brand Raw brand.
     * @return array<string,mixed>
     */
    public static function enrich_brand( array $brand ): array {
        $has_brand_overview = array_key_exists( 'brandOverview', $brand ) || array_key_exists( 'siteDescription', $brand );
        $brand = self::sanitize_brand( $brand );

        if ( ! $has_brand_overview && '' === trim( (string) ( $brand['brandOverview'] ?? '' ) ) ) {
            $brand['brandOverview'] = sanitize_textarea_field( get_bloginfo( 'description' ) );
        }

        return $brand;
    }

    /**
     * Sanitizes a brand payload for storage and prompt context.
     *
     * @param mixed $brand Brand payload.
     * @return array<string,mixed>
     */
    public static function sanitize_brand( $brand ): array {
        if ( ! is_array( $brand ) ) {
            return array();
        }

        $colors = is_array( $brand['colors'] ?? null ) ? $brand['colors'] : array();
        $typography = is_array( $brand['typography'] ?? null ) ? $brand['typography'] : array();
        $spacing = is_array( $brand['spacing'] ?? null ) ? $brand['spacing'] : array();
        $components = is_array( $brand['components'] ?? null ) ? $brand['components'] : array();

        $brand_overview = '';
        if ( isset( $brand['brandOverview'] ) ) {
            $brand_overview = sanitize_textarea_field( (string) $brand['brandOverview'] );
        } elseif ( isset( $brand['siteDescription'] ) ) {
            $brand_overview = sanitize_textarea_field( (string) $brand['siteDescription'] );
        }

        return array(
            'brandOverview' => $brand_overview,
            'colorScheme'   => in_array( $brand['colorScheme'] ?? '', array( 'dark', 'light' ), true ) ? $brand['colorScheme'] : 'light',
            'colors'        => array(
                'primary'       => self::sanitize_color( $colors['primary'] ?? '' ),
                'secondary'     => self::sanitize_color( $colors['secondary'] ?? '' ),
                'accent'        => self::sanitize_color( $colors['accent'] ?? '' ),
                'background'    => self::sanitize_color( $colors['background'] ?? '' ),
                'textPrimary'   => self::sanitize_color( $colors['textPrimary'] ?? '' ),
                'textSecondary' => self::sanitize_color( $colors['textSecondary'] ?? '' ),
            ),
            'typography'    => array(
                'fontFamilies' => array(
                    'primary' => sanitize_text_field( (string) ( $typography['fontFamilies']['primary'] ?? '' ) ),
                    'heading' => sanitize_text_field( (string) ( $typography['fontFamilies']['heading'] ?? '' ) ),
                ),
                'fontSizes'   => array(
                    'h1'   => self::sanitize_font_size( $typography['fontSizes']['h1'] ?? array() ),
                    'h2'   => self::sanitize_font_size( $typography['fontSizes']['h2'] ?? array() ),
                    'h3'   => self::sanitize_font_size( $typography['fontSizes']['h3'] ?? array() ),
                    'body' => self::sanitize_font_size( $typography['fontSizes']['body'] ?? array() ),
                ),
                'fontWeights' => array(
                    'regular' => absint( $typography['fontWeights']['regular'] ?? 400 ),
                    'medium'  => absint( $typography['fontWeights']['medium'] ?? 500 ),
                    'bold'    => absint( $typography['fontWeights']['bold'] ?? 700 ),
                ),
            ),
            'spacing'       => array(
                'baseUnit'     => absint( $spacing['baseUnit'] ?? 0 ),
                'borderRadius' => sanitize_text_field( (string) ( $spacing['borderRadius'] ?? '' ) ),
            ),
            'components'    => array(
                'buttonPrimary' => array(
                    'background'   => self::sanitize_color( $components['buttonPrimary']['background'] ?? '' ),
                    'textColor'    => self::sanitize_color( $components['buttonPrimary']['textColor'] ?? '' ),
                    'borderRadius' => sanitize_text_field( (string) ( $components['buttonPrimary']['borderRadius'] ?? '' ) ),
                ),
                'buttonSecondary' => array(
                    'background'   => sanitize_text_field( (string) ( $components['buttonSecondary']['background'] ?? '' ) ),
                    'textColor'    => self::sanitize_color( $components['buttonSecondary']['textColor'] ?? '' ),
                    'borderColor'  => self::sanitize_color( $components['buttonSecondary']['borderColor'] ?? '' ),
                    'borderRadius' => sanitize_text_field( (string) ( $components['buttonSecondary']['borderRadius'] ?? '' ) ),
                ),
            ),
        );
    }

    /**
     * Returns the REST schema for the saved brand option.
     *
     * @return array<string,mixed>
     */
    public static function get_brand_schema(): array {
        return array(
            'type'       => 'object',
            'properties' => array(
                'brandOverview' => array( 'type' => 'string' ),
                'colorScheme'   => array( 'type' => 'string' ),
                'colors'        => array( 'type' => 'object' ),
                'typography'    => array( 'type' => 'object' ),
                'spacing'       => array( 'type' => 'object' ),
                'components'    => array( 'type' => 'object' ),
            ),
        );
    }

    /**
     * Determines whether the brand contains meaningful saved data.
     *
     * @param array<string,mixed> $brand Sanitized brand payload.
     * @return bool
     */
    private static function has_meaningful_brand_data( array $brand ): bool {
        if ( '' !== trim( (string) ( $brand['brandOverview'] ?? '' ) ) ) {
            return true;
        }

        foreach ( array( 'primary', 'secondary', 'accent', 'background', 'textPrimary', 'textSecondary' ) as $color_key ) {
            if ( '' !== trim( (string) ( $brand['colors'][ $color_key ] ?? '' ) ) ) {
                return true;
            }
        }

        foreach ( array( 'primary', 'heading' ) as $font_key ) {
            if ( '' !== trim( (string) ( $brand['typography']['fontFamilies'][ $font_key ] ?? '' ) ) ) {
                return true;
            }
        }

        foreach ( array( 'h1', 'h2', 'h3', 'body' ) as $size_key ) {
            if ( '' !== trim( (string) ( $brand['typography']['fontSizes'][ $size_key ]['value'] ?? '' ) ) ) {
                return true;
            }
        }

        if ( absint( $brand['spacing']['baseUnit'] ?? 0 ) > 0 || '' !== trim( (string) ( $brand['spacing']['borderRadius'] ?? '' ) ) ) {
            return true;
        }

        foreach ( array( 'buttonPrimary', 'buttonSecondary' ) as $button_key ) {
            foreach ( array( 'background', 'textColor', 'borderColor', 'borderRadius' ) as $setting_key ) {
                if ( '' !== trim( (string) ( $brand['components'][ $button_key ][ $setting_key ] ?? '' ) ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks whether the current user can manage popup drafts.
     *
     * @return bool
     */
    public function can_manage_popups(): bool {
        $post_type_object = get_post_type_object( FOOCONVERT_CPT_POPUP );
        $capability       = $post_type_object && isset( $post_type_object->cap->create_posts )
            ? $post_type_object->cap->create_posts
            : 'manage_options';

        return current_user_can( $capability );
    }

    /**
     * Sanitizes a hex color or allows transparent.
     *
     * @param mixed $value Raw color.
     * @return string
     */
    private static function sanitize_color( $value ): string {
        $value = sanitize_text_field( (string) $value );

        if ( 'transparent' === strtolower( $value ) ) {
            return 'transparent';
        }

        $color = sanitize_hex_color( $value );
        return is_string( $color ) ? $color : '';
    }

    /**
     * Sanitizes a font size payload.
     *
     * @param mixed $value Raw font size.
     * @return array<string,string>
     */
    private static function sanitize_font_size( $value ): array {
        $value = is_array( $value ) ? $value : array();

        return array(
            'value' => sanitize_text_field( (string) ( $value['value'] ?? '' ) ),
            'min'   => sanitize_text_field( (string) ( $value['min'] ?? '' ) ),
            'max'   => sanitize_text_field( (string) ( $value['max'] ?? '' ) ),
        );
    }
}
