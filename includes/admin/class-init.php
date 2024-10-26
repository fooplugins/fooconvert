<?php
namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\FooConvert;

/**
 * FooConvert Admin Init Class
 * Runs all classes that need to run in the admin
 */

if ( !class_exists( 'FooPlugins\FooConvert\Admin\Init' ) ) {

    class Init
    {

        /**
         * Init constructor.
         */
        function __construct()
        {
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
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
            /** @noinspection PhpUndefinedFunctionInspection - shows as unresolvable in my IDE - see https://developer.wordpress.org/reference/functions/add_menu_page/ */
            add_menu_page(
                __( 'FooConvert', 'fooconvert' ),
                __( 'FooConvert', 'fooconvert' ),
                'manage_options',
                FOOCONVERT_MENU_SLUG,
                '__return_null',
                'dashicons-chart-bar'
            );

            foreach ( FooConvert::plugin()->widgets->get_post_types() as $post_type ) {

                if ( post_type_exists( $post_type ) ) {

                    $post_type_object = get_post_type_object( $post_type );
                    $menu_title = $post_type_object->label;
                    $capability = $post_type_object->cap->edit_posts;
                    $menu_slug = 'edit.php?post_type=' . $post_type;

                    add_submenu_page(
                        FOOCONVERT_MENU_SLUG, // Parent slug (top-level menu slug)
                        $menu_title,                    // Page title
                        $menu_title,                    // Submenu title
                        $capability,                    // Capability required
                        $menu_slug,                     // Submenu slug (unique identifier)
                        null                    // No custom callback (uses default CPT screen)
                    );
                }
            }

            // Hide the top submenu by removing it.
            remove_submenu_page( FOOCONVERT_MENU_SLUG, FOOCONVERT_MENU_SLUG );

//            add_submenu_page(
//                FOOCONVERT_MENU_SLUG,    // Parent slug (top-level menu slug)
//                __( 'Help', 'fooconvert' ),        // Page title
//                __( 'Help', 'fooconvert' ),        // Submenu title
//                'manage_options',         // Capability required
//                FOOCONVERT_MENU_SLUG,     // Submenu slug (unique identifier)
//                'fooconvert_welcome_page'  // Function to display the content
//            );
        }
    }
}