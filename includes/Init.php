<?php

namespace FooPlugins\FooConvert;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( __NAMESPACE__ . '\UpgradeMigration' ) ) {
    require_once __DIR__ . '/UpgradeMigration.php';
}

/**
 * FooConvert Init Class
 * Runs all classes that need to run at startup
 */

if ( !class_exists( __NAMESPACE__ . '\Init' ) ) {

    /**
     * Class Init.
     */
    class Init {

        /**
         * Register startup hooks and initialize runtime services.
         *
         * @return void
         */
        public function __construct() {
            // Load the plugin text domain for translations.
            add_action( 'init', function () {
                $plugin_rel_path = dirname( plugin_basename( FOOCONVERT_FILE ) ) . '/languages/';
                load_plugin_textdomain( FOOCONVERT_SLUG, false, $plugin_rel_path );
            } );

            new UpgradeMigration();

            // Initialize the main plugin.
            FooConvert::plugin();

            if ( is_admin() ) {
                new Admin\Init();
            }

            new EventHooks();
            new Cron();
            new Fonts();
            new Admin\Templates\Init();
            new Updater();
            new Consent\Init();

            if ( fooconvert_fs()->can_use_premium_code__premium_only() ) {
                // Check if the PRO version is running and run the PRO code.
                if ( file_exists( FOOCONVERT_PATH . 'pro/start.php' ) ) {
                    require_once FOOCONVERT_PATH . 'pro/start.php';
                }
            }
        }
    }
}
