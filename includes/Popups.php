<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Popups\Base\BasePopup;
use FooPlugins\FooConvert\Popups\Bar;
use FooPlugins\FooConvert\Popups\Flyout;
use FooPlugins\FooConvert\Popups\Overlay;

/**
 * This class both initializes and contains high level management utilities for plugin popups.
 */
class Popups extends BaseComponent {

    /**
     * @var BasePopup[]
     */
    private array $instances;

    /**
     * @var string[]
     */
    private array $tag_names = array();

    /**
     * Initializes the Popups.
     */
    function __construct() {
        parent::__construct();
        $this->instances = array(
            new Bar(),
            new Flyout(),
            new Overlay()
        );
    }

    //region General

    /**
     * Get the tag names for all popups.
     *
     * @return string[] A string array of tag names for all popups.
     *
     * @since 1.0.0
     */
    function get_tag_names(): array {
        if ( !empty( $this->tag_names ) ) {
            return $this->tag_names;
        }
        return $this->tag_names = Utils::array_map( $this->instances, function ( $popup ) {
            return $popup->get_tag_name();
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
