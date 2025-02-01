<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

use FooPlugins\FooConvert\Admin\FooFields\Container;

if ( ! class_exists( __NAMESPACE__ . '\FieldGroup' ) ) {

	class FieldGroup extends Field {

		protected $fields = false;

		/**
		 * @var bool|mixed Whether or not child fields should be indented. Defaults to false.
		 */
		protected $indent;

		/**
		 * FieldGroup constructor.
		 *
		 * @param $container Container
		 * @param $type string
		 * @param $field_config array
		 */
		function __construct( $container, $type, $field_config ) {
			parent::__construct( $container, $type, $field_config );
			$this->indent = isset( $field_config['indent'] ) && is_bool( $field_config['indent'] ) ? $field_config['indent'] : false;

			if ( isset( $field_config['fields'] ) && is_array( $field_config['fields'] ) ) {

				$parent_id = null;

				if ( false === $this->fields ) {
					$this->fields = array();
				}

				if ( isset( $field_config['parent_id'] ) ) {
					$parent_id = $field_config['parent_id'];
				}

				//create label field instance
				if ( isset( $this->label ) ) {
					$label_config = array(
						'id'          => $this->id . '_heading',
						'class'       => 'foofields-full-width',
						'type'        => 'heading',
						'label'       => $this->label,
						'desc'        => $this->description,
						'layout'      => $this->layout,
						'tooltip'     => $this->tooltip
					);
					$this->fields[] = $this->container->create_field_instance( $label_config['type'], $label_config, $parent_id );
				}

				foreach ( $this->container->get_ordered_array( $field_config['fields'] ) as $field_config ) {
					$this->fields[] = $this->container->create_field_instance( $field_config['type'], $field_config, $parent_id );
				}
			}
		}

		/**
		 * Override the base pre_render method as we don't need to perform error checking as this field is simply
		 * a container for other fields. We also don't add a layout class to the container element as that property
		 * is instead passed on to the heading field generated from the label property.
		 */
		function pre_render() {
			$this->classes[] = "foofields-type-{$this->type}";
			if ( ! $this->visible() ) {
				$this->classes[] = 'foofields-hidden';
			}
		}

		/**
		 * Override the base method and instead of rendering a simple label we instead generate and render a heading field.
		 */
		function render_label() {
		}

		/**
		 * Override the base method as we don't actually want any more container elements, this field itself is the
		 * container element. Instead we simply call the render_input method to preserve the logic chain.
		 *
		 * @param false $override_attributes
		 */
		function render_input_container( $override_attributes = false ) {
			$this->render_input( $override_attributes );
		}

		/**
		 * Override the base method to instead render each field supplied through the config.
		 *
		 * @param false $override_attributes
		 */
		function render_input( $override_attributes = false ) {
			if ( is_array( $this->fields ) ) {
				foreach ( $this->fields as $field ) {
					// child fields inherit this fields layout value unless they explicitly define there own
					if ( !isset( $field->config['layout'] ) ){
						$field->layout = $this->layout;
					}
					$field->pre_render();

					// only add the indent class if the option is enabled, the field is not a field group and the field doesn't already have the class set
					if ( $this->indent === true && $field->type !== 'field-group' && !in_array( 'foofields-indent', $field->classes ) ) {
						$field->classes[] = 'foofields-indent';
					}
					$field->render();
				}
			}
		}

//		/**
//		 * Gets a field value from the saved state.
//		 *
//		 * @param $field_config
//		 *
//		 * @return mixed|null
//		 */
//		function get_field_value( $field_config ) {
//			if ( array_key_exists( 'id', $field_config ) ) {
//				$state = $this->value();
//				if ( is_array( $state ) && array_key_exists( $field_config['id'], $state ) ) {
//					return $state[ $field_config['id'] ];
//				}
//				if ( isset( $field_config['default'] ) ) {
//					return $field_config['default'];
//				}
//			}
//
//			return null;
//		}

//		/**
//		 * Overrides the base method so we can fetch each of the child field values from the posted data.
//		 *
//		 * @param $sanitized_form_data
//		 *
//		 * @return array|mixed|string|null
//		 */
//		public function get_posted_value( $sanitized_form_data ) {
//			$return_value = null;
//			if ( is_array( $this->fields ) ) {
//				$return_value = array();
//				foreach ( $this->fields as $field_config ) {
//					$field_object                      = $this->container->create_field_instance( $field_config['type'], $field_config );
//					$return_value[ $field_object->id ] = $field_object->get_posted_value( $sanitized_form_data );
//				}
//			}
//
//			return $return_value;
//		}
	}
}
