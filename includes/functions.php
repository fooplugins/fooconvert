<?php
/**
 * Contains all the Global common functions used throughout the plugin.
 */

/**
 * Custom Autoloader used throughout FooConvert
 *
 * @param $class
 */
function fooconvert_autoloader( $class ) {
	/* Only autoload classes from this namespace */
	if ( false === strpos( $class, FOOCONVERT_NAMESPACE ) ) {
		return;
	}

	/* Remove namespace from class name */
	$class_file = str_replace( FOOCONVERT_NAMESPACE . '\\', '', $class );

	/* Convert sub-namespaces into directories */
	$class_path = explode( '\\', $class_file );
	$class_file = array_pop( $class_path );
	$class_path = strtolower( implode( '/', $class_path ) );

	/* Convert class name format to file name format */
	$class_file = fooconvert_uncamelize( $class_file );
	$class_file = str_replace( '_', '-', $class_file );
	$class_file = str_replace( '--', '-', $class_file );

	/* Load the class */
	require_once FGFUU_DIR . '/includes/' . $class_path . '/class-' . $class_file . '.php';
}

/**
 * Convert a CamelCase string to camel_case
 *
 * @param $str
 *
 * @return string
 */
function fooconvert_uncamelize( $str ) {
	$str    = lcfirst( $str );
	$lc     = strtolower( $str );
	$result = '';
	$length = strlen( $str );
	for ( $i = 0; $i < $length; $i ++ ) {
		$result .= ( $str[ $i ] == $lc[ $i ] ? '' : '_' ) . $lc[ $i ];
	}

	return $result;
}

/**
 * Returns true if the PRO version is running
 */
function fooconvert_is_pro() {
    global $fooconvert_pro;

    if ( isset( $fooconvert_pro ) ) {
        return $fooconvert_pro;
    }

    $fooconvert_pro = false;

    //Check if the PRO version of FooConvert is running
    if ( fooconvert_fs()->is__premium_only() ) {
        if ( fooconvert_fs()->can_use_premium_code() ) {
            $fooconvert_pro = true;
        }
    }

    return $fooconvert_pro;
}