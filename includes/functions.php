<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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


/**
 * Safe way to get value from an array
 *
 * @param $key
 * @param $array
 * @param $default
 *
 * @return mixed
 */
function fooconvert_safe_get_from_array( $key, $array, $default ) {
    if ( is_array( $array ) && array_key_exists( $key, $array ) ) {
        return $array[ $key ];
    } else if ( is_object( $array ) && property_exists( $array, $key ) ) {
        return $array->{$key};
    }

    return $default;
}

/**
 * Returns the fooconvert settings from options table
 */
function fooconvert_get_settings() {
    return get_option( FOOCONVERT_OPTION_DATA );
}

/**
 * Returns a specific option based on a key
 *
 * @param $key
 * @param $default
 *
 * @return mixed
 */
function fooconvert_get_setting( $key, $default = false ) {
    $settings = fooconvert_get_settings();

    return fooconvert_safe_get_from_array( $key, $settings, $default );
}

/**
 * Sets a specific option based on a key
 *
 * @param $key
 * @param $value
 *
 * @return mixed
 */
function fooconvert_set_setting( $key, $value ) {
    $settings = fooconvert_get_settings();
    $settings[$key] = $value;

    update_option( FOOCONVERT_OPTION_DATA, $settings );
}

/**
 * Returns true if FooConvert is in debug mode
 * @return bool
 */
function fooconvert_is_debug() {
    return fooconvert_get_setting( 'debug', false );
}

/**
 * Retrieve the custom post types for all FooConvert widgets.
 *
 * This function accesses the FooConvert plugin's widgets and retrieves
 * an array of custom post types associated with all widgets.
 *
 * @return string[] An array of custom post type strings.
 */
function fooconvert_get_post_types() {
    return \FooPlugins\FooConvert\FooConvert::plugin()->widgets->get_post_types();
}

/**
 * Checks if the given post type is valid for FooConvert widgets.
 *
 * @param string $post_type The post type to check.
 *
 * @return bool True if the post type is valid, false otherwise.
 */
function fooconvert_is_valid_post_type( $post_type ) {
    return in_array( $post_type, fooconvert_get_post_types() );
}

/**
 * Retrieves the retention period for FooConvert data.
 *
 * The retention period is the number of days FooConvert will store its data.
 *
 * @return int The retention period in days.
 */
function fooconvert_retention() {
    return apply_filters( 'fooconvert_retention', intval( FOOCONVERT_RETENTION_DEFAULT ) );
}

/**
 * Retrieves the URL for the FooConvert Addons admin page.
 *
 * @return string The URL for the FooConvert Addons admin page.
 */
function fooconvert_admin_url_addons() {
    return admin_url( 'admin.php?page=fooconvert-addons' );
}

/**
 * Retrieves the URL for the FooConvert Widget Stats admin page.
 *
 * @param int $widget_id The ID of the widget to view stats for.
 *
 * @return string The URL for the FooConvert Widget Stats admin page.
 */
function fooconvert_admin_url_widget_stats( $widget_id ) {
    return admin_url( 'admin.php?page=fooconvert-widget-stats&widget_id=' . $widget_id );
}

/**
 * Checks if the FooConvert PRO Analytics Addon is active.
 *
 * @return bool True if the FooConvert Analytics Addon is active, false otherwise.
 */
function fooconvert_is_analytics_addon_active() {
    return function_exists( 'fcpa_fs' ) && did_action('fcpa_fs_loaded');
}

/**
 * Retrieves the sorting preference for top performers in FooConvert.
 *
 * This function fetches the sorting criteria for top performers from the
 * WordPress options table. If no value is set, it defaults to 'engagement'.
 *
 * @return string The sorting preference for top performers.
 */
function fooconvert_top_performers_sort() {
    return get_option( FOOCONVERT_OPTION_TOP_PERFORMERS_SORT, 'engagements' );
}

function fooconvert_top_performers_sort_options() {
    return apply_filters( 'fooconvert_top_performers_sort_options', array(
        'engagements' => [
            'dropdown_option' => __( 'engagements', 'fooconvert' ),
            'table_header'    => __( 'Engagements', 'fooconvert' ),
            'tooltip'         => __( 'Total number of engagements', 'fooconvert' ),
            'metric'          => 'total_engagements',
            'function'        => 'intval',
            'meta_key'        => FOOCONVERT_META_KEY_METRIC_ENGAGEMENTS,
        ],
        'views'      => [
            'dropdown_option' => __( 'views', 'fooconvert' ),
            'table_header'    => __( 'Views', 'fooconvert' ),
            'tooltip'         => __( 'Total number of views', 'fooconvert' ),
            'metric'          => 'total_views',
            'function'        => 'intval',
            'meta_key'        => FOOCONVERT_META_KEY_METRIC_VIEWS,
        ],
    ) );
}

/**
 * Convert a percentage string to a float
 *
 * @param string $percentage Percentage string, e.g. '34.5%'
 * @return float
 */
function fooconvert_percentage_to_float( $percentage ) {
    if ( empty( $percentage ) ) {
        return 0;
    }

    // Remove the percentage sign if it exists
    $number = str_replace( '%', '', $percentage );

    // Convert the string to a float
    return floatval( $number );
}

/**
 * Retrieves a human-readable representation of when FooConvert stats were last updated.
 *
 * If the stats have never been updated, this function will return the string "Never".
 * Otherwise, it returns a string describing the time that has elapsed since the last update.
 *
 * @return string A human-readable representation of when FooConvert stats were last updated.
 */
function fooconvert_stats_last_updated() {
    $last_updated = get_option( FOOCONVERT_OPTION_STATS_LAST_UPDATED, fooconvert_stats_last_updated_default() );

    if ( $last_updated !== fooconvert_stats_last_updated_default() ) {
        // We can assume we have a timestamp.
        $last_updated = human_time_diff( $last_updated ) . ' ' . __( 'ago', 'fooconvert' );
    }
    return $last_updated;
}

/**
 * Checks if the FooConvert stats have ever been updated.
 *
 * This function checks if the value of `fooconvert_stats_last_updated()` is different
 * from the default value returned by `fooconvert_stats_last_updated_default()`.
 * If the values are different, this function returns `true`, indicating that the stats
 * have been updated at least once. Otherwise, it returns `false`.
 *
 * @return bool Whether the FooConvert stats have ever been updated.
 */
function fooconvert_has_stats_last_updated() {
    return fooconvert_stats_last_updated() !== fooconvert_stats_last_updated_default();
}

/**
 * Returns the default value for the last updated timestamp of FooConvert stats.
 *
 * If the stats have never been updated, this function will return the string "Never".
 * This default value is used for display purposes when no actual timestamp is available.
 *
 * @return string The default string "Never" indicating that stats have not been updated.
 */
function fooconvert_stats_last_updated_default() {
    return __( 'Never', 'fooconvert' );
}

/**
 * Retrieves a title for a FooConvert widget from a given post object.
 *
 * If the post has a title, it will be returned as is. If the post has no title,
 * the function will return a string in the format "Post Type #<post ID>".
 *
 * @param WP_Post $post The post object to fetch the title from.
 * @return string The title for the FooConvert widget.
 */
function fooconvert_get_widget_title( $post ) {
// Return an empty string if no valid post is found
    if ( ! $post ) {
        return '';
    }

    // Check if the post has a title
    if ( ! empty( $post->post_title ) ) {
        return $post->post_title;
    }

    // Get the post type object
    $post_type = get_post_type_object( $post->post_type );

    // Use the post type label with the post ID if title is empty
    $post_type_label = $post_type ? $post_type->labels->singular_name : 'Post';

    return $post_type_label . ' #' . $post->ID;
}