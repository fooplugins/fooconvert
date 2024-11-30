<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Admin\FooFields\SettingsPage;
use FooPlugins\FooConvert\Event;

/**
 * FooConvert Admin Settings Class
 */

if ( ! class_exists( 'FooPlugins\FooConvert\Admin\Settings' ) ) {

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

            $analytics_addon_link = '<a href="' . fooconvert_admin_url_addons() . '" target="_blank">' . __( 'Analytics PRO Addon', 'fooconvert' ) . '</a>';

            $general_tab = array(
                'id'     => 'general',
                'label'  => __( 'General', 'fooconvert' ),
                'icon'   => 'dashicons-admin-settings',
                'order'  => 10,
                'fields' => array(
                    'retention' => array(
                        'id'    => 'retention',
                        'type'  => 'html',
                        'label' => __( 'Retention Period', 'fooconvert' ),
                        'html'  => '<pre>' . esc_html( fooconvert_retention() ) . ' ' . __( 'days', 'fooconvert' ) . '</pre>',
                        'desc'  => __( 'The number of days before data is deleted.', 'fooconvert' ) . ' ' . sprintf( __( 'This can only be changed with the %s.', 'fooconvert' ), $analytics_addon_link )
                    ),
                    'debug' => array(
                        'id'    => 'debug',
                        'type'  => 'checkbox',
                        'label' => __( 'Enable Debug Mode', 'fooconvert' ),
                        'desc'  => __( 'Helps to debug problems and diagnose issues. Enable debugging if you need support for an issue you are having.', 'fooconvert' )
                    ),
                    'demo_content' => array(
                        'id'    => 'demo_content',
                        'type'  => 'checkbox',
                        'label' => __( 'Demo Content Created', 'fooconvert' ),
                        'desc'  => __( 'If the demo content has been created, then this will be checked. You can uncheck this to allow for demo content to be created again.', 'fooconvert' )
                    )
                )
			);

            $event = new Event();
            $database_stats = $event->get_event_table_stats();
            $orphaned_events = intval( $database_stats['Orphaned_Events'] );

            $stats_html = '<table>';
            $stats_html .= '<tr>';
            $stats_html .= '<td>' . esc_html__( 'Table', 'fooconvert' ) . '</td>';
            $stats_html .= '<td><pre>' . esc_html( $database_stats['Table'] ) . '</pre></td>';
            $stats_html .= '</tr>';
            $stats_html .= '<tr>';
            $stats_html .= '<td>' . __( 'Table Size (MB)', 'fooconvert' ) . '</td>';
            $stats_html .= '<td><pre>' . esc_html( $database_stats['Size_in_MB'] ) . '</pre></td>';
            $stats_html .= '</tr>';
            $stats_html .= '<tr>';
            $stats_html .= '<td>' . __( 'Event Row Count', 'fooconvert' ) . '</td>';
            $stats_html .= '<td><pre>' . esc_html( $database_stats['Number_of_Rows'] ) . '</pre></td>';
            $stats_html .= '</tr>';
            $stats_html .= '<tr>';
            $stats_html .= '<td>' . __( 'Widget Count With Events', 'fooconvert' ) . '</td>';
            $stats_html .= '<td><pre>' . esc_html( $database_stats['Unique_Widgets'] ) . '</pre></td>';
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
            $stats_html .= '<td><pre>' . esc_html( $database_stats['Unique_Orphaned_Widgets'] ) . '</pre></td>';
            $stats_html .= '</tr>';
            $stats_html .= '</table>';

			$database_tab = array(
                'id'     => 'database',
                'label'  => __( 'Database', 'fooconvert' ),
                'icon'   => 'dashicons-database',
                'order'  => 50,
                'fields' => array(
                    array(
                        'id'    => 'database_stats',
                        'type'  => 'html',
                        'label' => __( 'Database Stats', 'fooconvert' ),
                        'html' => $stats_html
                    ),
                    array(
                        'id'    => 'database_delete_all',
                        'type'  => 'ajaxbutton',
                        'callback' => array( $this, 'delete_all_events' ),
                        'button'   => __( 'Delete All Events', 'fooconvert' ),
                    )
                )
			);

            if ( $orphaned_events > 0 ) {
                $database_tab['fields'][] = array(
                    'id'    => 'database_delete_orphans',
                    'type'  => 'ajaxbutton',
                    'callback' => array( $this, 'delete_orphans' ),
                    'button'   => __( 'Delete Orphaned Data', 'fooconvert' ),
                );
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
				'general' => $general_tab,
                'database' => $database_tab,
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
                'message' => sprintf( __( 'Successfully deleted %s orphaned events.', 'fooconvert' ), $result )
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
                'message' => sprintf( __( 'Successfully deleted %s events.', 'fooconvert' ), $result )
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

			$debug_info = array(
					__( 'FooConvert version', 'fooconvert' )    => FOOCONVERT_VERSION,
					__( 'WordPress version', 'fooconvert' ) => $wp_version,
					__( 'Activated Theme', 'fooconvert' )   => $current_theme['Name'],
					__( 'WordPress URL', 'fooconvert' )     => get_site_url(),
					__( 'PHP version', 'fooconvert' )       => phpversion(),
                    __( 'Retention', 'fooconvert' )         => fooconvert_retention(),
					__( 'Settings', 'fooconvert' )          => $settings,
					__( 'Active Plugins', 'fooconvert' )    => $plugins
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
					print_r( $value );
					echo "\n";
				} ?></textarea>
			<?php
		}
	}
}
