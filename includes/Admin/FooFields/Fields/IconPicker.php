<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

use FooPlugins\FooConvert\Admin\FooFields\Fields\Field;

if ( !class_exists( __NAMESPACE__ . '\IconPicker' ) ) {

    /**
     * Class IconPicker.
     */
    class IconPicker extends Field {

        protected $i18n;

        /**
         * Returns the i18n.
         */
        public static function get_i18n() {
            return array(
                'select'  => __( 'Select Icon', 'fooconvert' ),
                'clear'   => __( 'Clear Icon', 'fooconvert' ),
                'default' => __( 'Default', 'fooconvert' )
            );
        }

        /**
         * Initializes the IconPicker.
         */
        public function __construct( $container, $type, $field_config ) {
            parent::__construct( $container, $type, $field_config );
            $this->i18n = isset( $field_config['i18n'] ) && is_array( $field_config['i18n'] ) ? $field_config['i18n'] : false;
        }

        /**
         * Handles data attributes.
         */
        public function data_attributes() {
            $data_attributes = parent::data_attributes();

            if ( isset( $this->default ) ) {
                $data_attributes['default'] = $this->default;
            }

            if ( isset( $this->i18n ) ) {
                $data_attributes['i18n'] = $this->i18n;
            }

            return $data_attributes;
        }

        /**
         * Renders input.
         */
        function render_input( $override_attributes = false ) {
            $field_value = $this->value();

            $attributes = array(
                'id'   => $this->unique_id,
                'name' => $this->name
            );
            if ( $override_attributes !== false ) {
                $attributes = wp_parse_args( $override_attributes, $attributes );
            }
            if ( isset( $this->placeholder ) ) {
                $attributes['placeholder'] = $this->placeholder;
            }
            $attributes['type'] = 'text';
            $attributes['value'] = $field_value;

            self::render_html_tag( 'input', $attributes );
        }
    }
}
