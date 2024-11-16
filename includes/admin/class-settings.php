<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Admin\FooFields\SettingsPage;

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
							'page_title'       => __( 'FooConvert Settings', 'FooConvert' ),
							'menu_title'       => __( 'Settings', 'FooConvert' ),
							'menu_parent_slug' => FOOCONVERT_MENU_SLUG,
							'layout'           => 'foofields-tabs-horizontal'
					)
			);
		}

		function get_tabs() {
			$general_tab = array(
					'id'     => 'general',
					'label'  => __( 'General', 'FooConvert' ),
					'icon'   => 'dashicons-admin-settings',
					'order'  => 10,
					'fields' => array(
							array(
									'id'    => 'always_enqueue',
									'type'  => 'checkbox',
									'label' => __( 'Always Enqueue Assets', 'FooConvert' ),
									'desc'  => __( 'By default, FooConvert javascript and stylesheet assets are only enqueued in your pages when needed. Some themes always need these assets in order to function.', 'FooConvert' )
							),
							array(
									'id'    => 'debug',
									'type'  => 'checkbox',
									'label' => __( 'Enable Debug Mode', 'FooConvert' ),
									'desc'  => __( 'Helps to debug problems and diagnose issues. Enable debugging if you need support for an issue you are having.', 'FooConvert' )
							)
					)
			);

			$advanced_tab = array(
					'id'     => 'advanced',
					'label'  => __( 'Advanced', 'FooConvert' ),
					'icon'   => 'dashicons-admin-generic',
					'order'  => 50,
					'fields' => array(
							array(
									'id'    => 'demo_content',
									'type'  => 'checkbox',
									'label' => __( 'Demo Content Created', 'FooConvert' ),
									'desc'  => __( 'If the demo content has been created, then this will be checked. You can uncheck this to allow for demo content to be created again.', 'FooConvert' )
							)
					)
			);

			if ( !FooConvert_is_pro() ) {
				$advanced_tab['fields'][] = array(
						'id'      => 'force_hide_trial',
						'label'   => __( 'Force Hide Trial Notice', 'foogallery' ),
						'desc'    => __( 'Force the trial notice admin banner to never show. If you find that even after dismissing the trial notice that it shows up again, you can enable this setting to make sure it is never shown again!', 'foogallery' ),
						'type'    => 'checkbox',
						'tab'     => 'advanced'
				);
			}

			$system_info_tab = array(
					'id'     => 'systeminfo',
					'label'  => __( 'System Info', 'FooConvert' ),
					'icon'   => 'dashicons-info',
					'order'  => 100,
					'fields' => array(
							array(
									'id'    => 'systeminfoheading',
									'label' => __( 'Your System Information', 'FooConvert' ),
									'desc'  => __( 'The below system info can be used when submitting a support ticket, to help us replicate your environment.', 'FooConvert' ),
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
				$general_tab,
				$advanced_tab,
				$system_info_tab,
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
					__( 'FooConvert version', 'FooConvert' )    => FOOCONVERT_VERSION,
					__( 'WordPress version', 'FooConvert' ) => $wp_version,
					__( 'Activated Theme', 'FooConvert' )   => $current_theme['Name'],
					__( 'WordPress URL', 'FooConvert' )     => get_site_url(),
					__( 'PHP version', 'FooConvert' )       => phpversion(),
					__( 'Settings', 'FooConvert' )          => $settings,
					__( 'Active Plugins', 'FooConvert' )    => $plugins
			);
			?>
			<style>
				.FooConvert-debug {
					width: 100%;
					font-family: "courier new";
					height: 500px;
				}
			</style>
			<textarea class="FooConvert-debug"><?php foreach ( $debug_info as $key => $value ) {
					echo esc_html( $key ) . ' : ';
					print_r( $value );
					echo "\n";
				} ?></textarea>
			<?php
		}
	}
}
