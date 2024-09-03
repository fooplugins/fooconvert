<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Blocks\Base\Base_Block;
use FooPlugins\FooConvert\Blocks\Example_Block;

class FooConvert_Blocks {
    function __construct() {
        $this->instances = array(
            new Example_Block()
        );
    }

    /**
     * @var Base_Block[]
     */
    private array $instances;

    /**
     * Get all widget instances.
     *
     * @return Base_Block[]
     *
     * @since 1.0.0
     */
    function get_instances() : array {
        return $this->instances;
    }

    function get_kses_definitions() : array {
        $defs = array();
        foreach ( $this->instances as $instance ) {
            $def = $instance->kses_definition();
            if ( !empty( $def ) ) {
                $defs = array_merge( $defs, $def );
            }
        }
        return $defs;
    }
}