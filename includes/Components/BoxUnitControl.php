<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BoxUnitControl.
 */
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

    /**
     * Determines whether possible box unit.
     */
    function is_possible_box_unit( $value ): bool {
        return Utils::some_keys( $value, array_keys( $this->default_box_unit ), function ( $key_value ) {
            return Utils::is_string( $key_value, true );
        } );
    }

    /**
     * Handles make box unit.
     */
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

    /**
     * Returns the sizes.
     */
    function get_sizes( $value, array $defaults = array() ): array {
        return $this->make_box_unit( $value, array_merge( array(), $this->default_box_unit_size, $defaults ) );
    }

    /**
     * Returns the styles.
     */
    function get_styles( $value, string $css_basename ): array {
        $styles = array();

        if ( Utils::is_string( $value, true ) ) {
            $styles["$css_basename"] = $value;
        } elseif ( $this->is_possible_box_unit( $value ) ) {
            list(
                'top' => $top,
                'right' => $right,
                'bottom' => $bottom,
                'left' => $left
                ) = $this->make_box_unit( $value, $this->default_box_unit );

            if ( !empty( $top ) ) {
                $styles["$css_basename-top"] = $top;
            }
            if ( !empty( $right ) ) {
                $styles["$css_basename-right"] = $right;
            }
            if ( !empty( $bottom ) ) {
                $styles["$css_basename-bottom"] = $bottom;
            }
            if ( !empty( $left ) ) {
                $styles["$css_basename-left"] = $left;
            }
        }

        return $styles;
    }

}