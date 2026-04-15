<?php

namespace FooPlugins\FooConvert\Blocks;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use FooPlugins\FooConvert\FooConvert;
use FooPlugins\FooConvert\Utils;
use WP_Block;

/**
 * Dynamic renderer for the countdown block.
 */
class Countdown extends BaseBlock {

    /**
     * Returns the KSES schema for the countdown custom element.
     *
     * @return array<string,array<string,bool>>
     */
    public function kses_definition(): array {
        return array(
            $this->get_tag_name() => array(
                'id'    => true,
                'class' => true,
                'layout' => true,
                'mode' => true,
                'fomo' => true,
                'value' => true,
                'expire-close' => true,
                'min-digits' => true,
            )
        );
    }

    /**
     * Returns the block name used during registration.
     *
     * @return string
     */
    function get_block_name(): string {
        return 'fc/countdown';
    }

    /**
     * Returns the custom element tag rendered on the frontend.
     *
     * @return string
     */
    function get_tag_name(): string {
        return 'fc-countdown';
    }

    /**
     * Registers the countdown block for the popup editor post type.
     *
     * @return false|array
     */
    function register_blocks() {
        return Utils::register_popup_blocks( array(
            array(
                'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'blocks/countdown/block.json',
                'args'           => array(
                    'render_callback' => array( $this, 'render' )
                )
            )
        ) );
    }

    /**
     * Injects slot content for the configured countdown labels.
     *
     * @param array    $attributes The current block attributes.
     * @param string   $content Unused inner block content.
     * @param WP_Block $block The current block instance.
     * @return string
     */
    function render( array $attributes, string $content, WP_Block $block ) {
        $content = '';

        $segment_settings = $this->get_settings( $attributes, 'segment' );
        $days_text = Utils::get_string( $segment_settings, 'daysText', __( 'Days', 'fooconvert' ) );
        if ( !empty( $days_text ) ) {
            $content .= sprintf( '<span slot="segment-text__days">%s</span>', $days_text );
        }
        $hours_text = Utils::get_string( $segment_settings, 'hoursText', __( 'Hours', 'fooconvert' ) );
        if ( !empty( $hours_text ) ) {
            $content .= sprintf( '<span slot="segment-text__hours">%s</span>', $hours_text );
        }
        $minutes_text = Utils::get_string( $segment_settings, 'minutesText', __( 'Minutes', 'fooconvert' ) );
        if ( !empty( $minutes_text ) ) {
            $content .= sprintf( '<span slot="segment-text__minutes">%s</span>', $minutes_text );
        }
        $seconds_text = Utils::get_string( $segment_settings, 'secondsText', __( 'Seconds', 'fooconvert' ) );
        if ( !empty( $seconds_text ) ) {
            $content .= sprintf( '<span slot="segment-text__seconds">%s</span>', $seconds_text );
        }

        return parent::render(
            $attributes,
            $content,
            $block
        );
    }

    /**
     * Returns frontend data for the countdown block.
     *
     * @param string   $instance_id The block instance ID.
     * @param array    $attributes The current block attributes.
     * @param WP_Block $block The current block instance.
     * @return array<string,mixed>
     */
    public function get_frontend_data( string $instance_id, array $attributes, WP_Block $block ): array {
        return array();
    }

    /**
     * Returns the frontend attributes applied to the countdown element.
     *
     * @param string   $instance_id The block instance ID.
     * @param array    $attributes The current block attributes.
     * @param WP_Block $block The current block instance.
     * @return array<string,mixed>
     */
    function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ): array {
        $attr = array();
        $settings = $this->get_settings( $attributes );
        $mode = Utils::get_string( $settings, 'mode', 'fomo' );
        if ( $mode === 'fomo' ) {
            $fomo_value = Utils::get_int( $settings, 'fomoValue', 10 );
            if ( ! empty( $fomo_value ) ) {
                $attr['fomo'] = $fomo_value;
            }
            $expiry = Utils::get_string( $settings, 'expiry', 'session' );
            if ( $expiry === 'persist' ) {
                $attr['persist'] = '';
            }
        } else {
            $specific_value = Utils::get_string( $settings, 'specificValue' );
            if ( ! empty( $specific_value ) ) {
                $attr['value'] = $specific_value;
            }
        }
        if ( !empty( $settings ) ) {
            $close_on_expire = Utils::get_bool( $settings, 'closeOnExpire' );
            if ( $close_on_expire === true ) {
                $attr['expire-close'] = '';
            }
        }
        $segment_settings = $this->get_settings( $attributes, 'segment' );
        if ( !empty( $segment_settings ) ) {
            $segment_layout = Utils::get_string( $segment_settings, 'layout', 'stack' );
            if ( $segment_layout !== 'stack' ) {
                $attr['layout'] = $segment_layout;
            }
            $segment_pad_digits = Utils::get_bool( $segment_settings, 'padDigits' );
            if ( ! empty( $segment_pad_digits ) ) {
                $attr['min-digits'] = 2;
            }
        }
        $font_family_classnames = FooConvert::plugin()->components->get_font_family_classnames( $attributes, [ 'styles', 'segment.styles', 'digits.styles' ] );
        if ( !empty( $font_family_classnames ) ) {
            $attr['class'] = implode( ' ', $font_family_classnames );
        }
        return $attr;
    }

    /**
     * Builds the frontend style rules for the countdown block.
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

        $segments = array();
        $segment_styles = $this->get_styles( $attributes, 'segment' );
        if ( !empty( $segment_styles ) ) {
            $segments = array_merge(
                $segments,
                $components->get_styles( $segment_styles, array(
                    /**
                     * Class get_styles.
                     */
                    'background' => array( Utils::class, 'get_css_background_property' ),
                    'text'       => 'color',
                    'digits' => '--digits-color'
                ) )
            );
        }

        $digits = array();
        $digits_styles = $this->get_styles( $attributes, 'digits' );
        if ( !empty( $digits_styles ) ) {
            $digits = array_merge(
                $digits,
                $components->get_styles( $digits_styles )
            );
        }

        $styles = [];
        if ( count( $host ) > 0 ) {
            $styles["#$instance_id"] = $host;
        }
        if ( count( $segments ) > 0 ) {
            $styles["#$instance_id::part(segment)"] = $segments;
        }
        if ( count( $digits ) > 0 ) {
            $styles["#$instance_id::part(segment-value)"] = $digits;
        }
        return $styles;
    }

    /**
     * Returns any font utility classes required by the countdown block.
     *
     * @param string   $instance_id The block instance ID.
     * @param array    $attributes The current block attributes.
     * @param WP_Block $block The current block instance.
     * @return string
     */
    function get_font_classes( string $instance_id, array $attributes, WP_Block $block ): string {

        $font_slugs = array();
        $styles = $this->get_styles( $attributes );
        $styles_font = Utils::get_string( $styles, 'typography.fontName' );
        $segment_styles = $this->get_styles( $attributes, 'segment' );

        return implode( ' ', $font_slugs );
    }
}
