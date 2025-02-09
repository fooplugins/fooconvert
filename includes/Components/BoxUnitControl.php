<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

class BoxUnitControl extends BaseComponent {

    private array $default_box_unit = array(
        'top'    => '',
        'right'  => '',
        'bottom' => '',
        'left'   => ''
    );

    private array $default_box_unit_size = array(
        'top'    => '0px',
        'right'  => '0px',
        'bottom' => '0px',
        'left'   => '0px'
    );

    function is_possible_box_unit( $value ): bool {
        return Utils::some_keys( $value, array_keys( $this->default_box_unit ), function ( $key_value ) {
            return Utils::is_string( $key_value, true );
        } );
    }

    private function make_box_unit( $value, array $defaults ): array {
        $box_value = array();
        if ( is_string( $value ) ) {
            foreach ( $defaults as $key => $default_value ) {
                $box_value[$key] = !empty( $value ) ? $value : $default_value;
            }
        } else {
            foreach ( $defaults as $key => $default_value ) {
                $given = Utils::get_string( $value, $key );
                $box_value[$key] = !empty( $given ) ? $given : $default_value;
            }
        }
        return $box_value;
    }

    function get_sizes( $value, array $defaults = array() ): array {
        return $this->make_box_unit( $value, array_merge( array(), $this->default_box_unit_size, $defaults ) );
    }

    function get_styles( $value, string $css_Basename, string $prefix = '' ): array {
        $styles = array();

        if ( Utils::is_string( $value, true ) ) {
            $styles["$prefix$css_Basename"] = $value;
        } elseif ( $this->is_possible_box_unit( $value ) ) {
            list(
                'top' => $top,
                'right' => $right,
                'bottom' => $bottom,
                'left' => $left
                ) = $this->make_box_unit( $value, $this->default_box_unit );

            if ( !empty( $top ) ) {
                $styles["$prefix$css_Basename-top"] = $top;
            }
            if ( !empty( $right ) ) {
                $styles["$prefix$css_Basename-right"] = $right;
            }
            if ( !empty( $bottom ) ) {
                $styles["$prefix$css_Basename-bottom"] = $bottom;
            }
            if ( !empty( $left ) ) {
                $styles["$prefix$css_Basename-left"] = $left;
            }
        }

        return $styles;
    }

}