<?php

namespace FooPlugins\FooConvert\Blocks;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Utils;
use WP_Block;

class Coupon extends BaseBlock {

    public function kses_definition(): array {
        return array(
            $this->get_tag_name() => array(
                'id'    => true,
                'class' => true,
                'layout' => true,
                'code' => true,
                'copy-close' => true,
                'copy-redirect' => true,
                'no-label' => true,
            )
        );
    }

    function get_block_name(): string {
        return 'fc/coupon';
    }

    function get_tag_name(): string {
        return 'fc-coupon';
    }

    function register_blocks() {
        $post_types = FooConvert::plugin()->widgets->get_post_types();
        return Utils::register_post_type_blocks( $post_types, array(
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'blocks/coupon/block.json',
                'args'           => array(
                    'render_callback' => array( $this, 'render' )
                )
            )
        ) );
    }

    function render( array $attributes, string $content, WP_Block $block ) {
        $content = '';

        $settings = $this->get_settings( $attributes );
        $label_text = Utils::get_string( $settings, 'label' );
        if ( !empty( $label_text ) ) {
            $content .= sprintf( '<span slot="label__text">%s</span>', $label_text );
        }

        $button_settings = $this->get_settings( $attributes, 'button' );
        $button_layout = Utils::get_string( $button_settings, 'layout', 'icon-only' );
        if ( in_array( $button_layout, [ 'text-only', 'icon-text', 'text-icon' ] ) ) {
            $button_text = Utils::get_string( $button_settings, 'text', __( 'Copy', 'fooconvert' ) );
            if ( !empty( $button_text ) ) {
                $content .= sprintf( '<span slot="copy-button__text">%s</span>', $button_text );
            }
        }

        return parent::render(
            $attributes,
            $content,
            $block
        );
    }

    public function get_frontend_data( string $instance_id, array $attributes, WP_Block $block ): array {
        $data = array();

        $settings = $this->get_settings( $attributes );
        $copied_message = Utils::get_string( $settings, 'copiedMessage', __( 'Copied!', 'fooconvert' ) );
        if ( !empty( $copied_message ) ) {
            $data['copiedMessage'] = $copied_message;
        }

        $code_settings = $this->get_settings( $attributes, 'code' );
        $code_text = Utils::get_string( $code_settings, 'text' );
        if ( !empty( $code_text ) ) {
            $data['code'] = $code_text;
        }
        $override_text = Utils::get_string( $code_settings, 'overrideText' );
        if ( !empty( $override_text ) ) {
            $data['override'] = $override_text;
        }

        return $data;
    }

    function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ): array {
        $attr = array();
        $settings = $this->get_settings( $attributes );
        if ( !empty( $settings ) ) {
            $no_label = Utils::get_bool( $settings, 'noLabel' );
            if ( ! empty( $no_label ) ) {
                $attr['no-label'] = '';
            }
            $layout = Utils::get_string( $settings, 'layout' );
            if ( ! empty( $layout ) ) {
                $attr['layout'] = $layout;
            }
            $close_on_copy = Utils::get_bool( $settings, 'closeOnCopy' );
            if ( $close_on_copy === true ) {
                $attr['copy-close'] = '';
            }
            $redirect_on_copy = Utils::get_bool( $settings, 'redirectOnCopy' );
            $redirect_url = Utils::get_string( $settings, 'redirectURL' );
            if ( $redirect_on_copy === true && !empty( $redirect_url ) ) {
                $attr['copy-redirect'] = $redirect_url;
            }
        }

        $button_settings = $this->get_settings( $attributes, 'button' );
        if ( !empty( $button_settings ) ) {
            $button_layout = Utils::get_string( $button_settings, 'layout', 'icon-only' );
            if ( !empty( $button_layout ) ) {
                $attr['button-layout'] = $button_layout;
            }
        }

        $font_family_classnames = FooConvert::plugin()->components->get_font_family_classnames( $attributes, [ 'styles', 'code.styles', 'button.styles' ] );
        if ( !empty( $font_family_classnames ) ) {
            $attr['class'] = implode( ' ', $font_family_classnames );
        }
        return $attr;
    }

    function get_frontend_styles( string $instance_id, array $attributes, WP_Block $block ): array {

        $components = FooConvert::plugin()->components;

        $host = array();
        $host_styles = $this->get_styles( $attributes );
        if ( !empty( $host_styles ) ) {
            $host = array_merge(
                $host,
                $components->get_styles( $host_styles )
            );
        }
        $host_settings = $this->get_settings( $attributes );
        if ( !empty( $host_settings ) ) {
            $host_text_align = Utils::get_string( $host_settings, 'textAlign' );
            if ( !empty( $host_text_align ) ) {
                $host['text-align'] = $host_text_align;
            }
        }

        $code = array();
        $code_text = array();
        $code_styles = $this->get_styles( $attributes, 'code' );
        if ( !empty( $code_styles ) ) {
            $inner_padding = Utils::get_key( $code_styles, 'innerPadding' );
            if ( !empty( $inner_padding ) ) {
                $code_text = array_merge(
                    $code_text,
                    $components->box_unit_control->get_styles( $inner_padding, 'padding' )
                );
            }

            $code = array_merge(
                $code,
                $components->get_styles( $code_styles )
            );
        }
        $code_settings = $this->get_settings( $attributes, 'code' );
        if ( !empty( $code_settings ) ) {
            $code_text_align = Utils::get_string( $code_settings, 'textAlign' );
            if ( !empty( $code_text_align ) ) {
                $code['text-align'] = $code_text_align;
            }
        }

        $button = array();
        $button_styles = $this->get_styles( $attributes, 'button' );
        if ( !empty( $button_styles ) ) {
            $button = array_merge(
                $button,
                $components->get_styles( $button_styles, array(
                    'background' => array( Utils::class, 'get_css_background_property' ),
                    'text'       => 'color',
                    'icon'       => '--icon-color'
                ) )
            );
        }

        $styles = [];
        if ( count( $host ) > 0 ) {
            $styles["#$instance_id"] = $host;
        }
        if ( count( $code ) > 0 ) {
            $styles["#$instance_id::part(code)"] = $code;
        }
        if ( count( $code_text ) > 0 ) {
            $styles["#$instance_id::part(code__text)"] = $code_text;
        }
        if ( count( $button ) > 0 ) {
            $styles["#$instance_id::part(copy-button)"] = $button;
        }
        return $styles;
    }

    public function get_frontend_icons( string $instance_id, array $attributes, WP_Block $block ): array {
        $icons = [];

        $button_settings = $this->get_settings( $attributes, 'button' );
        $button_layout = Utils::get_string( $button_settings, 'layout', 'icon-only' );
        if ( in_array( $button_layout, [ 'icon-only', 'icon-text', 'text-icon' ] ) ) {
            $icon_settings = Utils::get_array( $button_settings, 'icon' );
            $icon = $this->get_settings_icon( $icon_settings, 'copy-button__icon', 'default__copy' );
            if ( !empty( $icon ) ) {
                $icons[] = $icon;
            }
        }

        return $icons;
    }
}
