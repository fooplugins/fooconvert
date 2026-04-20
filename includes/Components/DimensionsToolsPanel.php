<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class DimensionsToolsPanel.
 */
class DimensionsToolsPanel extends BaseComponent {
    private BoxUnitControl $box_unit_control;

    /**
     * Initializes the DimensionsToolsPanel.
     */
    function __construct( BoxUnitControl $box_unit_control ) {
        parent::__construct();
        $this->box_unit_control = $box_unit_control;
    }

    /**
     * Returns the padding styles.
     */
    function get_padding_styles( array $value ): array {
        $padding = Utils::get_key( $value, 'padding' );
        if ( !empty( $padding ) ) {
            return $this->box_unit_control->get_styles( $padding, 'padding' );
        }
        return array();
    }

    /**
     * Returns the padding sizes.
     */
    function get_padding_sizes( array $value, array $defaults = array() ): array {
        $padding = Utils::get_key( $value, 'padding' );
        return $this->box_unit_control->get_sizes( $padding, $defaults );
    }

    /**
     * Returns the margin styles.
     */
    function get_margin_styles( array $value ): array {
        $margin = Utils::get_key( $value, 'margin' );
        if ( !empty( $margin ) ) {
            return $this->box_unit_control->get_styles( $margin, 'margin' );
        }
        return array();
    }

    /**
     * Returns the margin sizes.
     */
    function get_margin_sizes( array $value, array $defaults = array() ): array {
        $margin = Utils::get_key( $value, 'margin' );
        return $this->box_unit_control->get_sizes( $margin, $defaults );
    }

    /**
     * Returns the gap styles.
     */
    function get_gap_styles( array $value ): array {
        $gap = Utils::get_string( $value, 'gap' );
        if ( !empty( $gap ) ) {
            return array( 'gap' => $gap );
        }
        return array();
    }

    /**
     * Returns the styles.
     */
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