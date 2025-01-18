<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Event;
use FooPlugins\FooConvert\FooConvert;

/**
 * FooConvert Admin Dashboard Class
 */

if ( !class_exists( 'FooPlugins\FooConvert\Admin\Dashboard' ) ) {

    class Dashboard {
        /**
         * Init constructor.
         */
        function __construct() {
            add_action( 'fooconvert_admin_menu_before_post_types', array( $this, 'register_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

            add_action( 'wp_ajax_fooconvert_dashboard_task', array( $this, 'handle_task' ) );
        }

        /**
         * Checks nonce and user capabilities before performing an action.
         *
         * Verifies that the nonce is set and valid, and that the current user
         * has the capability of 'manage_options' if check_admin is true.
         *
         * @param bool $check_admin Whether to check if the user is an administrator.
         * @return bool True if the checks pass, otherwise an error message is displayed.
         *
         * @since 1.0.0
         */
        function do_checks( $check_admin = true ) {
            if ( isset( $_POST['nonce'] ) ) {
                // Sanitize the nonce
                $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

                // Verify the nonce
                if ( !wp_verify_nonce( $nonce, 'fooconvert-dashboard' ) ) {
                    wp_die( esc_html__( 'Invalid nonce!!', 'fooconvert' ) );
                }

                // Check if the current user is an administrator
                if ( $check_admin && !current_user_can( 'manage_options' ) ) {
                    wp_die( esc_html__( 'You do not have permission to access this page.', 'fooconvert' ) );
                }

                // If we get here, then all our checks have passed.
                return true;

            } else {
                wp_die( esc_html__( 'Nonce is not set.', 'fooconvert' ) );
            }
        }

        /**
         * Retrieves the dashboard task from the POST request.
         *
         * This function checks if the 'task' key is set in the $_POST array.
         * If set, it sanitizes and returns the task value. If not set, it
         * terminates execution with an error message.
         *
         * @return string Sanitized task value from the POST request.
         * @since 1.0.0
         */
        function get_dashboard_task() {
            $task = $this->get_post_value( 'task' );

            if ( empty( $task ) ) {
                wp_die( esc_html__( 'Dashboard task is not set.', 'fooconvert' ) );
            }

            return $task;
        }

        /**
         * Retrieves a sanitized value from the $_POST array.
         *
         * Checks if the $key is set in the $_POST array and sanitizes the value if set.
         * If the value is empty after sanitization, it will be set to the $default value.
         *
         * @param string $key The key to look for in the $_POST array.
         * @param string $default The default value if the key is not set or empty.
         * @return string The sanitized value from the $_POST array, or the $default value.
         *
         * @since 1.0.0
         */
        function get_post_value( $key, $default = '' ) {
            $value = $default;
            if ( isset( $_POST[ $key ] ) ) {
                $value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
                if ( empty( $value ) ) {
                    $value = $default;
                }
            }

            return $value;
        }

        /**
         * Handles AJAX requests for FooConvert dashboard tasks.
         *
         * Verifies nonce and user capabilities before performing the task.
         * If the checks pass, it calls the corresponding method for the task.
         *
         * @since 1.0.0
         */
        function handle_task() {
            if ( !$this->do_checks() ) {
                return;
            }

            $task = $this->get_dashboard_task();

            switch ( $task ) {
                case 'create_demo_widgets':
                    $this->create_demo_widgets();
                    break;
                case 'delete_demo_widgets':
                    $this->delete_demo_widgets();
                    break;
                case 'update_stats':
                    $this->update_stats();
                    break;
                case 'fetch_top_performers':
                    $this->fetch_top_performers();
                    break;
                case 'hide_panel':
                    $this->hide_panel();
                    break;
                default:
                    wp_die( esc_html__( 'Invalid dashboard task!', 'fooconvert' ) );

            }
        }

        function hide_panel() {
            $panel = $this->get_post_value( 'panel' );
            if ( empty( $panel ) ) {
                wp_send_json( [ 'message' => __( 'Panel is not set.', 'fooconvert' ) ] );
            }

            $hidden_panels = fooconvert_get_setting( 'hide_dashboard_panels', [] );

            if ( !in_array( $panel, $hidden_panels ) ) {
                $hidden_panels[$panel] = $panel;
                fooconvert_set_setting( 'hide_dashboard_panels', $hidden_panels );
                wp_send_json( [ 'message' => __( 'Panel hidden.', 'fooconvert' ) ] );
            }
        }

        /**
         * Function to create demo widgets.
         *
         * This function creates demo widgets and sends back a JSON success
         * response with the number of widgets created.
         *
         */
        function create_demo_widgets() {
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
                wp_send_json( [ 'message' => __( 'No widgets created!', 'fooconvert' ) ] );
            } else {
                // Translators: %d refers to the number of demo widgets created.
                wp_send_json( [ 'message' => sprintf( __( '%d demo widgets created successfully!', 'fooconvert' ), $created ) ] );
            }
        }

        /**
         * Function to delete all demo widgets.
         *
         * This function deletes all demo widgets and sends back a JSON success
         * response.
         *
         * @since 1.0.0
         */
        function delete_demo_widgets() {
            $demo = new DemoContent();
            $demo->delete();

            fooconvert_set_setting( 'demo_content', '' );

            wp_send_json( [ 'message' => __( 'All demo widgets deleted!', 'fooconvert' ) ] );
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
            $sort = $this->get_post_value( 'sort', 'engagement' );

            update_option( FOOCONVERT_OPTION_TOP_PERFORMERS_SORT, $sort );

            ob_start();
            require_once FOOCONVERT_INCLUDES_PATH . 'admin/views/dashboard-top-performers.php';
            $html = ob_get_clean();

            wp_send_json( [ 'html' => $html ] );
        }

        /**
         * Function to update the stats.
         *
         * This function updates all widget stats.
         *
         * @since 1.1.0
         */
        function update_stats() {
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
                function () {
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
                FOOCONVERT_ASSETS_URL . 'admin/vendor/balloon/balloon.css',
                array(),
                FOOCONVERT_VERSION
            );

            wp_enqueue_style(
                'fooconvert-dashboard-css',
                FOOCONVERT_INCLUDES_URL . 'admin/views/dashboard.css',
                array(),
                FOOCONVERT_VERSION
            );

            wp_enqueue_script(
                'fooconvert-dashboard-js',
                FOOCONVERT_INCLUDES_URL . 'admin/views/dashboard.js',
                array( 'jquery' ),
                FOOCONVERT_VERSION,
                true
            );

            wp_localize_script( 'fooconvert-dashboard-js', 'fooconvertData', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'fooconvert-dashboard' )
            ) );
        }
    }
}