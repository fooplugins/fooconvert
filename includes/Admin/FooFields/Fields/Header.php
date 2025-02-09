<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

if ( !class_exists( __NAMESPACE__ . '\Header' ) ) {

    class Header extends Field {

        function render_label() {
            return;
        }

        function render_description() {
            return;
        }

        function render_input( $override_attributes = false ) {
            if ( isset( $this->label ) ) {
                self::render_html_tag( 'h3', array(), $this->label, false );
                $this->render_tooltip();
                echo '</h3>';
            }
            if ( isset( $this->description ) ) {
                self::render_html_tag( 'p', array(), $this->description );
            }
        }
    }
}
