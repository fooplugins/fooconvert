<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

/**
 * Provides utility methods for working with the JavaScript `BorderRadiusControl` component.
 * @extends BaseComponent
 */
class BorderRadiusControl extends BaseComponent {

    private array $default_box_value = array(
        'topLeft'     => '0px',
        'topRight'    => '0px',
        'bottomRight' => '0px',
        'bottomLeft'  => '0px'
    );

    function make_box_value( array $value ): array {
        $box_value = array();
        foreach ( $this->default_box_value as $key => $default_value ) {
            $given = Utils::get_string( $value, $key );
            $box_value[$key] = !empty( $given ) ? $given : $default_value;
        }
        return $box_value;
    }

    function is_possible_box_value( $value ): bool {
        return Utils::some_keys( $value, array_keys( $this->default_box_value ), function ( $key_value ) {
            return Utils::is_string( $key_value, true );
        } );
    }

    function get_css_value( $value ): string {
        if ( is_string( $value ) ) {
            return $value;
        } elseif ( $this->is_possible_box_value( $value ) ) {
            list(
                'topLeft' => $topLeft,
                'topRight' => $topRight,
                'bottomRight' => $bottomRight,
                'bottomLeft' => $bottomLeft
                ) = $this->make_box_value( $value );
            return "$topLeft $topRight $bottomRight $bottomLeft";
        }
        return '';
    }

    function get_styles( $value, string $prefix = '' ): array {
        $styles = array();
        $border_radius = $this->get_css_value( $value );
        if ( !empty( $border_radius ) ) {
            $styles["{$prefix}border-radius"] = $border_radius;
        }
        return $styles;
    }

}