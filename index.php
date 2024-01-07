<?php
/*
Plugin Name: FooConvert – WordPress Sales and Conversion Plugin
Description: Turn clicks into conversions, visitors into customers – FooConvert is the ultimate catalyst for online success!
Version:     0.0.1
Author:      FooPlugins
Plugin URI:  https://fooplugins.com/fooconvert-wordpress-sales-conversion/
Author URI:  https://fooplugins.com/
Text Domain: fooconvert
License:     GPL-3.0+
Domain Path: /languages

@fs_premium_only /pro/

*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define some FooConvert essentials constants.
if ( ! defined( 'FOOCONVERT_SLUG' ) ) {
	define( 'FOOCONVERT_SLUG', 'fooconvert' );
	define( 'FOOCONVERT_NAMESPACE', 'FooPlugins\FooConvert' );
	define( 'FOOCONVERT_DIR', __DIR__ );
	define( 'FOOCONVERT_PATH', plugin_dir_path( __FILE__ ) );
	define( 'FOOCONVERT_URL', plugin_dir_url( __FILE__ ) );
	define( 'FOOCONVERT_ASSETS_URL', FOOCONVERT_URL . 'assets/' );
	define( 'FOOCONVERT_FILE', __FILE__ );
	define( 'FOOCONVERT_VERSION', '0.0.1' );
	define( 'FOOCONVERT_MIN_PHP', '5.6.0' ); // Minimum of PHP 5.4 required for autoloading, namespaces, etc.
	define( 'FOOCONVERT_MIN_WP', '5.0.0' );  // Minimum of WordPress 5 required.
}

// Include other essential FooConvert constants.
require_once FOOCONVERT_PATH . 'includes/constants.php';

// Include common global FooConvert functions.
require_once FOOCONVERT_PATH . 'includes/functions.php';

// Do a check to see if either free/pro version of the plugin is already running.
if ( function_exists( 'fooconvert_fs' ) ) {
    fooconvert_fs()->set_basename( true, __FILE__ );
} else {
	if ( ! function_exists( 'fooconvert_fs' ) ) {
		require_once FOOCONVERT_PATH . 'includes/freemius.php';
	}
}

// Check minimum requirements before loading the plugin.
if ( require_once FOOCONVERT_PATH . 'includes/startup-checks.php' ) {

	// Start autoloader.
	require_once FOOCONVERT_PATH . 'vendor/autoload.php';

	spl_autoload_register( 'fooconvert_autoloader' );

	// Hook in activation.
	register_activation_hook( __FILE__, array( 'FooPlugins\FooConvert\Activation', 'activate' ) );

	// Start the plugin!
	new FooPlugins\FooConvert\Init();
}
