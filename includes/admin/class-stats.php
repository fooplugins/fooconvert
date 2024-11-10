<?php
namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Event;
use FooPlugins\FooConvert\FooConvert;

/**
 * FooConvert Admin Stats Class
 */

if ( !class_exists( 'FooPlugins\FooConvert\Admin\Stats' ) ) {

    class Stats
    {
        /**
         * Init constructor.
         */
        function __construct()
        {
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            add_action( 'admin_init', array( $this, 'register_columns' ) );
            add_action( 'wp_ajax_fooconvert_fetch_stats',  array( $this, 'fetch_widget_stats' ) );
        }

        /**
         * AJAX callback to fetch widget stats.
         *
         * This function fetches the widget ID from the request and gets the widget
         * summary data from the Event class. It then prepares the response data
         * and sends it back to the client as JSON.
         *
         * @since 1.0.0
         */
        function fetch_widget_stats() {
            //get nonce
            $nonce = sanitize_text_field( $_POST['nonce'] );
            if ( !wp_verify_nonce($nonce, 'fooconvert-widget-stats' ) ) {
                wp_die( __( 'Invalid nonce!!', 'fooconvert' ) );
            }

            $widget_id = intval( sanitize_text_field( $_POST['widget_id'] ) );

            $event = new Event();
            $widget_summary_data = $event->get_widget_summary_data( $widget_id );

            $total_events = intval( $widget_summary_data['total_events'] );
            $total_views = intval( $widget_summary_data['total_views'] );
            $total_clicks = intval( $widget_summary_data['total_clicks'] );

            $data = array(
                'total_events' => $total_events,
                'total_views' => $total_views,
                'total_clicks' => $total_clicks,
                'click_through_rate' => round(( $total_clicks / $total_views ) * 100, 2),
            );

            $recent_activity_chart_data = [
                'labels' => [],
                'views' => [],
                'clicks' => [],
                'unique_visitors' => []
            ];

            foreach ( $widget_summary_data['recent_activity'] as $day) {
                $recent_activity_chart_data['labels'][] = $day['event_date'];
                $recent_activity_chart_data['views'][] = intval($day['views']);
                $recent_activity_chart_data['clicks'][] = intval($day['clicks']);
                $recent_activity_chart_data['unique_visitors'][] = intval($day['unique_visitors']);
            }

            $data['recent_activity'] = $recent_activity_chart_data;

            // Additional dummy data
//            $data['conversion_rate'] = 4.6;
//            $data['geo_breakdown'] = 'US: 40%, UK: 25%, CA: 15%, Other: 20%';
//            $data['device_browser'] = 'Mobile: 60%, Desktop: 40%';
//            $data['conversion_breakdown'] = [40, 60]; // Converted vs Not Converted
//            $data['engagement_trend'] = array(
//                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
//                'data' => [300, 450, 320, 500, 700, 600, 750]
//            );

            wp_send_json($data);
        }

        /**
         * Register a custom column for each post type that FooConvert supports.
         *
         * The custom column is titled "Stats" and contains a link to the stats page
         * for the widget.
         *
         * @since 1.0.0
         */
        public function register_columns() {
            foreach ( FooConvert::plugin()->widgets->get_post_types() as $post_type ) {
                add_filter( "manage_{$post_type}_posts_columns", function( $columns ) use ( $post_type ) {
                    return $this->create_stats_column( $post_type, $columns );
                } );
                add_action( "manage_{$post_type}_posts_custom_column", function( $column_name, $post_id ) use ( $post_type ) {
                    $this->create_stats_column_content( $post_type, $column_name, $post_id );
                }, 10, 2 );
            }
        }

        /**
         * Creates a custom column in the post type list table.
         *
         * @param string $post_type The post type that the column is being added to.
         * @param array $columns The existing columns in the list table.
         *
         * @return array The updated columns array.
         */
        public function create_stats_column( $post_type, $columns ) : array {
            // add the column after the default title column
            $updated = array();
            $inserted = false;
            foreach ( $columns as $column_name => $column_display_name ) {
                $updated[ $column_name ] = $column_display_name;
                if ( $column_name === 'title' ) {
                    $updated["{$post_type}_stats"] = __( 'Stats', 'fooconvert' );
                    $inserted = true;
                }
            }

            // if for some reason the column was not inserted, add it
            if ( !$inserted ) {
                $updated["{$post_type}_stats"] = __( 'Stats', 'fooconvert' );
            }
            return $updated;
        }


        /**
         * Renders the content of the "Stats" column in the post type list table.
         *
         * @param string $post_type The post type that the column is being rendered for.
         * @param string $column_name The name of the column being rendered.
         * @param int $post_id The ID of the post being rendered.
         *
         * @return void
         */
        public function create_stats_column_content( $post_type, $column_name, $post_id ) : void {
            if ( $column_name === "{$post_type}_stats" ) {

                $stats_page_url = admin_url( 'admin.php?page=fooconvert-widget-stats&widget_id=' . $post_id );

                echo '<a href="' . $stats_page_url . '">' . __( 'View Stats', 'fooconvert' ) . '</a>';
            }
        }

        /**
         * Callback for the `admin_menu` action.
         *
         * This hook registers stats page
         *
         * @access public
         * @since 1.0.0
         */
        public function register_menu() {

            // Register the widget stats page
            add_submenu_page(
                FOOCONVERT_MENU_SLUG,
                __( 'Widget Stats', 'fooconvert' ),
                __( 'Widget Stats', 'fooconvert' ),
                'manage_options',
                'fooconvert-widget-stats',
                function() {
                    require_once FOOCONVERT_INCLUDES_PATH . 'admin/views/widget-stats.php';
                }
            );
        }


        /**
         * Enqueues the necessary assets for the FooConvert stats page.
         *
         * Only loads on the FooConvert stats page (i.e. the page with the
         * `fooconvert_page_fooconvert-widget-stats` hook).
         *
         * Enqueues the following assets:
         * - `chartjs` (a local copy of Chart.js) with version 4.4.6
         * - `fooconvert-widget-stats-css` (the CSS for the widget stats page)
         * - `fooconvert-widget-stats-js` (the JS for the widget stats page)
         *   with a dependency on `chartjs`
         *
         * Also localizes the `fooconvertData` object with the following data:
         * - `ajaxUrl` (the URL of the WordPress AJAX endpoint)
         * - `nonce` (a nonce token for use with the AJAX endpoint)
         *
         * @param string $hook The current admin page hook.
         *
         * @return void
         */
        public function enqueue_assets( $hook ) {
            // Only load on the FooConvert stats page
            if ( $hook !== 'fooconvert_page_fooconvert-widget-stats' ) {
                return;
            }

            // Enqueue the local Chart.js script
            wp_enqueue_script(
                'chartjs',
                FOOCONVERT_URL . 'assets/admin/vendor/chartjs/chart.min.js',
                array(),
                '4.4.6',  // specify the version of Chart.js
                true
            );

            wp_enqueue_style(
                'fooconvert-widget-stats-css',
                FOOCONVERT_INCLUDES_URL . 'admin/views/widget-stats.css'
            );

            wp_enqueue_script(
                'fooconvert-widget-stats-js',
                FOOCONVERT_INCLUDES_URL . 'admin/views/widget-stats.js',
                array( 'jquery', 'chartjs' ), // Chart.js dependency
                FOOCONVERT_VERSION,
                true
            );

            wp_localize_script('fooconvert-widget-stats-js', 'fooconvertData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fooconvert-widget-stats')
            ));
        }
    }
}