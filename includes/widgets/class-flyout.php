<?php

namespace FooPlugins\FooConvert\Widgets;

use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Widgets\Base\Base_Widget;
use FooPlugins\FooConvert\Utils;
use WP_Block;

class Flyout extends Base_Widget {

    public function kses_definition() : array {
        return array(
            $this->get_tag_name() => array(
                'id' => true,
                'class' => true,
                'open' => true,
                'transitions' => true,
                'position' => true,
                'max-on-mobile' => true,
//                'left-top' => true,
//                'left-center' => true,
//                'left-bottom' => true,
//                'right-top' => true,
//                'right-center' => true,
//                'right-bottom' => true,
                'button-none' => true,
                'button-left' => true,
                'button-right' => true,
                'button-inside' => true,
                'button-corner' => true,
                'button-outside' => true,
            )
        );
    }

    /**
     * @inheritDoc
     */
    function get_post_type() : string {
        return 'fc-flyout';
    }

    function get_block_name() : string {
        return 'fc/flyout';
    }

    function get_tag_name() : string {
        return 'fc-flyout';
    }

    function register_blocks() {
        $post_type = $this->get_post_type();
        return Utils::register_post_type_blocks( $post_type, array(
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/flyout/block.json',
                'args' => array( 'render_callback' => array( $this, 'render' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/flyout/editor/blocks/open-button/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_empty' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/flyout/editor/blocks/container/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_content' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/flyout/editor/blocks/container/blocks/close-button/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_empty' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/flyout/editor/blocks/container/blocks/content/block.json',
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
                'name' => __( 'Flyouts', 'foobar' ),
                'singular_name' => __( 'Flyout', 'foobar' ),
                'add_new' => __( 'Add Flyout', 'foobar' ),
                'add_new_item' => __( 'Add New Flyout', 'foobar' ),
                'edit_item' => __( 'Edit Flyout', 'foobar' ),
                'new_item' => __( 'New Flyout', 'foobar' ),
                'view_item' => __( 'View Flyouts', 'foobar' ),
                'search_items' => __( 'Search Flyouts', 'foobar' ),
                'not_found' => __( 'No Flyouts found', 'foobar' ),
                'not_found_in_trash' => __( 'No Flyouts found in Trash', 'foobar' ),
                'all_items' => __( 'Flyouts', 'foobar' )
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
                    array( 'fc/flyout-open-button' ),
                    array(
                        'fc/flyout-container',
                        array(),
                        array(
                            array( 'fc/flyout-close-button' ),
                            array(
                                'fc/flyout-content',
                                array(),
                                array(
                                    array( 'core/paragraph' )
                                )
                            )
                        )
                    )
                ),
                'scope' => array( 'block' )
            )
        );
    }

    public function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ) : array {
        $attr = array();

        $settings = Utils::get_array( $attributes, 'settings' );
        if ( ! empty( $settings ) ) {
            $trigger = Utils::get_array( $settings, 'trigger' );
            if ( ! empty( $trigger ) ) {
                $trigger_type = Utils::get_string( $trigger, 'type' );
                if ( $trigger_type === 'immediate' ) {
                    $attr['open'] = '';
                }
            }

            $transitions = Utils::get_bool( $settings, 'transitions' );
            if ( ! empty( $transitions ) ) {
                $attr['transitions'] = '';
            }

            $position = Utils::get_string( $settings, 'position', 'right-center' );
            if ( $position !== 'right-center' ) {
                $attr['position'] = $position;
            }

            $max_on_mobile = Utils::get_bool( $settings, 'maxOnMobile' );
            if ( $max_on_mobile ) {
                $attr['max-on-mobile'] = '';
            }
        }

        $close_button = Utils::get_array( $attributes, 'closeButton' );
        if ( ! empty( $close_button ) ) {
            $close_button_position = Utils::get_string( $close_button, 'position', 'right' );
            if ( $close_button_position !== 'right' ) {
                $attr["button-$close_button_position"] = '';
            }
            $close_button_alignment = Utils::get_string( $close_button, 'alignment', 'inside' );
            if ( $close_button_alignment !== 'inside' ) {
                $attr["button-$close_button_alignment"] = '';
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
        $styles_attribute = Utils::get_array( $attributes, 'styles' );
        if ( ! empty( $styles_attribute ) ) {
            $root = array_merge( $root, $components->get_styles( $styles_attribute ) );
        }

        $container = array();
        $container_attribute = Utils::get_array( $attributes, 'container' );
        if ( ! empty( $container_attribute ) ) {
            $container_styles_attribute = Utils::get_array( $container_attribute, 'styles' );
            if ( ! empty( $container_styles_attribute ) ) {
                $container = array_merge( $container, $components->get_styles( $container_styles_attribute ) );
            }
        }

        $content = array();
        $content_attribute = Utils::get_array( $attributes, 'content' );
        if ( ! empty( $content_attribute ) ) {
            $content_styles_attribute = Utils::get_array( $content_attribute, 'styles' );
            if ( ! empty( $content_styles_attribute ) ) {
                $content = array_merge( $content, $components->get_styles( $content_styles_attribute, '', array(
                    'background' => 'background',
                    'text' => 'color'
                ) ) );

                $content_width = Utils::get_string( $content_styles_attribute, 'width', '480px' );
                if ( ! empty( $content_width ) && $content_width !== '480px' ) {
                    $content['width'] = $content_width;
                }
            }
        }

        $close_button = array();
        $close_button_attribute = Utils::get_array( $attributes, 'closeButton' );
        if ( ! empty( $close_button_attribute ) ) {
            $close_button_styles_attribute = Utils::get_array( $close_button_attribute, 'styles' );
            if ( ! empty( $close_button_styles_attribute ) ) {
                $close_button = array_merge( $close_button, $components->get_styles( $close_button_styles_attribute, '', array(
                    'background' => 'background',
                    'icon' => 'color'
                ) ) );
            }

            $close_button_settings = Utils::get_array( $close_button_attribute, 'settings' );
            if ( ! empty( $close_button_settings ) ) {
                $close_button_icon = Utils::get_array( $close_button_settings, 'icon' );
                if ( ! empty( $close_button_icon ) ) {
                    $close_button_icon_size = Utils::get_string( $close_button_icon, 'size' );
                    if ( ! empty( $close_button_icon_size ) ) {
                        $close_button['font-size'] = $close_button_icon_size;
                    }
                }
            }
        }

        $open_button = array();
        $open_button_attribute = Utils::get_array( $attributes, 'openButton' );
        if ( ! empty( $open_button_attribute ) ) {
            $open_button_styles_attribute = Utils::get_array( $open_button_attribute, 'styles' );
            if ( ! empty( $open_button_styles_attribute ) ) {
                $open_button = array_merge( $open_button, $components->get_styles( $open_button_styles_attribute, '', array(
                    'background' => 'background',
                    'icon' => 'color'
                ) ) );
            }

            $open_button_settings = Utils::get_array( $open_button_attribute, 'settings' );
            if ( ! empty( $open_button_settings ) ) {
                $open_button_icon = Utils::get_array( $open_button_settings, 'icon' );
                if ( ! empty( $open_button_icon ) ) {
                    $open_button_icon_size = Utils::get_string( $open_button_icon, 'size' );
                    if ( ! empty( $open_button_icon_size ) ) {
                        $open_button['font-size'] = $open_button_icon_size;
                    }
                }
            }
        }

        $styles = array();
        if ( count( $root ) > 0 ) {
            $styles["#$instance_id"] = $root;
        }
        if ( count( $container ) > 0 ) {
            $styles["#$instance_id::part(container)"] = $container;
        }
        if ( count( $content ) > 0 ) {
            $styles["#$instance_id::part(content)"] = $content;
        }
        if ( count( $close_button ) > 0 ) {
            $styles["#$instance_id::part(close-button)"] = $close_button;
        }
        if ( count( $open_button ) > 0 ) {
            $styles["#$instance_id::part(open-button)"] = $open_button;
        }
        return $styles;
    }

    public function get_frontend_icons( string $instance_id, array $attributes, WP_Block $block ) : array {
        $icons = [];
        $close_button = Utils::get_array( $attributes, 'closeButton' );
        if ( ! empty( $close_button ) ) {
            $close_button_settings = Utils::get_array( $close_button, 'settings' );
            if ( ! empty( $close_button_settings ) ) {
                $close_button_icon = Utils::get_array( $close_button_settings, 'icon' );
                if ( ! empty( $close_button_icon ) ) {
                    $close_button_icon_close = Utils::get_array( $close_button_icon, 'close' );
                    if ( ! empty( $close_button_icon_close ) ) {
                        $close_button_icon_close_svg = Utils::get_string( $close_button_icon_close, 'svg' );
                        if ( ! empty( $close_button_icon_close_svg ) ) {
                            $icons[] = $close_button_icon_close_svg;
                        }
                    }
                }
            }
        }
        $open_button = Utils::get_array( $attributes, 'openButton' );
        if ( ! empty( $open_button ) ) {
            $open_button_settings = Utils::get_array( $open_button, 'settings' );
            if ( ! empty( $open_button_settings ) ) {
                $open_button_icon = Utils::get_array( $open_button_settings, 'icon' );
                if ( ! empty( $open_button_icon ) ) {
                    $open_button_icon_open = Utils::get_array( $open_button_icon, 'open' );
                    if ( ! empty( $open_button_icon_open ) ) {
                        $open_button_icon_open_svg = Utils::get_string( $open_button_icon_open, 'svg' );
                        if ( ! empty( $open_button_icon_open_svg ) ) {
                            $icons[] = $open_button_icon_open_svg;
                        }
                    }
                }
            }
        }
        return $icons;
    }
}