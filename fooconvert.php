<?php
/*
Plugin Name: FooConvert
Description: Turn clicks into conversions, visitors into customers – FooConvert is the ultimate catalyst for online success!
Version:     0.0.10
Author:      FooPlugins
Plugin URI:  https://fooplugins.com/fooconvert-wordpress-sales-conversion/
Author URI:  https://fooplugins.com/
Text Domain: fooconvert
License:     GPL-3.0+
Domain Path: /languages
Requires at least: 6.5
Requires PHP: 7.4

@fs_premium_only /pro/

*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define some FooConvert essentials constants.
if ( ! defined( 'FOOCONVERT_SLUG' ) ) {
    define( 'FOOCONVERT_SLUG', 'fooconvert' );
    define( 'FOOCONVERT_NAME', 'FooConvert' );
    define( 'FOOCONVERT_NAMESPACE', 'FooPlugins\FooConvert' );
    define( 'FOOCONVERT_DIR', __DIR__ );
    define( 'FOOCONVERT_PATH', plugin_dir_path( __FILE__ ) );
    define( 'FOOCONVERT_URL', plugin_dir_url( __FILE__ ) );
    define( 'FOOCONVERT_INCLUDES_PATH', FOOCONVERT_PATH . 'includes/' );
    define( 'FOOCONVERT_INCLUDES_URL', FOOCONVERT_URL . 'includes/' );
    define( 'FOOCONVERT_ASSETS_PATH', FOOCONVERT_PATH . 'assets/' );
    define( 'FOOCONVERT_ASSETS_URL', FOOCONVERT_URL . 'assets/' );
    define( 'FOOCONVERT_FILE', __FILE__ );
    define( 'FOOCONVERT_VERSION', '0.0.10' );
    define( 'FOOCONVERT_MIN_PHP', '7.4.0' );
    define( 'FOOCONVERT_MIN_WP', '6.5.0' );
}

// Include other essential FooConvert constants.
require_once FOOCONVERT_INCLUDES_PATH . 'constants.php';

// Do a check to see if either free/pro version of the plugin is already running.
if ( function_exists( 'fooconvert_fs' ) ) {
    fooconvert_fs()->set_basename( true, __FILE__ );
} else {
    if ( ! function_exists( 'fooconvert_fs' ) ) {
        require_once FOOCONVERT_INCLUDES_PATH . 'freemius.php';
    }
}

// Check minimum requirements before loading the plugin.
if ( require_once FOOCONVERT_INCLUDES_PATH . 'startup-checks.php' ) {

	// Start autoloader.
	require_once FOOCONVERT_PATH . 'vendor/autoload.php';
    require_once FOOCONVERT_INCLUDES_PATH . 'autoload.php';

	spl_autoload_register( 'fooconvert_autoloader' );

	// Hook in activation.
	register_activation_hook( FOOCONVERT_FILE, array( FooPlugins\FooConvert\FooConvert::class, 'activated' ) );

	// Start the plugin!
    FooPlugins\FooConvert\FooConvert::plugin();
}
