<?php

namespace FooPlugins\FooConvert\Blocks;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Utils;
use WP_Block;

/**
 * Dynamic renderer for the sign-up block.
 */
class SignUp extends BaseBlock {

    /**
     * Returns the KSES schema for the sign-up custom element.
     *
     * @return array<string,array<string,bool>>
     */
    public function kses_definition(): array {
        return array(
            $this->get_tag_name() => array(
                'id'    => true,
                'class' => true,
                'layout' => true,
                'email-only' => true,
                'no-labels' => true,
                'stack-labels' => true,
                'button-layout' => true,
                'name-placeholder' => true,
                'email-placeholder' => true,
                'success-close' => true,
            )
        );
    }

    /**
     * Returns the block name used during registration.
     *
     * @return string
     */
    function get_block_name(): string {
        return 'fc/sign-up';
    }

    /**
     * Returns the custom element tag rendered on the frontend.
     *
     * @return string
     */
    function get_tag_name(): string {
        return 'fc-sign-up';
    }

    /**
     * Registers the sign-up block for the popup editor post type.
     *
     * @return false|array
     */
    function register_blocks() {
        return Utils::register_post_type_blocks( FOOCONVERT_CPT_POPUP, array(
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'blocks/sign-up/block.json',
                'args'           => array(
                    'render_callback' => array( $this, 'render' )
                )
            )
        ) );
    }

    /**
     * Injects slot content for sign-up labels and button text.
     *
     * @param array    $attributes The current block attributes.
     * @param string   $content Unused inner block content.
     * @param WP_Block $block The current block instance.
     * @return string
     */
    function render( array $attributes, string $content, WP_Block $block ) {
        $content = '';

        $inputs_settings = $this->get_settings( $attributes, 'inputs' );
        $name_label = Utils::get_string( $inputs_settings, 'nameLabel', __( 'Name', 'fooconvert' ) );
        if ( !empty( $name_label ) ) {
            $content .= sprintf( '<span slot="input-label__name">%s</span>', $name_label );
        }
        $email_label = Utils::get_string( $inputs_settings, 'emailLabel', __( 'Email', 'fooconvert' ) );
        if ( !empty( $email_label ) ) {
            $content .= sprintf( '<span slot="input-label__email">%s</span>', $email_label );
        }

        $button_settings = $this->get_settings( $attributes, 'button' );
        $button_layout = Utils::get_string( $button_settings, 'layout', 'text-only' );
        $button_show_text = in_array( $button_layout, [ 'text-only', 'icon-text', 'text-icon' ] );
        if ( $button_show_text === true ) {
            $button_text = Utils::get_string( $button_settings, 'text', __( 'Submit', 'fooconvert' ) );
            if ( !empty( $button_text ) ) {
                $content .= sprintf( '<span slot="submit-button__text">%s</span>', $button_text );
            }
        }

        return parent::render(
            $attributes,
            $content,
            $block
        );
    }

    /**
     * Returns frontend data used by the sign-up interaction script.
     *
     * @param string   $instance_id The block instance ID.
     * @param array    $attributes The current block attributes.
     * @param WP_Block $block The current block instance.
     * @return array<string,mixed>
     */
    public function get_frontend_data( string $instance_id, array $attributes, WP_Block $block ): array {
        $data = array();

        $settings = $this->get_settings( $attributes );
        $success_message = Utils::get_string( $settings, 'successMessage', __( 'Thanks!', 'fooconvert' ) );
        if ( !empty( $success_message ) ) {
            $data['successMessage'] = $success_message;
        }

        $inputs_settings = $this->get_settings( $attributes, 'inputs' );
        $name_placeholder = Utils::get_string( $inputs_settings, 'namePlaceholder' );
        if ( !empty( $name_placeholder ) ) {
            $data['namePlaceholder'] = $name_placeholder;
        }
        $email_placeholder = Utils::get_string( $inputs_settings, 'emailPlaceholder' );
        if ( !empty( $email_placeholder ) ) {
            $data['emailPlaceholder'] = $email_placeholder;
        }

        return $data;
    }

    /**
     * Returns the frontend attributes applied to the sign-up element.
     *
     * @param string   $instance_id The block instance ID.
     * @param array    $attributes The current block attributes.
     * @param WP_Block $block The current block instance.
     * @return array<string,mixed>
     */
    function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ): array {
        $attr = array();
        $settings = $this->get_settings( $attributes );
        if ( !empty( $settings ) ) {
            $layout = Utils::get_string( $settings, 'layout' );
            if ( ! empty( $layout ) ) {
                $attr['layout'] = $layout;
            }
            $close_on_success = Utils::get_bool( $settings, 'closeOnSuccess' );
            if ( $close_on_success === true ) {
                $attr['success-close'] = '';
            }
        }
        $inputs_settings = $this->get_settings( $attributes, 'inputs' );
        if ( !empty( $inputs_settings ) ) {
            $email_only = Utils::get_bool( $inputs_settings, 'emailOnly' );
            if ( $email_only === true ) {
                $attr['email-only'] = '';
            }

            $no_labels = Utils::get_bool( $inputs_settings, 'noLabels' );
            if ( $no_labels === true ) {
                $attr['no-labels'] = '';
            } else {
                $stack_labels = Utils::get_bool( $inputs_settings, 'stackLabels' );
                if ( $stack_labels === true ) {
                    $attr['stack-labels'] = '';
                }
            }

            $name_placeholder = Utils::get_string( $settings, 'namePlaceholder' );
            if ( !empty( $name_placeholder ) ) {
                $attr['name-placeholder'] = $name_placeholder;
            }

            $email_placeholder = Utils::get_string( $settings, 'emailPlaceholder' );
            if ( !empty( $email_placeholder ) ) {
                $attr['email-placeholder'] = $email_placeholder;
            }
        }

        $button_settings = $this->get_settings( $attributes, 'button' );
        if ( !empty( $button_settings ) ) {
            $button_layout = Utils::get_string( $button_settings, 'layout', 'text-only' );
            if ( !empty( $button_layout ) ) {
                $attr['button-layout'] = $button_layout;
            }
        }

        $font_family_classnames = FooConvert::plugin()->components->get_font_family_classnames( $attributes, [ 'styles', 'inputs.styles', 'button.styles' ] );
        if ( !empty( $font_family_classnames ) ) {
            $attr['class'] = implode( ' ', $font_family_classnames );
        }
        return $attr;
    }

    /**
     * Builds the frontend style rules for the sign-up block.
     *
     * @param string   $instance_id The block instance ID.
     * @param array    $attributes The current block attributes.
     * @param WP_Block $block The current block instance.
     * @return array<string,array<string,mixed>>
     */
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

        $inputs = array();
        $inputs_styles = $this->get_styles( $attributes, 'inputs' );
        if ( !empty( $inputs_styles ) ) {
            $inputs = array_merge(
                $inputs,
                $components->get_styles( $inputs_styles, array(
                    /**
                     * Class get_settings.
                     */
                    'background' => array( Utils::class, 'get_css_background_property' ),
                    'text'       => 'color',
                    'placeholder' => '--placeholder-color'
                ) )
            );
        }

        $buttons = array();
        $button = array();

        $button_settings = $this->get_settings( $attributes, 'button' );
        if ( !empty( $button_settings ) ) {
            $button_justify = Utils::get_string( $button_settings, 'justify', 'center' );
            if ( $button_justify !== 'center' ) {
                $buttons['justify-content'] = $button_justify;
            }
            $button_width = Utils::get_string( $button_settings, 'width', 'fit-content' );
            if ( $button_width !== 'fit-content' ) {
                $button['width'] = $button_width;
            }
        }

        $button_styles = $this->get_styles( $attributes, 'button' );
        if ( !empty( $button_styles ) ) {
            $button = array_merge(
                $button,
                $components->get_styles( $button_styles, array(
                    /**
                     * Class count.
                     */
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
        if ( count( $inputs ) > 0 ) {
            $styles["#$instance_id::part(input)"] = $inputs;
        }
        if ( count( $buttons ) > 0 ) {
            $styles["#$instance_id::part(buttons)"] = $buttons;
        }
        if ( count( $button ) > 0 ) {
            $styles["#$instance_id::part(submit-button)"] = $button;
        }
        return $styles;
    }

    /**
     * Returns the icons required by the sign-up block.
     *
     * @param string   $instance_id The block instance ID.
     * @param array    $attributes The current block attributes.
     * @param WP_Block $block The current block instance.
     * @return array<int,array<string,mixed>>
     */
    public function get_frontend_icons( string $instance_id, array $attributes, WP_Block $block ): array {
        $icons = [];

        $button_settings = $this->get_settings( $attributes, 'button' );
        if ( !empty( $button_settings ) ) {
            $button_layout = Utils::get_string( $button_settings, 'layout', 'text-only' );
            $show_icon = in_array( $button_layout, [ 'icon-only', 'icon-text', 'text-icon' ] );
            if ( $show_icon === true ) {
                $icon_settings = Utils::get_array( $button_settings, 'icon' );
                $icon = $this->get_settings_icon( $icon_settings, 'submit-button__icon', 'default__send' );
                if ( !empty( $icon ) ) {
                    $icons[] = $icon;
                }
            }
        }

        return $icons;
    }
}
