<?php
/**
 * An abstract base class that houses common functions
 */

namespace FooPlugins\FooConvert\Admin\FooFields;

if ( ! class_exists( __NAMESPACE__ . '\Base' ) ) {

	abstract class Base {

		/**
		 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
		 * Non-scalar values are ignored.
		 *
		 * @param string|array $var Data to sanitize.
		 *
		 * @return string|array
		 */
		protected function clean( $var ) {
			if ( is_array( $var ) ) {
				return array_map( array( $this, 'clean' ), $var );
			} else {
				return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
			}
		}

        // phpcs:disable WordPress.Security.NonceVerification.Recommended

		/**
		 * Safe way to get value from the request object
		 *
		 * @param $key
		 * @param null $default
		 * @param bool $clean
		 *
		 * @return mixed
		 */
		protected function safe_get_from_request( $key, $default = null, $clean = true ) {
			if ( isset( $_REQUEST[ $key ] ) ) {
				$value = wp_unslash( $_REQUEST[ $key ] );
				if ( $clean ) {
					return $this->clean( $value );
				}

				return $value;
			}

			return $default;
		}

		/**
		 * Run clean over posted textarea but maintain line breaks.
		 *
		 * @param string $var Data to sanitize.
		 *
		 * @return string
		 */
		protected function sanitize_textarea( $var ) {
			return implode( "\n", array_map( array( $this, 'clean' ), explode( "\n", $var ) ) );
		}

		/**
		 * Return a sanitized and unslashed key from $_REQUEST
		 *
		 * @param $key
		 *
		 * @return string|null
		 */
		static function sanitize_key( $key ) {
			if ( isset( $_REQUEST[ $key ] ) ) {
				return sanitize_key( wp_unslash( $_REQUEST[ $key ] ) );
			}

			return null;
		}

		/**
		 * Return a sanitized and unslashed value from $_REQUEST
		 *
		 * @param $key
		 *
		 * @return string|null
		 */
		static function sanitize_text( $key ) {
			if ( isset( $_REQUEST[ $key ] ) ) {
				return sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) );
			}

			return null;
		}

        // phpcs:enable

		/**
		 * Safely renders an HTML tag
		 *
		 * @param $tag
		 * @param $attributes
		 * @param string $inner
		 * @param bool $close
		 * @param bool $escape_inner
		 */
		static function render_html_tag( $tag, $attributes, $inner = null, $close = true, $escape_inner = true ) {
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<' . $tag . ' ';
			//make sure all attributes are escaped
			$attributes     = array_map( 'esc_attr', $attributes );
			$attributePairs = [];
			foreach ( $attributes as $key => $val ) {
				if ( is_null( $val ) ) {
					continue;
				} else if ( is_int( $key ) ) {
					$attributePairs[] = esc_attr( $val );
				} else {
					$val              = esc_attr( $val );
					$attributePairs[] = "{$key}=\"{$val}\"";
				}
			}
			echo implode( ' ', $attributePairs );

			if ( in_array( $tag, array( 'img', 'input', 'br', 'hr', 'meta', 'etc' ) ) ) {
				echo ' />';
				return;
			}
			echo '>';
			if ( isset( $inner ) ) {
				if ( $escape_inner ) {
					echo esc_html( $inner );
				} else {
					echo $inner;
				}
			}
			if ( $close ) {
				echo '</' . $tag . '>';
			}
            // phpcs:enable
		}
	}
}
