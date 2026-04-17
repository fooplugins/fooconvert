<?php

namespace FooPlugins\FooConvert\Popups\Base;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use WP_Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BasePopup.
 */
abstract class BasePopup extends BaseBlock {

    public array $supported = array( 'shortcode', 'display-rules', 'compatibility' );

    /**
     * Get the popup post type name.
     *
     * @return string
     *
     * @since 1.0.0
     */
    abstract function get_post_type(): string;
}
