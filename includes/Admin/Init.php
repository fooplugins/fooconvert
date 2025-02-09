<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Data\Schema;
use FooPlugins\FooConvert\FooConvert;

/**
 * FooConvert Admin Init Class
 * Runs all classes that need to run in the admin
 */

if ( !class_exists( 'FooPlugins\FooConvert\Admin\Init' ) ) {

    class Init {

        /**
         * Init constructor.
         */
        function __construct() {
            add_action( 'admin_init', array( $this, 'check_database' ) );
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
            add_action( 'in_admin_header', array( $this, 'add_custom_header' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueues' ) );

            new namespace\Stats();
            new namespace\Dashboard();
            new namespace\ContainerManager();
            new namespace\Settings();
            if ( fooconvert_fs()->is_not_paying() ) {
                // Only run this code if the PRO version is not running.
                new namespace\Promotions();
            }
        }

        /**
         * Checks and ensures the necessary database tables are created.
         *
         * This method initializes the Schema class and calls the function
         * to create the event table if it does not already exist.
         *
         * @access public
         * @since 1.0.0
         */
        public function check_database() {
            $schema = new Schema();
            $schema->create_event_table_if_needed();
        }

        /**
         * Callback for the `admin_menu` action.
         *
         * This hook registers the root FooConvert menu for the plugin.
         *
         * @access public
         * @since 1.0.0
         */
        public function register_menu() {
            add_menu_page(
                __( 'FooConvert', 'fooconvert' ),
                __( 'FooConvert', 'fooconvert' ),
                'manage_options',
                FOOCONVERT_MENU_SLUG,
                '__return_null',
                'dashicons-chart-bar',
                50
            );

            do_action( 'fooconvert_admin_menu_before_post_types' );

            foreach ( FooConvert::plugin()->widgets->get_post_types() as $post_type ) {

                if ( post_type_exists( $post_type ) ) {

                    $post_type_object = get_post_type_object( $post_type );
                    $menu_title = $post_type_object->label;
                    $capability = $post_type_object->cap->edit_posts;
                    $menu_slug = 'edit.php?post_type=' . $post_type;

                    add_submenu_page(
                        FOOCONVERT_MENU_SLUG,           // Parent slug (top-level menu slug)
                        $menu_title,                    // Page title
                        $menu_title,                    // Submenu title
                        $capability,                    // Capability required
                        $menu_slug,                     // Submenu slug (unique identifier)
                        null                    // No custom callback (uses default CPT screen)
                    );
                }
            }

            do_action( 'fooconvert_admin_menu_after_post_types' );
        }

        private function is_valid_page() {
            /**
             * Check whether the get_current_screen function exists
             * because it is loaded only after 'admin_init' hook.
             */
            if ( function_exists( 'get_current_screen' ) ) {
                $current_screen = get_current_screen();

                // Do not show the header on the pricing page.
                if ( str_contains( $current_screen->id, 'fooconvert-pricing' ) ) {
                    return false;
                }

                // Else, if the current screen is a fooconvert-related screen.
                else if ( str_contains( $current_screen->id, FOOCONVERT_MENU_SLUG ) ) {
                    return true;
                }

                $current_post_Type = $current_screen->post_type;
                if ( in_array( $current_post_Type, FooConvert::plugin()->widgets->get_post_types(), true ) ) {
                    return $current_screen->base !== 'post';
                }
            }

            return false;
        }


        /**
         * Enqueues.
         *
         * @return void
         */
        public function admin_enqueues() {
            if ( $this->is_valid_page() ) {
                wp_enqueue_style(
                    'fooconvert-admin',
                    FOOCONVERT_ASSETS_URL . 'admin/css/admin.css',
                    null,
                    FOOCONVERT_VERSION
                );

//                wp_enqueue_script(
//                    'fooconvert-admin',
//                    FOOCONVERT_ASSETS_URL . 'admin/js/admin.js',
//                    null,
//                    FOOCONVERT_VERSION,
//                    true
//                );
            }
        }

        /**
         * Add a custom header to our admin pages.
         *
         * @return void
         */
        public function add_custom_header() {
            if ( $this->is_valid_page() ) {
                $current_screen = get_current_screen();

                $drop = '';
                $pages = [ 'contact', 'pricing' ];
                foreach ( $pages as $page ) {
                    $check = FOOCONVERT_MENU_SLUG . '-' . $page;
                    if ( str_contains( $current_screen->id, $check ) ) {
                        $drop = 'drop';
                        break;
                    }
                }
                ?>
                <div class="fooconvert-admin-header <?php
                echo esc_attr( $drop );
                ?>">
                    <div class="fooconvert-title">
                        <img class="fooconvert-logo"
                             src="<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                             echo esc_url( FOOCONVERT_ASSETS_URL . 'admin/img/horizontal-logo-50.png' );
                             ?>" alt="FooConvert Logo">
                        <p>Version <?php
                            echo esc_html( FOOCONVERT_VERSION );
                            ?></p>
                    </div>
                </div>
                <?php
            }
        }
    }
}