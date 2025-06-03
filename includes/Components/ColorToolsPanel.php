<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

class ColorToolsPanel extends BaseComponent {

    /**
     * @param array $value
     * @param array $key_to_css_map
     * @return array
     */
    public function get_styles( array $value, array $key_to_css_map = array() ): array {
        $styles = [];
        if ( !empty( $value ) ) {
            if ( empty( $key_to_css_map ) ) {
                $key_to_css_map = array(
                    'background' => array( Utils::class, 'get_css_background_property' ),
                    'text' => 'color'
                );
            }

            foreach ( $value as $key => $key_value ) {
                if ( !empty( $key_value ) ) {
                    if ( array_key_exists( $key, $key_to_css_map ) ) {
                        $mapped = $key_to_css_map[$key];

                        if ( is_string( $mapped ) ) {
                            $styles[$key_to_css_map[$key]] = $key_value;
                        } else if ( is_callable( $mapped ) ) {
                            $result = call_user_func( $mapped, $key_value );
                            if ( is_string( $result ) && !empty( $result ) ) {
                                $styles[$result] = $key_value;
                            }
                        }
                    }
                }
            }
        }
        return $styles;
    }
}