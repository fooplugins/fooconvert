<?php

namespace FooPlugins\FooConvert\Widgets;

use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Widgets\Base\BaseWidget;
use FooPlugins\FooConvert\Utils;
use WP_Block;

class Popup extends BaseWidget {

    public function kses_definition() : array {
        return array(
            $this->get_tag_name() => array(
                'id' => true,
                'class' => true,
                'open' => true,
                'transitions' => true,
                'position' => true,
                'max-on-mobile' => true,
                'hide-scrollbar' => true,
                'close-button' => true
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
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/popup/editor/blocks/container/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_content' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/popup/editor/blocks/container/blocks/close-button/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_empty' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/popup/editor/blocks/container/blocks/content/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_check_compatibility' ) )
            )
        ) );
    }

    /**
     * @inheritDoc
     */
    function register_post_type() {
        return register_post_type( $this->get_post_type(), array(
            'labels' => array(
                'name' => __( 'Popups', 'fooconvert' ),
                'singular_name' => __( 'Popup', 'fooconvert' ),
                'add_new' => __( 'Add Popup', 'fooconvert' ),
                'add_new_item' => __( 'Add New Popup', 'fooconvert' ),
                'edit_item' => __( 'Edit Popup', 'fooconvert' ),
                'new_item' => __( 'New Popup', 'fooconvert' ),
                'view_item' => __( 'View Popups', 'fooconvert' ),
                'search_items' => __( 'Search Popups', 'fooconvert' ),
                'not_found' => __( 'No Popups found', 'fooconvert' ),
                'not_found_in_trash' => __( 'No Popups found in Trash', 'fooconvert' ),
                'all_items' => __( 'Popups', 'fooconvert' )
            ),
            'has_archive' => false,
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_in_menu' => false,
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
        return apply_filters('fooconvert_editor_variations-' . $this->get_post_type(), array(
            array(
                'name' => 'empty',
                'title' => __( 'Empty', 'fooconvert' ),
                'description' => __( 'A blank slate that you can use to build your own popup from scratch.', 'fooconvert' ),
                'attributes' => array(
                    'template' => 'empty'
                ),
                'innerBlocks' => array(
                    array(
                        'fc/popup-container',
                        array(),
                        array(
                            array( 'fc/popup-close-button' ),
                            array( 'fc/popup-content' )
                        )
                    )
                ),
                'scope' => array( 'block' )
            ),
            array(
                'name' => 'black_friday_popup',
                'title' => __( 'Black Friday Popup', 'fooconvert' ),
                'description' => __( 'A typical Black Friday popup to help drive sales.', 'fooconvert' ),
                'icon' => '',
                'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/black_friday_popup.png',
                'attributes' => array(
                    'settings' => array(
                        'transitions' => true,
                        'hideScrollbar' => true,
                        'maxOnMobile' => true,
                        'trigger' => array(
                            'type' => 'exit-intent',
                            'data' => 5
                        ),
                        'backdropIgnore' => false
                    ),
                    'closeButton' => array(
                        'settings' => array(
                            'icon' => array(
                                'slug' => 'default__close-small',
                                'size' => '48px'
                            )
                        )
                    ),
                    'content' => array(
                        'styles' => array(
                            'color' => array(
                                'background' => 'linear-gradient(135deg,rgb(6,147,227) 0%,rgb(157,85,225) 100%)'
                            ),
                            'border' => array(
                                'radius' => '18px',
                                'color' => '#111111',
                                'style' => 'solid',
                                'width' => '3px'
                            ),
                            'width' => '720px',
                            'dimensions' => array(
                                'padding' => '30px',
                            )
                        )
                    )
                ),
                'innerBlocks' => array(
                    array(
                        'fc/popup-container',
                        array(),
                        array(
                            array(
                                'fc/popup-close-button',
                                array(),
                                array(),
                            ),
                            array(
                                'fc/popup-content',
                                array(),
                                array(
                                    array(
                                        'core/heading',
                                        array(
                                            'textAlign' => 'center',
                                            'content' => 'WELCOME TO BLACK FRIDAY',
                                            'level' => 2,
                                            'className' => 'is-style-default',
                                            'fontFamily' => 'body'
                                        ),
                                        array()
                                    ),
                                    array(
                                        'core/paragraph',
                                        array(
                                            'content' => '<strong>🔥crazy deals are finally here 🔥</strong>',
                                            'dropCap' => false,
                                            'align' => 'center'
                                        ),
                                        array()
                                    ),
                                    array(
                                        'core/paragraph',
                                        array(
                                            'content' => '<strong>LIMITED STOCK</strong>!',
                                            'dropCap' => false,
                                            'align' => 'center'
                                        ),
                                        array()
                                    ),
                                    array(
                                        'core/paragraph',
                                        array(
                                            'content' => '⚡<strong>Act fast!</strong>⚡',
                                            'dropCap' => false,
                                            'align' => 'center'
                                        ),
                                        array()
                                    ),
                                    array(
                                        'core/buttons',
                                        array(
                                            'layout' => array(
                                                'type' => 'flex',
                                                'justifyContent' => 'center'
                                            )
                                        ),
                                        array(
                                            array(
                                                'core/button',
                                                array(
                                                    'tagName' => 'a',
                                                    'type' => 'button',
                                                    'url' => '/shop',
                                                    'text' => 'Save 70%',
                                                    'style' => array(
                                                        'border' => array(
                                                            'radius' => '54px'
                                                        )
                                                    ),
                                                    'anchor' => 'cta',
                                                    'textAlign' => 'center'
                                                ),
                                                array()
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                'scope' => array(
                    'block'
                )
            ),
        ) );
    }

    public function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ) : array {

        $attr = array();
        $settings = Utils::get_array( $attributes, 'settings' );
        if ( ! empty( $settings ) ) {
            $transitions = Utils::get_bool( $settings, 'transitions' );
            if ( ! empty( $transitions ) ) {
                $attr['transitions'] = '';
            }

            $max_on_mobile = Utils::get_bool( $settings, 'maxOnMobile' );
            if ( $max_on_mobile ) {
                $attr['max-on-mobile'] = '';
            }

            $hide_scrollbar = Utils::get_bool( $settings, 'hideScrollbar' );
            if ( $hide_scrollbar ) {
                $attr['hide-scrollbar'] = '';
            }
        }

        $close_button = Utils::get_array( $attributes, 'closeButton' );
        if ( ! empty( $close_button ) ) {
            $close_button_settings = Utils::get_array( $close_button, 'settings' );
            if ( ! empty( $close_button_settings ) ) {
                $close_button_hidden = Utils::get_bool( $close_button_settings, 'hidden' );
                if ( $close_button_hidden ) {
                    $attr['close-button'] = 'none';
                } else {
                    $close_button_position = Utils::get_string( $close_button_settings, 'position', 'right' );
                    if ( $close_button_position !== 'right' ) {
                        $attr['close-button'] = $close_button_position;
                    }
                }
            }
        }

        return $attr;
    }

    public function get_frontend_data( string $instance_id, array $attributes, WP_Block $block ) : array {
        $data = array(
            'postType' => $this->get_post_type(),
        );
        $post_id = Utils::get_int( $attributes, 'postId' );
        if ( ! empty( $post_id ) ) {
            $data['postId'] = $post_id;
        }
        $template = Utils::get_string( $attributes, 'template' );
        if ( ! empty( $template ) ) {
            $data['template'] = $template;
        }

        $settings = Utils::get_array( $attributes, 'settings' );
        if ( ! empty( $settings ) ) {
            $trigger = Utils::get_array( $settings, 'trigger' );
            if ( ! empty( $trigger ) ) {
                $data = array_merge(
                    $data,
                    FooConvert::plugin()->components->open_trigger_panel->get_data( $trigger )
                );
            }

            $close_anchor = Utils::get_string( $settings, 'closeAnchor' );
            if ( ! empty( $close_anchor ) ) {
                $data['closeAnchor'] = $close_anchor;
            }

            $backdrop_ignore = Utils::get_bool( $settings, 'backdropIgnore' );
            if ( ! empty( $backdrop_ignore ) ) {
                $data['backdropIgnore'] = $backdrop_ignore;
            }

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

        $content = array();
        $content_attribute = Utils::get_array( $attributes, 'content' );
        if ( ! empty( $content_attribute ) ) {
            $content_styles_attribute = Utils::get_array( $content_attribute, 'styles' );
            if ( ! empty( $content_styles_attribute ) ) {
                $content = array_merge( $content, $components->get_styles( $content_styles_attribute, '', array(
                    'background' => 'background',
                    'text' => 'color'
                ) ) );

                $content_width = Utils::get_string( $content_styles_attribute, 'width', '720px' );
                if ( ! empty( $content_width ) && $content_width !== '720px' ) {
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

        $styles = array();
        if ( count( $root ) > 0 ) {
            $styles["#$instance_id"] = $root;
        }
        if ( count( $content ) > 0 ) {
            $styles["#$instance_id::part(content)"] = $content;
        }
        if ( count( $close_button ) > 0 ) {
            $styles["#$instance_id::part(close-button)"] = $close_button;
        }
        return $styles;
    }

    public function get_frontend_icons( string $instance_id, array $attributes, WP_Block $block ) : array {
        $icons = [];
        $close_icon_slug = Utils::get_key_path( $attributes, 'closeButton.settings.icon.slug' );
        if ( ! empty( $close_icon_slug ) ) {
            $close_icon = $this->get_frontend_icon( $close_icon_slug, 'close-button__icon' );
            if ( ! empty( $close_icon ) ) {
                $icons[] = $close_icon;
            }
        }
        return $icons;
    }
}