<?php

namespace FooPlugins\FooConvert\Brand;

defined( 'ABSPATH' ) || exit;

/**
 * Shared extraction helpers used by the local and remote brand extractors.
 */
trait ExtractorHelpers {

    /**
     * CSS values that mean "no real size defined here".
     *
     * @var string[]
     */
    private static array $font_size_reset_keywords = array(
        'inherit',
        'initial',
        'unset',
        'revert',
        'revert-layer',
    );

    /**
     * Resolves a relative URL against a base URL.
     *
     * @param string $href Relative or absolute URL.
     * @param string $base_url Base URL.
     * @return string
     */
    private function resolve_url( string $href, string $base_url ): string {
        if ( '' === $href ) {
            return '';
        }

        if ( 0 === strpos( $href, '//' ) ) {
            return 'https:' . $href;
        }

        if ( preg_match( '#^https?://#i', $href ) ) {
            return $href;
        }

        if ( preg_match( '#^(data:|blob:)#i', $href ) ) {
            return '';
        }

        return rtrim( $base_url, '/' ) . '/' . ltrim( $href, '/' );
    }

    /**
     * Returns the scheme + host base URL for a full URL.
     *
     * @param string $url Full URL.
     * @return string
     */
    private function get_base_url( string $url ): string {
        $parts = wp_parse_url( $url );
        $scheme = isset( $parts['scheme'] ) ? (string) $parts['scheme'] : 'https';
        $host   = isset( $parts['host'] ) ? (string) $parts['host'] : '';

        return '' !== $host ? $scheme . '://' . $host : '';
    }

    /**
     * Normalizes a hex color to 6-char uppercase form.
     *
     * @param string $hex Raw hex string.
     * @return string
     */
    private function normalize_hex( string $hex ): string {
        $hex = ltrim( $hex, '#' );

        if ( 3 === strlen( $hex ) ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if ( 6 === strlen( $hex ) || 8 === strlen( $hex ) ) {
            return '#' . strtoupper( substr( $hex, 0, 6 ) );
        }

        return '';
    }

    /**
     * Converts a hex color to an RGB triplet.
     *
     * @param string $hex Hex color.
     * @return int[]
     */
    private function hex_to_rgb( string $hex ): array {
        $hex = ltrim( $hex, '#' );

        return array(
            hexdec( substr( $hex, 0, 2 ) ),
            hexdec( substr( $hex, 2, 2 ) ),
            hexdec( substr( $hex, 4, 2 ) ),
        );
    }

    /**
     * Calculates luminance for a hex color.
     *
     * @param string $hex Hex color.
     * @return float
     */
    private function luminance( string $hex ): float {
        list( $r, $g, $b ) = $this->hex_to_rgb( $hex );

        return ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;
    }

    /**
     * Calculates saturation for a hex color.
     *
     * @param string $hex Hex color.
     * @return float
     */
    private function saturation( string $hex ): float {
        list( $r, $g, $b ) = $this->hex_to_rgb( $hex );
        $max = max( $r, $g, $b );
        $min = min( $r, $g, $b );

        if ( 0 === $max ) {
            return 0.0;
        }

        return ( $max - $min ) / $max;
    }

    /**
     * Checks whether a color is too close to pure white or black to be useful.
     *
     * @param string $hex Hex color.
     * @return bool
     */
    private function is_boring_color( string $hex ): bool {
        $lum = $this->luminance( $hex );

        return $lum > 0.95 || $lum < 0.02;
    }

    /**
     * Converts a CSS color value to hex where possible.
     *
     * @param string $val CSS value.
     * @return string
     */
    private function css_value_to_hex( string $val ): string {
        $val = trim( $val );

        if ( 0 === strpos( $val, '#' ) ) {
            return $this->normalize_hex( $val );
        }

        if ( preg_match( '/rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i', $val, $matches ) ) {
            return sprintf( '#%02X%02X%02X', intval( $matches[1] ), intval( $matches[2] ), intval( $matches[3] ) );
        }

        return '';
    }

    /**
     * Filters colors to saturated ones.
     *
     * @param string[] $colors Colors.
     * @return string[]
     */
    private function filter_saturated( array $colors ): array {
        return array_values(
            array_filter(
                $colors,
                function ( string $color ): bool {
                    return $this->saturation( $color ) > 0.3;
                }
            )
        );
    }

    /**
     * Filters colors to dark ones.
     *
     * @param string[] $colors Colors.
     * @return string[]
     */
    private function filter_dark( array $colors ): array {
        return array_values(
            array_filter(
                $colors,
                function ( string $color ): bool {
                    return $this->luminance( $color ) < 0.4;
                }
            )
        );
    }

    /**
     * Filters colors to neutral dark ones.
     *
     * @param string[] $colors Colors.
     * @return string[]
     */
    private function filter_neutral_dark( array $colors ): array {
        return array_values(
            array_filter(
                $colors,
                function ( string $color ): bool {
                    return $this->luminance( $color ) < 0.4 && $this->saturation( $color ) < 0.2;
                }
            )
        );
    }

    /**
     * Determines whether the palette reads as dark or light.
     *
     * @param array<string,string> $colors Semantic colors.
     * @return string
     */
    private function determine_color_scheme( array $colors ): string {
        $background = isset( $colors['background'] ) && is_string( $colors['background'] )
            ? $colors['background']
            : '#FFFFFF';

        return $this->luminance( $background ) < 0.5 ? 'dark' : 'light';
    }

    /**
     * Finds a color CSS variable that matches one of the supplied keywords.
     *
     * @param string   $css CSS text.
     * @param string[] $keywords Keywords to match against CSS var names.
     * @return string
     */
    private function find_css_var_color( string $css, array $keywords ): string {
        foreach ( $keywords as $keyword ) {
            $patterns = array(
                '/--[\w-]*[-]' . preg_quote( $keyword, '/' ) . '\s*:\s*(#[0-9A-Fa-f]{6})\b/i',
                '/--[\w-]*[-]' . preg_quote( $keyword, '/' ) . '\s*:\s*(rgba?\([^)]+\))/i',
            );

            foreach ( $patterns as $pattern ) {
                if ( preg_match( $pattern, $css, $matches ) ) {
                    $hex = $this->css_value_to_hex( $matches[1] );
                    if ( '' !== $hex ) {
                        return $hex;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Resolves a CSS variable to a font-family value.
     *
     * @param string $css_text CSS text.
     * @param string $var_name CSS variable name without the leading dashes.
     * @return string
     */
    private function resolve_css_var_font( string $css_text, string $var_name ): string {
        $escaped = preg_quote( trim( $var_name ), '/' );

        if ( preg_match( '/' . $escaped . '\s*:\s*["\']?([^;"\'}\)]+)/i', $css_text, $matches ) ) {
            $resolved = trim( $matches[1], " \t\n\r\0\x0B\"'" );
            if ( '' !== $resolved && ! $this->is_generic_font( $resolved ) && 0 !== strpos( $resolved, 'var(' ) ) {
                return $resolved;
            }
        }

        return '';
    }

    /**
     * Checks whether a font family is generic.
     *
     * @param string $name Font family name.
     * @return bool
     */
    private function is_generic_font( string $name ): bool {
        static $generics = array(
            'serif',
            'sans-serif',
            'monospace',
            'cursive',
            'fantasy',
            'system-ui',
            'ui-serif',
            'ui-sans-serif',
            'ui-monospace',
            'ui-rounded',
            'inherit',
            'initial',
            'revert',
            'unset',
            '-apple-system',
            'blinkmacsystemfont',
        );

        return in_array( strtolower( trim( $name ) ), $generics, true );
    }

    /**
     * Splits a CSS comma-delimited list while preserving nested parentheses.
     *
     * @param string $value CSS list value.
     * @return string[]
     */
    private function split_css_list( string $value ): array {
        $items = array();
        $depth = 0;
        $buffer = '';

        for ( $i = 0, $len = strlen( $value ); $i < $len; $i++ ) {
            $char = $value[ $i ];

            if ( '(' === $char ) {
                $depth++;
            } elseif ( ')' === $char ) {
                $depth--;
            } elseif ( ',' === $char && 0 === $depth ) {
                $items[] = $buffer;
                $buffer  = '';
                continue;
            }

            $buffer .= $char;
        }

        $items[] = $buffer;

        return $items;
    }

    /**
     * Splits the three arguments inside a clamp() expression.
     *
     * @param string $clamp_expr clamp() CSS expression.
     * @return string[]
     */
    private function split_clamp_args( string $clamp_expr ): array {
        $inner = preg_replace( '/^clamp\s*\(\s*/i', '', $clamp_expr );
        $inner = is_string( $inner ) ? preg_replace( '/\s*\)\s*$/', '', $inner ) : '';
        $inner = is_string( $inner ) ? $inner : '';

        $args  = array();
        $depth = 0;
        $buffer = '';

        for ( $i = 0, $len = strlen( $inner ); $i < $len; $i++ ) {
            $char = $inner[ $i ];

            if ( '(' === $char ) {
                $depth++;
            } elseif ( ')' === $char ) {
                $depth--;
            } elseif ( ',' === $char && 0 === $depth ) {
                $args[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $args[] = $buffer;

        return 3 === count( $args ) ? $args : array();
    }

    /**
     * Extracts semantic colors from HTML and CSS.
     *
     * @param string $html HTML body.
     * @param string $css_text CSS text.
     * @return array<string,string>
     */
    private function extract_colors( string $html, string $css_text ): array {
        $combined = $css_text . "\n" . $html;

        preg_match_all( '/#([0-9A-Fa-f]{3,8})\b/', $combined, $hex_matches );
        preg_match_all( '/rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i', $combined, $rgb_matches, PREG_SET_ORDER );

        $color_counts = array();

        foreach ( $hex_matches[0] as $hex ) {
            $normalized = $this->normalize_hex( $hex );
            if ( '' !== $normalized && ! $this->is_boring_color( $normalized ) ) {
                $color_counts[ $normalized ] = ( $color_counts[ $normalized ] ?? 0 ) + 1;
            }
        }

        foreach ( $rgb_matches as $matches ) {
            $hex = sprintf( '#%02X%02X%02X', intval( $matches[1] ), intval( $matches[2] ), intval( $matches[3] ) );
            if ( ! $this->is_boring_color( $hex ) ) {
                $color_counts[ $hex ] = ( $color_counts[ $hex ] ?? 0 ) + 1;
            }
        }

        arsort( $color_counts );
        $top = array_keys( array_slice( $color_counts, 0, 10, true ) );

        $primary    = $this->find_css_var_color( $css_text, array( 'primary', 'brand', 'main' ) );
        $secondary  = $this->find_css_var_color( $css_text, array( 'secondary' ) );
        $accent     = $this->find_css_var_color( $css_text, array( 'accent', 'highlight', 'cta' ) );
        $background = $this->find_css_var_color( $css_text, array( 'background', 'bg', 'body-bg' ) );
        $text_pri   = $this->find_css_var_color( $css_text, array( 'text', 'foreground', 'fg', 'text-primary', 'body-color' ) );
        $text_sec   = $this->find_css_var_color( $css_text, array( 'text-secondary', 'text-muted', 'muted' ) );

        $saturated     = $this->filter_saturated( $top );
        $darks         = $this->filter_dark( $top );
        $neutral_darks = $this->filter_neutral_dark( $top );

        if ( '' === $background ) {
            if ( preg_match( '/\b(?:body|html)\b[^{}]*\{[^}]*background(?:-color)?\s*:\s*([^;}{]+)/i', $css_text, $matches ) ) {
                $background = $this->css_value_to_hex( trim( $matches[1] ) );
            }

            if ( '' === $background ) {
                $background = '#FFFFFF';
            }
        }

        $text_primary   = '' !== $text_pri ? $text_pri : ( $neutral_darks[0] ?? $darks[0] ?? '#000000' );
        $text_secondary = '' !== $text_sec ? $text_sec : ( $neutral_darks[1] ?? $darks[1] ?? '#666666' );

        if ( $text_secondary === $text_primary ) {
            $text_secondary = '#666666';
        }

        return array(
            'primary'       => '' !== $primary ? $primary : ( $saturated[0] ?? ( $top[0] ?? '#000000' ) ),
            'secondary'     => '' !== $secondary ? $secondary : ( $saturated[1] ?? ( $top[1] ?? '#333333' ) ),
            'accent'        => '' !== $accent ? $accent : ( $saturated[2] ?? ( $top[2] ?? '#0066CC' ) ),
            'background'    => $background,
            'textPrimary'   => $text_primary,
            'textSecondary' => $text_secondary,
        );
    }

    /**
     * Extracts a font size for an element from CSS.
     *
     * @param string $css CSS text.
     * @param string $element Element selector.
     * @param string $default Default fallback size.
     * @return array<string,string>
     */
    private function extract_font_size( string $css, string $element, string $default ): array {
        $pattern = '/\b' . preg_quote( $element, '/' ) . '\b[^{}]*\{[^}]*font-size\s*:\s*([^;}{]+)/i';

        if ( preg_match( $pattern, $css, $matches ) ) {
            $value = trim( $matches[1] );

            if ( preg_match( '/var\(\s*--([^,)]+)/i', $value, $var_matches ) ) {
                $escaped = preg_quote( trim( $var_matches[1] ), '/' );
                if ( preg_match( '/--' . $escaped . '\s*:\s*([^;}{]+)/i', $css, $resolved_matches ) ) {
                    $resolved = trim( $resolved_matches[1] );
                    if ( '' !== $resolved && 0 !== strpos( $resolved, 'var(' ) ) {
                        $value = $resolved;
                    }
                }
            }

            if ( '' !== $value && ! in_array( strtolower( $value ), self::$font_size_reset_keywords, true ) ) {
                return $this->parse_font_size_value( $value );
            }
        }

        $class_patterns = array(
            '/\.(?:text-|heading-?)' . preg_quote( $element, '/' ) . '\b[^{}]*\{[^}]*font-size\s*:\s*([^;}{]+)/i',
            '/\.' . preg_quote( $element, '/' ) . '\b[^{}]*\{[^}]*font-size\s*:\s*([^;}{]+)/i',
        );

        foreach ( $class_patterns as $class_pattern ) {
            if ( ! preg_match( $class_pattern, $css, $matches ) ) {
                continue;
            }

            $value = trim( $matches[1] );

            if ( preg_match( '/var\(\s*--([^,)]+)/i', $value, $var_matches ) ) {
                $escaped = preg_quote( trim( $var_matches[1] ), '/' );
                if ( preg_match( '/--' . $escaped . '\s*:\s*([^;}{]+)/i', $css, $resolved_matches ) ) {
                    $resolved = trim( $resolved_matches[1] );
                    if ( '' !== $resolved && 0 !== strpos( $resolved, 'var(' ) ) {
                        $value = $resolved;
                    }
                }
            }

            if ( '' !== $value && ! in_array( strtolower( $value ), self::$font_size_reset_keywords, true ) ) {
                return $this->parse_font_size_value( $value );
            }
        }

        return $this->parse_font_size_value( $default );
    }

    /**
     * Converts a raw CSS size string into the normalized size payload.
     *
     * @param string $raw Raw CSS size.
     * @return array<string,string>
     */
    private function parse_font_size_value( string $raw ): array {
        $raw = trim( $raw );

        if ( preg_match( '/^clamp\s*\(/i', $raw ) ) {
            $args = $this->split_clamp_args( $raw );
            if ( 3 === count( $args ) ) {
                return array(
                    'value' => $raw,
                    'min'   => trim( $args[0] ),
                    'max'   => trim( $args[2] ),
                );
            }
        }

        return array( 'value' => $raw );
    }

    /**
     * Builds the typography object from font list + CSS.
     *
     * @param string[] $fonts Fonts.
     * @param string   $css_text CSS text.
     * @return array<string,mixed>
     */
    private function build_typography( array $fonts, string $css_text ): array {
        $primary = $fonts[0] ?? 'sans-serif';

        if ( preg_match( '/(?:\.elementor-kit-\d+|body)\b[^{}]*\{[^}]*(?<![a-z-])font-family\s*:\s*([^;}{]+)/i', $css_text, $body_matches ) ) {
            $body_families = $this->split_css_list( trim( $body_matches[1] ) );
            $body_first    = trim( $body_families[0], " \t\n\r\0\x0B\"'" );

            if ( '' !== $body_first && ! $this->is_generic_font( $body_first ) && ! preg_match( '/^var\s*\(/i', $body_first ) ) {
                $primary = $body_first;
            }
        }

        $code = '';
        foreach ( $fonts as $font ) {
            if ( preg_match( '/mono|code|consol/i', $font ) ) {
                $code = $font;
                break;
            }
        }

        $heading = $primary;
        if ( preg_match( '/\bh[12]\b[^{}]*\{[^}]*font-family\s*:\s*([^;}{]+)/i', $css_text, $matches ) ) {
            $raw      = trim( $matches[1], " \t\n\r\0\x0B\"'" );
            $heading  = $this->split_css_list( $raw )[0];
            $heading  = trim( $heading, " \t\n\r\0\x0B\"'" );

            if ( preg_match( '/var\(\s*--([^,)]+)/i', $heading, $var_matches ) ) {
                $heading = $this->resolve_css_var_font( $css_text, trim( $var_matches[1] ) );
                if ( '' === $heading ) {
                    $heading = $primary;
                }
            } elseif ( $this->is_generic_font( $heading ) ) {
                $heading = $primary;
            }
        }

        $weights = array(
            'regular' => 400,
            'medium'  => 500,
            'bold'    => 700,
        );

        if ( preg_match_all( '/font-weight\s*:\s*(\d{3})\b/i', $css_text, $weight_matches ) ) {
            $found = array_unique( $weight_matches[1] );
            sort( $found );

            if ( count( $found ) >= 1 ) {
                $weights['regular'] = intval( $found[0] );
            }

            if ( count( $found ) >= 2 ) {
                $weights['medium'] = intval( $found[ (int) floor( count( $found ) / 2 ) ] );
            }

            if ( count( $found ) >= 3 ) {
                $weights['bold'] = intval( end( $found ) );
            }
        }

        return array(
            'fontFamilies' => array(
                'primary' => $primary,
                'heading' => $heading,
                'code'    => '' !== $code ? $code : 'monospace',
            ),
            'fontSizes' => array(
                'h1'   => $this->extract_font_size( $css_text, 'h1', '48px' ),
                'h2'   => $this->extract_font_size( $css_text, 'h2', '36px' ),
                'h3'   => $this->extract_font_size( $css_text, 'h3', '24px' ),
                'body' => $this->extract_font_size( $css_text, 'body', '16px' ),
            ),
            'fontWeights' => $weights,
        );
    }

    /**
     * Extracts spacing primitives from CSS.
     *
     * @param string $css_text CSS text.
     * @return array<string,mixed>
     */
    private function extract_spacing( string $css_text ): array {
        $base   = 8;
        $radius = '8px';

        if ( preg_match( '/--[\w-]*(spacing|space|gap)[\w-]*\s*:\s*(\d+)px/i', $css_text, $matches ) ) {
            $base = intval( $matches[2] );
        }

        if ( preg_match( '/--[\w-]*(radius|rounded)[\w-]*\s*:\s*([^;}\s]+)/i', $css_text, $matches ) ) {
            $radius = trim( $matches[2] );
        } elseif ( preg_match_all( '/border-radius\s*:\s*(\d+px)/i', $css_text, $matches ) ) {
            $counts = array_count_values( $matches[1] );
            arsort( $counts );
            $radius = (string) array_key_first( $counts );
        }

        return array(
            'baseUnit'     => $base,
            'borderRadius' => $radius,
        );
    }

    /**
     * Extracts button styles from CSS.
     *
     * @param string               $css_text CSS text.
     * @param array<string,string> $colors Semantic colors.
     * @return array<string,mixed>
     */
    private function extract_button_styles( string $css_text, array $colors ): array {
        $wp_admin_colors = array( '#32373C', '#32373c', '#0073AA', '#0073aa' );

        $primary_button = array(
            'background'   => $colors['primary'],
            'textColor'    => '#FFFFFF',
            'borderRadius' => '8px',
        );
        $secondary_button = array(
            'background'   => 'transparent',
            'textColor'    => $colors['primary'],
            'borderColor'  => $colors['primary'],
            'borderRadius' => '8px',
        );

        $button_selectors = array(
            '/(?:\.elementor-button|\.btn-primary)(?:\s|,|\{)[^}]*\{([^}]+)\}/i',
            '/(?:\.btn|\.button)(?:\s|,|\{)[^}]*\{([^}]+)\}/i',
            '/\bbutton\b(?:\s|,|\{)[^}]*\{([^}]+)\}/i',
        );

        foreach ( $button_selectors as $button_pattern ) {
            if ( ! preg_match( $button_pattern, $css_text, $matches ) ) {
                continue;
            }

            $block    = $matches[1] ?? $matches[0];
            $found_bg = '';

            if ( preg_match( '/background(?:-color)?\s*:\s*([^;]+)/i', $block, $bg_matches ) ) {
                $hex = $this->css_value_to_hex( trim( $bg_matches[1] ) );
                if ( '' !== $hex && ! in_array( $hex, $wp_admin_colors, true ) ) {
                    $found_bg = $hex;
                }
            }

            if ( '' === $found_bg ) {
                continue;
            }

            $primary_button['background'] = $found_bg;

            if ( preg_match( '/(?<![-\w])color\s*:\s*([^;]+)/i', $block, $fg_matches ) ) {
                $hex = $this->css_value_to_hex( trim( $fg_matches[1] ) );
                if ( '' !== $hex ) {
                    $primary_button['textColor'] = $hex;
                }
            }

            if ( preg_match( '/border-radius\s*:\s*([^;]+)/i', $block, $radius_matches ) ) {
                $primary_button['borderRadius']   = trim( $radius_matches[1] );
                $secondary_button['borderRadius'] = trim( $radius_matches[1] );
            }

            break;
        }

        return array(
            'buttonPrimary'   => $primary_button,
            'buttonSecondary' => $secondary_button,
        );
    }

    /**
     * Assembles the extracted branding array.
     *
     * @param array<string,string> $colors Semantic colors.
     * @param string[]             $fonts Font families.
     * @param string               $logo Logo URL.
     * @param string               $favicon Favicon URL.
     * @param string               $og_image OG image URL.
     * @param string               $css_text CSS text.
     * @return array<string,mixed>
     */
    private function assemble_branding( array $colors, array $fonts, string $logo, string $favicon, string $og_image, string $css_text ): array {
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
            'typography'  => $this->build_typography( $fonts, $css_text ),
            'spacing'     => $this->extract_spacing( $css_text ),
            'components'  => $this->extract_button_styles( $css_text, $colors ),
            'images'      => array(
                'logo'    => $logo,
                'favicon' => $favicon,
                'ogImage' => $og_image,
            ),
        );
    }
}
