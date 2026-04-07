<?php

namespace FooPlugins\FooConvert\Components\Base;

use FooPlugins\FooConvert\Utils;

/**
 * Class BaseComponent.
 */
abstract class BaseComponent {

    /**
     * Initializes the BaseComponent.
     */
    function __construct() {
        add_action( 'fooconvert_enqueued_editor_assets', array( $this, 'enqueue_component_data' ) );
    }

    /**
     * Returns the component data name.
     */
    function get_component_data_name(): string {
        return '';
    }

    /**
     * Returns the component data.
     */
    function get_component_data(): array {
        return array();
    }

    /**
     * Enqueues component data.
     */
    function enqueue_component_data( string $handle ) {
        $js_script = Utils::to_js_script( $this->get_component_data_name(), $this->get_component_data() );
        if ( !empty( $js_script ) ) {
            // Reviewers:
            // The $js_script is built up from data required by this component and is both
            // HTML decoded and JSON encoded by the to_js_script method.
            // phpcs:ignore WordPress.Security.EscapeOutput
            wp_add_inline_script( $handle, $js_script, 'before' );
        }
    }
}