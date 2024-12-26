<?php
/**
 * An abstract container that will house metaboxes and fields
 */

namespace FooPlugins\FooConvert\Admin\FooFields;

use FooPlugins\FooConvert\Admin\FooFields\Fields\Field;

if ( ! class_exists( __NAMESPACE__ . '\Container' ) ) {

	abstract class Container extends Base {

		/**
		 * @var array
		 */
		public $config;

		/**
		 * @var Manager
		 */
		public $manager;

		/**
		 * @var Field[]
		 */
		protected $fields = array();

		/**
		 * @var array
		 */
		public $unique_field_types = array();

		/**
		 * @var array
		 */
		protected $state = null;

		/**
		 * Config validation errors
		 *
		 * @var array
		 */
		protected $config_validation_errors = array();

		protected $state_field_errors = array();

		const STATE_KEY = '__state';

		protected $show_rules = array(
			'tabs'   => array(),
			'fields' => array()
		);

		function __construct( $config ) {
			$this->config = $config;

			$this->validate_config();

			if ( isset( $this->config['manager'] ) ) {
				$this->manager = namespace\Manager::get_manager( $this->config['manager'] );
				$this->manager->register_container( $this );
			}
		}

		/**
		 * Base validation for all containers
		 */
		function validate_config() {
			if ( !isset( $this->config['manager'] ) ) {
				$this->add_config_validation_error( __( 'ERROR : There is no "manager" value set for the container, which means nothing will work!', $this->manager->text_domain ) );
			}
		}

		/**
		 * Add a validation error and create an error field
		 *
		 * @param $error_message
		 */
		function add_config_validation_error( $error_message ) {
			$this->config_validation_errors[] = $error_message;

			$error_field = array(
				'id'   => 'validation_error_' . count( $this->config_validation_errors ),
				'type' => 'error',
				'text' => $error_message,
				'order' => -1
			);

			if ( ! isset( $this->config['fields'] ) ) {
				$this->config['fields'] = array();
			}

			$this->config['fields'][] = $error_field;
		}

		/**
		 * Finds all fields within the config
		 *
		 * @param $config
		 * @param string $parent_id
		 */
		function parse_config( &$config = null, $parent_id = null ) {
			if ( !isset( $config ) ) {
				$config = $this->config;
			}

			//parse any tabs
			if ( isset( $config['tabs'] ) ) {
				foreach ( $config['tabs'] as &$tab ) {
					$this->parse_config( $tab, $tab['id'] );

					//check if the tab has any data show rules
					$this->show_rule_add( $tab, 'tabs' );
				}
			}

			//parse any fields
			if ( isset( $config['fields'] ) ) {
				foreach ( $config['fields'] as &$field ) {
					$field_type = isset( $field['type'] ) ? $field['type'] : 'unknown';
					$this->create_field_instance( $field_type, $field, $parent_id );
				}
			}
		}

		/**
		 * Add show rules to an array for easier access later
		 *
		 * @param $config
		 * @param $config_type
		 */
		function show_rule_add( $config, $config_type ) {
			if ( isset( $config['data'] ) && isset( $config['data']['show-when'] ) ) {
				$this->show_rules[$config_type][ $this->get_unique_id($config) ] = $config['data']['show-when'];
			}
		}

		/***
		 * Determined if the tab/field config is visible
		 *
		 * @param $unique_id
		 * @param $type
		 *
		 * @return bool
		 */
		function show_rule_is_visible( $unique_id, $type ) {
			if ( array_key_exists( $unique_id, $this->show_rules[$type] ) ) {
				//we have show when rules for the tab/fields

				$show_rule = $this->show_rules[$type][$unique_id];
				$show_rule_value = $show_rule['value'];
				$show_rule_operator = isset( $show_rule['operator'] ) ? $show_rule['operator'] : '===';
				$show_rule_field_id = $this->get_unique_id( array( 'id' => $show_rule['field'] ) );
				$show_rule_field_visible = $this->show_rule_is_visible( $show_rule_field_id, 'fields' );

				// if the target field is not visible then hide this field
				if ( false === $show_rule_field_visible ) return false;

				$show_rule_field = $this->fields[$show_rule_field_id];
				$show_rule_field_value = $show_rule_field->value();

				if ( '===' === $show_rule_operator ) {
					if ( $show_rule_value === $show_rule_field_value ) {
						return true;
					}
				} else if ( '!==' === $show_rule_operator ) {
					if ( $show_rule_value !== $show_rule_field_value ) {
						return true;
					}
				} else if ( 'indexOf' === $show_rule_operator ) {
					if ( strpos( $show_rule_field_value, $show_rule_value ) !== false ) {
						return true;
					}
				} else if ( 'regex' === $show_rule_operator ) {
					if ( preg_match( '/' . $show_rule_value . '/', $show_rule_field_value ) === 1 ) {
						return true;
					}
				}
				//otherwise it should not be visible
				return false;
			}

			//always visible by default
			return true;
		}

		/**
		 * Create a field instance
		 *
		 * @param $field_type
		 * @param $field_config
		 * @param string $parent_id
		 *
		 * @return Field|mixed
		 */
		function create_field_instance( $field_type, &$field_config, $parent_id = null ) {
			$field_id = $this->get_unique_id( $field_config );

			//check if we have a duplicate field id
			if ( array_key_exists( $field_id, $this->fields ) ) {
				$field_config['id'] = $field_config['id'] . '_duplicate';
				$field_config['type'] = $field_type = 'error';
                // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
                // Translators: %s is the field id
				$field_config['text'] = sprintf( __( 'ERROR : Duplicate field id: %s', $this->manager->text_domain ), $field_config['id'] );
                // phpcs:enable
				$field_id = $this->get_unique_id( $field_config );
			}

			//if we have a parent (tab) then set it on the config
			if ( $parent_id !== null ) {
				$field_config['parent_id'] = $parent_id;
			}

			$mappings = $this->get_field_type_mappings();

			if ( array_key_exists( $field_type, $mappings ) ) {
				$field_instance_type = $mappings[ $field_type ];
				$field_instance      = new $field_instance_type( $this, $field_type, $field_config );
			} else {
				$field_instance_type = __NAMESPACE__ . '\Fields\Field';
				$field_instance = new Field( $this, $field_type, $field_config );
			}
			if ( $parent_id !== null ) {
				$field_instance->parent_id = $parent_id;
			}
			$this->fields[ $field_id ] = $field_instance;
			if ( !in_array( $field_type, $this->unique_field_types ) ) {
				$this->unique_field_types[$field_type] = $field_instance_type;
			}

			//check if the field has any data show rules
			$this->show_rule_add( $field_config, 'fields' );

			return $field_instance;
		}

		/**
		 * Returns a filterable list of field type mappings. This will allow new custom mappings to be added within plugins
		 *
		 * @return mixed|void
		 */
		function get_field_type_mappings() {
			return $this->apply_filters( 'field_type_mappings', array(
				'ajaxbutton'      => __NAMESPACE__ . '\Fields\AjaxButton',
				'selectize'       => __NAMESPACE__ . '\Fields\Selectize',
				'selectize-multi' => __NAMESPACE__ . '\Fields\SelectizeMulti',
				'suggest'         => __NAMESPACE__ . '\Fields\Suggest',
				'embed-metabox'   => __NAMESPACE__ . '\Fields\EmbedMetabox',
				'repeater'        => __NAMESPACE__ . '\Fields\Repeater',
				'icon'            => __NAMESPACE__ . '\Fields\Icon',
				'help'            => __NAMESPACE__ . '\Fields\Icon',
				'error'           => __NAMESPACE__ . '\Fields\Icon',
				'heading'         => __NAMESPACE__ . '\Fields\Header',
				'textarea'        => __NAMESPACE__ . '\Fields\Textarea',
				'checkboxlist'    => __NAMESPACE__ . '\Fields\InputList',
				'radiolist'       => __NAMESPACE__ . '\Fields\InputList',
				'htmllist'        => __NAMESPACE__ . '\Fields\InputList',
				'repeater-index'  => __NAMESPACE__ . '\Fields\RepeaterIndex',
				'repeater-delete' => __NAMESPACE__ . '\Fields\RepeaterDelete',
				'field-group'     => __NAMESPACE__ . '\Fields\FieldGroup',
				'time'            => __NAMESPACE__ . '\Fields\Time',
				'datetime'        => __NAMESPACE__ . '\Fields\DateTime',
                'icon-picker'     => __NAMESPACE__ . '\Fields\IconPicker',
                'time-selector'   => __NAMESPACE__ . '\Fields\TimeSelector',
                'numeric'         => __NAMESPACE__ . '\Fields\Numeric',
                'blurb'           => __NAMESPACE__ . '\Fields\Blurb',
                'button'          => __NAMESPACE__ . '\Fields\Button',
			) );
		}

		/**
		 * Returns a unique id for the config array
		 * @param $config
		 *
		 * @return string
		 */
		function get_unique_id( $config ) {
			if ( !isset( $config['id'] ) ) {
				$config['id'] = 'unknown';
			}

			return $this->container_id() . '_' . $config['id'];
		}

		/**
		 * Generate the field name used on the html input tag
		 *
		 * @param $field
		 *
		 * @return string
		 */
		public function get_field_name( $field ) {
			return sprintf( '%s[%s]', $this->container_id(), $field['id'] );
		}

		/**
		 * Returns true if the container has fields
		 * @return bool
		 */
		public function has_fields() {
			$this->load_fields();

			return count( $this->fields ) > 0;
		}

		/**
		 * Gets the saved state from the database
		 * @return mixed
		 */
		abstract function get_state();

		/**
		 * Builds up an identifier for the container
		 * @return string
		 */
		abstract function container_id();

		/**
		 * Cater for both doing a do_action and call the function if it was passed in
		 *
		 * @param $tag
		 */
		public function do_action( $tag ) {
			$args = func_get_args();

			if ( isset( $this->config['actions'] ) && isset( $this->config['actions'][ $tag ] ) ) {
				call_user_func_array(
					$this->config['actions'][ $tag ],
					array_merge( array_slice( $args, 1 ) )
				);
			}

			call_user_func_array( 'do_action', array_merge(
				array( $this->build_hook_tag( $tag ) ),
				array_slice( $args, 1 )
			) );
		}

		/**
		 * Shortcut function to add an action
		 *
		 * @param $tag
		 * @param $function_to_add
		 * @param int $priority
		 * @param int $accepted_args
		 */
		public function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
			add_action( $this->build_hook_tag( $tag ), $function_to_add, $priority, $accepted_args );
		}

		/**
		 * Shortcut function to add a filter
		 *
		 * @param $tag
		 * @param $function_to_add
		 * @param int $priority
		 * @param int $accepted_args
		 */
		public function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
			add_filter( $this->build_hook_tag( $tag ), $function_to_add, $priority, $accepted_args );
		}

		/**
		 * Cater for both doing a apply_filters and call the function if it was passed in
		 *
		 * @param $tag
		 * @param mixed ...$arg
		 *
		 * @return mixed|void
		 */
		public function apply_filters( $tag, $value ) {
			$args = func_get_args();

			if ( isset( $this->config['filters'] ) && isset( $this->config['filters'][ $tag ] ) ) {
				return call_user_func_array(
					$this->config['filters'][ $tag ],
					array_slice( $args, 1 )
				);
			}

			return call_user_func_array( 'apply_filters', array_merge(
					array( $this->build_hook_tag( $tag ) ),
					array_slice( $args, 1 ) )
			);
		}

		/**
		 * Builds a hook tag from the class namespace plus the tag passed in
		 *
		 * @param $tag
		 *
		 * @return string
		 */
		public function build_hook_tag( $tag ) {
			$full_tag = get_class($this) . '\\' . $tag;       //build up the full tag from the class namespace + tag
			$parts = explode( '\\', $full_tag );      //break it into the different parts
			array_shift( $parts );            //remove the first part from the start
			return strtolower( implode( '_', $parts ) );//bring the lowercase parts back together separated by "_"
		}

		/**
		 * Enqueues all assets
		 */
		protected function enqueue_all() {
			$this->manager->enqueue_assets();
			$this->do_action( 'enqueue_assets' );

			//enqueue any scripts provided from config
			if ( isset( $this->config['scripts'] ) ) {
				foreach ( $this->config['scripts'] as $script ) {
					wp_enqueue_script( $script['handle'], $script['src'], $script['deps'], $script['ver'], isset( $script['in_footer'] ) ? $script['in_footer'] : false );
				}
			}

			//enqueue any styles provided from config
			if ( isset( $this->config['styles'] ) ) {
				foreach ( $this->config['styles'] as $style ) {
					wp_enqueue_style( $style['handle'], $style['src'], $style['deps'], $style['ver'], isset( $style['media'] ) ? $style['media'] : 'all' );
				}
			}
		}

		/**
		 * Returns an array of container classes
		 *
		 * @return array
		 */
		function get_container_classes() {
			$classes[] = 'foofields-container';
			if ( ! isset( $this->config['layout'] ) ) {
				if ( isset( $this->config['tabs'] ) ) {
					$classes[] = 'foofields-tabs-vertical';
				}
			} else {
				$classes[] = $this->config['layout'];
			}

			return $classes;
		}

		/**
		 * merges fields passed into the constructor with merges from get_fields()
		 */
		protected function load_fields() {
			if ( count( $this->fields ) === 0 ) {

				$must_parse = false;

				//get any fields
				$fields = $this->get_fields();
				if ( !empty( $fields ) ) {
					$this->config['fields'] = $fields;
					$must_parse = true;
				}

				//get any tabs
				$tabs = $this->get_tabs();
				if ( !empty( $tabs ) ) {
					$this->config['tabs'] = $tabs;
					$must_parse = true;
				}

				if ( $must_parse ) {
					//parse the config again, if we need to
					$this->parse_config();
				}
			}
		}

		/**
		 * Allows the fields to be specified in a function, rather than being passed into the constructor
		 *
		 * @return array
		 */
		protected function get_fields() {
			if ( isset( $this->config['fields'] ) ) {
				return $this->config['fields'];
			}

			return array();
		}

		/**
		 * Allows the tabs to be specified in a function, rather than being passed into the constructor
		 *
		 * @return array
		 */
		protected function get_tabs() {
			if ( isset( $this->config['tabs'] ) ) {
				return $this->config['tabs'];
			}

			return array();
		}

		/**
		 * Renders all tabs and fields for a container
		 */
		function render_container() {
			if ( ! $this->has_fields() ) {
				return; //do nothing!
			}

			$this->render_fields_before();

			self::render_html_tag( 'div', array(
				'id' => $this->container_id() . '-container',
				'class' => implode( ' ', $this->get_container_classes() )
			), null, false );

			//render the state input
			self::render_html_tag( 'input', array(
				'type' => 'hidden',
				'name' => $this->container_id() . '[' . self::STATE_KEY . ']',
				'value' => $this->container_last_state_value()
			) );

			$this->render_tabs();

			$this->render_content( $this->config );

			echo '</div>';

			$this->render_fields_after();
		}

		protected function render_fields_before() {
			//does nothing by default
		}

		protected function render_fields_after() {
			//does nothing by default
		}

		/**
		 * Do any processing of errors before the fields are rendered
		 */
		function process_state_errors( $config = null) {
			$state = $this->get_state();

			//get errors

		}

		/**
		 * Renders the tabs
		 */
		function render_tabs() {
			if ( ! isset( $this->config['tabs'] ) ) {
				return;
			}

			echo '<ul class="foofields-tabs">';
			foreach ( $this->get_ordered_array( $this->config['tabs'] ) as $tab ) {
				$this->render_tab( $tab );
			}
			echo '</ul>';
		}

		/**
		 * Returns a sorted array of things (tabs or fields)
		 *
		 * @param $things
		 *
		 * @return
		 */
		function get_ordered_array( $things ) {
			uasort( $things, array( $this, 'sort_order' ) );

			return $things;
		}

		/**
		 * Used to sort things
		 *
		 * @param mixed $a
		 * @param mixed $b
		 *
		 * @return int
		 */
		function sort_order( $a, $b ) {
			if ( isset( $a['order'] ) && isset( $b['order'] ) ) {
				if ( $a['order'] === $b['order'] ) {
					return 0;
				}
				return ( $a['order'] < $b['order'] ) ? -1 : 1;
			}

			return 0;
		}

		/**
		 * Renders a tab
		 *
		 * @param $tab
		 * @param string $class
		 * @param string $anchor_class
		 */
		function render_tab( $tab, $class = 'foofields-tab', $anchor_class = 'foofields-tab-link' ) {
			$tab_content_id = $tab['id'];
			$sorted_tabs = null;

			if ( isset( $tab['tabs'] ) ) {
				$sorted_tabs = $this->get_ordered_array( $tab['tabs'] );
			}

			if ( ! isset( $tab['fields'] ) && isset( $sorted_tabs ) ) {
				//set the tab_id to be the first child tab
				$tab_content_id = $sorted_tabs[0]['id'];
			}

			$tab_id = $this->get_unique_id( array( 'id' => $tab_content_id ) );

			$tab_classes = array( $class );

			//check if the tab content is hidden, if so then hide the tab too
			if ( isset( $tab['class'] ) && strpos( $tab['class'], 'foofields-hidden' ) !== false ) {
				$tab_classes[] = 'foofields-hidden';
			} else {
				if ( !$this->show_rule_is_visible( $tab_id, 'tabs' ) ) {
					$tab_classes[] = 'foofields-hidden';
				}
			}

			$last_state = $this->container_last_state_value();
			$content_id = $tab_id . '-content';

			if ( $last_state === $content_id ) {
				$tab_classes[] = 'foofields-active';
			}

			$tab_attributes = array(
				'id' => $tab_id . '-tab',
				'class' => implode(' ', $tab_classes )
			);
			$tab_data_attributes = isset( $tab['data'] ) ? $tab['data'] : array();

			if ( count( $tab_data_attributes ) > 0 ) {
				$tab_attributes = array_merge( $tab_attributes, $this->process_data_attributes( $tab_data_attributes ) );
			}

			self::render_html_tag( 'li', $tab_attributes, null, false );
			self::render_html_tag( 'a', array( 'class' => $anchor_class, 'href' => '#' . $content_id ), null, false );
			if ( isset( $tab['icon'] ) ) {
				if ( strpos( $tab['icon'], 'dashicons' ) === 0 ) {
					self::render_html_tag( 'span', array( 'class' => 'foofields-tab-icon dashicons ' . $tab['icon'] ) );
				} else {
					self::render_html_tag( 'span', array( 'class' => 'foofields-tab-icon' ), $tab['icon'] );
				}
			}
			self::render_html_tag( 'span', array( 'class' => 'foofields-tab-text' ), $tab['label'] );

			//check if there are any errors stored in the state
			$errors = $this->get_field_errors( $tab_content_id );
			if ( $errors !== false && is_array( $errors ) && count( $errors ) > 0 ) {
                // phpcs:disable WordPress.WP.I18n.MissingSingularPlaceholder
				self::render_html_tag( 'span', array(
					'class' => 'foofields-tab-error',
                    // Translators: %s is the number of errors
					'title' => sprintf( _n( 'There is an error. Click to see more info.', 'There are %s errors. Click to see more info.', count( $errors ), $this->manager->text_domain ), count( $errors ) )
				), count( $errors ) );
                // phpcs:enable
			}

			echo '</a>';
			if ( isset( $sorted_tabs ) ) {
				echo '<ul class="foofields-tab-menu">';
				foreach ( $sorted_tabs as $child_tab ) {
					self::render_tab( $child_tab, 'foofields-tab-menu-item', 'foofields-tab-menu-link' );
				}
				echo '</ul>';
			}
			echo '</li>';
		}

		/**
		 * Get fields errors for a particular parent (usually a tab)
		 * @param $parent_id
		 *
		 * @return mixed
		 */
		function get_field_errors( $parent_id ) {
			if ( !array_key_exists( $parent_id, $this->state_field_errors ) ) {
				$errors = array();

				$state = $this->get_state();

				if ( isset( $state['__errors'] ) ) {
					foreach ( $this->fields as $field ) {
						if ( $field->parent_id === $parent_id && array_key_exists( $field->field_data_key(), $state['__errors'] ) ) {
							$errors[] = $state['__errors'][$field->field_data_key()]['message'];
						}
					}
				}

				$this->state_field_errors[$parent_id] = $errors;
			}

			return $this->state_field_errors[$parent_id];
		}

		/**
		 * Renders the tab content
		 *
		 * @param $content
		 */
		function render_content( $content, $parent_id = null ) {
			if ( isset( $content['fields'] ) ){
				$classes[] = 'foofields-content';
				if ( isset( $content['class'] ) ) {
					$classes[] = $content['class'];
				}
				$content_id = $this->get_unique_id( $content ) . '-content';

				$last_state = $this->container_last_state_value();

				if ( $last_state === $content_id ) {
					$classes[] = 'foofields-active';
				}

				self::render_html_tag( 'div', array(
					'class' => implode( ' ', $classes ),
					'id'    => $content_id
				), null, false );

				if ( $parent_id !== null ) {
					//check if there are any errors stored in the state, and render them
					$errors = $this->get_field_errors( $parent_id );
					if ( $errors !== false && is_array( $errors ) && count( $errors ) > 0 ) {
                        // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
						$error_message = '<strong>' . esc_html( __( 'The following errors were found:', $this->manager->text_domain ) ) . '</strong><br />';
						$error_message .= implode( '<br />', $errors );

						$error_field_config = array(
							'id'    => 'errors_' . $parent_id,
							'type'  => 'error',
							'text'  => $error_message,
							'class' => 'foofields-colspan-4'
						);

						$error_field_object = $this->create_field_instance( 'error', $error_field_config, $parent_id );
						$error_field_object->pre_render();
						$error_field_object->render( false );
					}
				}

				$this->render_fields( $content['fields'] );

				echo '</div>';
			}
			if ( isset( $content['tabs'] ) ) {
				foreach ( $content['tabs'] as $tab ) {
					self::render_content( $tab, $tab['id'] );
				}
			}
		}

		/**
		 * Renders a group of fields
		 *
		 * @param array $fields
		 */
		function render_fields( $fields ) {
			foreach ( $this->get_ordered_array( $fields ) as $field_config ) {
				$field_id = $this->get_unique_id( $field_config );
				if ( array_key_exists( $field_id, $this->fields ) ) {
					$field_instance = $this->fields[ $field_id ];
					$field_instance->pre_render();
					$field_instance->render();
				}
			}
		}

		/***
		 * Returns the field value from state, or gets a default value
		 *
		 * @param $field_config
		 *
		 * @return mixed|string
		 */
		function get_state_value( $field_config ) {
			$state = $this->get_state();

			if ( is_array( $state ) && array_key_exists( $field_config['id'], $state ) ) {
				return $state[ $field_config['id'] ];
			}

			if ( isset( $field_config['default'] ) ) {
				return $field_config['default'];
			}

			return '';
		}

		/**
		 * Returns the saved state for the container
		 *
		 * @return mixed|string
		 */
		function container_last_state_value() {
			$state = $this->get_state();

			if ( array_key_exists( self::STATE_KEY, $state ) ) {
				return $state[self::STATE_KEY];
			}

			return '';
		}

		/**
		 * Get the sanitized posted data for the container
		 *
		 * @param $post_id
		 *
		 * @return mixed|void
		 */
		protected function get_posted_data() {

			$sanitized_data = $this->safe_get_from_request( $this->container_id(), array(), false );

			$posted_data = array();
			//loop through our flat list of fields and get all the data
			foreach ( $this->fields as $field ) {
				$posted_field_data = $field->get_posted_value( $sanitized_data );

				//validate the field
				if ( ! $field->validate( $posted_field_data ) ) {

					//add an error to the posted_data so that it is persisted
					if ( ! isset( $posted_data['__errors'] ) ) {
						$posted_data['__errors'] = array();
					}
					$posted_data['__errors'][ $field->field_data_key() ] = array(
						'message' => $field->error
					);
				}

				//if we got a value then add it to our posted_data
				if ( isset( $posted_field_data ) ) {
					$posted_data[ $field->field_data_key() ] = $posted_field_data;
				}
			}

			//get the container last state
			if ( array_key_exists( self::STATE_KEY, $sanitized_data ) ) {
				$posted_data[self::STATE_KEY] = $sanitized_data[self::STATE_KEY];
			}

			return $this->apply_filters( 'get_posted_data', $posted_data, $this );
		}

		/**
		 * Process any special data attributes
		 * @param $key
		 * @param $value
		 *
		 * @return array
		 */
		function process_data_attribute( $key, $value ) {
			if ( 'show-when' === $key && is_array( $value ) ) {
				$value['field'] = $this->get_unique_id( array( 'id' => $value['field'] ) ) . '-field';
			}
			return is_array( $value ) ? $this->json_encode( $value ) : $value;
		}

		/**
		 * JSON encodes the provides options array into a string
		 *
		 * @param $options
		 *
		 * @return array|false|string
		 */
		function json_encode( $options ){
			if ( defined( 'JSON_UNESCAPED_UNICODE' ) ) {
				return wp_json_encode( $options, JSON_UNESCAPED_UNICODE );
			} else {
				return wp_json_encode( $options );
			}
		}

		/***
		 * Process all data attributes before they are rendered
		 *
		 * @param $data_attributes
		 *
		 * @return array
		 */
		function process_data_attributes( $data_attributes ) {
			$result = array();
			if ( is_array( $data_attributes ) && count( $data_attributes ) > 0 ) {
				foreach ( $data_attributes as $key => $value ) {
					$process_value = $this->process_data_attribute( $key, $value );
					if ( strpos( $key, 'data-') !== 0 ) {
						$key = 'data-' . $key;
					}
					$result[$key] = $process_value;
				}
			}
			return $result;
		}
	}
}
