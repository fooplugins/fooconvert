<?php

namespace FooPlugins\FooConvert\Blocks;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Utils;
use WP_Block;

class ExampleBlock extends BaseBlock {

    public function kses_definition() : array {
        return array(
            $this->get_tag_name() => array(
                'id' => true,
                'class' => true
            )
        );
    }

    function get_block_name() : string {
        return 'fc/example-block';
    }

    function get_tag_name() : string {
        return 'fc-example-block';
    }

    function register_blocks() {
        $post_types = FooConvert::plugin()->widgets->get_post_types();
        return Utils::register_post_type_blocks( $post_types, array(
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'blocks/example-block/block.json',
                'args' => array(
                    'render_callback' => array( $this, 'render' )
                )
            )
        ) );
    }

    function render( array $attributes, string $content, WP_Block $block ) {
        // the rich-text component is configured to store its content in the 'content' attribute,
        // here we simply grab that and replace any supplied content (should be empty!) with our own.
        return parent::render(
            $attributes,
            Utils::get_string( $attributes, 'content' ),
            $block
        );
    }

    public function get_frontend_data( string $instance_id, array $attributes, WP_Block $block ) : array {
        return array();
    }

    function get_frontend_styles( string $instance_id, array $attributes, WP_Block $block ) : array {
        $root = array();

        $styles_attribute = Utils::get_array( $attributes, 'styles' );
        if ( !empty( $styles_attribute ) ) {
            $root = array_merge(
                $root,
                FooConvert::plugin()->components->get_styles( $styles_attribute, '', array(
                    'background' => 'background',
                    'text' => 'color'
                ) )
            );
        }

        $styles = [];
        if ( count( $root ) > 0 ) {
            $styles["#$instance_id::part(button)"] = $root;
        }
        return $styles;
    }
}