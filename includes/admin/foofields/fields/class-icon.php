<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

use FooPlugins\FooConvert\Admin\FooFields\Container;

if ( ! class_exists( __NAMESPACE__ . '\Icon' ) ) {

	class Icon extends Field {

		/**
		 * The icon for the field
		 * @var string
		 */
		protected $icon;

		/**
		 * The text for the field
		 * @var string
		 */
		protected $text;

		/**
		 * Field constructor.
		 *
		 * @param $container Container
		 * @param $type string
		 * @param $field_config array
		 */
		function __construct( $container, $type, $field_config ) {
			parent::__construct( $container, $type, $field_config );

			$this->icon = isset( $field_config['icon'] ) ? $field_config['icon'] : 'dashicons-editor-help';
			$this->label = null;
			if ( 'help' === $this->type ) {
				$this->type = 'icon';
				$this->icon = 'dashicons-editor-help';
			} else if ( 'error' === $this->type ) {
				$this->type  = 'icon';
				$this->icon  = 'dashicons-warning';
				$this->classes[] = 'icon-red';
			}
			$this->text = isset( $field_config['text'] ) ? $field_config['text'] : '';
		}

		function render_label() {
			return;
		}

		function render_description() {
			return;
		}

		function render_input( $override_attributes = false ) {
			self::render_html_tag( 'span', array( 'class' => 'dashicons ' . $this->icon ) );

			echo esc_html( $this->text );
		}
	}
}
