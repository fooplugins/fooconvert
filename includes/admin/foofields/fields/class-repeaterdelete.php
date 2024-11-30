<?php
namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

if ( ! class_exists( __NAMESPACE__ . '\RepeaterDelete' ) ) {

	class RepeaterDelete extends Field {

		protected $delete_confirmation_message;
		protected $delete_title;

		function __construct( $container, $type, $field_config ) {
			parent::__construct( $container, $type, $field_config );

			$this->delete_confirmation_message = isset( $field_config['delete_confirmation_message'] ) ? $field_config['delete_confirmation_message'] : __( 'Are you sure?', $container->manager->text_domain );
			$this->delete_title = isset( $field_config['delete_title'] ) ? $field_config['delete_title'] : __( 'Delete Row', $container->manager->text_domain );
		}

		/**
		 * Render the ajax button field
		 */
		function render_input( $override_attributes = false ) {
			self::render_html_tag( 'a', array(
				'class' => 'foofields-repeater-delete',
				'data-confirm' => $this->delete_confirmation_message,
				'href' => '#delete',
				'title' => $this->delete_title
			), null, false );
			self::render_html_tag('span', array( 'class' => 'dashicons dashicons-trash' ) );
			echo '</a>';
		}
	}
}
