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

    /**
     * The KSES definition to allow iframes. This is required to allow core WP embed blocks as content.
     * @var array
     */
    private array $iframe_def = array(
        'iframe' => array(
            'aria-controls' => true,
            'aria-current' => true,
            'aria-describedby' => true,
            'aria-details' => true,
            'aria-expanded' => true,
            'aria-hidden' => true,
            'aria-label' => true,
            'aria-labelledby' => true,
            'aria-live' => true,
            'data-*' => true,
            'dir' => true,
            'id' => true,
            'class' => true,
            'allow' => true,
            'allowfullscreen' => true,
            'height' => true,
            'loading' => true,
            'name' => true,
            'referrerpolicy' => true,
            'sandbox' => true,
            'src' => true,
            'srcdoc' => true,
            'width' => true
        )
    );

    function get_kses_definitions() : array {
        $defs = array();
        $defs = array_merge( $defs, $this->iframe_def );
        foreach ( $this->instances as $instance ) {
            $def = $instance->kses_definition();
            if ( !empty( $def ) ) {
                $defs = array_merge( $defs, $def );
            }
        }
        return $defs;
    }
}