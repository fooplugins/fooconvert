<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

/**
 * Provides utility methods for working with the JavaScript `BorderControl` component.
 * @extends BaseComponent
 */
class BorderControl extends BaseComponent {

    private array $default_value = array(
        'color' => '',
        'style' => '',
        'width' => ''
    );

    private array $default_box_value = array(
        'top' => array(),
        'right' => array(),
        'bottom' => array(),
        'left' => array()
    );

    private function make_value( array $value, array $defaults ) : array {
        $box_value = array();
        foreach ( $defaults as $key => $default_value ) {
            $given = Utils::get_key( $value, $key );
            $box_value[ $key ] = ! empty( $given ) ? $given : $default_value;
        }
        return $box_value;
    }

    function is_possible_value( $value ) : bool {
        return Utils::some_keys( $value, array_keys( $this->default_value ), function ( $key_value ) {
            return Utils::is_string( $key_value, true );
        } );
    }

    function is_possible_box_value( $value ) : bool {
        return Utils::some_keys( $value, array_keys( $this->default_box_value ), function ( $key_value ) {
            return $this->is_possible_value( $key_value );
        } );
    }

    function get_css_value( $value, bool $style_required = true ) : string {
        if ( $this->is_possible_value( $value ) ) {
            list(
                'color' => $color,
                'style' => $style,
                'width' => $width
                ) = $this->make_value( $value, $this->default_value );

            if ( !empty( $width ) && ( !$style_required || ( !empty( $style ) && $style !== 'none' ) ) ) {
                return "$width $style $color";
            }
        }
        return '';
    }

    function get_styles( $value, string $prefix = '', bool $style_required = true ) : array {
        $styles = array();
        $border = $this->get_css_value( $value );
        if ( ! empty( $border ) ) {
            $styles["{$prefix}border"] = $border;
        } elseif ( $this->is_possible_box_value( $value ) ) {
            list(
                'top' => $top,
                'right' => $right,
                'bottom' => $bottom,
                'left' => $left
                ) = $this->make_value( $value, $this->default_box_value );

            $borderTop = $this->get_css_value( $top, $style_required );
            if ( ! empty( $borderTop ) ) {
                $styles["{$prefix}border-top"] = $borderTop;
            }

            $borderRight = $this->get_css_value( $right, $style_required );
            if ( ! empty( $borderRight ) ) {
                $styles["{$prefix}border-right"] = $borderRight;
            }

            $borderBottom = $this->get_css_value( $bottom, $style_required );
            if ( ! empty( $borderBottom ) ) {
                $styles["{$prefix}border-bottom"] = $borderBottom;
            }

            $borderLeft = $this->get_css_value( $left, $style_required );
            if ( ! empty( $borderLeft ) ) {
                $styles["{$prefix}border-left"] = $borderLeft;
            }
        }
        return $styles;
    }

}