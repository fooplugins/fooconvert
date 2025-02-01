<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

use FooPlugins\FooConvert\Admin\FooFields\Fields\Field;

if ( ! class_exists( __NAMESPACE__ . '\TimeSelector' ) ) {

	class TimeSelector extends Field {

		function render_input( $override_attributes = false ) {

			$time_in_seconds = intval( $this->value() );

			$days = floor( $time_in_seconds / DAY_IN_SECONDS );
			$hours = floor(( $time_in_seconds % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
			$minutes = floor(( $time_in_seconds % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
			$seconds = floor( $time_in_seconds % MINUTE_IN_SECONDS );

			echo '<div class="foofields-time-selector">';
			$this->render_component( 'days', 'd', $days );
			$this->render_component( 'hours', 'h', $hours, 23 );
			$this->render_component( 'minutes', 'm', $minutes, 59 );
			$this->render_component( 'seconds', 's', $seconds, 59 );
			echo '</div>';

		}

		function render_component( $type, $suffix, $value = 0, $max = 0 ){

			$unique_id = $this->unique_id . '_' . $type;
			$label_attributes = array(
				'class' => 'foofields-time-component foofields-time-' . $type,
				'for' => $unique_id
			);

			$input_attributes = array(
				'class' => 'foofields-time-input',
				'name' => $this->name . '[' . $type . ']',
				'id' => $unique_id,
				'type' => 'number',
				'value' => $value,
				'min' => 0,
				'step' => 1
			);

			if ( $max > 0 ){
				$input_attributes['max'] = $max;
			}

			$span_attributes = array(
				'class' => 'foofields-time-suffix'
			);

			$this->render_html_tag( 'label', $label_attributes, null, false );

			$this->render_html_tag( 'input', $input_attributes );
			$this->render_html_tag( 'span', $span_attributes, $suffix );

			echo '</label>';
		}

		function get_posted_value( $sanitized_form_data ) {
			$values = parent::get_posted_value( $sanitized_form_data );

			if ( is_array( $values ) ) {
				$days    = intval( $values['days'] ) * DAY_IN_SECONDS;
				$hours   = intval( $values['hours'] ) * HOUR_IN_SECONDS;
				$minutes = intval( $values['minutes'] ) * MINUTE_IN_SECONDS;
				$seconds = intval( $values['seconds'] );

				return $days + $hours + $minutes + $seconds;

			} else {
				return $values;
			}
		}
	}
}
