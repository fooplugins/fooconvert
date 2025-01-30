<?php
namespace FooPlugins\FooConvert;

/**
 * FooConvert Init Class
 * Runs all classes that need to run at startup
 */

if ( !class_exists( __NAMESPACE__ . '\Init' ) ) {

    class Init {

        public function __construct() {

            // Load the plugin text domain for translations.
            add_action( 'init', function() {
                $plugin_rel_path = dirname( plugin_basename( FOOCONVERT_FILE ) ) . '/languages/';
                load_plugin_textdomain( FOOCONVERT_SLUG, false, $plugin_rel_path );
            } );

            // Initialize the main plugin.
            FooConvert::plugin();

            if ( is_admin() ) {
                new Admin\Init();
            }

            new Event_Hooks();
            new Cron();

            // Check if the PRO version is running and run the PRO code.
            if ( fooconvert_is_pro() ) {
                if ( file_exists( FOOCONVERT_PATH . 'pro/start.php' ) ) {
                    require_once FOOCONVERT_PATH . 'pro/start.php';
                }
            }
        }
    }
}