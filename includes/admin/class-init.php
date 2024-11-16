<?php
namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Data\Schema;
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
            add_action( 'admin_init', array( $this, 'check_database' ) );
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
            add_action( 'in_admin_header', array($this, 'add_custom_header') );
            add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueues') );

            new Stats();
        }

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
                'dashicons-chart-bar'
            );

            add_submenu_page(
                FOOCONVERT_MENU_SLUG,
                __( 'Dashboard', 'fooconvert' ),
                __( 'Dashboard', 'fooconvert' ),
                'manage_options',
                FOOCONVERT_MENU_SLUG,
                function() {
                    require_once FOOCONVERT_INCLUDES_PATH . 'admin/views/dashboard.php';
                }
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
        }

        private function is_valid_page() {
            /**
             * Check whether the get_current_screen function exists
             * because it is loaded only after 'admin_init' hook.
             */
            if ( function_exists( 'get_current_screen' ) ) {
                $current_screen = get_current_screen();

                if ( $current_screen->id === 'toplevel_page_' . FOOCONVERT_MENU_SLUG ) {
                    return true;
                }

                if ( str_starts_with( $current_screen->id, 'fooconvert_page_fooconvert-' ) ) {
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
                if ( FOOCONVERT_MENU_SLUG . '-contact' === $current_screen->base ||
                    FOOCONVERT_MENU_SLUG . '-pricing' === $current_screen->base ) {
                    $drop = 'drop';
                } else {
                    $drop = '';
                }
                ?>
                <div class="fooconvert-admin-header <?php
                echo esc_attr( $drop );
                ?>">
                    <div class="fooconvert-title">
                        <img class="fooconvert-logo" src="<?php
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