<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

if ( !class_exists( __NAMESPACE__ . '\Time' ) ) {

    class Time extends Field {

        function render_input( $override_attributes = false ) {

            $time_in_seconds = intval( $this->value() );

            $hours = floor( ( $time_in_seconds % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
            $minutes = floor( ( $time_in_seconds % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );

            self::render_html_tag( 'input', array(
                'name'  => $this->name . '[hours]',
                'id'    => $this->unique_id . '_hours',
                'type'  => 'number',
                'value' => $hours,
                'max'   => 23
            ) );

            self::render_html_tag( 'input', array(
                'name'  => $this->name . '[minutes]',
                'id'    => $this->unique_id . '_minutes',
                'type'  => 'number',
                'value' => $minutes,
                'max'   => 59
            ) );
        }

        function get_posted_value( $sanitized_form_data ) {
            $values = parent::get_posted_value( $sanitized_form_data );

            $hours = intval( $values['hours'] ) * HOUR_IN_SECONDS;
            $minutes = intval( $values['minutes'] ) * MINUTE_IN_SECONDS;

            return $hours + $minutes;
        }
    }
}
