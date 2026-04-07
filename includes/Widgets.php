<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Widgets\Base\BaseWidget;
use FooPlugins\FooConvert\Widgets\Bar;
use FooPlugins\FooConvert\Widgets\Flyout;
use FooPlugins\FooConvert\Widgets\Popup;

/**
 * This class both initializes and contains high level management utilities for plugin widgets.
 */
class Widgets extends BaseComponent {

    /**
     * @var BaseWidget[]
     */
    private array $instances;

    /**
     * @var string[]
     */
    private array $tag_names = array();

    /**
     * Initializes the Widgets.
     */
    function __construct() {
        parent::__construct();
        $this->instances = array(
            new Bar(),
            new Flyout(),
            new Popup()
        );
    }

    //region General

    /**
     * Get the tag names for all widgets.
     *
     * @return string[] A string array of tag names for all widgets.
     *
     * @since 1.0.0
     */
    function get_tag_names(): array {
        if ( !empty( $this->tag_names ) ) {
            return $this->tag_names;
        }
        return $this->tag_names = Utils::array_map( $this->instances, function ( $widget ) {
            return $widget->get_tag_name();
        } );
    }

    /**
     * Returns the kses definitions.
     */
    function get_kses_definitions(): array {
        $defs = array();
        foreach ( $this->instances as $instance ) {
            $def = $instance->kses_definition();
            if ( !empty( $def ) ) {
                $defs = array_merge( $defs, $def );
            }
        }
        return $defs;
    }

    //endregion

}
