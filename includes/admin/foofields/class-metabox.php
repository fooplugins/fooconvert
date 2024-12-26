<?php

namespace FooPlugins\FooConvert\Admin\FooFields;

if ( ! class_exists( __NAMESPACE__ . '\Metabox' ) ) {

	abstract class Metabox extends Container {

		protected $post;

		protected $post_id;

		function __construct( $config ) {
			parent::__construct( $config );

			//add the metaboxes for a person
			add_action( 'add_meta_boxes_' . $this->config['post_type'], array( $this, 'add_meta_boxes' ) );

			//save extra post data for a person
			add_action( 'save_post', array( $this, 'save_post' ) );

			//enqueue assets needed for this metabox
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			if ( isset( $config['disable_close'] ) && $config['disable_close'] === true ) {
				$filter = 'postbox_classes_' . $this->config['post_type'] . '_' . $this->container_id();
				add_filter( $filter, array( $this, 'ensure_metabox_not_closed' ) );
			}

			add_action( 'admin_init', array( $this, 'init_fields_for_ajax' ) );
		}

		/**
		 * Validate the config to ensure we have everything for a metabox
		 */
		function validate_config() {
			parent::validate_config();

			if ( ! isset( $this->config['meta_key'] ) ) {
				if ( isset( $this->config['suppress_meta_key_error'] ) && $this->config['suppress_meta_key_error'] ) {
					//Do nothing. Suppress the error message for the missing meta_key
				} else {
                    // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
					$this->add_config_validation_error( __( 'WARNING : There is no "meta_key" value set for the metabox, which means nothing will be saved! If this is intentional, then set "suppress_meta_key_error" to true.', $this->manager->text_domain ) );
				}
			}
		}

		/**
		 * Builds up an identifier from post_type and metabox_id
		 * @return string
		 */
		public function container_id() {
			return $this->config['post_type'] . '-' . $this->config['metabox_id'];
		}

		public function get_unique_id( $config ) {
			if ( !isset( $config[ 'id' ] ) && isset( $config[ 'metabox_id' ] ) ) {
				$config[ 'id' ] = $config[ 'metabox_id' ];
			}
			return parent::get_unique_id( $config );
		}

		/**
		 * Add metaboxe to the CPT
		 *
		 * @param $post
		 */
		function add_meta_boxes( $post ) {
			$this->post = $post;

			if ( ! isset( $this->config['metabox_render_function'] ) ) {
				$this->config['metabox_render_function'] = array( $this, 'render_metabox' );
			}

			if ( $this->apply_filters( 'must_add_meta_boxes', true ) ) {
				add_meta_box(
						$this->container_id(),
						$this->config['metabox_title'],
						$this->config['metabox_render_function'],
						$this->config['post_type'],
						isset( $this->config['context'] ) ? $this->config['context'] : 'normal',
						isset( $this->config['priority'] ) ? $this->config['priority'] : 'default'
				);
			}
		}

		/**
		 * Render the metabox contents
		 *
		 * @param $post
		 */
		public function render_metabox( $post ) {
			$full_id = $this->container_id();

			//render the nonce used to validate when saving the metabox fields
			?><input type="hidden" name="<?php echo esc_attr( $full_id ); ?>_nonce"
					 id="<?php echo esc_attr( $full_id ); ?>_nonce"
					 value="<?php echo esc_attr( wp_create_nonce( $full_id ) ); ?>"/><?php

			//allow custom metabox rendering
			$this->do_action( 'render', $post );

			//render any fields
			$this->render_container();
		}

		/**
		 * Get the data saved against the post
		 *
		 * @return array|mixed
		 */
		function get_state() {
			if ( ! isset( $this->state ) ) {

				$state = array();

				if ( isset( $this->config['meta_key'] ) ) {
					//get the state from the post meta
					$state = get_post_meta( $this->post->ID, $this->config['meta_key'], true );
				}

				$state = $this->apply_filters( 'get_state', $state, $this->post );

				if ( ! is_array( $state ) ) {
					$state = array();
				}

				$this->state = $state;
			}

			return $this->state;
		}

		/**
		 * Hook into the save post and save the fields
		 *
		 * @param $post_id
		 *
		 * @return mixed
		 */
		public function save_post( $post_id ) {
			// check autosave first
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			$this->post_id = $post_id;

			$full_id = $this->container_id();

			// verify nonce
			if ( array_key_exists( $full_id . '_nonce', $_POST ) &&
				 wp_verify_nonce( wp_unslash( sanitize_text_field( $_POST[ $full_id . '_nonce' ] ) ), $full_id ) ) {

				//if we get here, we are dealing with the correct metabox

				// unhook this function so it doesn't loop infinitely
				remove_action( 'save_post', array( $this, 'save_post' ) );

				//fire an action
				$this->do_action( 'save', $post_id );

				//if we have fields, then we can save that data
				if ( $this->has_fields() && $this->apply_filters( 'can_save', true, $this ) ) {

					//get the current state of the posted form
					$state = $this->get_posted_data();

					$this->do_action( 'before_save_post_meta', $post_id, $state );

					if ( isset( $this->config['meta_key'] ) ) {
						update_post_meta( $post_id, $this->config['meta_key'], $state );
					}

					$this->do_action( 'after_save_post_meta', $post_id, $state );
				}

				// re-hook this function
				add_action( 'save_post', array( $this, 'save_post' ) );
			}
		}

		/**
		 * Returns the post ID
		 * @return int
		 */
		function get_post_id() {
			if ( isset( $this->post_id ) ) {
				return $this->post_id;
			}
			if ( isset( $this->post ) ) {
				return $this->post->ID;
			}

			return 0;
		}

		/***
		 * Enqueue the assets needed by the metabox
		 */
		function enqueue_assets() {
			if ( $this->is_admin_edit_mode() && $this->is_current_post_type() ) {
				// Register, enqueue scripts and styles here
				$this->enqueue_all();
			}
		}

		/**
		 * Override the container classes for metaboxes
		 *
		 * @return array
		 */
		function get_container_classes() {
			$classes = parent::get_container_classes();

			$classes[] = 'foofields-style-metabox';

			return $classes;
		}

		/**
		 * Renders styling needed for a metabox
		 */
		function render_fields_before() {
			?>
			<style>
				<?php echo '#' . esc_attr( $this->container_id() ) . ' .inside' ?> {
					margin: 0;
					padding: 0;
				}
			</style>
			<?php
		}

		/**
		 * Returns the admin post type currently being viewed/edited
		 *
		 * @return string|null
		 */
		function get_admin_post_type() {
			global $post, $typenow, $current_screen, $pagenow;

			$post_type = null;

			if ( $post && ( property_exists( $post, 'post_type' ) || method_exists( $post, 'post_type' ) ) ) {
				$post_type = $post->post_type;
			}

			if ( empty( $post_type ) && ! empty( $current_screen ) && ( property_exists( $current_screen, 'post_type' ) || method_exists( $current_screen, 'post_type' ) ) && ! empty( $current_screen->post_type ) ) {
				$post_type = $current_screen->post_type;
			}

			if ( empty( $post_type ) && ! empty( $typenow ) ) {
				$post_type = $typenow;
			}

			if ( empty( $post_type ) && function_exists( 'get_current_screen' ) ) {
				$get_current_screen = get_current_screen();
				if ( property_exists( $get_current_screen, 'post_type' ) && ! empty( $get_current_screen->post_type ) ) {
					$post_type = $get_current_screen->post_type;
				}
			}

            // phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( empty( $post_type ) && isset( $_REQUEST['post'] ) && ! empty( $_REQUEST['post'] ) && function_exists( 'get_post_type' ) && $get_post_type = get_post_type( (int) $_REQUEST['post'] ) ) {
				$post_type = $get_post_type;
			}

			if ( empty( $post_type ) && isset( $_REQUEST['post_type'] ) && ! empty( $_REQUEST['post_type'] ) ) {
				$post_type = sanitize_key( $_REQUEST['post_type'] );
			}
            // phpcs:enable

			if ( empty( $post_type ) && 'edit.php' == $pagenow ) {
				$post_type = 'post';
			}

			return $post_type;
		}

		/**
		 * Returns true if current admin page is an edit page
		 * @return bool
		 */
		function is_admin_edit_mode() {
			global $pagenow;

			return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
		}

		/**
		 * Returns true if the current post type is the post type for the metabox
		 * @return bool
		 */
		function is_current_post_type() {
			return $this->get_admin_post_type() === $this->config['post_type'];
		}

		/**
		 * Returns true if an ajax call has been made from the metabox page
		 * @return bool
		 */
		function is_metabox_page_ajax() {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

				$action = $this->safe_get_from_request( 'action' );

				if ( $action === 'heartbeat' ) {
					return false;
				}

				return $this->config['post_type'] === $this->manager->get_post_type_from_ajax_request();
			}
			return false;
		}

		/**
		 * Ensures the metabox is not closed
		 *
		 * @param $classes
		 *
		 * @return array
		 */
		function ensure_metabox_not_closed( $classes ) {
			if ( is_array( $classes ) && in_array( 'closed', $classes ) ) {
				$classes = array_diff( $classes, array( 'closed' ) );
			}
			return $classes;
		}

		/**
		 * Make sure all fields are loaded for ajax calls
		 */
		function init_fields_for_ajax() {
			//ensure the fields are loaded in ajax requests
			if ( $this->is_metabox_page_ajax() ) {
				$this->load_fields();
			}
		}
	}
}
