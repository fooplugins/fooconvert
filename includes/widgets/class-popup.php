<?php

namespace FooPlugins\FooConvert\Widgets;

use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Widgets\Base\Base_Widget;
use FooPlugins\FooConvert\Utils;
use WP_Block;

class Popup extends Base_Widget {

    public function kses_definition() : array {
        return array(
            $this->get_tag_name() => array(
                'id' => true,
                'class' => true,
                'open' => true,
                'transitions' => true,
                'button-none' => true,
                'button-left' => true,
                'button-right' => true,
                'button-inside' => true,
                'button-corner' => true,
                'button-outside' => true,
                'hide-scrollbar' => true,
                'backdrop-ignore' => true,
            )
        );
    }

    /**
     * @inheritDoc
     */
    function get_post_type() : string {
        return 'fc-popup';
    }

    function get_block_name() : string {
        return 'fc/popup';
    }

    function get_tag_name() : string {
        return 'fc-popup';
    }

    function register_blocks() {
        $post_type = $this->get_post_type();
        return Utils::register_post_type_blocks( $post_type, array(
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/popup/block.json',
                'args' => array( 'render_callback' => array( $this, 'render' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/popup/editor/button/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_empty' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/popup/editor/content/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_content' ) )
            )
        ) );
    }

    /**
     * @inheritDoc
     */
    function register_post_type() {
        return register_post_type( $this->get_post_type(), array(
            'labels'        => array(
                'name'               => __( 'Popups', 'foobar' ),
                'singular_name'      => __( 'Popup', 'foobar' ),
                'add_new'            => __( 'Add Popup', 'foobar' ),
                'add_new_item'       => __( 'Add New Popup', 'foobar' ),
                'edit_item'          => __( 'Edit Popup', 'foobar' ),
                'new_item'           => __( 'New Popup', 'foobar' ),
                'view_item'          => __( 'View Popups', 'foobar' ),
                'search_items'       => __( 'Search Popups', 'foobar' ),
                'not_found'          => __( 'No Popups found', 'foobar' ),
                'not_found_in_trash' => __( 'No Popups found in Trash', 'foobar' ),
                'all_items'          => __( 'Popups', 'foobar' )
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
                    array( 'fc/popup-button' ),
                    array(
                        'fc/popup-content',
                        array(),
                        array(
                            array( 'core/paragraph' )
                        )
                    )
                ),
                'scope' => array( 'block' )
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

        $hide_scrollbar = Utils::get_bool( $attributes, 'hideScrollbar' );
        if ( ! empty( $hide_scrollbar ) ) {
            $attr['hide-scrollbar'] = '';
        }

        $backdrop_ignore = Utils::get_bool( $attributes, 'backdropIgnore' );
        if ( ! empty( $backdrop_ignore ) ) {
            $attr['backdrop-ignore'] = '';
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
                $button_alignment = Utils::get_string( $button, 'alignment', 'inside' );
                if ( $button_alignment !== 'inside' ) {
                    $attr["button-$button_alignment"] = '';
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
        $backdrop = array();
        $container = array();
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
                $backdrop = array_merge( $backdrop, $components->color_tools_panel->get_styles( $color, array(
                    'backdrop' => 'background'
                ) ) );
                $content = array_merge( $content, $components->color_tools_panel->get_styles( $color, array(
                    'background' => 'background'
                ) ) );
            }
            $dimensions = Utils::get_array( $styles_attribute, 'dimensions' );
            if ( ! empty( $dimensions ) ) {
                $content = array_merge(
                    $content,
                    $components->dimensions_tools_panel->get_padding_styles( $dimensions )
                );

                list( 'left' => $margin_left, 'right' => $margin_right ) = $components->dimensions_tools_panel->get_margin_sizes( $dimensions );
                $container = array_merge(
                    $container,
                    $components->dimensions_tools_panel->get_margin_styles( $dimensions ),
                    array(
                        'max-width' => "calc(100% - $margin_left - $margin_right)",
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
        if ( count( $backdrop ) > 0 ) {
            $styles["#$instance_id::part(backdrop)"] = $backdrop;
        }
        if ( count( $container ) > 0 ) {
            $styles["#$instance_id::part(container)"] = $container;
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
            }
        }
        return $icons;
    }
}