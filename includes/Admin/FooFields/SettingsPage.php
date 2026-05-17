<?php

/**
 * An settings container that will house fields
 */

namespace FooPlugins\FooConvert\Admin\FooFields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( __NAMESPACE__ . '\SettingsPage' ) ) {

    /**
     * Class SettingsPage.
     */
    abstract class SettingsPage extends Container {

        protected $settings_id;
        protected $menu_parent_slug;
        protected $capability;
        protected $menu_position;

        /**
         * Initializes the SettingsPage.
         */
        function __construct( $config ) {
            parent::__construct( $config );

            $this->settings_id = $this->config['settings_id'];
            $this->menu_parent_slug = isset( $this->config['menu_parent_slug'] ) ? $this->config['menu_parent_slug'] : 'options-general.php';
            $this->capability = isset( $this->config['capability'] ) ? $this->config['capability'] : 'manage_options';
            $this->menu_position = isset( $this->config['position'] ) ? $this->config['capability'] : null;

            //add the menu for the settings page
            add_action( 'admin_menu', array( $this, 'add_menu' ) );

            //register settings
            add_action( 'admin_init', array( $this, 'init_settings' ) );

            //enqueue assets needed for the settings page
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        }

        /**
         * Gets the translatable menu title
         *
         * @return string
         */
        function get_menu_title() {
            // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
            return __( 'Settings', $this->manager->text_domain );
            // phpcs:enable
        }

        /**
         * Gets the translatable title of the page
         *
         * @return string
         */
        function get_page_title() {
            // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
            return __( 'Settings', $this->manager->text_domain );
            // phpcs:enable
        }

        /**
         * Validate the config to ensure we have everything for a metabox
         */
        function validate_config() {
            parent::validate_config();

            if ( !isset( $this->config['settings_id'] ) ) {
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
                $this->add_config_validation_error( __( 'ERROR : There is no "settings_id" value set for the settings page, which means nothing will be saved!', $this->manager->text_domain ) );
            }
        }

        /**
         * Get the data saved in the options table
         *
         * @return array|mixed
         */
        function get_state() {
            if ( !isset( $this->state ) ) {

                //get the state from the post meta
                $state = get_option( $this->container_id() );

                if ( !is_array( $state ) ) {
                    $state = array();
                }

                $state = $this->apply_filters( 'get_state', $state );

                $this->state = $state;
            }

            return $this->state;
        }

        /**
         * Builds up a simple identifier for the container
         * @return mixed|string
         */
        function container_id() {
            return $this->config['settings_id'] . '-settings';
        }

        /**
         * Add menu to the tools menu
         */
        public function add_menu() {
            add_submenu_page(
                $this->menu_parent_slug,
                $this->get_page_title(),
                $this->get_menu_title(),
                $this->capability,
                $this->container_id(),
                array( $this, 'render_settings_page' ),
                $this->menu_position
            );
        }

        /**
         * Override the load_fields function to only load fields if the settings page is being displayed
         */
        protected function load_fields() {
            if ( $this->is_settings_page() ) {
                parent::load_fields();
            }
        }

        /**
         * Renders the contents for the settings page
         */
        public function render_settings_page() {
            ?>
            <div class="wrap">
                <h2><?php echo esc_html( $this->get_page_title() ); ?></h2>
                <?php if ( function_exists( 'settings_errors' ) ) {
                    settings_errors();
                } ?>
                <form action="options.php" method="post">
                    <?php settings_fields( $this->container_id() ); ?>

                    <?php $this->render_container(); ?>

                    <p class="submit">
                        <input name="submit" class="button-primary" type="submit"
                               value="<?php
                               // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
                               esc_attr_e( 'Save Changes', $this->manager->text_domain ); ?>"/>
                        <input name="<?php echo esc_attr( $this->container_id() ); ?>[reset-defaults]"
                               onclick="return confirm('<?php
                               // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
                               esc_attr_e( 'Are you sure you want to restore all settings back to their default values?', $this->manager->text_domain ); ?>');"
                               class="button-secondary" type="submit"
                               value="<?php
                               // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
                               esc_attr_e( 'Restore Defaults', $this->manager->text_domain ); ?>"/>
                    </p>
                </form>
            </div>
            <?php
        }

        /***
         * Enqueue the assets needed by the metabox
         */
        /**
         * Enqueues assets.
         */
        function enqueue_assets() {
            if ( $this->is_settings_page() ) {
                // Register, enqueue scripts and styles here
                $this->enqueue_all();
            }
        }

        /**
         * Returns true if the settings page is being displayed
         * @return bool
         */
        function is_settings_page() {
            // If AJAX, bail early
            if ( $this->is_settings_page_ajax() ) {
                return true;
            }

            // On normal admin screen load
            if ( function_exists('get_current_screen') ) {
                $screen = get_current_screen();
                if ( is_object($screen) && strpos($screen->id, $this->container_id()) !== false ) {
                    return true;
                }
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Settings API option_page value is used only to detect the current settings page.
            $option_page = isset( $_POST['option_page'] ) ? sanitize_text_field( wp_unslash( $_POST['option_page'] ) ) : '';

            if ( $option_page === $this->container_id() ) {
                // This IS your settings page save
                return true;
            }

            return false;
        }

        /**
         * Returns true if an ajax call has been made from the settings page
         * @return bool
         */
        function is_settings_page_ajax() {
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

                $referrer = wp_get_referer();
                $url = wp_parse_url( $referrer, PHP_URL_QUERY );
                if ( !empty( $url ) ) {
                    parse_str( $url, $query );
                    return isset( $query['page'] ) && $this->container_id() === $query['page'];
                }
            }
            return false;
        }

        /**
         * Override the container classes for metaboxes
         *
         * @return array
         */
        function get_container_classes() {
            $classes = parent::get_container_classes();

            $classes[] = 'foofields-style-settings';

            return $classes;
        }

        /**
         * Register settings
         */
        function init_settings() {
            register_setting( $this->container_id(), $this->container_id(), array( 'sanitize_callback' => array( $this, 'sanitize_callback' ) ) );

            //ensure the fields are loaded in ajax requests
            if ( $this->is_settings_page_ajax() ) {
                $this->load_fields();
            }
        }

        // validate our settings
        /**
         * Handles sanitize callback.
         */
        function sanitize_callback( $input ) {

            //check to see if the options were reset
            if ( isset ( $input['reset-defaults'] ) ) {
                delete_option( $this->container_id() );
                add_settings_error(
                    'reset',
                    'reset_error',
                    // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
                    __( 'Settings restored to default values', $this->manager->text_domain ),
                    'updated'
                );

                return false;
            } else {
                $settings_data = $this->get_posted_data();

                if ( is_array( $settings_data ) ) {
                    $input = $settings_data;
                }
            }

            return $input;
        }
    }
}
