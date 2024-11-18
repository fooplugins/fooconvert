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
                'position' => true,
                'button-position' => true,
                'close-button' => true,
                'open-button' => true
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
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/bar/editor/blocks/open-button/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_empty' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/bar/editor/blocks/container/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_content' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/bar/editor/blocks/container/blocks/close-button/block.json',
                'args' => array( 'render_callback' => array( $this, 'render_empty' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'widgets/bar/editor/blocks/container/blocks/content/block.json',
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
                'name' => __( 'Bars', 'foobar' ),
                'singular_name' => __( 'Bar', 'foobar' ),
                'add_new' => __( 'Add Bar', 'foobar' ),
                'add_new_item' => __( 'Add New Bar', 'foobar' ),
                'edit_item' => __( 'Edit Bar', 'foobar' ),
                'new_item' => __( 'New Bar', 'foobar' ),
                'view_item' => __( 'View Bars', 'foobar' ),
                'search_items' => __( 'Search Bars', 'foobar' ),
                'not_found' => __( 'No Bars found', 'foobar' ),
                'not_found_in_trash' => __( 'No Bars found in Trash', 'foobar' ),
                'all_items' => __( 'Bars', 'foobar' )
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
        return array(
            array(
                'name' => 'empty',
                'title' => __( 'Empty', 'fooconvert' ),
                'description' => __( 'Empty', 'fooconvert' ),
                'attributes' => array(
                    'template' => 'empty'
                ),
                'innerBlocks' => array(
                    array( 'fc/bar-open-button' ),
                    array(
                        'fc/bar-container',
                        array(),
                        array(
                            array( 'fc/bar-close-button' ),
                            array( 'fc/bar-content' )
                        )
                    )
                ),
                'scope' => array( 'block' )
            ),
            array(
                'name' => 'black_friday_bar',
                'title' => __( 'Black Friday Bar', 'fooconvert' ),
                'description' => __( 'A typical Black Friday bar to help drive sales.', 'fooconvert' ),
                'icon' => '',
                'attributes' => array(
                    'viewState' => 'open',
                    'settings' => array(
                        'trigger' => array(
                            'type' => 'timer',
                            'data' => 3
                        ),
                        'transitions' => true
                    ),
                    'openButton' => array(
                        'settings' => array(
                            'hidden' => true
                        )
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
                            'dimensions' => array(
                                'margin' => '5px',
                                'padding' => '3px',
                                'gap' => '16px'
                            )
                        )
                    ),
                    'template' => 'black_friday_bar'
                ),
                'innerBlocks' => array(
                    array(
                        'fc/bar-open-button',
                        array(),
                        array()
                    ),
                    array(
                        'fc/bar-container',
                        array(),
                        array(
                            array(
                                'fc/bar-close-button',
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
                                            'content' => '<strong>🔥Black Friday deals are finally here - LIMITED STOCK - act fast!</strong>⚡',
                                            'dropCap' => false
                                        ),
                                        array()
                                    ),
                                    array(
                                        'core/buttons',
                                        array(),
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
                                                    'anchor' => 'cta'
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
            array(
                'name' => 'cookie_consent_bar',
                'title' => __( 'Cookie Consent Bar', 'fooconvert' ),
                'description' => __( 'A simple bottom bar that is dismissed when the button is clicked.', 'fooconvert' ),
                'icon' => '',
                'attributes' => array(
                    'viewState' => 'open',
                    'template' => 'cookie_consent_bar',
                    'settings' => array(
                        'position' => 'bottom',
                        'transitions' => true,
                        'trigger' => array(
                            'type' => 'immediate'
                        ),
                        'closeAnchor' => 'accept'
                    ),
                    'openButton' => array(
                        'settings' => array(
                            'hidden' => true
                        )
                    ),
                    'closeButton' => array(
                        'settings' => array(
                            'hidden' => true
                        )
                    ),
                    'content' => array(
                        'styles' => array(
                            'color' => array(
                                'background' => '#76736e',
                                'text' => '#ffffff'
                            ),
                            'border' => array(
                                'radius' => '0px',
                                'style' => 'none',
                                'width' => '0px'
                            ),
                            'dimensions' => array(
                                'margin' => '0px',
                                'gap' => '16px',
                                'padding' => '0px'
                            )
                        )
                    ),
                    'styles' => array(
                        'dimensions' => array(
                            'padding' => '0px'
                        )
                    )
                ),
                'innerBlocks' => array(
                    array(
                        'fc/bar-open-button',
                        array(),
                        array()
                    ),
                    array(
                        'fc/bar-container',
                        array(),
                        array(
                            array(
                                'fc/bar-close-button',
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
                                            'content' => '🍪 by continuing, you consent to our use of cookies',
                                            'dropCap' => false
                                        ),
                                        array()
                                    ),
                                    array(
                                        'core/buttons',
                                        array(),
                                        array(
                                            array(
                                                'core/button',
                                                array(
                                                    'tagName' => 'a',
                                                    'type' => 'button',
                                                    'text' => 'Accept',
                                                    'className' => 'is-style-outline',
                                                    'fontSize' => 'small',
                                                    'anchor' => 'accept',
                                                    'style' => array(
                                                        'border' => array(
                                                            'width' => '2px'
                                                        ),
                                                        'spacing' => array(
                                                            'padding' => array(
                                                                'top' => '3px',
                                                                'bottom' => '3px'
                                                            )
                                                        )
                                                    )
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
            )
        );
    }

    public function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ) : array {
        $attr = array();

        $settings = Utils::get_array( $attributes, 'settings' );
        if ( ! empty( $settings ) ) {
            $transitions = Utils::get_bool( $settings, 'transitions' );
            if ( ! empty( $transitions ) ) {
                $attr['transitions'] = '';
            }

            $position = Utils::get_string( $settings, 'position', 'top' );
            if ( $position !== 'top' ) {
                $attr['position'] = $position;
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

        $open_button = Utils::get_array( $attributes, 'openButton' );
        if ( ! empty( $open_button ) ) {
            $open_button_settings = Utils::get_array( $open_button, 'settings' );
            if ( ! empty( $open_button_settings ) ) {
                $open_button_hidden = Utils::get_bool( $open_button_settings, 'hidden' );
                if ( $open_button_hidden ) {
                    $attr['open-button'] = 'none';
                } else {
                    $open_button_position = Utils::get_string( $open_button_settings, 'position', 'right' );
                    if ( $open_button_position !== 'right' ) {
                        $attr['open-button'] = $open_button_position;
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
        $close_icon_slug = Utils::get_key_path( $attributes, 'closeButton.settings.icon.slug' );
        if ( ! empty( $close_icon_slug ) ) {
            $close_icon = $this->get_frontend_icon( $close_icon_slug, 'close-button__icon' );
            if ( ! empty( $close_icon ) ) {
                $icons[] = $close_icon;
            }
        }
        $open_icon_slug = Utils::get_key_path( $attributes, 'openButton.settings.icon.slug' );
        if ( ! empty( $open_icon_slug ) ) {
            $open_icon = $this->get_frontend_icon( $open_icon_slug, 'open-button__icon' );
            if ( ! empty( $open_icon ) ) {
                $icons[] = $open_icon;
            }
        }
        return $icons;
    }
}