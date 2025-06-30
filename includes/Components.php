<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Components\BackgroundImagePanel;
use FooPlugins\FooConvert\Components\BorderControl;
use FooPlugins\FooConvert\Components\BorderRadiusControl;
use FooPlugins\FooConvert\Components\BorderToolsPanel;
use FooPlugins\FooConvert\Components\BoxUnitControl;
use FooPlugins\FooConvert\Components\ColorToolsPanel;
use FooPlugins\FooConvert\Components\DimensionsToolsPanel;
use FooPlugins\FooConvert\Components\OpenTriggerPanel;
use FooPlugins\FooConvert\Components\TypographyToolsPanel;

/**
 * This class contains utility classes and methods to make dynamic block rendering a bit simpler by providing
 * PHP helpers for the various JavaScript components used to build up the block.
 *
 * An instance of this class is exposed as the `FooConvert::plugin()->components` property.
 */
class Components {

    /**
     * Create a new instance of this class.
     */
    function __construct() {
        // individual controls
        $this->border_control = new BorderControl();
        $this->border_radius_control = new BorderRadiusControl();
        $this->box_unit_control = new BoxUnitControl();

        // composite controls
        $this->background_image_panel = new BackgroundImagePanel();
        $this->border_tools_panel = new BorderToolsPanel( $this->border_control, $this->border_radius_control );
        $this->color_tools_panel = new ColorToolsPanel();
        $this->dimensions_tools_panel = new DimensionsToolsPanel( $this->box_unit_control );
        $this->open_trigger_panel = new OpenTriggerPanel();
        $this->typography_tools_panel = new TypographyToolsPanel();
    }

    public BorderControl $border_control;
    public BorderRadiusControl $border_radius_control;
    public BoxUnitControl $box_unit_control;

    public BackgroundImagePanel $background_image_panel;
    public BorderToolsPanel $border_tools_panel;
    public ColorToolsPanel $color_tools_panel;
    public DimensionsToolsPanel $dimensions_tools_panel;
    public OpenTriggerPanel $open_trigger_panel;
    public TypographyToolsPanel $typography_tools_panel;

    function get_styles( array $styles_attribute, array $color_map = array() ): array {
        $styles = array();
        if ( !empty( $styles_attribute ) ) {
            $border = Utils::get_array( $styles_attribute, 'border' );
            if ( !empty( $border ) ) {
                $styles = array_merge( $styles, $this->border_tools_panel->get_styles( $border ) );
            }
            $background_and_color_styles = $this->get_background_and_color_styles( $styles_attribute, $color_map );
            if ( !empty( $background_and_color_styles ) ) {
                $styles = array_merge( $styles, $background_and_color_styles );
            }
            $dimensions = Utils::get_array( $styles_attribute, 'dimensions' );
            if ( !empty( $dimensions ) ) {
                $styles = array_merge( $styles, $this->dimensions_tools_panel->get_styles( $dimensions ) );
            }
            $typography = Utils::get_array( $styles_attribute, 'typography' );
            if ( !empty( $typography ) ) {
                $styles = array_merge( $styles, $this->typography_tools_panel->get_styles( $typography ) );
            }
        }
        return $styles;
    }

    function get_background_and_color_styles( array $styles_attribute, array $color_map = array() ): array {
        $styles = array();
        $color = Utils::get_array( $styles_attribute, 'color' );
        $color_styles = array();
        if ( !empty( $color ) ) {
            $color_styles = $this->color_tools_panel->get_styles( $color, $color_map );
            $styles = array_merge( $styles, $color_styles );
        }
        $background = Utils::get_array( $styles_attribute, 'background' );
        $background_styles = array();
        if ( !empty( $background ) ) {
            $background_styles = $this->background_image_panel->get_styles( $background );
            $styles = array_merge( $styles, $background_styles );
        }
        if ( array_key_exists( 'background-image', $color_styles ) && array_key_exists( 'background-image', $background_styles ) ) {
            $styles['background-image'] = $background_styles['background-image'] . ',' . $color_styles['background-image'];
        }
        return $styles;
    }

    function get_font_family_classnames( array $attributes, array $paths ): array {
        $classes = array();
        if ( !empty( $attributes ) ) {
            foreach ( $paths as $path ) {
                $styles_attribute = Utils::get_key_path( $attributes, $path );
                if ( !empty( $styles_attribute ) ) {
                    $typography = Utils::get_array( $styles_attribute, 'typography' );
                    if ( !empty( $typography ) ) {
                        $classname = $this->typography_tools_panel->get_font_family_classname( $typography );
                        if ( !empty( $classname ) && !in_array( $classname, $classes ) ) {
                            $classes[] = $classname;
                        }
                    }
                }
            }
        }
        return $classes;
    }
}