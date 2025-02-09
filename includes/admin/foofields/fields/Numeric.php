<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

use FooPlugins\FooConvert\Admin\FooFields\Fields\Field;

if ( !class_exists( __NAMESPACE__ . '\Numeric' ) ) {

    class Numeric extends Field {

        public $min;
        public $max;
        public $step;
        public $prefix;
        public $suffix;

        public function __construct( $container, $type, $field_config ) {
            parent::__construct( $container, $type, $field_config );
            $this->min = isset( $field_config['min'] ) && is_numeric( $field_config['min'] ) ? $field_config['min'] : 0;
            $this->max = isset( $field_config['max'] ) && is_numeric( $field_config['max'] ) ? $field_config['max'] : 0;
            $this->step = isset( $field_config['step'] ) && is_numeric( $field_config['step'] ) ? $field_config['step'] : 1;
            $this->prefix = isset( $field_config['prefix'] ) && is_string( $field_config['prefix'] ) ? $field_config['prefix'] : null;
            $this->suffix = isset( $field_config['suffix'] ) && is_string( $field_config['suffix'] ) ? $field_config['suffix'] : null;
        }

        function render_input( $override_attributes = false ) {

            $this->render_html_tag( 'label', array(
                'class' => 'foofields-numeric-input',
                'for'   => $this->unique_id
            ), null, false );

            if ( isset( $this->prefix ) ) {
                $this->render_html_tag( 'span', array(
                    'class' => 'foofields-numeric-input-prefix',
                ), $this->prefix );
            }

            $input_attributes = array(
                'name'  => $this->name,
                'id'    => $this->unique_id,
                'type'  => 'number',
                'value' => $this->value(),
                'min'   => $this->min,
                'step'  => $this->step
            );

            if ( $this->max > 0 ) {
                $input_attributes['max'] = $this->max;
            }

            $this->render_html_tag( 'input', $input_attributes );

            if ( isset( $this->suffix ) ) {
                $this->render_html_tag( 'span', array(
                    'class' => 'foofields-numeric-input-suffix',
                ), $this->suffix );
            }

            echo '</label>';

        }
    }
}
