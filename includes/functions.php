<?php

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
        return $array[$key];
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
    if ( fooconvert_fs()->can_use_premium_code__premium_only() ) {
        return apply_filters( 'fooconvert_retention', intval( FOOCONVERT_RETENTION_DEFAULT ) );
    }
    return FOOCONVERT_RETENTION_DEFAULT;
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
 * Retrieves the URL for the FooConvert Pricing admin page.
 *
 * @return string The URL for the FooConvert Pricing admin page.
 */
function fooconvert_admin_url_pricing() {
    return admin_url( 'admin.php?page=fooconvert-pricing' );
}

/**
 * Retrieves the base URL for the FooConvert Widget Stats admin page.
 *
 * This function constructs and returns the base URL for accessing the
 * FooConvert Widget Stats page in the WordPress admin area.
 *
 * @return string The base URL for the FooConvert Widget Stats admin page.
 */
function fooconvert_admin_url_widget_stats_base() {
    return 'admin.php?page=' . FOOCONVERT_MENU_SLUG_WIDGET_STATS;
}

/**
 * Retrieves the URL for the FooConvert Widget Stats admin page.
 *
 * @param int $widget_id The ID of the widget to view stats for.
 *
 * @return string The URL for the FooConvert Widget Stats admin page.
 */
function fooconvert_admin_url_widget_stats( $widget_id ) {
    return admin_url( fooconvert_admin_url_widget_stats_base() . '&widget_id=' . $widget_id );
}

/**
 * Retrieves the URL for the FooConvert Widget Edit admin page.
 *
 * @param int $widget_id The ID of the widget to edit.
 *
 * @return string The URL for the FooConvert Widget Edit admin page.
 */
function fooconvert_admin_url_widget_edit( $widget_id ) {
    return admin_url( 'post.php?post=' . $widget_id . '&action=edit' );
}

/**
 * Checks if the FooConvert PRO Analytics Addon is active.
 *
 * @return bool True if the FooConvert Analytics Addon is active, false otherwise.
 */
function fooconvert_is_analytics_addon_active() {
    return function_exists( 'fcpa_fs' ) && did_action( 'fcpa_fs_loaded' );
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

function fooconvert_widget_metric_options() {
    // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
    return apply_filters( 'fooconvert_widget_metric_options', array(
        'engagements' => [
            'dropdown_option' => __( 'engagements', 'fooconvert' ),
            'label'           => __( 'Engagements', 'fooconvert' ),
            'description'     => __( 'Total number of engagements', 'fooconvert' ),
            'metric'          => 'total_engagements',
            'function'        => 'intval',
            'meta_key'        => FOOCONVERT_META_KEY_METRIC_ENGAGEMENTS,
        ],
        'views'       => [
            'dropdown_option' => __( 'views', 'fooconvert' ),
            'label'           => __( 'Views', 'fooconvert' ),
            'description'     => __( 'Total number of views', 'fooconvert' ),
            'metric'          => 'total_views',
            'function'        => 'intval',
            'meta_key'        => FOOCONVERT_META_KEY_METRIC_VIEWS,
        ],
    ) );
    // phpcs:enable
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
    if ( !$post ) {
        return '';
    }

    // Check if the post has a title
    if ( !empty( $post->post_title ) ) {
        return $post->post_title;
    }

    return fooconvert_get_widget_post_type_label( $post ) . ' #' . $post->ID;
}

/**
 * Retrieves a singular label for a given post type.
 *
 * If the post is not available, the function will return an empty string.
 * If the post has a valid post type, the singular name for that post type
 * will be returned. If the post type is not available, the function will
 * return a generic label 'Post'.
 *
 * @param WP_Post|string $thing The post object to fetch the post type from.
 * @return string The singular label for the post type.
 */
function fooconvert_get_widget_post_type_label( $thing ) {
    if ( !$thing ) {
        return '';
    }
    if ( $thing instanceof WP_Post ) {
        $post_type = get_post_type_object( $thing->post_type );
        return $post_type ? $post_type->labels->singular_name : 'Post';
    } else if ( is_string( $thing ) ) {
        switch ( $thing ) {
            case 'fc-popup':
                return __( 'Popup', 'fooconvert' );
            case 'fc-flyout':
                return __( 'Flyout', 'fooconvert' );
            case 'fc-bar':
                return __( 'Bar', 'fooconvert' );
        }
    }
    return '';
}
