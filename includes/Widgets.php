<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Widgets\Base\BaseWidget;
use FooPlugins\FooConvert\Widgets\Bar;
use FooPlugins\FooConvert\Widgets\Flyout;
use FooPlugins\FooConvert\Widgets\PostType;
use FooPlugins\FooConvert\Widgets\Popup;
use WP_Post;

/**
 * This class both initializes and contains high level management utilities for plugin widgets.
 */
class Widgets extends BaseComponent {

    /**
     * @var BaseWidget[]
     */
    private array $instances;

    /**
     * @var PostType
     */
    private PostType $post_type;

    /**
     * @var string[]
     */
    private array $tag_names = array();

    /**
     * Initializes the Widgets.
     */
    function __construct() {
        parent::__construct();
        $this->post_type = new PostType();
        $this->instances = array(
            new Bar(),
            new Flyout(),
            new Popup()
        );
    }

    //region General

    /**
     * Get all widget instances.
     *
     * @return BaseWidget[]
     *
     * @since 1.0.0
     */
    function get_instances(): array {
        return $this->instances;
    }

    /**
     * Returns the popup post type registrar.
     *
     * @return PostType
     */
    function get_post_type(): PostType {
        return $this->post_type;
    }

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
     * Returns the instance.
     */
    function get_instance( $thing ): ?BaseWidget {
        if ( $thing instanceof WP_Post || is_numeric( $thing ) ) {
            $post_type = fooconvert_get_widget_logical_post_type( $thing );
        } else if ( is_string( $thing ) ) {
            $post_type = fooconvert_get_widget_logical_post_type( $thing );
        } else {
            $post_type = '';
        }

        foreach ( $this->instances as $instance ) {
            if ( $instance->get_post_type() === $post_type ) {
                return $instance;
            }
        }
        return null;
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

    /**
     * Check if the current page is the popup editor.
     *
     * @return bool True if the current page is the popup editor, otherwise false.
     *
     * @since 1.0.0
     */
    function is_editor(): bool {
        return Utils::is_post_type_editor( FOOCONVERT_CPT_POPUP );
    }

    //endregion

}
