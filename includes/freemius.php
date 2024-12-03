<?php
/**
 * Runs all the Freemius initialization code for FooConvert
 */
if ( ! function_exists( 'fooconvert_fs' ) ) {
	// Create a helper function for easy SDK access.
	function fooconvert_fs() {
		global $fooconvert_fs;

		if ( ! isset( $fooconvert_fs ) ) {
            // Activate multisite network integration.
            if ( ! defined( 'WP_FS__PRODUCT_14677_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_14677_MULTISITE', true );
            }

			// Include Freemius SDK.
			require_once FOOCONVERT_PATH . 'vendor/freemius/wordpress-sdk/start.php';

            $fooconvert_fs = fs_dynamic_init( array(
				'id'                  => '14677',
				'slug'                => 'fooconvert',
				'type'                => 'plugin',
				'public_key'          => 'pk_88b6346482978e6778a77c484cfbe',
				'is_premium'          => false,
				'has_addons'          => true,
				'has_paid_plans'      => false,
                'menu'                => array(
                    'slug'           => 'fooconvert',
                    'support'        => false,
                ),
			) );
		}

		return $fooconvert_fs;
	}

	// Init Freemius.
    fooconvert_fs();
	// Signal that SDK was initiated.
	do_action( 'fooconvert_fs_loaded' );

    fooconvert_fs()->add_filter( 'plugin_icon',	function ( $icon ) {
		return FOOCONVERT_ASSETS_PATH . 'media/icon.png';
	}, 10, 1 );
}

