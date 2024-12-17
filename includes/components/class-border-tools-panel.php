<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\Base_Component;
use FooPlugins\FooConvert\Utils;

class Border_Tools_Panel extends Base_Component {
    private Border_Control $border_control;
    private Border_Radius_Control $border_radius_control;

    function __construct( Border_Control $border_control, Border_Radius_Control $border_radius_control ) {
        parent::__construct();
        $this->border_control = $border_control;
        $this->border_radius_control = $border_radius_control;
    }

    function get_styles( array $value, string $prefix = '', bool $style_required = true ) : array {
        $styles = array();
        $shadow = Utils::get_string( $value, 'shadow' );
        if ( !empty( $shadow ) ) {
            $styles["{$prefix}box-shadow"] = $shadow;
        }
        $radius = Utils::get_key( $value, 'radius' );
        if ( !empty( $radius ) ) {
            $styles = array_merge(
                $styles,
                $this->border_radius_control->get_styles( $radius, $prefix )
            );
        }
        return array_merge(
            $styles,
            $this->border_control->get_styles( $value, $prefix, $style_required )
        );
    }
}