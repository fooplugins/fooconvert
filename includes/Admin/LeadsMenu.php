<?php

namespace FooPlugins\FooConvert\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( __NAMESPACE__ . '\LeadsMenu' ) ) {

    /**
     * Registers the FooConvert leads submenu in wp-admin.
     */
    class LeadsMenu {
        /**
         * Hooks the leads page into the admin menu and asset loading flow.
         *
         * @return void
         */
        public function __construct() {
            add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        }

        /**
         * Registers the leads submenu page beneath the main FooConvert menu.
         *
         * @return void
         */
        public function add_menu_page() {
            add_submenu_page(
                'fooconvert',
                __( 'Leads', 'fooconvert' ),
                __( 'Leads', 'fooconvert' ),
                'manage_options',
                'fooconvert-leads',
                array( $this, 'render_page' )
            );
        }

        /**
         * Enqueues assets for the leads screen only.
         *
         * @param string $hook The current admin page hook suffix.
         * @return void
         */
        public function enqueue_scripts( $hook ) {
            if ( 'fooconvert_page_fooconvert-leads' !== $hook ) {
                return;
            }

            wp_enqueue_style(
                'fooconvert-leads',
                FOOCONVERT_INCLUDES_URL . 'Admin/Views/leads.css',
                array(),
                FOOCONVERT_VERSION
            );

            wp_enqueue_script(
                'fooconvert-leads',
                FOOCONVERT_INCLUDES_URL . 'Admin/Views/leads.js',
                array( 'jquery' ),
                FOOCONVERT_VERSION,
                true
            );
        }

        /**
         * Renders the leads admin page template.
         *
         * @return void
         */
        public function render_page() {
            include_once FOOCONVERT_INCLUDES_PATH . 'Admin/Views/leads.php';
        }
    }
}
