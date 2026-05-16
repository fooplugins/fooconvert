<?php

namespace FooPlugins\FooConvert\Blocks;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Utils;
use WP_Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic renderer for the split layout block and its panels.
 */
class SplitLayout extends BaseBlock {

    /**
     * Returns the KSES schema.
     */
    public function kses_definition(): array {
        return array();
    }

    /**
     * Returns the block name used during registration.
     */
    function get_block_name(): string {
        return 'fc/split-layout';
    }

    /**
     * Returns the frontend wrapper tag.
     */
    function get_tag_name(): string {
        return 'div';
    }

    /**
     * Creates a semantic instance ID for the rendered div wrapper.
     */
    function create_instance_id( array $attributes ): string {
        $unique_id = Utils::get_string( $attributes, 'uniqueId' );
        if ( !empty( $unique_id ) ) {
            return 'fc-split-layout-' . $unique_id;
        }
        return wp_unique_prefixed_id( 'fc-split-layout-' );
    }

    /**
     * Registers the split layout and panel blocks.
     */
    function register_blocks() {
        return Utils::register_popup_blocks( array(
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'blocks/split-layout/block.json',
                'args'           => array(
                    'render_callback' => array( $this, 'render' )
                )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'blocks/split-layout/blocks/panel/block.json',
                'args'           => array(
                    'render_callback' => array( $this, 'render_panel' )
                )
            )
        ) );
    }

    /**
     * Returns frontend attributes applied to the layout wrapper.
     */
    function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ): array {
        $settings = $this->get_settings( $attributes );
        $fixed_side = Utils::get_string( $settings, 'fixedSide', 'right' );
        $vertical_alignment = Utils::get_string( $settings, 'verticalAlignment', 'center' );

        return array(
            'class' => implode( ' ', array_filter( array(
                'fc--split-layout',
                in_array( $fixed_side, array( 'left', 'right' ), true ) ? 'fc--split-layout--fixed-' . $fixed_side : '',
                in_array( $vertical_alignment, array( 'top', 'center', 'bottom', 'stretch' ), true ) ? 'fc--split-layout--align-' . $vertical_alignment : '',
            ) ) )
        );
    }

    /**
     * Builds frontend style rules for the split layout.
     */
    function get_frontend_styles( string $instance_id, array $attributes, WP_Block $block ): array {
        $components = FooConvert::plugin()->components;
        $styles = array();
        $host_styles = $this->get_styles( $attributes );

        if ( !empty( $host_styles ) ) {
            $styles = array_merge( $styles, $components->get_styles( $host_styles ) );
        }

        $settings = $this->get_settings( $attributes );
        $fixed_width = Utils::get_string( $settings, 'fixedWidth', '360px' );
        if ( !empty( $fixed_width ) ) {
            $styles['--fc-split-layout-fixed-width'] = $fixed_width;
        }

        return count( $styles ) > 0 ? array(
            "#$instance_id" => $styles
        ) : array();
    }

    /**
     * Renders a split layout panel.
     */
    function render_panel( array $attributes, string $content, WP_Block $block ): string {
        $styles = $this->get_panel_styles( $attributes );
        $settings = $this->get_settings( $attributes );
        $justify_content = Utils::get_string( $settings, 'justifyContent', 'center' );
        $horizontal_alignment = Utils::get_string( $settings, 'horizontalAlignment', 'center' );

        if ( !empty( $justify_content ) ) {
            $styles['justify-content'] = $justify_content;
        }
        if ( !empty( $horizontal_alignment ) ) {
            $styles['align-items'] = $horizontal_alignment;
        }

        $wrapper_attributes = array(
            'class' => 'fc--split-layout-panel',
        );

        if ( !empty( $styles ) ) {
            $wrapper_attributes['style'] = $this->styles_to_attribute( $styles );
        }

        ob_start();
        ?><div <?php echo wp_kses_data( get_block_wrapper_attributes( $wrapper_attributes ) ); ?>><?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic inner block HTML is filtered by kses() before output.
            echo $this->kses( $attributes, do_blocks( $content ), $block, 'content' );
        ?></div><?php
        return ob_get_clean();
    }

    /**
     * Returns computed panel styles.
     */
    private function get_panel_styles( array $attributes ): array {
        $panel_styles = $this->get_styles( $attributes );

        if ( empty( $panel_styles ) ) {
            return array();
        }

        return FooConvert::plugin()->components->get_styles( $panel_styles );
    }

    /**
     * Converts an associative style array into an inline style value.
     */
    private function styles_to_attribute( array $styles ): string {
        $declarations = array();
        foreach ( $styles as $name => $value ) {
            if ( Utils::is_string( $name, true ) && ( Utils::is_string( $value, true ) || is_int( $value ) ) ) {
                $declarations[] = $name . ': ' . $value;
            }
        }
        return implode( '; ', $declarations );
    }
}
