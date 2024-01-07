<?php
/**
 * FooConvert Init Class
 * Runs at the startup of the plugin
 * Assumes after all checks have been made, and all is good to go!
 *
 * @package FooPlugins\FooConvert
 */

namespace FooPlugins\FooConvert;

if ( ! class_exists( __NAMESPACE__ . '\Init' ) ) {

	/**
	 * Class Init
	 */
	class Init {

		/**
		 * Initialize the plugin by setting localization, filters, and administration functions.
		 */
		public function __construct() {
			// Load the plugin text domain.
			add_action( 'init', function() {
				$plugin_rel_path = dirname( plugin_basename( FOOCONVERT_FILE ) ) . '/languages/';
				load_plugin_textdomain( FOOCONVERT_SLUG, false, $plugin_rel_path );
			} );

			if ( is_admin() ) {
				//new namespace\Admin\Init();
			} else {
				//new namespace\Front\Init();
			}

			// Check if the PRO version of FooConvert is running and run the PRO code.
			if ( fooconvert_is_pro() ) {
				//new namespace\Pro\Init();
			}
		}
	}
}
