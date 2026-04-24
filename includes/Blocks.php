<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use FooPlugins\FooConvert\Blocks\Countdown;
use FooPlugins\FooConvert\Blocks\Coupon;
use FooPlugins\FooConvert\Blocks\SignUp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Blocks.
 */
class Blocks {
    /**
     * Initializes the Blocks.
     */
    function __construct() {
        $this->instances = array(
            new Countdown(),
            new Coupon(),
            new SignUp()
        );

        /**
         * Fires after the core FooConvert blocks have been instantiated,
         * so that extensions can register their own `BaseBlock` instances
         * without touching this class. Subscribers should call
         * `$blocks->register( new MyBlock() )` to participate in the shared
         * kses definition and block-instance enumeration.
         *
         * Block instances self-register with WordPress via their own
         * `init` hook (see `BaseBlock::__construct`), so adding a block
         * here is only necessary when the block contributes a kses
         * definition or needs to be visible to other block-level features.
         *
         * @since 2.1.0
         *
         * @param Blocks $blocks The core blocks collection.
         */
        do_action( 'fooconvert_register_blocks', $this );
    }

    /**
     * @var BaseBlock[]
     */
    private array $instances;

    /**
     * Get all popup instances.
     *
     * @return BaseBlock[]
     *
     * @since 1.0.0
     */
    function get_instances(): array {
        return $this->instances;
    }

    /**
     * Handles register.
     */
    function register( $block ) {
        if ( $block instanceof BaseBlock ) {
            $this->instances[] = $block;
        }
    }

    /**
     * The KSES definition to allow iframes. This is required to allow core WP embed blocks as content.
     * @var array
     */
    private array $iframe_def = array(
        'iframe' => array(
            'aria-controls'    => true,
            'aria-current'     => true,
            'aria-describedby' => true,
            'aria-details'     => true,
            'aria-expanded'    => true,
            'aria-hidden'      => true,
            'aria-label'       => true,
            'aria-labelledby'  => true,
            'aria-live'        => true,
            'data-*'           => true,
            'dir'              => true,
            'id'               => true,
            'class'            => true,
            'allow'            => true,
            'allowfullscreen'  => true,
            'height'           => true,
            'loading'          => true,
            'name'             => true,
            'referrerpolicy'   => true,
            'sandbox'          => true,
            'src'              => true,
            'srcdoc'           => true,
            'width'            => true
        )
    );

    /**
     * Returns the kses definitions.
     */
    function get_kses_definitions(): array {
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
