<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

if ( ! class_exists( __NAMESPACE__ . '\Suggest' ) ) {

	class Suggest extends Field {

		protected $query;

		function __construct( $container, $type, $field_config ) {
			parent::__construct( $container, $type, $field_config );

			$this->query = isset( $field_config['query'] ) ? $field_config['query'] : false;

			//handle ajax auto suggest fields
 			add_action( $this->field_ajax_action_name(), array( $this, 'ajax_handle_autosuggest' ) );
		}

		/**
		 * Override the data attributes
		 * @return array
		 */
		function data_attributes() {
			$data_attributes = parent::data_attributes();
			$data_attributes['data-query'] = build_query( array(
				'action'     => $this->field_action_name(),
				'nonce'      => $this->create_nonce()
			) );
			$data_attributes['data-multiple'] = isset( $field['multiple'] ) ? $field['multiple'] : 'false';
			$data_attributes['data-separator'] = isset( $field['separator'] ) ? $field['separator'] : ',';
			return $data_attributes;
		}


		/**
		 * Renders the autosuggest field
		 *
		 * @param $optional_attributes
		 *
		 * @return mixed|void
		 */
		function render_input( $override_attributes = false ) {
			$attributes = wp_parse_args( $override_attributes, array(
				'type'        => 'text',
				'id'          => $this->unique_id,
				'name'        => $this->name,
				'value'       => $this->value(),
				'placeholder' => $this->placeholder,
			) );

			self::render_html_tag( 'input', $attributes );
		}

		/**
		 * Ajax handler for suggest fields
		 */
		function ajax_handle_autosuggest() {
			if ( $this->verify_nonce() ) {

				$s = $this->sanitize_text( 'q' );
				$comma = _x( ',', 'page delimiter' );
				if ( ',' !== $comma ) {
					$s = str_replace( $comma, ',', $s );
				}
				if ( false !== strpos( $s, ',' ) ) {
					$s = explode( ',', $s );
					$s = $s[ count( $s ) - 1 ];
				}
				$s = trim( $s );

				if ( !empty( $s ) ) {
					$this->container->do_action( 'suggest_' . $this->unique_id, $s, $this );

					if ( is_array( $this->query ) ) {
						$query_type = $this->query['type'];
						$query_data = $this->query['data'];

						$results = array();

						if ( 'post' === $query_type ) {

							$posts = get_posts(
								array(
									's'              => $s,
									'posts_per_page' => 5,
									'post_type'      => $query_data
								)
							);

							foreach ( $posts as $post ) {
								$results[] = $post->post_title;
							}

						} else if ( 'taxonomy' == $query_type ) {

							$terms = get_terms(
								array(
									'search'         => $s,
									'taxonomy'       => $query_data,
									'hide_empty'     => false
								)
							);

							foreach ( $terms as $term ) {
								$results[] = $term->name;
							}
						}

						echo esc_html( join( "\n", $results ) );
					}

				}
			}

			wp_die();
		}
	}
}
