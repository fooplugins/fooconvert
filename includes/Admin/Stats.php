<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Event;
use FooPlugins\FooConvert\FooConvert;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FooConvert Admin Stats Class
 */

if ( !class_exists( 'FooPlugins\FooConvert\Admin\Stats' ) ) {

    /**
     * Class Stats.
     */
    class Stats {
        /**
         * Init constructor.
         */
        function __construct() {
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
            add_action( 'wp_ajax_fooconvert_fetch_stats', array( $this, 'fetch_popup_stats' ) );
            add_action( 'admin_init', array( $this, 'enqueue_popup' ) );
            add_action( 'admin_footer', array( $this, 'render_enqueued' ) );
            add_filter( 'post_row_actions', array( $this, 'add_stats_row_action' ), 10, 2 );
            add_filter( 'fooconvert-popup-frontend-attributes', array( $this, 'override_popup_attributes' ), 10, 4 );
        }

        /**
         * Overrides popup attributes.
         */
        function override_popup_attributes( $attributes, $instance_id, $tag_name, $block ) {
            if ( fooconvert_is_popup_stats_page() ) {
                $attributes['settings']['trigger'] = [
                    'version'   => 2,
                    'lifetime'  => 'page',
                    'frequency' => [
                        'mode'            => 'repeat',
                        'cooldownSeconds' => 0
                    ],
                    'steps'     => [
                        [
                            'event' => 'fc.anchor.click',
                            'where' => [
                                'ids' => [ 'fooconvert-popup-preview' ]
                            ]
                        ]
                    ]
                ];
            }

            return $attributes;
        }

        /**
         * Renders enqueued.
         */
        function render_enqueued() {
            FooConvert::plugin()->display_rules->render_enqueued();
        }

        /**
         * Enqueues popup.
         */
        function enqueue_popup() {
            if ( fooconvert_is_popup_stats_page() ) {
                // This is what the block editor loads behind the scenes
                //require_once ABSPATH . 'wp-includes/block-editor.php';
                //register_core_block_types();

                $post_id = absint( $_GET['post_id'] );

                // We need to make sure the popup is enqueued for the admin stats page.
                FooConvert::plugin()->display_rules->add_to_queue( $post_id, 'admin_stats_preview' );
            }
        }

        /**
         * AJAX callback to fetch popup stats.
         *
         * This function fetches the popup ID from the request and gets the popup
         * summary data from the Event class. It then prepares the response data
         * and sends it back to the client as JSON.
         *
         * @since 1.0.0
         */
        function fetch_popup_stats() {
            if ( isset( $_POST['nonce'] ) ) {
                // Sanitize the nonce
                $nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

                // Verify the nonce
                if ( !wp_verify_nonce( $nonce, 'fooconvert-popup-stats' ) ) {
                    wp_die( esc_html__( 'Invalid nonce!!', 'fooconvert' ) );
                }

                $post_id = isset( $_POST['post_id'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) ) : 0;

                $saved_days = intval( get_option( FOOCONVERT_OPTION_RECENT_ACTIVITY_DAYS, FOOCONVERT_METRICS_DAYS_DEFAULT ) );
                $days = isset( $_POST['days'] ) ? intval( sanitize_text_field( wp_unslash( $_POST['days'] ) ) ) : $saved_days;
                if ( $days !== $saved_days ) {
                    // We have a chosen number of days, so let's save it for next time.
                    update_option( FOOCONVERT_OPTION_RECENT_ACTIVITY_DAYS, $days );
                }
                if ( $days === 0 ) {
                    $days = FOOCONVERT_METRICS_DAYS_DEFAULT;
                }
                if ( $days > fooconvert_retention() ) {
                    $days = fooconvert_retention();
                }

                if ( $post_id === 0 ) {
                    wp_die( esc_html__( 'Invalid popup ID!!', 'fooconvert' ) );
                }

                $event = new Event();

                // Get metrics first.
                $data = [
                    'metrics' => $event->get_popup_metrics( $post_id, $days )
                ];

                $recent_activity_chart_data = [
                    'labels' => []
                ];

                $activity_meta_data = apply_filters( 'fooconvert_popup_stats_activity_meta_data', [
                    'events'          => [
                        'label'                  => __( 'Events', 'fooconvert' ),
                        'data'                   => [],
                        'borderColor'            => 'rgb(112, 112, 112)',
                        'cubicInterpolationMode' => 'monotone',
                        'tension'                => 0.4,
                        'fill'                   => false
                    ],
                    'views'           => [
                        'label'                  => __( 'Views', 'fooconvert' ),
                        'data'                   => [],
                        'borderColor'            => 'rgb(75, 192, 192)',
                        'cubicInterpolationMode' => 'monotone',
                        'tension'                => 0.4,
                        'fill'                   => false
                    ],
                    'engagements'     => [
                        'label'                  => __( 'Engagements', 'fooconvert' ),
                        'data'                   => [],
                        'borderColor'            => 'rgb(255, 99, 132)',
                        'cubicInterpolationMode' => 'monotone',
                        'tension'                => 0.4,
                        'fill'                   => false
                    ],
                    'unique_visitors' => [
                        'label'                  => __( 'Unique Visitors', 'fooconvert' ),
                        'data'                   => [],
                        'borderColor'            => 'rgb(54, 162, 235)',
                        'cubicInterpolationMode' => 'monotone',
                        'tension'                => 0.4,
                        'fill'                   => true
                    ],
                ] );

                // Get daily activity next.
                $daily_activity = $event->get_popup_daily_activity( $post_id, $days );

                $min = 999;
                $max = 0;

                foreach ( $daily_activity as $day ) {
                    $recent_activity_chart_data['labels'][] = $day['event_date'];
                    foreach ( $activity_meta_data as $key => $meta_data ) {
                        $raw_value = isset( $day[$key] ) ? $day[$key] : 0;
                        $value = isset( $meta_data['value_type'] ) && $meta_data['value_type'] === 'float'
                            ? (float) $raw_value
                            : intval( $raw_value );
                        $activity_meta_data[$key]['data'][] = $value;
                        if ( $value < $min ) {
                            $min = $value;
                        }
                        if ( $value > $max ) {
                            $max = $value;
                        }
                    }
                }

                $recent_activity_chart_data['min'] = $min;
                $recent_activity_chart_data['max'] = $max;

                foreach ( $activity_meta_data as $key => $meta_data ) {
                    $recent_activity_chart_data['datasets'][] = $meta_data;
                }

                $data['recent_activity'] = $recent_activity_chart_data;

                $data = apply_filters( 'fooconvert_popup_stats_data', $data, $post_id, $days );

                // Additional dummy data
                //            $data['conversion_rate'] = 4.6;
                //            $data['geo_breakdown'] = 'US: 40%, UK: 25%, CA: 15%, Other: 20%';
                //            $data['device_browser'] = 'Mobile: 60%, Desktop: 40%';
                //            $data['conversion_breakdown'] = [40, 60]; // Converted vs Not Converted
                //            $data['engagement_trend'] = array(
                //                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                //                'data' => [300, 450, 320, 500, 700, 600, 750]
                //            );

                wp_send_json( $data );
            } else {
                wp_die( esc_html__( 'Nonce is not set.', 'fooconvert' ) );
            }
        }

        /**
         * Adds the stats link to popup row actions before destructive actions.
         *
         * @param array   $actions Existing row actions.
         * @param WP_Post $post Current post object.
         * @return array
         */
        public function add_stats_row_action( array $actions, WP_Post $post ): array {
            if ( $post->post_type !== FOOCONVERT_CPT_POPUP ) {
                return $actions;
            }

            $stats_page_url = fooconvert_admin_url_popup_stats( $post->ID );
            $stats_action = '<a href="' . esc_url( $stats_page_url ) . '">' . esc_html__( 'View Stats', 'fooconvert' ) . '</a>';

            unset( $actions['fooconvert_stats'] );

            $updated = array();
            $inserted = false;

            foreach ( $actions as $action_name => $action_html ) {
                if ( in_array( $action_name, array( 'trash', 'delete' ), true ) && !$inserted ) {
                    $updated['fooconvert_stats'] = $stats_action;
                    $inserted = true;
                }

                $updated[ $action_name ] = $action_html;
            }

            if ( !$inserted ) {
                $updated['fooconvert_stats'] = $stats_action;
            }

            return $updated;
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

            // Register the popup stats page
            add_submenu_page(
                FOOCONVERT_MENU_SLUG,
                __( 'Stats', 'fooconvert' ),
                __( 'Stats', 'fooconvert' ),
                'manage_options',
                FOOCONVERT_MENU_SLUG_POPUP_STATS,
                function () {
                    require_once FOOCONVERT_INCLUDES_PATH . 'Admin/Views/popup-stats.php';
                }
            );
        }


        /**
         * Enqueues the necessary assets for the FooConvert stats page.
         *
         * Only loads on the FooConvert stats page (i.e. the page with the
         * `fooconvert_page_fooconvert-popup-stats` hook).
         *
         * Enqueues the following assets:
         * - `chartjs` (a local copy of Chart.js) with version 4.4.6
         * - `fooconvert-popup-stats-css` (the CSS for the popup stats page)
         * - `fooconvert-popup-stats-js` (the JS for the popup stats page)
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
            if ( $hook !== 'fooconvert_page_fooconvert-popup-stats' ) {

                // Hide "Popup Stats" submenu if we are NOT on the page.
                wp_add_inline_style( 'wp-admin', '
                    /* Hide "Popup Stats" submenu */
                    #toplevel_page_fooconvert .wp-submenu li a[href="' . fooconvert_admin_url_popup_stats_base() . '"] {
                        display: none !important;
                    }
                ' );

                return;
            }

            // Enqueue the local Chart.js script
            wp_enqueue_script(
                'chartjs',
                FOOCONVERT_ASSETS_URL . 'admin/vendor/chartjs/chart.min.js',
                array(),
                '4.4.6',  // specify the version of Chart.js
                true
            );

            // Enqueue the local Chart.js annotations script
            wp_enqueue_script(
                'chartjs-annotations',
                FOOCONVERT_ASSETS_URL . 'admin/vendor/chartjs/chart.annotations.min.js',
                array( 'chartjs' ),
                '4.4.6',  // specify the version of Chart.js
                true
            );

            wp_enqueue_style(
                'fooconvert-balloon-css',
                FOOCONVERT_ASSETS_URL . 'admin/vendor/balloon/balloon.css',
                array(),
                FOOCONVERT_VERSION
            );

            wp_enqueue_style(
                'fooconvert-popup-stats-css',
                FOOCONVERT_INCLUDES_URL . 'Admin/Views/popup-stats.css',
                array(),
                FOOCONVERT_VERSION
            );

            wp_enqueue_script(
                'fooconvert-popup-stats-js',
                FOOCONVERT_INCLUDES_URL . 'Admin/Views/popup-stats.js',
                array( 'jquery', 'chartjs' ), // Chart.js dependency
                FOOCONVERT_VERSION,
                true
            );

            wp_localize_script( 'fooconvert-popup-stats-js', 'fooconvertData', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'fooconvert-popup-stats' )
            ) );

            FooConvert::plugin()->ensure_frontend_assets_enqueued();

            // 1. Register all blocks (core + plugin)
            require_once ABSPATH . 'wp-includes/block-editor.php';

            // 2. Trigger enqueue actions manually
            do_action('enqueue_block_assets');          // Block frontend + editor shared assets
            do_action('enqueue_block_editor_assets');   // Editor-only assets (the big one)

            do_action( 'fooconvert_popup_stats_enqueue_assets' );
        }
    }
}
