<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;

class ColorToolsPanel extends BaseComponent {

    /**
     * @param array $value
     * @param string|array $prefix_or_map
     * @return array
     */
    public function get_styles( array $value, $prefix_or_map ) : array {
        $styles = [];
        if ( ! empty( $value ) ) {
            foreach ( $value as $key => $key_value ) {
                if ( ! empty( $key_value ) ) {
                    if ( is_string( $prefix_or_map ) ) {
                        $styles[ "$prefix_or_map$key" ] = $key_value;
                    } else if ( is_array( $prefix_or_map ) && array_key_exists( $key, $prefix_or_map ) ) {
                        $styles[ $prefix_or_map[ $key ] ] = $key_value;
                    }
                }
            }
        }
        return $styles;
    }
}