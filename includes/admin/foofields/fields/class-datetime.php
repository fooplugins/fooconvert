<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

use FooPlugins\FooConvert\Admin\FooFields\Container;

if ( ! class_exists( __NAMESPACE__ . '\DateTime' ) ) {

	class DateTime extends Field {

		/**
		 * If the field should adjust to the WP timezone.
		 * @var bool
		 */
		protected $adjust_timezone;

		/**
		 * Field constructor.
		 *
		 * @param $container Container
		 * @param $type string
		 * @param $field_config array
		 */
		function __construct( $container, $type, $field_config ) {
			parent::__construct( $container, $type, $field_config );

			$this->adjust_timezone = isset( $field_config['adjust_timezone'] ) ? $field_config['adjust_timezone'] : true;
		}

		function render_input( $override_attributes = false ) {

			$timestamp = intval( $this->value() );

			if ( $timestamp === 0 && isset( $this->default ) ){
				$timestamp = $this->default;
			}

			$datetime = new \DateTime();
			$datetime->setTimestamp( $timestamp );

			$timezone = wp_timezone();

			$datetime->setTimezone( $timezone );

			$hours = intval( $datetime->format( 'H' ) );
			$minutes = intval( $datetime->format( 'i' ) );

			echo '<div class="foofields-time-selector">';

			self::render_html_tag( 'input', array(
				'name' => $this->name . '[date]',
				'id' => $this->unique_id . '_date',
				'type' => 'date',
				'value' => $datetime->format( 'Y-m-d' )
			) );

			$this->render_component( 'hours', 'H', $hours, 23 );
			$this->render_component( 'minutes', 'm', $minutes, 59 );
			echo '</div>';

		}

		function render_component( $type, $suffix, $value = 0, $max = 0 ){

			$unique_id = $this->unique_id . '_' . $type;
			$label_attributes = array(
				'class' => 'foofields-time-component foofields-time-' . $type,
				'for' => $unique_id
			);

			$padded_value = str_pad( $value, 2, '0', STR_PAD_LEFT );

			$input_attributes = array(
				'class' => 'foofields-time-input',
				'name' => $this->name . '[' . $type . ']',
				'id' => $unique_id,
				'type' => 'number',
				'value' => $padded_value,
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

		/**
		 * @throws \Exception
		 */
		function get_posted_value( $sanitized_form_data ) {
			$values = parent::get_posted_value( $sanitized_form_data );

			if ( is_array( $values ) ) {
				$date    = $values['date'];
				$hours   = intval( $values['hours'] );
				$minutes = intval( $values['minutes'] );

				if ( empty( $date ) ) {
					$date = '1970-01-01';
				}

				$timezone = wp_timezone();
				$datetime = new \DateTimeImmutable( $date . ' ' . $hours . ':' . $minutes, $timezone );
				$timestamp = $datetime->getTimestamp();
			} else {
				$timestamp = $values;
			}

			return $timestamp;
		}

		function validate( $posted_value ) {
			$timestamp = intval( $posted_value );
//			if ( $this->adjust_timezone ) {
//				$timestamp = $timestamp + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
//			}
            // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
			if ( isset( $this->config['minimum'] ) && is_numeric( $this->config['minimum'] ) && $timestamp < $this->config['minimum'] ){
				$minimum = date( 'r', $this->config['minimum'] );
                // Translators: %1$s is a date, %2$s is the field label.
				$message = __( 'Please select a date greater than %1$s for %2$s!', $this->container->manager->text_domain );
				$this->error = sprintf( $message, $minimum, $this->label );
				return false;
			}
			if ( isset( $this->config['minimum'] ) && $this->config['minimum'] === 'now' && $timestamp < time() ){
                // Translators: %s is the field label.
				$message = __( 'Please select a date in the future for %s!', $this->container->manager->text_domain );
				$this->error = sprintf( $message, $this->label );
				return false;
			}
            // phpcs:enable
			return parent::validate( $posted_value );
		}
	}
}
