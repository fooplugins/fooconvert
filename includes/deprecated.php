<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the single registered widget post type used by FooConvert.
 *
 * @deprecated 2.0.0 Use FOOCONVERT_CPT_POPUP directly.
 *
 * @return string
 */
function fooconvert_get_registered_post_type() {
    if ( function_exists( '_deprecated_function' ) ) {
        _deprecated_function( __FUNCTION__, '2.0.0', 'FOOCONVERT_CPT_POPUP' );
    }

    return FOOCONVERT_CPT_POPUP;
}
