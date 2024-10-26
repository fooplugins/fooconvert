<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Components\Border_Control;
use FooPlugins\FooConvert\Components\Border_Radius_Control;
use FooPlugins\FooConvert\Components\Border_Tools_Panel;
use FooPlugins\FooConvert\Components\Box_Unit_Control;
use FooPlugins\FooConvert\Components\Color_Tools_Panel;
use FooPlugins\FooConvert\Components\Dimensions_Tools_Panel;
use FooPlugins\FooConvert\Components\Open_Trigger_Panel;
use FooPlugins\FooConvert\Components\Typography_Tools_Panel;

/**
 * This class contains utility classes and methods to make dynamic block rendering a bit simpler by providing
 * PHP helpers for the various JavaScript components used to build up the block.
 *
 * An instance of this class is exposed as the `FooConvert::plugin()->components` property.
 */
class FooConvert_Components {

    /**
     * Create a new instance of this class.
     */
    function __construct() {
        // individual controls
        $this->border_control = new Border_Control();
        $this->border_radius_control = new Border_Radius_Control();
        $this->box_unit_control = new Box_Unit_Control();

        // composite controls
        $this->border_tools_panel = new Border_Tools_Panel( $this->border_control, $this->border_radius_control );
        $this->color_tools_panel = new Color_Tools_Panel();
        $this->dimensions_tools_panel = new Dimensions_Tools_Panel( $this->box_unit_control );
        $this->open_trigger_panel = new Open_Trigger_Panel();
        $this->typography_tools_panel = new Typography_Tools_Panel();
    }

    public Border_Control $border_control;
    public Border_Radius_Control $border_radius_control;
    public Box_Unit_Control $box_unit_control;

    public Border_Tools_Panel $border_tools_panel;
    public Color_Tools_Panel $color_tools_panel;
    public Dimensions_Tools_Panel $dimensions_tools_panel;
    public Open_Trigger_Panel $open_trigger_panel;
    public Typography_Tools_Panel $typography_tools_panel;

    function get_styles( array $styles_attribute, string $prefix = '', array $color_map = array() ) : array {
        $styles = array();
        if ( ! empty( $styles_attribute ) ) {
            $border = Utils::get_array( $styles_attribute, 'border' );
            if ( !empty( $border ) ) {
                $styles = array_merge( $styles, $this->border_tools_panel->get_styles( $border, $prefix ) );
            }
            $color = Utils::get_array( $styles_attribute, 'color' );
            if ( !empty( $color ) ) {
                $styles = array_merge( $styles, $this->color_tools_panel->get_styles( $color, !empty( $color_map ) ? $color_map : $prefix ) );
            }
            $dimensions = Utils::get_array( $styles_attribute, 'dimensions' );
            if ( !empty( $dimensions ) ) {
                $styles = array_merge( $styles, $this->dimensions_tools_panel->get_styles( $dimensions, $prefix ) );
            }
            $typography = Utils::get_array( $styles_attribute, 'typography' );
            if ( !empty( $typography ) ) {
                $styles = array_merge( $styles, $this->typography_tools_panel->get_styles( $typography, $prefix ) );
            }
        }
        return $styles;
    }
}