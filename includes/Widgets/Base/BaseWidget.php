<?php

namespace FooPlugins\FooConvert\Widgets\Base;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use WP_Block;

/**
 * Class BaseWidget.
 */
abstract class BaseWidget extends BaseBlock {

    public array $supported = array( 'shortcode', 'display-rules', 'compatibility' );

    /**
     * Get the widget post type name.
     *
     * @return string
     *
     * @since 1.0.0
     */
    abstract function get_post_type(): string;
}
