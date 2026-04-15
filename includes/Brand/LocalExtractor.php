<?php

namespace FooPlugins\FooConvert\Brand;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts branding from the current WordPress site using local theme data.
 */
class LocalExtractor {

    use ExtractorHelpers;

    /**
     * Extracts branding from the current site, falling back to a remote scrape.
     *
     * @return array<string,mixed>|\WP_Error
     */
    public function extract() {
        $settings = function_exists( 'wp_get_global_settings' ) ? wp_get_global_settings() : array();
        $styles   = function_exists( 'wp_get_global_styles' ) ? wp_get_global_styles() : array();

        $has_colors = ! empty( $settings['color']['palette'] );
        $has_fonts  = ! empty( $settings['typography']['fontFamilies'] );

        if ( ! $has_colors && ! $has_fonts ) {
            $remote = new RemoteExtractor();
            return $remote->extract( get_site_url() );
        }

        $logo     = $this->local_logo();
        $favicon  = $this->local_favicon();
        $og_image = $this->local_og_image();
        $colors   = $this->local_colors( $settings, $styles );
        $fonts    = $this->local_fonts( $settings );
        $typo     = $this->local_typography( $settings, $fonts );
        $spacing  = $this->local_spacing( $settings, $styles );
        $comps    = $this->local_components( $styles, $colors, $spacing );

        return array(
            'colorScheme' => $this->determine_color_scheme( $colors ),
            'logo'        => $logo,
            'colors'      => $colors,
            'fonts'       => array_map(
                function ( string $font ): array {
                    return array( 'family' => $font );
                },
                array_values( array_unique( $fonts ) )
            ),
            'typography'  => $typo,
            'spacing'     => $spacing,
            'components'  => $comps,
            'images'      => array(
                'logo'    => $logo,
                'favicon' => $favicon,
                'ogImage' => $og_image,
            ),
        );
    }

    /**
     * Returns the current theme logo URL.
     *
     * @return string
     */
    private function local_logo(): string {
        if ( has_custom_logo() ) {
            $id  = get_theme_mod( 'custom_logo' );
            $src = wp_get_attachment_image_src( $id, 'full' );
            if ( ! empty( $src[0] ) ) {
                return (string) $src[0];
            }
        }

        return '';
    }

    /**
     * Returns the site icon URL.
     *
     * @return string
     */
    private function local_favicon(): string {
        $site_icon = get_site_icon_url( 512 );
        return is_string( $site_icon ) ? $site_icon : '';
    }

    /**
     * Returns the configured OG image where available.
     *
     * @return string
     */
    private function local_og_image(): string {
        $yoast = get_option( 'wpseo_social' );
        if ( ! empty( $yoast['og_default_image'] ) && is_string( $yoast['og_default_image'] ) ) {
            return $yoast['og_default_image'];
        }

        $rank_math = get_option( 'rank_math_social_settings' );
        if ( ! empty( $rank_math['social_image_id'] ) ) {
            $src = wp_get_attachment_image_src( intval( $rank_math['social_image_id'] ), 'full' );
            if ( ! empty( $src[0] ) ) {
                return (string) $src[0];
            }
        }

        $aioseo = get_option( 'aioseo_options' );
        if ( ! empty( $aioseo['social']['facebook']['general']['defaultImageUrl'] ) && is_string( $aioseo['social']['facebook']['general']['defaultImageUrl'] ) ) {
            return $aioseo['social']['facebook']['general']['defaultImageUrl'];
        }

        return '';
    }

    /**
     * Builds the semantic color palette from theme settings and styles.
     *
     * @param array<string,mixed> $settings Theme settings.
     * @param array<string,mixed> $styles Theme styles.
     * @return array<string,string>
     */
    private function local_colors( array $settings, array $styles ): array {
        $bg_raw   = $styles['color']['background'] ?? '#ffffff';
        $text_raw = $styles['color']['text'] ?? '#000000';

        $bg_hex   = is_string( $bg_raw ) && preg_match( '/^#[0-9a-f]{3,8}$/i', $bg_raw ) ? $this->normalize_hex( $bg_raw ) : '#FFFFFF';
        $text_hex = is_string( $text_raw ) && preg_match( '/^#[0-9a-f]{3,8}$/i', $text_raw ) ? $this->normalize_hex( $text_raw ) : '#000000';

        $colors = array(
            'primary'       => '',
            'secondary'     => '',
            'accent'        => '',
            'background'    => '' !== $bg_hex ? $bg_hex : '#FFFFFF',
            'textPrimary'   => '' !== $text_hex ? $text_hex : '#000000',
            'textSecondary' => '#666666',
        );

        $palette_raw   = $settings['color']['palette'] ?? array();
        $theme_palette = isset( $palette_raw['theme'] ) && is_array( $palette_raw['theme'] ) ? $palette_raw['theme'] : ( is_array( $palette_raw ) ? $palette_raw : array() );

        $slug_map = array(
            'primary'       => array( 'primary', 'brand', 'main', 'accent-1', 'brand-primary' ),
            'secondary'     => array( 'secondary', 'accent-2', 'brand-secondary' ),
            'accent'        => array( 'accent', 'highlight', 'tertiary', 'cta', 'accent-3' ),
            'textSecondary' => array( 'text-secondary', 'muted', 'subtle', 'foreground-muted' ),
        );

        foreach ( $theme_palette as $item ) {
            $slug  = isset( $item['slug'] ) ? (string) $item['slug'] : '';
            $color = isset( $item['color'] ) ? (string) $item['color'] : '';
            if ( '' === $color ) {
                continue;
            }

            foreach ( $slug_map as $key => $candidates ) {
                if ( '' === $colors[ $key ] && in_array( $slug, $candidates, true ) ) {
                    $colors[ $key ] = $color;
                }
            }
        }

        if ( '' === $colors['primary'] && ! empty( $theme_palette[0]['color'] ) ) {
            $colors['primary'] = (string) $theme_palette[0]['color'];
        }
        if ( '' === $colors['secondary'] && ! empty( $theme_palette[1]['color'] ) ) {
            $colors['secondary'] = (string) $theme_palette[1]['color'];
        }
        if ( '' === $colors['accent'] && ! empty( $theme_palette[2]['color'] ) ) {
            $colors['accent'] = (string) $theme_palette[2]['color'];
        }

        if ( '' === $colors['primary'] ) {
            $colors['primary'] = '#000000';
        }
        if ( '' === $colors['secondary'] ) {
            $colors['secondary'] = '#333333';
        }
        if ( '' === $colors['accent'] ) {
            $colors['accent'] = '#0066CC';
        }
        if ( $colors['textSecondary'] === $colors['textPrimary'] ) {
            $colors['textSecondary'] = '#666666';
        }

        return $colors;
    }

    /**
     * Returns a flat list of theme font family names.
     *
     * @param array<string,mixed> $settings Theme settings.
     * @return string[]
     */
    private function local_fonts( array $settings ): array {
        $families_raw = $settings['typography']['fontFamilies'] ?? array();
        $theme_fonts  = isset( $families_raw['theme'] ) && is_array( $families_raw['theme'] ) ? $families_raw['theme'] : ( is_array( $families_raw ) ? $families_raw : array() );

        if ( ! is_array( $theme_fonts ) ) {
            return array( 'sans-serif' );
        }

        $fonts = array();
        foreach ( $theme_fonts as $font ) {
            $name = $this->first_font_name( isset( $font['fontFamily'] ) ? (string) $font['fontFamily'] : '' );
            if ( '' !== $name ) {
                $fonts[] = $name;
            }
        }

        return ! empty( $fonts ) ? $fonts : array( 'sans-serif' );
    }

    /**
     * Extracts the first concrete font name from a CSS font-family string.
     *
     * @param string $raw CSS font-family string.
     * @return string
     */
    private function first_font_name( string $raw ): string {
        if ( '' === $raw ) {
            return '';
        }

        $parts = explode( ',', $raw );
        $first = trim( $parts[0], " \t\n\r\"'" );

        return '' !== $first && ! $this->is_generic_font( $first ) ? $first : '';
    }

    /**
     * Builds typography settings from theme.json.
     *
     * @param array<string,mixed> $settings Theme settings.
     * @param string[]            $fonts Theme fonts.
     * @return array<string,mixed>
     */
    private function local_typography( array $settings, array $fonts ): array {
        $primary = $fonts[0] ?? 'sans-serif';
        $heading = $primary;
        $code    = 'monospace';

        $families_raw = $settings['typography']['fontFamilies'] ?? array();
        $theme_fonts  = isset( $families_raw['theme'] ) && is_array( $families_raw['theme'] ) ? $families_raw['theme'] : ( is_array( $families_raw ) ? $families_raw : array() );

        if ( is_array( $theme_fonts ) ) {
            foreach ( $theme_fonts as $font ) {
                $slug = strtolower( isset( $font['slug'] ) ? (string) $font['slug'] : '' );
                $name = strtolower( isset( $font['name'] ) ? (string) $font['name'] : '' );
                $family = $this->first_font_name( isset( $font['fontFamily'] ) ? (string) $font['fontFamily'] : '' );

                if ( '' === $family ) {
                    continue;
                }

                if ( preg_match( '/mono|code|consol/i', $slug . $name . $family ) ) {
                    $code = $family;
                    continue;
                }

                if ( preg_match( '/heading|display|serif/i', $slug . $name ) ) {
                    $heading = $family;
                    continue;
                }

                if ( preg_match( '/body|text|primary|sans/i', $slug . $name ) ) {
                    $primary = $family;
                }
            }
        }

        $sizes_raw   = $settings['typography']['fontSizes'] ?? array();
        $theme_sizes = isset( $sizes_raw['theme'] ) && is_array( $sizes_raw['theme'] ) ? $sizes_raw['theme'] : ( is_array( $sizes_raw ) ? $sizes_raw : array() );
        $font_sizes  = $this->map_local_font_sizes( is_array( $theme_sizes ) ? $theme_sizes : array() );

        return array(
            'fontFamilies' => array(
                'primary' => $primary,
                'heading' => $heading,
                'code'    => $code,
            ),
            'fontSizes'   => $font_sizes,
            'fontWeights' => array(
                'regular' => 400,
                'medium'  => 500,
                'bold'    => 700,
            ),
        );
    }

    /**
     * Maps theme size presets to h1/h2/h3/body sizes.
     *
     * @param array<int,array<string,mixed>> $theme_sizes Theme font sizes.
     * @return array<string,array<string,string>>
     */
    private function map_local_font_sizes( array $theme_sizes ): array {
        $defaults = array(
            'h1'   => array( 'value' => '48px' ),
            'h2'   => array( 'value' => '36px' ),
            'h3'   => array( 'value' => '24px' ),
            'body' => array( 'value' => '16px' ),
        );

        if ( empty( $theme_sizes ) ) {
            return $defaults;
        }

        $slug_map = array(
            'h1'   => array( 'xx-large', 'huge', 'gigantic', '4xl', '3xl', 'xxxl', 'xxl', 'heading-1', 'h1' ),
            'h2'   => array( 'x-large', 'extra-large', '2xl', 'xxl', 'heading-2', 'h2' ),
            'h3'   => array( 'large', 'lg', 'heading-3', 'h3' ),
            'body' => array( 'medium', 'normal', 'base', 'regular', 'md', 'body', 'text', 'small', 'sm' ),
        );

        $result = array(
            'h1'   => null,
            'h2'   => null,
            'h3'   => null,
            'body' => null,
        );

        foreach ( $theme_sizes as $size ) {
            $slug  = strtolower( isset( $size['slug'] ) ? (string) $size['slug'] : '' );
            $value = isset( $size['size'] ) ? (string) $size['size'] : '';

            if ( '' === $value ) {
                continue;
            }

            foreach ( $slug_map as $key => $candidates ) {
                if ( null === $result[ $key ] && in_array( $slug, $candidates, true ) ) {
                    $result[ $key ] = $this->parse_font_size_value( $value );
                }
            }
        }

        $has_empty_slots = ! empty( array_filter( $result, 'is_null' ) );
        if ( $has_empty_slots ) {
            $sortable = array_filter(
                $theme_sizes,
                static function ( array $size ): bool {
                    return ! empty( $size['size'] );
                }
            );

            usort(
                $sortable,
                function ( array $a, array $b ): int {
                    return $this->css_size_to_px( isset( $b['size'] ) ? (string) $b['size'] : '' ) <=> $this->css_size_to_px( isset( $a['size'] ) ? (string) $a['size'] : '' );
                }
            );

            $i = 0;
            $total = count( $sortable );
            foreach ( array( 'h1', 'h2', 'h3', 'body' ) as $slot ) {
                if ( null !== $result[ $slot ] ) {
                    continue;
                }

                while ( $i < $total ) {
                    $value = isset( $sortable[ $i ]['size'] ) ? (string) $sortable[ $i ]['size'] : '';
                    $i++;

                    if ( '' !== $value ) {
                        $result[ $slot ] = $this->parse_font_size_value( $value );
                        break;
                    }
                }
            }
        }

        foreach ( $defaults as $key => $default ) {
            if ( null === $result[ $key ] ) {
                $result[ $key ] = $default;
            }
        }

        return $result;
    }

    /**
     * Converts a CSS size to approximate pixels for sorting.
     *
     * @param string $value CSS size value.
     * @return float
     */
    private function css_size_to_px( string $value ): float {
        $value = trim( $value );

        if ( preg_match( '/^clamp\s*\(/i', $value ) ) {
            $args = $this->split_clamp_args( $value );
            if ( 3 === count( $args ) ) {
                return $this->css_size_to_px( trim( $args[2] ) );
            }
        }

        if ( preg_match( '/^([\d.]+)\s*px/i', $value, $matches ) ) {
            return (float) $matches[1];
        }

        if ( preg_match( '/^([\d.]+)\s*r?em/i', $value, $matches ) ) {
            return (float) $matches[1] * 16;
        }

        return 0;
    }

    /**
     * Extracts spacing primitives from theme settings.
     *
     * @param array<string,mixed> $settings Theme settings.
     * @param array<string,mixed> $styles Theme styles.
     * @return array<string,mixed>
     */
    private function local_spacing( array $settings, array $styles ): array {
        $radius = '';
        $raw    = $styles['border']['radius'] ?? '';

        if ( is_string( $raw ) && '' !== $raw && ! preg_match( '/^var\s*\(/i', $raw ) ) {
            $radius = $raw;
        }

        if ( '' === $radius ) {
            $preset = $settings['border']['radius'] ?? '';
            if ( is_string( $preset ) && '' !== $preset ) {
                $radius = $preset;
            }
        }

        $radius = '' !== $radius ? $radius : '8px';

        $base  = 8;
        $scale = $settings['spacing']['spacingScale'] ?? array();
        if ( is_array( $scale ) && isset( $scale['increment'] ) ) {
            $candidate = intval( $scale['increment'] );
            if ( $candidate > 0 ) {
                $base = $candidate;
            }
        }

        return array(
            'baseUnit'     => $base,
            'borderRadius' => $radius,
        );
    }

    /**
     * Extracts primary and secondary button styles from theme styles.
     *
     * @param array<string,mixed> $styles Theme styles.
     * @param array<string,string> $colors Semantic colors.
     * @param array<string,mixed> $spacing Spacing values.
     * @return array<string,mixed>
     */
    private function local_components( array $styles, array $colors, array $spacing ): array {
        $button_styles = array();

        if ( function_exists( 'wp_get_global_styles' ) ) {
            $button_styles = wp_get_global_styles(
                array( 'color' ),
                array(
                    'block_name' => 'core/button',
                    'transforms' => array( 'resolve-variables' ),
                )
            );
        }

        $bg = ( isset( $button_styles['background'] ) && is_string( $button_styles['background'] ) && preg_match( '/^#[0-9a-f]{3,8}$/i', $button_styles['background'] ) )
            ? $button_styles['background']
            : $colors['primary'];
        $fg = ( isset( $button_styles['text'] ) && is_string( $button_styles['text'] ) && preg_match( '/^#[0-9a-f]{3,8}$/i', $button_styles['text'] ) )
            ? $button_styles['text']
            : '#FFFFFF';

        return array(
            'buttonPrimary' => array(
                'background'   => $bg,
                'textColor'    => $fg,
                'borderRadius' => isset( $spacing['borderRadius'] ) ? (string) $spacing['borderRadius'] : '8px',
            ),
            'buttonSecondary' => array(
                'background'   => 'transparent',
                'textColor'    => $colors['primary'],
                'borderColor'  => $colors['primary'],
                'borderRadius' => isset( $spacing['borderRadius'] ) ? (string) $spacing['borderRadius'] : '8px',
            ),
        );
    }
}
