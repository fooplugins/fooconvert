<?php

/**
 * An autoloader based on the {@link https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/ WordPress - PHP Coding Standards}.
 *
 * Supports loading of classes, enums, interfaces or traits using the following file name formats.
 *
 *  - `class Provider` => `class-provider.php`
 *  - `interface IProvider` => `interface-iprovider.php` OR `interface-provider.php`
 *  - `trait Provider_Properties` => `trait-provider-properties.php`
 *  - `enum Provider_State` => `enum-provider-state.php`
 *
 * @param string $qualified_name The qualified name, including namespace, of the class, enum, interface or trait that requires loading.
 * @return void
 * @remarks
 *  This autoloader requires the `FOOCONVERT_NAMESPACE` and `FOOCONVERT_INCLUDES_PATH` global constants to be defined.
 */
function fooconvert_autoloader( string $qualified_name ) {

    /* Only autoload classes from this namespace */
    if ( ! str_starts_with( $qualified_name, FOOCONVERT_NAMESPACE . '\\' ) ) {
        return;
    }

    /*
     * Remove the base namespace from the qualified name
     *
     *      'Base\Namespace\Folder\Sub_Folder\Target_Name' => 'Folder\Sub_Folder\Target_Name'
     */
    $_name = str_replace( FOOCONVERT_NAMESPACE . '\\', '', $qualified_name );

    /*
     * Convert sub-namespaces into a directory path and extract the target name
     *
     *      'Folder\Sub_Folder\Target_Name'
     *           => $path = 'folder/sub-folder/'
     *           => $target_name = 'target-name'
     */
    $lower_dashed = preg_replace( '/_+/', '-', strtolower( $_name ) );
    $parts = explode( '\\', $lower_dashed );
    $target_name = array_pop( $parts );
    $path = implode( '/', $parts );

    // ensure the path ends with a slash
    if ( ! empty( $path ) && ! str_ends_with( $path, '/' ) ) {
        $path .= '/';
    }

    /*
     * Attempt to load the target name from the directory path using the type specific naming
     * conventions in the following order.
     *
     *      'target-name'
     *          => 'INCLUDES_PATH/folder/sub-folder/class-target-name.php'
     *          => 'INCLUDES_PATH/folder/sub-folder/interface-target-name.php'
     *          => 'INCLUDES_PATH/folder/sub-folder/trait-target-name.php'
     *          => 'INCLUDES_PATH/folder/sub-folder/enum-target-name.php'
     *
     * NOTE: If the target name starts with the 'i' character an additional check is performed for interfaces
     *
     *      'itarget-name'
     *          => 'INCLUDES_PATH/folder/sub-folder/interface-itarget-name.php'
     *          => 'INCLUDES_PATH/folder/sub-folder/interface-target-name.php'
     */
    $class_path = FOOCONVERT_INCLUDES_PATH . $path . "class-$target_name.php";
    if ( file_exists( $class_path ) ) {
        require_once $class_path;
        return;
    }

    $interface_path = FOOCONVERT_INCLUDES_PATH . $path . "interface-$target_name.php";
    if ( file_exists( $interface_path ) ) {
        require_once $interface_path;
        return;
    }

    // additional check for possible interface
    if ( str_starts_with( $target_name, 'i' ) ) {
        $maybe_interface = substr( $target_name, 1 );
        $maybe_interface_path = FOOCONVERT_INCLUDES_PATH . $path . "interface-$maybe_interface.php";
        if ( file_exists( $maybe_interface_path ) ) {
            require_once $maybe_interface_path;
            return;
        }
    }

    $trait_path = FOOCONVERT_INCLUDES_PATH . $path . "trait-$target_name.php";
    if ( file_exists( $trait_path ) ) {
        require_once $trait_path;
        return;
    }

    $enum_path = FOOCONVERT_INCLUDES_PATH . $path . "enum-$target_name.php";
    if ( file_exists( $enum_path ) ) {
        require_once $enum_path;
    }
}