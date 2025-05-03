<?php

namespace FooPlugins\FooConvert\Components\Base;

use FooPlugins\FooConvert\Utils;

abstract class BaseComponent {

    function __construct() {
        add_action( 'fooconvert_enqueued_editor_assets', array( $this, 'enqueue_component_data' ) );
    }

    function get_component_data_name(): string {
        return '';
    }

    function get_component_data(): array {
        return array();
    }

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