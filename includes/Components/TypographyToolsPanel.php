<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TypographyToolsPanel.
 */
class TypographyToolsPanel extends BaseComponent {
    /**
     * Returns the styles.
     */
    public function get_styles( array $attr_value ): array {
        $styles = [];

        $font_family = Utils::get_string( $attr_value, 'fontFamily' );
        if ( !empty( $font_family ) ) {
            $styles['font-family'] = $font_family;
        } else {
            $font_family = Utils::get_key_path( $attr_value, 'fontFamily.style.fontFamily' );
            if ( !empty( $font_family ) ) {
                $styles['font-family'] = $font_family;
            }
        }

        $font_size = Utils::get_string( $attr_value, 'fontSize' );
        if ( !empty( $font_size ) ) {
            $styles['font-size'] = $font_size;
        }

        // fontAppearance
        $font_style = Utils::get_string( $attr_value, 'fontStyle' );
        $font_weight = Utils::get_int( $attr_value, 'fontWeight' );
        if ( !empty( $font_style ) && !empty( $font_weight ) ) {
            $styles['font-style'] = $font_style;
            $styles['font-weight'] = $font_weight;
        }

        $line_height = Utils::get_string( $attr_value, 'lineHeight' );
        if ( !empty( $line_height ) ) {
            $styles['line-height'] = $line_height;
        }

        $letter_spacing = Utils::get_string( $attr_value, 'letterSpacing' );
        if ( !empty( $letter_spacing ) ) {
            $styles['letter-spacing'] = $letter_spacing;
        }

        $text_decoration = Utils::get_string( $attr_value, 'textDecoration' );
        if ( !empty( $text_decoration ) ) {
            $styles['text-decoration'] = $text_decoration;
        }

        $text_transform = Utils::get_string( $attr_value, 'textTransform' );
        if ( !empty( $text_transform ) ) {
            $styles['text-transform'] = $text_transform;
        }

        return $styles;
    }

    /**
     * Returns the font family classname.
     */
    public function get_font_family_classname( $attr_value ) : ?string {
        $font_family = Utils::get_key_path( $attr_value, 'fontFamily.key' );
        if ( Utils::is_string( $font_family, true ) ) {
            return "uses-$font_family-font-family";
        }
        return null;
    }
}