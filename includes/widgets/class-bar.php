<?php

namespace FooPlugins\FooConvert\Widgets;

use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Widgets\Base\Base_Widget;
use FooPlugins\FooConvert\Utils;
use WP_Block;

class Bar extends Base_Widget {

    public function kses_definition() : array {
        return array(
            $this->get_tag_name() => array(
                'id' => true,
                'class' => true,
                'open' => true,
                'transitions' => true,
                'page-push' => true,
                'top' => true,
                'bottom' => true,
                'button-toggle' => true,
                'button-none' => true,
                'button-left' => true,
                'button-right' => true,
            )
        );
    }

    /**
     * @inheritDoc
     */
    function get_post_type() : string {
        return 'fc-bar';
    }

    function get_block_name() : string {
        return 'fc/bar';
    }

    function get_tag_name() : string {
        return 'fc-bar';
    }

    function register_blocks() {
        $post_type = $this->get_post_type();
        return Utils::register_post_type_blocks( $post_type, array(
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/bar/block.json',
                'args' => array( 'render_callback' => array( $this, 'render' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/bar/editor/button/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_empty' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/bar/editor/content/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_content' ) )
            )
        ) );
    }

    /**
     * @inheritDoc
     */
    function register_post_type() {
        return register_post_type( $this->get_post_type(), array(
            'labels' => array(
                'name' => __( 'Bars', 'fooconvert' ),
                'singular_name' => __( 'Bar', 'fooconvert' )
            ),
            'has_archive' => false,
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_in_menu' => FOOCONVERT_MENU_SLUG,
            'supports' => [ 'title', 'editor', 'author', 'custom-fields' ],
            'template' => array(
                array( $this->get_block_name() )
            ),
            'template_lock' => 'all'
        ) );
    }

    /**
     * @inheritDoc
     */
    function get_editor_variations() : array {
        return array(
            array(
                'name' => 'empty',
                'title' => __( 'Empty', 'fooconvert' ),
                'description' => __( 'Empty', 'fooconvert' ),
                'attributes' => array(
                    'variation' => 'empty'
                ),
                'innerBlocks' => array(
                    array( 'fc/bar-button' ),
                    array(
                        'fc/bar-content',
                        array(),
                        array(
                            array( 'core/paragraph' )
                        )
                    )
                ),
                'scope' => array( 'block' )
            ),
            array(
                'slug' => 'basic',
                'title' => __( 'Basic', 'fooconvert' ),
                'description' => __( 'A basic bar with minimal styling.', 'fooconvert' ),
                'icon' => '',
                'attributes' => array(
                    'styles' => array(
                        'color' => array(
                            'background' => 'linear-gradient(335deg,rgb(238,238,238) 0%,rgb(169,184,195) 100%)'
                        ),
                        'border' => array(
                            'top' => array(
                                'style' => 'none',
                                'width' => '0px'
                            ),
                            'right' => array(
                                'style' => 'none',
                                'width' => '0px'
                            ),
                            'bottom' => array(
                                'color' => '#abb8c3',
                                'style' => 'solid',
                                'width' => '2px'
                            ),
                            'left' => array(
                                'style' => 'none',
                                'width' => '0px'
                            )
                        )
                    ),
                    'button' => array(
                        'styles' => array(
                            'dimensions' => array(
                                'margin' => '16px'
                            )
                        )
                    ),
                    'trigger' => array(
                        'type' => 'immediate'
                    ),
                    'lockTrigger' => false
                ),
                'innerBlocks' => array(
                    array(
                        'fc/bar-button',
                        array(),
                        array()
                    ),
                    array(
                        'fc/bar-content',
                        array(),
                        array(
                            array(
                                'core/paragraph',
                                array(
                                    'content' => 'Enter your message here!',
                                    'dropCap' => false
                                ),
                                array()
                            )
                        )
                    )
                ),
                'scope' => array(
                    'block'
                )
            )
        );
    }

    public function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ) : array {
        $attr = array();

        $trigger = Utils::get_array( $attributes, 'trigger' );
        if ( ! empty( $trigger ) ) {
            $trigger_type = Utils::get_string( $trigger, 'type' );
            if ( $trigger_type === 'immediate' ) {
                $attr['open'] = '';
            }
        }

        $transitions = Utils::get_bool( $attributes, 'transitions' );
        if ( ! empty( $transitions ) ) {
            $attr['transitions'] = '';
        }

        $page_push = Utils::get_bool( $attributes, 'pagePush' );
        if ( ! empty( $page_push ) ) {
            $attr['page-push'] = '';
        }

        $position = Utils::get_string( $attributes, 'position', 'top' );
        if ( $position !== 'top' ) {
            $attr[ $position ] = '';
        }

        $hide_button = Utils::get_bool( $attributes, 'hideButton' );
        if ( $hide_button ) {
            $attr['button-none'] = '';
        } else {
            $button = Utils::get_array( $attributes, 'button' );
            if ( ! empty( $button ) ) {
                $button_position = Utils::get_string( $button, 'position', 'right' );
                if ( $button_position !== 'right' ) {
                    $attr["button-$button_position"] = '';
                }
                $button_action = Utils::get_string( $button, 'action', 'close' );
                if ( $button_action === 'toggle' ) {
                    $attr['button-toggle'] = '';
                }
            }
        }

        return $attr;
    }

    public function get_frontend_data( string $instance_id, array $attributes, WP_Block $block ) : array {
        $data = array();
        $post_id = Utils::get_int( $attributes, 'postId' );
        if ( ! empty( $post_id ) ) {
            $data['postId'] = $post_id;
        }

        $trigger = Utils::get_array( $attributes, 'trigger' );
        if ( ! empty( $trigger ) ) {
            $data = array_merge(
                $data,
                FooConvert::plugin()->components->open_trigger_panel->get_data( $trigger )
            );
        }

        $close_anchor = Utils::get_string( $attributes, 'closeAnchor' );
        if ( ! empty( $close_anchor ) ) {
            $data['closeAnchor'] = $close_anchor;
        }

        return $data;
    }

    public function get_frontend_styles( string $instance_id, array $attributes, WP_Block $block ) : array {

        $components = FooConvert::plugin()->components;

        $root = array();
        $content = array();
        $styles_attribute = Utils::get_array( $attributes, 'styles' );
        if ( ! empty( $styles_attribute ) ) {
            $border = Utils::get_array( $styles_attribute, 'border' );
            if ( ! empty( $border ) ) {
                $content = array_merge( $content, $components->border_tools_panel->get_styles( $border ) );
            }
            $color = Utils::get_array( $styles_attribute, 'color' );
            if ( ! empty( $color ) ) {
                $root = array_merge( $root, $components->color_tools_panel->get_styles( $color, array(
                    'text' => 'color'
                ) ) );
                $content = array_merge( $content, $components->color_tools_panel->get_styles( $color, array(
                    'background' => 'background'
                ) ) );
            }
            $dimensions = Utils::get_array( $styles_attribute, 'dimensions' );
            if ( ! empty( $dimensions ) ) {
                $content = array_merge(
                    $content,
                    $components->dimensions_tools_panel->get_padding_styles( $dimensions ),
                    $components->dimensions_tools_panel->get_gap_styles( $dimensions )
                );

                list( 'left' => $margin_left, 'right' => $margin_right ) = $components->dimensions_tools_panel->get_margin_sizes( $dimensions );
                $root = array_merge(
                    $root,
                    $components->dimensions_tools_panel->get_margin_styles( $dimensions ),
                    array(
                        'width' => "calc(100% - $margin_left - $margin_right)",
                    )
                );

            }
            $typography = Utils::get_array( $styles_attribute, 'typography' );
            if ( ! empty( $typography ) ) {
                $root = array_merge( $root, $components->typography_tools_panel->get_styles( $typography ) );
            }
        }

        $button = array();
        $button_attribute = Utils::get_array( $attributes, 'button' );
        if ( ! empty( $button_attribute ) ) {
            $button_styles_attribute = Utils::get_array( $button_attribute, 'styles' );
            if ( ! empty( $button_styles_attribute ) ) {
                $button = array_merge( $button, $components->get_styles( $button_styles_attribute, '', array(
                    'background' => 'background',
                    'icon' => 'color'
                ) ) );
            }

            $button_icon = Utils::get_array( $button_attribute, 'icon' );
            if ( ! empty( $button_icon ) ) {
                $icon_size = Utils::get_string( $button_icon, 'size' );
                if ( ! empty( $icon_size ) ) {
                    $button['font-size'] = $icon_size;
                }
            }
        }

        $styles = array();
        if ( count( $root ) > 0 ) {
            $styles["#$instance_id"] = $root;
        }
        if ( count( $content ) > 0 ) {
            $styles["#$instance_id::part(content)"] = $content;
        }
        if ( count( $button ) > 0 ) {
            $styles["#$instance_id::part(button)"] = $button;
        }
        return $styles;
    }

    public function get_frontend_icons( string $instance_id, array $attributes, WP_Block $block ) : array {
        $icons = [];
        $button = Utils::get_array( $attributes, 'button' );
        if ( !empty( $button ) ) {
            $icon = Utils::get_array( $button, 'icon' );
            if ( !empty( $icon ) ) {
                $icon_close  = Utils::get_array( $icon, 'close' );
                if ( !empty( $icon_close ) ) {
                    $icon_close_svg = Utils::get_string( $icon_close, 'svg' );
                    if ( ! empty( $icon_close_svg ) ) {
                        $icons[] = $icon_close_svg;
                    }
                }

                $icon_open  = Utils::get_array( $icon, 'open' );
                if ( !empty( $icon_open ) ) {
                    $icon_open_svg = Utils::get_string( $icon_open, 'svg' );
                    if ( ! empty( $icon_open_svg ) ) {
                        $icons[] = $icon_open_svg;
                    }
                }
            }
        }
        return $icons;
    }
}