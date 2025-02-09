<?php

namespace FooPlugins\FooConvert\Widgets\Base;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Utils;
use WP_Block;
use WP_Error;
use WP_Post_Type;

abstract class BaseWidget extends BaseBlock {

    public array $supported = array( 'shortcode', 'display-rules', 'compatibility' );

    function init() {
        $post_type = $this->register_post_type();
        if ( $post_type instanceof WP_Post_Type ) {
            if ( $this->supports( 'compatibility' ) ) {
                FooConvert::plugin()->compatibility->register( $post_type->name );
            }
            if ( $this->supports( 'display-rules' ) ) {
                FooConvert::plugin()->display_rules->register( $post_type->name );
            }
            if ( $this->supports( 'shortcode' ) ) {
                FooConvert::plugin()->widgets->register_shortcode( $post_type->name );
            }
            parent::init();
        }
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
     * @return WP_Error|WP_Post_Type
     *
     * @since 1.0.0
     */
    abstract function register_post_type();
}