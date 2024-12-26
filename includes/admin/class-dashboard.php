<?php
namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Event;
use FooPlugins\FooConvert\FooConvert;

/**
 * FooConvert Admin Dashboard Class
 */

if ( !class_exists( 'FooPlugins\FooConvert\Admin\Dashboard' ) ) {

    class Dashboard
    {
        /**
         * Init constructor.
         */
        function __construct()
        {
            add_action('fooconvert_admin_menu_before_post_types', array($this, 'register_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

            add_action('wp_ajax_fooconvert_dashboard_top_performers', array($this, 'fetch_top_performers'));
            add_action('wp_ajax_fooconvert_create_demo_widgets', array($this, 'create_demo_widgets'));
            add_action('wp_ajax_fooconvert_delete_demo_widgets', array($this, 'delete_demo_widgets'));
            add_action('wp_ajax_fooconvert_update_stats', array($this, 'update_stats'));
        }

        /**
         * AJAX callback to create demo widgets.
         *
         * This function fetches a nonce from the request and verifies it.
         * If the nonce is invalid, it dies with an error message.
         * Otherwise, it creates demo widgets and sends back a JSON success
         * response with the number of widgets created.
         *
         * @since 1.0.0
         */
        function create_demo_widgets() {
            //get nonce
            $nonce = sanitize_text_field( $_POST['nonce'] );
            if ( !wp_verify_nonce($nonce, 'fooconvert-dashboard' ) ) {
                wp_die( esc_html__( 'Invalid nonce!!', 'fooconvert' ) );
            }

            ob_start();
            $demo = new DemoContent();
            $created = $demo->create( true );
            $content = ob_get_clean();

            if ( !empty( $content ) ) {
                // TODO : There were errors creating demo content. Probably DB related, which need to be logged somewhere.
                // For now, they can be ignored.
            }

            fooconvert_set_setting( 'demo_content', 'on' );

            if ( $created === 0 ) {
                wp_send_json( ['message' => __( 'No widgets created!', 'fooconvert' ) ] );
            } else {
                // Translators: %d refers to the number of demo widgets created.
                wp_send_json( ['message' => sprintf( __( '%d demo widgets created successfully!', 'fooconvert' ), $created  ) ] );
            }
        }

        /**
         * AJAX callback to delete all demo widgets.
         *
         * This function fetches a nonce from the request and verifies it.
         * If the nonce is invalid, it dies with an error message.
         * Otherwise, it deletes all demo widgets and sends back a JSON success
         * response.
         *
         * @since 1.0.0
         */
        function delete_demo_widgets() {
            //get nonce
            $nonce = sanitize_text_field( $_POST['nonce'] );
            if ( !wp_verify_nonce($nonce, 'fooconvert-dashboard' ) ) {
                wp_die( esc_html__( 'Invalid nonce!!', 'fooconvert' ) );
            }

            $demo = new DemoContent();
            $demo->delete();

            fooconvert_set_setting( 'demo_content', '' );

            wp_send_json( ['message' => __( 'All demo widgets deleted!', 'fooconvert' ) ] );
        }

        /**
         * AJAX callback to fetch top performers for the dashboard.
         *
         * This function fetches a nonce from the request and verifies it.
         * If the nonce is invalid, it dies with an error message.
         * Otherwise, it sends back the top performers.
         *
         * @since 1.0.0
         */
        function fetch_top_performers() {
            //get nonce
            $nonce = sanitize_text_field( $_POST['nonce'] );
            if ( !wp_verify_nonce($nonce, 'fooconvert-dashboard' ) ) {
                wp_die( esc_html__( 'Invalid nonce!!', 'fooconvert' ) );
            }

            $sort = sanitize_text_field( $_POST['sort'] );
            if ( empty( $sort ) ) {
                $sort = 'engagement';
            }

            update_option( FOOCONVERT_OPTION_TOP_PERFORMERS_SORT, $sort );

            ob_start();
            require_once FOOCONVERT_INCLUDES_PATH . 'admin/views/dashboard-top-performers.php';
            $html = ob_get_clean();

            wp_send_json( ['html' => $html ] );
        }

        function update_stats() {
            //get nonce
            $nonce = sanitize_text_field( $_POST['nonce'] );
            if ( !wp_verify_nonce($nonce, 'fooconvert-dashboard' ) ) {
                wp_die( esc_html__( 'Invalid nonce!!', 'fooconvert' ) );
            }

            $stats = new \FooPlugins\FooConvert\Stats();
            $stats->update();

            wp_send_json( [
                'message' => fooconvert_stats_last_updated()
            ] );
        }

        /**
         * Callback for the `admin_menu` action.
         *
         * This hook registers dashboard page
         *
         * @access public
         * @since 1.0.0
         */
        public function register_menu() {

            add_submenu_page(
                FOOCONVERT_MENU_SLUG,
                __( 'FooConvert Dashboard', 'fooconvert' ),
                __( 'Dashboard', 'fooconvert' ),
                'manage_options',
                FOOCONVERT_MENU_SLUG,
                function() {
                    require_once FOOCONVERT_INCLUDES_PATH . 'admin/views/dashboard.php';
                }
            );
        }


        /**
         * Enqueues the necessary assets for the FooConvert dashboard page.
         *
         * Only loads on the FooConvert dashboard page (i.e. the page with the
         * `fooconvert_page_fooconvert-dashboard` hook).
         *
         * Enqueues the following assets:
         * - `fooconvert-balloon-css` (the CSS for the balloon tooltip library)
         * - `fooconvert-dashboard-css` (the CSS for the dashboard page)
         * - `fooconvert-dashboard-js` (the JS for the dashboard page)
         *   with a dependency on `jquery`
         *   and localizes the `fooconvertData` object with the following data:
         *   - `ajaxUrl` (the URL of the WordPress AJAX endpoint)
         *   - `nonce` (a nonce token for use with the AJAX endpoint)
         *
         * @param string $hook The current admin page hook.
         *
         * @return void
         */
        public function enqueue_assets( $hook ) {
            // Only load on the FooConvert dashboard page
            if ( $hook !== 'toplevel_page_fooconvert' ) {
                return;
            }

            wp_enqueue_style(
                'fooconvert-balloon-css',
                FOOCONVERT_ASSETS_URL . 'admin/vendor/balloon/balloon.css'
            );

            wp_enqueue_style(
                'fooconvert-dashboard-css',
                FOOCONVERT_INCLUDES_URL . 'admin/views/dashboard.css'
            );

            wp_enqueue_script(
                'fooconvert-dashboard-js',
                FOOCONVERT_INCLUDES_URL . 'admin/views/dashboard.js',
                array( 'jquery' ),
                FOOCONVERT_VERSION,
                true
            );

            wp_localize_script('fooconvert-dashboard-js', 'fooconvertData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fooconvert-dashboard')
            ));
        }
    }
}