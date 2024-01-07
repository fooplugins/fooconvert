<?php
namespace FooPlugins\FooConvert;

/**
 * FooConvert Activation Class
 * Contains the activate method that runs on register_activation_hook
 */

if ( !class_exists( __NAMESPACE__ . '\Activation' ) ) {

	class Activation {
		/**
		 * Fired when the plugin is activated.
		 *
		 * @param    boolean $network_wide       True if WPMU superadmin uses
		 *                                       "Network Activate" action, false if
		 *                                       WPMU is disabled or plugin is
		 *                                       activated on an individual blog.
		 */
		public static function activate( $network_wide ) {
			$plugin_data = get_site_option( FOOCONVERT_OPTION_DATA );
			$save_data     = false;
			if ( false === $plugin_data ) {
				$plugin_data = array(
					'version'       => FOOCONVERT_VERSION,
					'first_version' => FOOCONVERT_VERSION,
					'first_install' => time()
				);
				$save_data = true;
			} else {
				$version = $plugin_data['version'];

				if ( $version !== FOOBAR_VERSION ) {
					//the version has been updated

					$plugin_data['version'] = FOOCONVERT_VERSION;
					$save_data              = true;
				}
			}

			if ( $save_data ) {
				update_site_option( FOOCONVERT_OPTION_DATA, $plugin_data );
			}
		}
	}
}
