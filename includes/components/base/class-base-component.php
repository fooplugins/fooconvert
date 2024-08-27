<?php

namespace FooPlugins\FooConvert\Components\Base;

use FooPlugins\FooConvert\Utils;

abstract class Base_Component {

    function __construct() {
        add_action( 'fooconvert_enqueued_editor_assets', array( $this, 'enqueue_component_data' ) );
    }

    function get_component_data_name() : string {
        return '';
    }

    function get_component_data() : array {
        return array();
    }

    function enqueue_component_data( string $handle ) {
        $js_script = Utils::to_js_script( $this->get_component_data_name(), $this->get_component_data() );
        if ( !empty( $js_script ) ) {
            wp_add_inline_script( $handle, $js_script, 'before' );
        }
    }
}