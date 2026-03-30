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
    private array $post_types = array();

    /**
     * @var string[]
     */
    private array $tag_names = array();

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
     * Get the custom post types for all widgets.
     *
     * @return string[] A string array of custom post types for all widgets.
     *
     * @since 1.0.0
     */
    function get_post_types(): array {
        if ( !empty( $this->post_types ) ) {
            return $this->post_types;
        }
        return $this->post_types = Utils::array_map( $this->instances, function ( $widget ) {
            return $widget->get_post_type();
        } );
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

    function get_instance( string $post_type ): ?BaseWidget {
        foreach ( $this->instances as $instance ) {
            if ( $instance->get_post_type() === $post_type ) {
                return $instance;
            }
        }
        return null;
    }

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
     * Check if the current page is the editor for any of the widget custom post types.
     *
     * @return bool True if the current page is the editor for one of the widgets, otherwise false.
     *
     * @since 1.0.0
     */
    function is_editor(): bool {
        return Utils::is_post_type_editor( $this->get_post_types() );
    }

    //endregion

    //region Shortcode

    public function register_shortcode( string $post_type ) {
        add_shortcode( $post_type, array( $this, 'render_shortcode' ) );
    }

    public function render_shortcode( array $attributes, ?string $content, string $tag ) {
        $attributes = shortcode_atts( [ 'id' => 0 ], $attributes, $tag );
        $post_id = (int)$attributes['id'];
        if ( !empty( $post_id ) && !FooConvert::plugin()->display_rules->is_enqueued( $post_id ) ) {
            $queueable = FooConvert::plugin()->display_rules->get_queueable( $post_id, 'shortcode' );
            if ( !empty( $queueable ) ) {
                return FooConvert::plugin()->display_rules->render_queueable( $queueable );
            }
        }
        return false;
    }

    //endregion

}
