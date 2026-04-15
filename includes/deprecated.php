<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the single registered popup post type used by FooConvert.
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

/**
 * Returns the registered popup post types.
 *
 * @deprecated 2.0.0 Use array( FOOCONVERT_CPT_POPUP ) or FOOCONVERT_CPT_POPUP directly.
 *
 * @return string[]
 */
function fooconvert_get_post_types() {
    if ( function_exists( '_deprecated_function' ) ) {
        _deprecated_function( __FUNCTION__, '2.0.0', 'array( FOOCONVERT_CPT_POPUP )' );
    }

    return array( FOOCONVERT_CPT_POPUP );
}

/**
 * Checks if the given post type is the popup CPT.
 *
 * @deprecated 2.0.0 Use $post_type === FOOCONVERT_CPT_POPUP directly.
 *
 * @param string $post_type The post type to check.
 * @return bool
 */
function fooconvert_is_valid_post_type( $post_type ) {
    if ( function_exists( '_deprecated_function' ) ) {
        _deprecated_function( __FUNCTION__, '2.0.0', '$post_type === FOOCONVERT_CPT_POPUP' );
    }

    return $post_type === FOOCONVERT_CPT_POPUP;
}
