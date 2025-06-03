<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

class DimensionsToolsPanel extends BaseComponent {
    private BoxUnitControl $box_unit_control;

    function __construct( BoxUnitControl $box_unit_control ) {
        parent::__construct();
        $this->box_unit_control = $box_unit_control;
    }

    function get_padding_styles( array $value ): array {
        $padding = Utils::get_key( $value, 'padding' );
        if ( !empty( $padding ) ) {
            return $this->box_unit_control->get_styles( $padding, 'padding' );
        }
        return array();
    }

    function get_padding_sizes( array $value, array $defaults = array() ): array {
        $padding = Utils::get_key( $value, 'padding' );
        return $this->box_unit_control->get_sizes( $padding, $defaults );
    }

    function get_margin_styles( array $value ): array {
        $margin = Utils::get_key( $value, 'margin' );
        if ( !empty( $margin ) ) {
            return $this->box_unit_control->get_styles( $margin, 'margin' );
        }
        return array();
    }

    function get_margin_sizes( array $value, array $defaults = array() ): array {
        $margin = Utils::get_key( $value, 'margin' );
        return $this->box_unit_control->get_sizes( $margin, $defaults );
    }

    function get_gap_styles( array $value ): array {
        $gap = Utils::get_string( $value, 'gap' );
        if ( !empty( $gap ) ) {
            return array( 'gap' => $gap );
        }
        return array();
    }

    function get_styles( array $value ): array {
        $styles = array();
        $padding = $this->get_padding_styles( $value );
        if ( !empty( $padding ) ) {
            $styles = array_merge( $styles, $padding );
        }
        $margin = $this->get_margin_styles( $value );
        if ( !empty( $margin ) ) {
            $styles = array_merge( $styles, $margin );
        }
        $gap = $this->get_gap_styles( $value );
        if ( !empty( $gap ) ) {
            $styles = array_merge( $styles, $gap );
        }
        return $styles;
    }
}