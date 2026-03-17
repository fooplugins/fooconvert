<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Data\Schema;
use FooPlugins\FooConvert\FooConvert;

if ( !class_exists( 'FooPlugins\FooConvert\Admin\Init' ) ) {

    class Init {
        function __construct() {
            add_action( 'admin_init', array( $this, 'check_database' ) );
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
            add_action( 'in_admin_header', array( $this, 'add_custom_header' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueues' ) );

            new namespace\Stats();
            new namespace\Dashboard();
            new namespace\ContainerManager();
            new namespace\Settings();
            new namespace\LeadsMenu();
            if ( fooconvert_fs()->is_not_paying() ) {
                new namespace\Promotions();
            }

            add_filter( 'block_editor_settings_all', function( $settings, $context ) {
                if ( FooConvert::plugin()->widgets->is_editor() ) {
                    $settings['localAutosaveInterval'] = 0;
                    $settings['autosaveInterval'] = 0;
                }

                return $settings;
            }, 10, 2 );
        }

        public function check_database() {
            $schema = new Schema();
            $schema->create_event_table_if_needed();
        }

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
                        FOOCONVERT_MENU_SLUG,
                        $menu_title,
                        $menu_title,
                        $capability,
                        $menu_slug,
                        null
                    );
                }
            }

            do_action( 'fooconvert_admin_menu_after_post_types' );
        }

        private function is_valid_page() {
            if ( function_exists( 'get_current_screen' ) ) {
                $current_screen = get_current_screen();

                if ( str_contains( $current_screen->id, 'fooconvert-pricing' ) ) {
                    return false;
                } else if ( str_contains( $current_screen->id, FOOCONVERT_MENU_SLUG ) ) {
                    return true;
                }

                $current_post_Type = $current_screen->post_type;
                if ( in_array( $current_post_Type, FooConvert::plugin()->widgets->get_post_types(), true ) ) {
                    return $current_screen->base !== 'post';
                }
            }

            return false;
        }

        public function admin_enqueues() {
            if ( $this->is_valid_page() ) {
                wp_enqueue_style(
                    'fooconvert-admin',
                    FOOCONVERT_ASSETS_URL . 'admin/css/admin.css',
                    null,
                    FOOCONVERT_VERSION
                );
            }
        }

        public function add_custom_header() {
            if ( $this->is_valid_page() ) {
                $current_screen = get_current_screen();

                $drop = '';
                $pages = array( 'contact', 'pricing' );
                foreach ( $pages as $page ) {
                    $check = FOOCONVERT_MENU_SLUG . '-' . $page;
                    if ( str_contains( $current_screen->id, $check ) ) {
                        $drop = 'drop';
                        break;
                    }
                }
                ?>
                <div class="fooconvert-admin-header <?php echo esc_attr( $drop ); ?>">
                    <div class="fooconvert-title">
                        <img class="fooconvert-logo" src="<?php echo esc_url( FOOCONVERT_ASSETS_URL . 'admin/img/horizontal-logo-50.png' ); ?>" alt="FooConvert Logo">
                        <p>Version <?php echo esc_html( FOOCONVERT_VERSION ); ?></p>
                    </div>
                </div>
                <?php
            }
        }
    }
}
