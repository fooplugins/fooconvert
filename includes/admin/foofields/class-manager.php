<?php

namespace FooPlugins\FooConvert\Admin\FooFields;

if ( ! class_exists( __NAMESPACE__ . '\Manager' ) ) {

	abstract class Manager {
		/**
		 * @var Container[]
		 */
		private $containers = array();

		/**
		 * @var array
		 */
		public $config;

		/**
		 * The id of the manager
		 * @var string
		 */
		public $id;

		/**
		 * The textdomain of the plugin
		 * @var string
		 */
		public $text_domain;

		/**
		 * The base URL of the plugin. Used for building URL's for enqueue asset calls
		 * @var mixed
		 */
		public $plugin_url;

		/**
		 * The version of the plugin. Used for versioning enqueue asset calls
		 * @var mixed
		 */
		public $plugin_version;

		function __construct( $config ) {
			$this->config = $config;
			if ( ! isset( $config['id'] ) ) {
				throw new \Exception( __( 'ERROR : There is no "id" value set for your container manager!' ), 66601 );
			}
			if ( ! isset( $config['text_domain'] ) ) {
				throw new \Exception( __( 'ERROR : There is no "text_domain" value set! Translations will not work!' ), 66601 );
			}
			if ( ! isset( $config['plugin_url'] ) ) {
				throw new \Exception( __( 'ERROR : There is no "plugin_url" value set! Things will not work!' ), 66602 );
			}
			if ( ! isset( $config['plugin_version'] ) ) {
				throw new \Exception( __( 'ERROR : There is no "plugin_version" value set! Things will not work!' ), 66603 );
			}

			$this->id = $config['id'];
			$this->text_domain = $config['text_domain'];
			$this->plugin_url = $config['plugin_url'];
			$this->plugin_version = $config['plugin_version'];
			$this->containers = array();

			$this->register_myself();
		}

		/**
		 * Returns a registered manager
		 *
		 * @param $id
		 *
		 * @return false|Manager
		 */
		public static function get_manager( $id ) {
			global $foofields_container_managers;

			if ( isset( $foofields_container_managers ) && array_key_exists( $id, $foofields_container_managers ) ) {
				return $foofields_container_managers[$id];
			}

			return false;
		}

		private function register_myself() {
			global $foofields_container_managers;

			if ( !isset( $foofields_container_managers ) ) {
				$foofields_container_managers = array();
			}

			$foofields_container_managers[$this->id] = $this;
		}

		private function unique_field_types() {
			$field_types = array();
			foreach ( $this->containers as $container ) {
				$field_types = array_merge( $field_types, $container->unique_field_types );
			}

			return $field_types;
		}

		private function has_fields() {
			$has_fields = false;

			//ensure all container fields have been loaded
			foreach ( $this->containers as $container ) {
				if ( $container->has_fields() ) {
					$has_fields = true;
				}
			}

			return $has_fields;
		}

		public function enqueue_assets() {
			//enqueue assets if there are any fields
			if ( $this->has_fields() ) {
				wp_enqueue_script( 'selectize', $this->plugin_url . 'assets/admin/vendor/selectize/selectize.min.js', array( 'jquery' ), $this->plugin_version );
				wp_enqueue_script( 'foofields', $this->plugin_url . 'assets/admin/vendor/foofields/foofields.min.js', array(
					'jquery',
					'jquery-ui-sortable',
					'wp-color-picker',
					'selectize'
				), $this->plugin_version );

				$this->enqueue_field_translations();

				wp_enqueue_style( 'selectize', $this->plugin_url . 'assets/admin/vendor/selectize/selectize.css', array(), $this->plugin_version );
				wp_enqueue_style( 'foofields', $this->plugin_url . 'assets/admin/vendor/foofields/foofields.min.css', array(
					'wp-color-picker',
					'selectize'
				), $this->plugin_version );
			}
		}

		function enqueue_field_translations() {
			$translations = array();

			foreach ( $this->unique_field_types() as $type => $class ) {
				$function = array( $class, 'get_i18n' );
				if ( is_callable( $function ) ) {
					$translations[$type] = call_user_func( $function );
				}
			}

			if ( count( $translations ) > 0 ) {
				wp_localize_script( 'foofields', 'FOOFIELDS_I18N', $translations );
			}
		}

		function register_container( $container ) {
			$this->containers[] = $container;
		}

		/**
		 * Gets the post_type from the ajax request
		 *
		 * @return false|string
		 */
		function get_post_type_from_ajax_request() {
			global $foofields_post_type_from_ajax_request;

			if ( isset( $foofields_post_type_from_ajax_request ) ) {
				return $foofields_post_type_from_ajax_request;
			}

			$referrer = wp_get_raw_referer();
			$url = parse_url( $referrer, PHP_URL_QUERY);
			if ( !empty( $url ) ) {
				parse_str($url, $query);

				//we know we came from an edit post page
				if (isset($query['post']) && isset($query['action']) && $query['action'] === 'edit') {
					$post_id = intval($query['post']);

					if ($post_id > 0) {
						return $foofields_post_type_from_ajax_request = get_post_type($post_id);
					}
				}
			}

			return false;
		}
	}
}
