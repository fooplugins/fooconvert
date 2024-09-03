<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Components\Base\Base_Component;
use FooPlugins\FooConvert\Widgets\Bar;
use FooPlugins\FooConvert\Widgets\Base\Base_Widget;
use WP_Query;

/**
 * This class both initializes and contains high level management utilities for plugin widgets.
 */
class FooConvert_Widgets extends Base_Component {

    /**
     * @var Base_Widget[]
     */
    private array $instances;

    /**
     * @var string[]
     */
    private array $post_types = array();

    function __construct() {
        parent::__construct();
        $this->instances = array(
            new Bar()
        );
    }

    //region General

    /**
     * Get all widget instances.
     *
     * @return Base_Widget[]
     *
     * @since 1.0.0
     */
    function get_instances() : array {
        return $this->instances;
    }

    /**
     * Get the custom post types for all widgets.
     *
     * @return string[] A string array of custom post types for all widgets.
     *
     * @since 1.0.0
     */
    function get_post_types() : array {
        if ( ! empty( $this->post_types ) ) {
            return $this->post_types;
        }
        return $this->post_types = Utils::array_map( $this->instances, function ( $widget ) {
            return $widget->get_post_type();
        } );
    }

    function get_instance( string $post_type ) : ?Base_Widget {
        foreach ( $this->instances as $instance ) {
            if ( $instance->get_post_type() === $post_type ) {
                return $instance;
            }
        }
        return null;
    }

    function get_kses_definitions() : array {
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
    function is_editor() : bool {
        return Utils::is_post_type_editor( $this->get_post_types() );
    }

    //endregion

    //region Shortcode

    public function register_shortcode( string $post_type ) {
        add_shortcode( $post_type, array( $this, 'render_shortcode' ) );
    }

    public function render_shortcode( array $attributes, ?string $content, string $tag ) {
        $attributes = shortcode_atts( [ 'id' => 0 ], $attributes, $tag );
        $post_id = (int) $attributes['id'];
        if ( ! empty( $post_id ) && ! FooConvert::plugin()->display_rules->is_enqueued( $post_id ) ) {
            $args = [ 'post_type' => $tag, 'p' => $post_id ];
            $query = new WP_Query( $args );
            if ( $query->have_posts() ) {
                ob_start();
                while ( $query->have_posts() ) : $query->the_post();
                    the_content();
                endwhile;
                wp_reset_postdata();
                return ob_get_clean();
            }
        }
        return false;
    }

    //endregion

}