<?php

namespace FooPlugins\FooConvert\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes admin script dependencies for the supported WordPress version range.
 */
class ScriptDependencies {

	/**
	 * Prepares a generated asset dependency list before enqueueing.
	 *
	 * @param array<int,string> $dependencies Script dependencies from an asset file.
	 * @return array<int,string>
	 */
	public static function prepare( array $dependencies ): array {
		if ( in_array( 'react-jsx-runtime', $dependencies, true ) ) {
			self::register_react_jsx_runtime_shim();
		}

		return $dependencies;
	}

	/**
	 * Registers the JSX runtime handle for WordPress versions that do not include it.
	 *
	 * @return void
	 */
	private static function register_react_jsx_runtime_shim(): void {
		if ( function_exists( 'wp_script_is' ) && wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
			return;
		}

		if ( ! function_exists( 'wp_register_script' ) || ! function_exists( 'wp_add_inline_script' ) ) {
			return;
		}

		wp_register_script(
			'react-jsx-runtime',
			false,
			array( 'wp-element' ),
			FOOCONVERT_VERSION,
			true
		);

		wp_add_inline_script(
			'react-jsx-runtime',
			'window.ReactJSXRuntime=window.ReactJSXRuntime||{Fragment:window.wp.element.Fragment,jsx:function(type,props,key){if(key!==undefined){props=Object.assign({},props,{key:key});}return window.wp.element.createElement(type,props);},jsxs:function(type,props,key){if(key!==undefined){props=Object.assign({},props,{key:key});}return window.wp.element.createElement(type,props);}};',
			'before'
		);
	}
}
