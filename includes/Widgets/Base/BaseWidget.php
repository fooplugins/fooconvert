<?php

namespace FooPlugins\FooConvert\Widgets\Base;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Utils;
use WP_Block;
use WP_Error;
use WP_Post_Type;

/**
 * Class BaseWidget.
 */
abstract class BaseWidget extends BaseBlock {

    public array $supported = array( 'shortcode', 'display-rules', 'compatibility' );

    /**
     * Handles init.
     */
    function init() {
        $registered = $this->register_post_type();
        if ( $registered instanceof WP_Post_Type ) {
            $registered_post_types = array( $registered->name );
        } else if ( is_array( $registered ) ) {
            $registered_post_types = $registered;
        } else {
            $registered_post_types = array();
        }

        foreach ( $registered_post_types as $post_type ) {
            if ( $this->supports( 'compatibility' ) ) {
                FooConvert::plugin()->compatibility->register( $post_type );
            }
            if ( $this->supports( 'display-rules' ) ) {
                FooConvert::plugin()->display_rules->register( $post_type );
            }
            if ( $this->supports( 'shortcode' ) ) {
                FooConvert::plugin()->widgets->register_shortcode( $post_type );
            }
        }

        parent::init();
    }

    /**
     * Get the widget post type name.
     *
     * @return string
     *
     * @since 1.0.0
     */
    abstract function get_post_type(): string;

    /**
     * Register the widget post type.
     *
     * @return false|string[]|WP_Error|WP_Post_Type
     *
     * @since 1.0.0
     */
    abstract function register_post_type();
}
