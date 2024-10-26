<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\Base_Component;
use FooPlugins\FooConvert\Utils;

class Typography_Tools_Panel extends Base_Component {
    public function get_styles( array $attr_value, string $prefix = '' ) : array {
        $styles = [];

        $font_family = Utils::get_string( $attr_value, 'fontFamily' );
        if ( !empty( $font_family ) ) {
            $styles["{$prefix}font-family"] = $font_family;
        }

        $font_size = Utils::get_string( $attr_value, 'fontSize' );
        if ( !empty( $font_size ) ) {
            $styles["{$prefix}font-size"] = $font_size;
        }

        // fontAppearance
        $font_style = Utils::get_string( $attr_value, 'fontStyle' );
        $font_weight = Utils::get_string( $attr_value, 'fontWeight' );
        if ( !empty( $font_style ) && !empty( $font_weight ) ) {
            $styles["{$prefix}font-style"] = $font_style;
            $styles["{$prefix}font-weight"] = $font_weight;
        }

        $line_height = Utils::get_string( $attr_value, 'lineHeight' );
        if ( !empty( $line_height ) ) {
            $styles["{$prefix}line-height"] = $line_height;
        }

        $letter_spacing = Utils::get_string( $attr_value, 'letterSpacing' );
        if ( !empty( $letter_spacing ) ) {
            $styles["{$prefix}letter-spacing"] = $letter_spacing;
        }

        $text_decoration = Utils::get_string( $attr_value, 'textDecoration' );
        if ( !empty( $text_decoration ) ) {
            $styles["{$prefix}text-decoration"] = $text_decoration;
        }

        $text_transform = Utils::get_string( $attr_value, 'textTransform' );
        if ( !empty( $text_transform ) ) {
            $styles["{$prefix}text-transform"] = $text_transform;
        }

        return $styles;
    }
}