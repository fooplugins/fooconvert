<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

if ( !class_exists( __NAMESPACE__ . '\Header' ) ) {

    /**
     * Class Header.
     */
    class Header extends Field {

        /**
         * Renders label.
         */
        function render_label() {
            return;
        }

        /**
         * Renders description.
         */
        function render_description() {
            return;
        }

        /**
         * Renders input.
         */
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
