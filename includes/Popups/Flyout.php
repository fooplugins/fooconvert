<?php

namespace FooPlugins\FooConvert\Popups;

use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Popups\Base\BasePopup;
use FooPlugins\FooConvert\Utils;
use WP_Block;

/**
 * Class Flyout.
 */
class Flyout extends BasePopup {

    /**
     * Handles kses definition.
     */
    public function kses_definition(): array {
        return array(
            $this->get_tag_name() => array(
                'id'            => true,
                'class'         => true,
                'open'          => true,
                'transitions'   => true,
                'position'      => true,
                'max-on-mobile' => true,
                'close-button'  => true,
                'open-button'   => true
            )
        );
    }

    /**
     * @inheritDoc
     */
    function get_post_type(): string {
        return 'fc-flyout';
    }

    /**
     * Returns the block name.
     */
    function get_block_name(): string {
        return 'fc/flyout';
    }

    /**
     * Returns the tag name.
     */
    function get_tag_name(): string {
        return 'fc-flyout';
    }

    /**
     * Registers blocks.
     */
    function register_blocks() {
        return Utils::register_popup_blocks( array(
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'popups/flyout/block.json',
                'args'           => array( 'render_callback' => array( $this, 'render' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'popups/flyout/editor/blocks/open-button/block.json',
                'args'           => array( 'render_callback' => array( $this, 'render_empty' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'popups/flyout/editor/blocks/container/block.json',
                'args'           => array( 'render_callback' => array( $this, 'render_content' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'popups/flyout/editor/blocks/container/blocks/close-button/block.json',
                'args'           => array( 'render_callback' => array( $this, 'render_empty' ) )
            ),
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'popups/flyout/editor/blocks/container/blocks/content/block.json',
                'args'           => array( 'render_callback' => array( $this, 'render_check_compatibility' ) )
            )
        ) );
    }

    /**
     * @inheritDoc
     */
    function get_editor_variations(): array {
        $variations = apply_filters( 'fooconvert_editor_variations-' . $this->get_post_type(), array(
            array(
                'name'        => 'empty_flyout',
                'title'       => __( 'Empty', 'fooconvert' ),
                'description' => __( 'A blank slate that you can use to build your own flyout from scratch.', 'fooconvert' ),
                'thumbnail'   => FOOCONVERT_ASSETS_URL . 'media/templates/template__blank.png',
                'picker'      => array(
                    'category'     => 'blank',
                    'tags'         => array( 'blank' ),
                    'availability' => 'included',
                ),
                'attributes'  => array(
                    'openButton'  => array(
                        'styles' => array(
                            'border'     => array(
                                'radius' => '4px',
                                'color'  => '#DDDDDD',
                                'style'  => 'solid',
                                'width'  => '1px'
                            )
                        )
                    ),
                    'content'     => array(
                        'styles' => array(
                            'border'     => array(
                                'radius' => '4px',
                                'color'  => '#DDDDDD',
                                'style'  => 'solid',
                                'width'  => '1px'
                            )
                        )
                    ),
                    'template' => 'empty_flyout'
                ),
                'innerBlocks' => array(
                    array( 'fc/flyout-open-button' ),
                    array(
                        'fc/flyout-container',
                        array(),
                        array(
                            array( 'fc/flyout-close-button' ),
                            array( 'fc/flyout-content' )
                        )
                    )
                ),
                'scope'       => array( 'block' )
            ),
            array(
                'name'        => 'black_friday_flyout',
                'title'       => __( 'Black Friday Flyout', 'fooconvert' ),
                'description' => __( 'A typical Black Friday flyout to help drive sales.', 'fooconvert' ),
                'thumbnail'   => FOOCONVERT_ASSETS_URL . 'media/templates/template__black_friday.png',
                'picker'      => array(
                    'category'     => 'promotion',
                    'tags'         => array( 'seasonal', 'offer' ),
                    'availability' => 'included',
                    'preview'      => FOOCONVERT_ASSETS_URL . 'media/templates/fullsize/template__black_friday.png',
                ),
                'attributes'  => array(
                    'viewState'   => 'open',
                    'settings'    => array(
                        'trigger'     => array(
                            'version'   => 2,
                            'lifetime'  => 'page',
                            'frequency' => array(
                                'mode'            => 'once',
                                'cooldownSeconds' => 0
                            ),
                            'steps'     => array(
                                array(
                                    'event' => 'fc.scroll.percent',
                                    'where' => array(
                                        'percent' => 20
                                    )
                                )
                            )
                        ),
                        'transitions' => true
                    ),
                    'openButton'  => array(
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
                    'content'     => array(
                        'styles' => array(
                            'color'      => array(
                                'background' => 'linear-gradient(135deg,rgb(6,147,227) 0%,rgb(157,85,225) 100%)'
                            ),
                            'border'     => array(
                                'radius' => '18px',
                                'color'  => '#111111',
                                'style'  => 'solid',
                                'width'  => '3px'
                            ),
                            'dimensions' => array(
                                'margin'  => '10px',
                                'padding' => '30px',
                                'gap'     => '16px'
                            ),
                            'width'      => '480px'
                        )
                    ),
                    'template'    => 'black_friday_flyout'
                ),
                'innerBlocks' => array(
                    array(
                        'fc/flyout-open-button',
                        array(),
                        array()
                    ),
                    array(
                        'fc/flyout-container',
                        array(),
                        array(
                            array(
                                'fc/flyout-close-button',
                                array(),
                                array()
                            ),
                            array(
                                'fc/flyout-content',
                                array(),
                                array(
                                    array(
                                        'core/heading',
                                        array(
                                            'textAlign'  => 'center',
                                            'content'    => 'WELCOME TO<br>BLACK FRIDAY',
                                            'level'      => 2,
                                            'className'  => 'is-style-default',
                                            'fontFamily' => 'body'
                                        ),
                                        array()
                                    ),
                                    array(
                                        'core/paragraph',
                                        array(
                                            'content' => '<strong>🔥crazy deals are finally here 🔥</strong>',
                                            'dropCap' => false,
                                            'align'   => 'center'
                                        ),
                                        array()
                                    ),
                                    array(
                                        'core/paragraph',
                                        array(
                                            'content' => '<strong>LIMITED STOCK</strong>!',
                                            'dropCap' => false,
                                            'align'   => 'center'
                                        ),
                                        array()
                                    ),
                                    array(
                                        'core/paragraph',
                                        array(
                                            'content' => '⚡<strong>Act fast!</strong>⚡',
                                            'dropCap' => false,
                                            'align'   => 'center'
                                        ),
                                        array()
                                    ),
                                    array(
                                        'core/buttons',
                                        array(
                                            'layout' => array(
                                                'type'           => 'flex',
                                                'justifyContent' => 'center'
                                            )
                                        ),
                                        array(
                                            array(
                                                'core/button',
                                                array(
                                                    'tagName'   => 'a',
                                                    'type'      => 'button',
                                                    'url'       => '/shop',
                                                    'text'      => 'Save 70%',
                                                    'style'     => array(
                                                        'border' => array(
                                                            'radius' => '54px'
                                                        )
                                                    ),
                                                    'anchor'    => 'cta',
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
                'scope'       => array(
                    'block'
                )
            )
        ) );

        return FooConvert::plugin()->components->variation_picker->prepare_variations( $variations );
    }

    /**
     * Returns the frontend attributes.
     */
    public function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ): array {
        $attr = array();

        $settings = Utils::get_array( $attributes, 'settings' );
        if ( !empty( $settings ) ) {
            $transitions = Utils::get_bool( $settings, 'transitions' );
            if ( !empty( $transitions ) ) {
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
        if ( !empty( $close_button ) ) {
            $close_button_settings = Utils::get_array( $close_button, 'settings' );
            if ( !empty( $close_button_settings ) ) {
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
        if ( !empty( $open_button ) ) {
            $open_button_settings = Utils::get_array( $open_button, 'settings' );
            if ( !empty( $open_button_settings ) ) {
                $open_button_hidden = Utils::get_bool( $open_button_settings, 'hidden' );
                if ( $open_button_hidden ) {
                    $attr['open-button'] = 'none';
                }
            }
        }

        return $attr;
    }

    /**
     * Returns the frontend data.
     */
    public function get_frontend_data( string $instance_id, array $attributes, WP_Block $block ): array {
        $data = array(
            'postType' => $this->get_post_type(),
        );
        $post_id = Utils::get_int( $attributes, 'postId' );
        if ( !empty( $post_id ) ) {
            $data['postId'] = $post_id;
        }
        $template = Utils::get_string( $attributes, 'template' );
        if ( !empty( $template ) ) {
            $data['template'] = $template;
        }

        $settings = Utils::get_array( $attributes, 'settings' );
        if ( !empty( $settings ) ) {
            $trigger = Utils::get_array( $settings, 'trigger' );
            if ( !empty( $trigger ) ) {
                $data = array_merge(
                    $data,
                    FooConvert::plugin()->components->open_trigger_panel->get_data( $trigger )
                );
            }

            $close_anchor = Utils::get_string( $settings, 'closeAnchor' );
            if ( !empty( $close_anchor ) ) {
                $data['closeAnchor'] = $close_anchor;
            }
        }

        return $data;
    }

    /**
     * Returns the frontend styles.
     */
    public function get_frontend_styles( string $instance_id, array $attributes, WP_Block $block ): array {

        $components = FooConvert::plugin()->components;

        $root = array();
        $styles_attribute = Utils::get_array( $attributes, 'styles' );
        if ( !empty( $styles_attribute ) ) {
            $root = array_merge( $root, $components->get_styles( $styles_attribute ) );
        }

        $container = array();
        $container_attribute = Utils::get_array( $attributes, 'container' );
        if ( !empty( $container_attribute ) ) {
            $container_styles_attribute = Utils::get_array( $container_attribute, 'styles' );
            if ( !empty( $container_styles_attribute ) ) {
                $container = array_merge( $container, $components->get_styles( $container_styles_attribute ) );
            }
        }

        $content = array();
        $content_attribute = Utils::get_array( $attributes, 'content' );
        if ( !empty( $content_attribute ) ) {
            $content_styles_attribute = Utils::get_array( $content_attribute, 'styles' );
            if ( !empty( $content_styles_attribute ) ) {
                $content = array_merge( $content, $components->get_styles( $content_styles_attribute ) );

                $content_width = Utils::get_string( $content_styles_attribute, 'width', '480px' );
                if ( !empty( $content_width ) && $content_width !== '480px' ) {
                    $content['width'] = $content_width;
                }
            }
        }

        $close_button = array();
        $close_button_attribute = Utils::get_array( $attributes, 'closeButton' );
        if ( !empty( $close_button_attribute ) ) {
            $close_button_styles_attribute = Utils::get_array( $close_button_attribute, 'styles' );
            if ( !empty( $close_button_styles_attribute ) ) {
                $close_button = array_merge( $close_button, $components->get_styles( $close_button_styles_attribute, array(
                    /**
                     * Class Utils.
                     */
                    'background' => array( Utils::class, 'get_css_background_property' ),
                    'icon'       => 'color'
                ) ) );
            }

            $close_button_settings = Utils::get_array( $close_button_attribute, 'settings' );
            if ( !empty( $close_button_settings ) ) {
                $close_button_icon = Utils::get_array( $close_button_settings, 'icon' );
                if ( !empty( $close_button_icon ) ) {
                    $close_button_icon_size = Utils::get_string( $close_button_icon, 'size' );
                    if ( !empty( $close_button_icon_size ) ) {
                        $close_button['font-size'] = $close_button_icon_size;
                    }
                }
            }
        }

        $open_button = array();
        $open_button_attribute = Utils::get_array( $attributes, 'openButton' );
        if ( !empty( $open_button_attribute ) ) {
            $open_button_styles_attribute = Utils::get_array( $open_button_attribute, 'styles' );
            if ( !empty( $open_button_styles_attribute ) ) {
                $open_button = array_merge( $open_button, $components->get_styles( $open_button_styles_attribute, array(
                    /**
                     * Class Utils.
                     */
                    'background' => array( Utils::class, 'get_css_background_property' ),
                    'icon'       => 'color'
                ) ) );
            }

            $open_button_settings = Utils::get_array( $open_button_attribute, 'settings' );
            if ( !empty( $open_button_settings ) ) {
                $open_button_icon = Utils::get_array( $open_button_settings, 'icon' );
                if ( !empty( $open_button_icon ) ) {
                    $open_button_icon_size = Utils::get_string( $open_button_icon, 'size' );
                    if ( !empty( $open_button_icon_size ) ) {
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

    /**
     * Returns the frontend icons.
     */
    public function get_frontend_icons( string $instance_id, array $attributes, WP_Block $block ): array {
        $icons = [];
        $close_icon_slug = Utils::get_key_path( $attributes, 'closeButton.settings.icon.slug' );
        if ( !empty( $close_icon_slug ) ) {
            $close_icon = $this->get_frontend_icon( $close_icon_slug, 'close-button__icon' );
            if ( !empty( $close_icon ) ) {
                $icons[] = $close_icon;
            }
        }
        $open_icon_slug = Utils::get_key_path( $attributes, 'openButton.settings.icon.slug' );
        if ( !empty( $open_icon_slug ) ) {
            $open_icon = $this->get_frontend_icon( $open_icon_slug, 'open-button__icon' );
            if ( !empty( $open_icon ) ) {
                $icons[] = $open_icon;
            }
        }
        return $icons;
    }
}
