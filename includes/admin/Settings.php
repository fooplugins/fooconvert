<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Admin\FooFields\SettingsPage;
use FooPlugins\FooConvert\Event;

/**
 * FooConvert Admin Settings Class
 */

if ( !class_exists( 'FooPlugins\FooConvert\Admin\Settings' ) ) {

    class Settings extends SettingsPage {

        public function __construct() {
            parent::__construct(
                array(
                    'manager'          => FOOCONVERT_SLUG,
                    'settings_id'      => FOOCONVERT_SLUG,
                    'page_title'       => __( 'FooConvert Settings', 'fooconvert' ),
                    'menu_title'       => __( 'Settings', 'fooconvert' ),
                    'menu_parent_slug' => FOOCONVERT_MENU_SLUG,
                    'layout'           => 'foofields-tabs-horizontal'
                )
            );
        }

        function get_tabs() {

            $pro_link = '<a href="' . fooconvert_admin_url_pricing() . '" target="_blank">' . __( 'FooConvert PRO', 'fooconvert' ) . '</a>';

            $hide_dashboard_panels_choices = apply_filters( 'fooconvert_hide_dashboard_panels_choices', array(
                'getting-started' => __( 'Getting Started', 'fooconvert' ),
                'help'            => __( 'Help', 'fooconvert' ),
                'premium'         => __( 'Premium Addons', 'fooconvert' )
            ) );

            $general_tab = array(
                'id'     => 'general',
                'label'  => __( 'General', 'fooconvert' ),
                'icon'   => 'dashicons-admin-settings',
                'order'  => 10,
                'fields' => array(
                    'retention'             => array(
                        'id'    => 'retention',
                        'order' => 1,
                        'type'  => 'html',
                        'label' => __( 'Retention Period', 'fooconvert' ),
                        'html'  => '<pre>' . esc_html( fooconvert_retention() ) . ' ' . __( 'days', 'fooconvert' ) . '</pre>',
                        // Translators: %s refers to the link to the FooConvert PRO Pricing page.
                        'desc'  => __( 'The number of days before data is deleted.', 'fooconvert' ) . ' ' . sprintf( __( 'This can only be changed with %s.', 'fooconvert' ), $pro_link )
                    ),
                    'debug'                 => array(
                        'id'    => 'debug',
                        'order' => 10,
                        'type'  => 'checkbox',
                        'label' => __( 'Enable Debug Mode', 'fooconvert' ),
                        'desc'  => __( 'Helps to debug problems and diagnose issues. Enable debugging if you need support for an issue you are having.', 'fooconvert' )
                    ),
                    'hide_promos'           => array(
                        'id'    => 'hide_promos',
                        'order' => 20,
                        'type'  => 'checkbox',
                        'label' => __( 'Hide Promos', 'fooconvert' ),
                        'desc'  => __( 'If enabled, will hide all promotional messages within the admin area, including the dashboard Addons panel.', 'fooconvert' )
                    ),
                    'hide_dashboard_panels' => array(
                        'id'      => 'hide_dashboard_panels',
                        'order'   => 30,
                        'type'    => 'checkboxlist',
                        'label'   => __( 'Hide Dashboard Panels', 'fooconvert' ),
                        'choices' => $hide_dashboard_panels_choices,
                        'desc'    => __( 'Will hide specific dashboard panel if checked.', 'fooconvert' )
                    ),
                    'demo_content'          => array(
                        'id'    => 'demo_content',
                        'order' => 40,
                        'type'  => 'checkbox',
                        'label' => __( 'Demo Content Created', 'fooconvert' ),
                        'desc'  => __( 'If the demo content has been created, then this will be checked. You can uncheck this to allow for demo content to be created again.', 'fooconvert' )
                    )
                )
            );

            $dataBasetab = array(
                'id'     => 'database',
                'label'  => __( 'Database', 'fooconvert' ),
                'icon'   => 'dashicons-database',
                'order'  => 50,
                'fields' => []
            );

            $event = new Event();
            $event_table_exists = $event->does_table_exist();

            if ( !$event_table_exists ) {
                $dataBasetab['fields'][] = array(
                    'id'    => 'dataBaseerror',
                    'type'  => 'html',
                    'label' => __( 'Database Error', 'fooconvert' ),
                    'html'  => '<h3 style="color:red">' . esc_html__( 'Event Table Does Not Exist!', 'fooconvert' ) . '</h3>'
                );
            }

            if ( fooconvert_is_debug() ) {
                $dataBasedata = get_option( FOOCONVERT_OPTION_DATABaseDATA );
                if ( !empty( $dataBasedata ) ) {
                    $dataBasetab['fields'][] = array(
                        'id'    => 'dataBasedata',
                        'type'  => 'html',
                        'label' => __( 'Database Data', 'fooconvert' ),
                        'html'  => '<pre>' . esc_html( print_r( $dataBasedata, true ) ) . '</pre>' // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                    );
                }
            }

            if ( $event_table_exists ) {
                $dataBasestats = $event->get_event_table_stats();
                if ( !empty( $dataBasestats ) ) {
                    $orphaned_events = intval( $dataBasestats['Orphaned_Events'] );

                    $stats_html = '<table>';
                    $stats_html .= '<tr>';
                    $stats_html .= '<td>' . esc_html__( 'Table', 'fooconvert' ) . '</td>';
                    $stats_html .= '<td><pre>' . esc_html( $dataBasestats['Table'] ) . '</pre></td>';
                    $stats_html .= '</tr>';
                    $stats_html .= '<tr>';
                    $stats_html .= '<td>' . __( 'Table Size (MB)', 'fooconvert' ) . '</td>';
                    $stats_html .= '<td><pre>' . esc_html( $dataBasestats['Size_in_MB'] ) . '</pre></td>';
                    $stats_html .= '</tr>';
                    $stats_html .= '<tr>';
                    $stats_html .= '<td>' . __( 'Event Row Count', 'fooconvert' ) . '</td>';
                    $stats_html .= '<td><pre>' . esc_html( $dataBasestats['Number_of_Rows'] ) . '</pre></td>';
                    $stats_html .= '</tr>';
                    $stats_html .= '<tr>';
                    $stats_html .= '<td>' . __( 'Widget Count With Events', 'fooconvert' ) . '</td>';
                    $stats_html .= '<td><pre>' . esc_html( $dataBasestats['Unique_Widgets'] ) . '</pre></td>';
                    $stats_html .= '</tr>';
                    $stats_html .= '<tr>';
                    $stats_html .= '<td>' . __( 'Orphaned Event Count', 'fooconvert' ) . '</td>';
                    $stats_html .= '<td><pre>';
                    if ( $orphaned_events > 0 ) {
                        $stats_html .= '<span style="color: red">';
                    }
                    $stats_html .= esc_html( $orphaned_events );
                    if ( $orphaned_events > 0 ) {
                        $stats_html .= '</span>';
                    }
                    $stats_html .= '</pre></td>';
                    $stats_html .= '</tr>';
                    $stats_html .= '<tr>';
                    $stats_html .= '<td>' . __( 'Orphaned Widget Count', 'fooconvert' ) . '</td>';
                    $stats_html .= '<td><pre>' . esc_html( $dataBasestats['Unique_Orphaned_Widgets'] ) . '</pre></td>';
                    $stats_html .= '</tr>';
                    $stats_html .= '</table>';

                    $dataBasetab['fields'][] = array(
                        'id'    => 'dataBasestats',
                        'type'  => 'html',
                        'label' => __( 'Database Stats', 'fooconvert' ),
                        'html'  => $stats_html
                    );

                    $dataBasetab['fields'][] = array(
                        'id'       => 'dataBasedelete_old',
                        'type'     => 'ajaxbutton',
                        'callback' => array( $this, 'delete_old_events' ),
                        'button'   => __( 'Delete Old Events', 'fooconvert' ),
                        'desc'     => __( 'This will permanently delete all events older than the retention period.', 'fooconvert' ) . ' ' . __( 'Currently :', 'fooconvert' ) . ' ' . fooconvert_retention() . ' ' . __( 'days.', 'fooconvert' ),
                    );

                    $dataBasetab['fields'][] = array(
                        'id'       => 'dataBasedelete_all',
                        'type'     => 'ajaxbutton',
                        'callback' => array( $this, 'delete_all_events' ),
                        'button'   => __( 'Delete All Events', 'fooconvert' ),
                        'desc'     => __( 'This will permanently delete all events.', 'fooconvert' ),
                    );

                    if ( $orphaned_events > 0 ) {
                        $dataBasetab['fields'][] = array(
                            'id'       => 'dataBasedelete_orphans',
                            'type'     => 'ajaxbutton',
                            'callback' => array( $this, 'delete_orphans' ),
                            'button'   => __( 'Delete Orphaned Data', 'fooconvert' ),
                        );
                    }
                }
            }

            $system_info_tab = array(
                'id'     => 'systeminfo',
                'label'  => __( 'System Info', 'fooconvert' ),
                'icon'   => 'dashicons-info',
                'order'  => 100,
                'fields' => array(
                    array(
                        'id'    => 'systeminfoheading',
                        'label' => __( 'Your System Information', 'fooconvert' ),
                        'desc'  => __( 'The below system info can be used when submitting a support ticket, to help us replicate your environment.', 'fooconvert' ),
                        'type'  => 'heading',
                    ),
                    array(
                        'id'     => 'systeminfodetail',
                        'layout' => 'inline',
                        'type'   => 'system_info',
                        'render' => array( $this, 'render_system_info' )
                    )
                )
            );

            return apply_filters( 'fooconvert_admin_settings', array(
                'general'    => $general_tab,
                'database'   => $dataBasetab,
                'systeminfo' => $system_info_tab,
            ) );
        }

        /**
         * Deletes orphaned events.
         *
         * This callback is triggered when the user clicks the "Delete Orphaned Data" button on the database tab.
         *
         * @since 1.0.0
         */
        function delete_orphans() {
            $event = new Event();
            $result = $event->delete_orphaned_events();

            wp_send_json_success( array(
                // Translators: %d refers to the number of orphaned events that were deleted.
                'message' => sprintf( __( 'Successfully deleted %d orphaned events.', 'fooconvert' ), $result )
            ) );
        }

        /**
         * Deletes old events.
         *
         * This callback is triggered when the user clicks the "Delete Old Events" button on the database tab.
         *
         * @since 1.0.0
         */
        function delete_old_events() {
            $event = new Event();
            $result = $event->delete_old_events();

            wp_send_json_success( array(
                // Translators: %d refers to the number of events that were deleted.
                'message' => sprintf( __( 'Successfully deleted %d events.', 'fooconvert' ), $result )
            ) );
        }

        /**
         * Deletes all events.
         *
         * This callback is triggered when the user clicks the "Delete All Events" button on the database tab.
         *
         * @since 1.0.0
         */
        function delete_all_events() {
            $event = new Event();
            $result = $event->delete_all_events();

            wp_send_json_success( array(
                // Translators: %d refers to the number of events that were deleted.
                'message' => sprintf( __( 'Successfully deleted %d events.', 'fooconvert' ), $result )
            ) );
        }

        /**
         * Render some system info
         *
         * @param $field
         */
        function render_system_info( $field ) {
            global $wp_version;

            $current_theme = wp_get_theme();

            $settings = fooconvert_get_settings();

            //get all activated plugins
            $plugins = array();
            foreach ( get_option( 'active_plugins' ) as $plugin_slug => $plugin ) {
                $plugins[] = $plugin;
            }

            $event = new Event();
            $event_table_exists = $event->does_table_exist();
            if ( !$event_table_exists ) {
                $dataBasestats = __( 'ERROR : The events table does not exist!', 'fooconvert' );
            } else {
                $dataBasestats = $event->get_event_table_stats();
            }

            $cron_jobs = $this->get_cron_jobs();

            $debug_info = array(
                __( 'FooConvert version', 'fooconvert' ) => FOOCONVERT_VERSION,
                __( 'WordPress version', 'fooconvert' )  => $wp_version,
                __( 'Activated Theme', 'fooconvert' )    => $current_theme['Name'],
                __( 'WordPress URL', 'fooconvert' )      => get_site_url(),
                __( 'PHP version', 'fooconvert' )        => phpversion(),
                __( 'Retention', 'fooconvert' )          => fooconvert_retention(),
                __( 'Cron Jobs', 'fooconvert' )          => $cron_jobs,
                __( 'Database', 'fooconvert' )           => $dataBasestats,
                __( 'Active Plugins', 'fooconvert' )     => $plugins,
                __( 'Settings', 'fooconvert' )           => $settings,
            );
            ?>
            <style>
                .fooconvert-debug {
                    width: 100%;
                    font-family: "courier new";
                    height: 500px;
                }
            </style>
            <textarea class="fooconvert-debug"><?php foreach ( $debug_info as $key => $value ) {
                    echo esc_html( $key ) . ' : ';
                    print_r( $value ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
                    echo "\n";
                } ?></textarea>
            <?php
        }

        /**
         * Returns an associative array of cron jobs related to FooConvert.
         *
         * Each key is a unix timestamp, and the value is an associative array of cron hooks.
         * The keys of the inner array are the cron hook names, and the values are the cron job
         * arrays, as returned by _get_cron_array.
         *
         * Only returns cron jobs if the current user has the manage_options capability.
         *
         * @return array An associative array of cron jobs, or an empty array if the current user
         *               does not have the manage_options capability.
         * @since 1.0.0
         *
         */
        function get_cron_jobs() {
            if ( current_user_can( 'manage_options' ) ) {
                $cron_jobs = _get_cron_array();
                $filtered_jobs = [];

                foreach ( $cron_jobs as $timestamp => $jobs ) {
                    foreach ( $jobs as $hook => $job ) {
                        if ( strpos( $hook, 'fooconvert' ) === 0 ) {
                            $filtered_jobs[$timestamp][$hook] = $job;
                        }
                    }
                }

                return $filtered_jobs;
            }

            return [];
        }
    }
}
