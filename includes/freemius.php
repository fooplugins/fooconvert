<?php
/**
 * Freemius Init code.
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
            require_once dirname( FOOCONVERT_FILE ) . '/freemius/start.php';

            $fooconvert_fs = fs_dynamic_init( array(
                'id'                  => '14677',
                'slug'                => 'fooconvert',
                'type'                => 'plugin',
                'public_key'          => 'pk_88b6346482978e6778a77c484cfbe',
                'is_premium'          => false,
                'has_addons'          => false,
                'has_paid_plans'      => false,
//                'menu'                => array(
//                    'slug'           => 'edit.php?post_type=fooconvert',
//                    'support'        => false,
//                ),
            ) );
        }

        return $fooconvert_fs;
    }

    // Init Freemius.
    fooconvert_fs();
    // Signal that SDK was initiated.
    do_action( 'fooconvert_fs_loaded' );
}