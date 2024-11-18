<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

use FooPlugins\FooConvert\Admin\FooFields\Fields\Field;
use FooPlugins\FooConvert\Utils;

if ( ! class_exists( __NAMESPACE__ . '\Button' ) ) {

	class Button extends Field {

		private $sizes = array(
			'small',
			'medium',
			'large',
			'hero',
		);

		public $text;
		public $icon;
		public $href;
		public $target = '_self';
		public $size = 'medium'; // small, medium, large, hero
		public $submit = false;
		public $primary = false;
		public $disabled = false;

		public function __construct( $container, $type, $field_config ) {
			parent::__construct( $container, $type, $field_config );
			$this->text = Utils::get_string( 'text', $field_config );
			$this->icon = Utils::get_string( 'icon', $field_config );
			$this->href = Utils::get_string( 'href', $field_config );
			$this->target = Utils::get_string( 'target', $field_config, '_self' );
			$this->size = Utils::get_string( 'size', $field_config, 'medium', $this->sizes );
			$this->submit = Utils::get_bool( 'submit', $field_config, false );
			$this->primary = Utils::get_bool( 'primary', $field_config, false );
			$this->disabled = Utils::get_bool( 'disabled', $field_config, false );
		}

		function render( $render_label = true, $override_attributes = false ) {
			$is_link = isset( $this->href ) && !$this->submit;
			$classes = array( 'button' );
			if ( $this->primary ) {
				$classes[] = 'button-primary';
			}
			if ( $this->size !== 'medium' ) {
				$classes[] = 'button-' . $this->size;
			}
			$attributes = array(
				'class' => implode( ' ', $classes )
			);
			if ( $this->disabled ) {
				$attributes[ 'disabled' ] = 'disabled';
			}
			if ( $is_link ) {
				$attributes[ 'href' ] = $this->href;
				if ( $this->target !== '_self' ) {
					$attributes[ 'target' ] = $this->target;
				}
			} else {
				$attributes[ 'type' ] = $this->submit ? 'submit' : 'button';
			}

			self::render_html_tag( $is_link ? 'a' : 'button', $attributes, null, false );

			if ( isset( $this->icon ) ) {
				self::render_html_tag( 'i', array( 'class' => 'dashicons dashicons-' . $this->icon ) );
			}
			if ( isset( $this->text ) ) {
				self::render_html_tag( 'span', array(), $this->text, true, false );
			}

			echo $is_link ? '</a>' : '</button>';
		}
	}
}
