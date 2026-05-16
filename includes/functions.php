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
 * Polyfill wrapper for str_contains().
 *
 * @param string $haystack The string to search in.
 * @param string $needle The substring to search for.
 * @return bool True when the needle is found in the haystack.
 */
function fooconvert_str_contains( $haystack, $needle ) {
    if ( function_exists( 'str_contains' ) ) {
        return str_contains( $haystack, $needle );
    }

    return '' === $needle || false !== strpos( $haystack, $needle );
}

/**
 * Polyfill wrapper for str_starts_with().
 *
 * @param string $haystack The string to inspect.
 * @param string $needle The prefix to look for.
 * @return bool True when the haystack starts with the needle.
 */
function fooconvert_str_starts_with( $haystack, $needle ) {
    if ( function_exists( 'str_starts_with' ) ) {
        return str_starts_with( $haystack, $needle );
    }

    return 0 === strncmp( $haystack, $needle, strlen( $needle ) );
}

/**
 * Polyfill wrapper for str_ends_with().
 *
 * @param string $haystack The string to inspect.
 * @param string $needle The suffix to look for.
 * @return bool True when the haystack ends with the needle.
 */
function fooconvert_str_ends_with( $haystack, $needle ) {
    if ( function_exists( 'str_ends_with' ) ) {
        return str_ends_with( $haystack, $needle );
    }

    if ( '' === $needle ) {
        return true;
    }

    return 0 === substr_compare( $haystack, $needle, -strlen( $needle ) );
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
 * Sanitizes the popup editor background setting value.
 *
 * @param mixed $value Raw popup editor background value.
 * @return string
 */
function fooconvert_sanitize_popup_editor_background( $value ) {
    $allowed = array( 'transparent', 'white', 'black' );
    $normalized = is_string( $value ) ? strtolower( trim( $value ) ) : '';

    return in_array( $normalized, $allowed, true ) ? $normalized : 'transparent';
}

/**
 * Returns the popup editor background setting.
 *
 * @return string
 */
function fooconvert_get_popup_editor_background() {
    return fooconvert_sanitize_popup_editor_background(
        fooconvert_get_setting( 'popup_editor_background', 'transparent' )
    );
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
 * Returns the supported logical popup types.
 *
 * @return string[]
 */
function fooconvert_get_popup_types() {
    return array(
        FOOCONVERT_POPUP_TYPE_BAR,
        FOOCONVERT_POPUP_TYPE_FLYOUT,
        FOOCONVERT_POPUP_TYPE_OVERLAY,
    );
}

/**
 * Normalizes popup type inputs to the canonical popup type.
 *
 * @param mixed $value Raw popup type value.
 * @return string
 */
function fooconvert_normalize_popup_type( $value ) {
    if ( !is_string( $value ) ) {
        return '';
    }

    switch ( strtolower( trim( $value ) ) ) {
        case FOOCONVERT_POPUP_TYPE_BAR:
        case FOOCONVERT_CPT_BAR:
        case 'fc/bar':
            return FOOCONVERT_POPUP_TYPE_BAR;
        case FOOCONVERT_POPUP_TYPE_FLYOUT:
        case FOOCONVERT_CPT_FLYOUT:
        case 'fc/flyout':
            return FOOCONVERT_POPUP_TYPE_FLYOUT;
        case FOOCONVERT_POPUP_TYPE_OVERLAY:
        case FOOCONVERT_POPUP_TYPE_POPUP:
        case FOOCONVERT_CPT_POPUP:
        case 'fc/overlay':
        case 'fc/popup':
        case 'fc-overlay':
            return FOOCONVERT_POPUP_TYPE_OVERLAY;
    }

    return '';
}

/**
 * Sanitizes a popup type for storage.
 *
 * @param mixed $value Raw popup type value.
 * @return string
 */
function fooconvert_sanitize_popup_type( $value ) {
    $popup_type = fooconvert_normalize_popup_type( $value );

    return $popup_type !== '' ? $popup_type : FOOCONVERT_POPUP_TYPE_OVERLAY;
}

/**
 * Maps a popup type to the corresponding logical popup post type.
 *
 * @param string $popup_type Popup type.
 * @return string
 */
function fooconvert_get_popup_type_post_type( $popup_type ) {
    switch ( fooconvert_normalize_popup_type( $popup_type ) ) {
        case FOOCONVERT_POPUP_TYPE_BAR:
            return FOOCONVERT_CPT_BAR;
        case FOOCONVERT_POPUP_TYPE_FLYOUT:
            return FOOCONVERT_CPT_FLYOUT;
        case FOOCONVERT_POPUP_TYPE_OVERLAY:
            return FOOCONVERT_CPT_POPUP;
    }

    return '';
}

/**
 * Maps a popup type to its root popup block name.
 *
 * @param string $popup_type Popup type.
 * @return string
 */
function fooconvert_get_popup_type_block_name( $popup_type ) {
    switch ( fooconvert_normalize_popup_type( $popup_type ) ) {
        case FOOCONVERT_POPUP_TYPE_BAR:
            return 'fc/bar';
        case FOOCONVERT_POPUP_TYPE_FLYOUT:
            return 'fc/flyout';
        case FOOCONVERT_POPUP_TYPE_OVERLAY:
            return 'fc/overlay';
    }

    return '';
}

/**
 * Returns the singular label for a popup type identifier.
 *
 * @param string $popup_type Popup type.
 * @return string
 */
function fooconvert_get_popup_type_name( $popup_type ) {
    switch ( fooconvert_normalize_popup_type( $popup_type ) ) {
        case FOOCONVERT_POPUP_TYPE_BAR:
            return __( 'Bar', 'fooconvert' );
        case FOOCONVERT_POPUP_TYPE_FLYOUT:
            return __( 'Flyout', 'fooconvert' );
        case FOOCONVERT_POPUP_TYPE_OVERLAY:
            return __( 'Overlay', 'fooconvert' );
    }

    return '';
}

/**
 * Resolves the canonical popup type for a popup.
 *
 * @param WP_Post|int|string $thing Popup post, ID, or logical type identifier.
 * @return string
 */
function fooconvert_get_popup_type( $thing ) {
    if ( $thing instanceof WP_Post ) {
        $post = $thing;
    } else if ( is_numeric( $thing ) ) {
        $post = get_post( (int) $thing );
        if ( !$post instanceof WP_Post ) {
            return '';
        }
    } else if ( is_string( $thing ) ) {
        return fooconvert_normalize_popup_type( $thing );
    } else {
        return '';
    }

    $stored_popup_type = get_post_meta( $post->ID, FOOCONVERT_META_KEY_POPUP_TYPE, true );
    $popup_type = fooconvert_normalize_popup_type( $stored_popup_type );
    $has_legacy_popup_meta = is_string( $stored_popup_type ) && strtolower( trim( $stored_popup_type ) ) === FOOCONVERT_POPUP_TYPE_POPUP;

    if ( $popup_type !== '' && !$has_legacy_popup_meta ) {
        return $popup_type;
    }

    if ( is_string( $post->post_content ) && $post->post_content !== '' ) {
        $blocks = parse_blocks( $post->post_content );
        foreach ( $blocks as $block ) {
            if ( !is_array( $block ) || !isset( $block['blockName'] ) ) {
                continue;
            }

            $popup_type = fooconvert_normalize_popup_type( $block['blockName'] );
            if ( $popup_type !== '' ) {
                return $popup_type;
            }
        }
    }

    $popup_type = fooconvert_normalize_popup_type( $post->post_type );
    if ( $popup_type !== '' ) {
        return $popup_type;
    }

    if ( $has_legacy_popup_meta ) {
        return FOOCONVERT_POPUP_TYPE_OVERLAY;
    }

    if ( $post->post_type === FOOCONVERT_CPT_POPUP ) {
        return FOOCONVERT_POPUP_TYPE_OVERLAY;
    }

    return '';
}

/**
 * Returns the popup type requested in the current admin request.
 *
 * @return string
 */
function fooconvert_get_requested_popup_type() {
    $popup_type = isset( $_GET['popup_type'] ) ? sanitize_key( wp_unslash( $_GET['popup_type'] ) ) : '';

    return fooconvert_normalize_popup_type( $popup_type );
}

/**
 * Checks whether WooCommerce is active.
 *
 * @return bool
 */
function fooconvert_is_woocommerce_active() {
    return class_exists( 'WooCommerce' );
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
 * Returns the trial URL within the admin.
 */
function fooconvert_admin_url_trial() {
    return fooconvert_fs()->get_trial_url();
}

/**
 * Retrieves the base URL for the FooConvert Popup Stats admin page.
 *
 * This function constructs and returns the base URL for accessing the
 * FooConvert Popup Stats page in the WordPress admin area.
 *
 * @return string The base URL for the FooConvert Popup Stats admin page.
 */
function fooconvert_admin_url_popup_stats_base() {
    return 'admin.php?page=' . FOOCONVERT_MENU_SLUG_POPUP_STATS;
}

/**
 * Retrieves the URL for the FooConvert Popup Stats admin page.
 *
 * @param int $post_id The ID of the popup to view stats for.
 *
 * @return string The URL for the FooConvert Popup Stats admin page.
 */
function fooconvert_admin_url_popup_stats( $post_id ) {
    return admin_url( fooconvert_admin_url_popup_stats_base() . '&post_id=' . $post_id );
}

/**
 * Retrieves the dedicated frontend preview URL for a popup.
 *
 * @param int $post_id The ID of the popup to preview.
 * @return string The URL for the popup preview page.
 */
function fooconvert_popup_preview_url( $post_id ) {
    $post_id = absint( $post_id );

    return add_query_arg(
        array(
            'fooconvert_popup_preview' => $post_id,
            '_fcpreviewnonce' => wp_create_nonce( 'fooconvert-popup-preview-' . $post_id ),
        ),
        home_url( '/' )
    );
}

/**
 * Retrieves the URL for the FooConvert Popup Edit admin page.
 *
 * @param int $post_id The ID of the popup to edit.
 *
 * @return string The URL for the FooConvert Popup Edit admin page.
 */
function fooconvert_admin_url_popup_edit( $post_id ) {
    return admin_url( 'post.php?post=' . $post_id . '&action=edit' );
}

/**
 * Retrieves the URL for the FooConvert popup type chooser.
 *
 * @return string
 */
function fooconvert_admin_url_popup_type_chooser() {
    return admin_url( 'admin.php?page=' . FOOCONVERT_MENU_SLUG_POPUP_CHOOSER );
}

/**
 * Retrieves the URL for creating a new FooConvert popup.
 *
 * @param string $popup_type Optional popup type.
 * @return string
 */
function fooconvert_admin_url_popup_new( $popup_type = '' ) {
    $args = array(
        'post_type' => FOOCONVERT_CPT_POPUP,
    );

    $popup_type = fooconvert_normalize_popup_type( $popup_type );
    if ( $popup_type !== '' ) {
        $args['popup_type'] = $popup_type;
    }

    return admin_url( add_query_arg( $args, 'post-new.php' ) );
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

/**
 * Returns the popup post statuses that should appear on the dashboard.
 *
 * @return array<int,string>
 */
function fooconvert_get_dashboard_popup_statuses() {
    if ( function_exists( 'get_post_stati' ) ) {
        $statuses = get_post_stati( array( 'show_in_admin_all_list' => true ), 'names' );
        if ( is_array( $statuses ) && !empty( $statuses ) ) {
            $statuses = array_values( array_diff( $statuses, array( 'trash', 'auto-draft', 'inherit' ) ) );
            if ( !empty( $statuses ) ) {
                return $statuses;
            }
        }
    }

    return array( 'publish', 'future', 'draft', 'pending', 'private' );
}

/**
 * Determines whether a popup post should appear in dashboard rankings and tables.
 *
 * @param WP_Post|int $thing Popup post object or ID.
 * @return bool
 */
function fooconvert_is_dashboard_popup_post( $thing ) {
    if ( $thing instanceof WP_Post ) {
        $post = $thing;
    } else if ( is_numeric( $thing ) ) {
        $post = get_post( (int) $thing );
    } else {
        return false;
    }

    if ( !$post instanceof WP_Post || $post->post_type !== FOOCONVERT_CPT_POPUP ) {
        return false;
    }

    $status = isset( $post->post_status ) ? (string) $post->post_status : '';
    if ( $status === '' && function_exists( 'get_post_status' ) ) {
        $status = (string) get_post_status( $post );
    }

    return $status !== 'trash';
}

/**
 * Adds a Google font to the list of fonts used in the editor and frontend.
 *
 * @param array $fonts The list of fonts.
 * @param string $name The display name of the font.
 * @param string $url The Google Fonts family string.
 * @param bool $overwrite Whether to overwrite an existing font entry.
 */
function fooconvert_add_font( array &$fonts, string $name, string $url, bool $overwrite = false ) {
    $slug = sanitize_title( $name );
    $exists = array_key_exists( $slug, $fonts );
    if ( $overwrite || !$exists ) {
        $fonts[ $slug ] = [
            'slug' => $slug,
            'url'  => $url,
            'name' => $name
        ];
    }
}

/**
 * Strips unnecessary parts from a Google Fonts URL if the full URL was pasted.
 *
 * @param string $google_font_value The saved font family value.
 * @return string
 */
function fooconvert_fix_google_font_url( $google_font_value ) {
    $array_to_strip = [ 'https://fonts.googleapis.com/css2?family=', '&display=swap' ];
    return str_replace( $array_to_strip, '', $google_font_value );
}

/**
 * Handles popup metric options.
 */
function fooconvert_popup_metric_options() {
    // phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key
    return apply_filters( 'fooconvert_popup_metric_options', array(
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
 * Retrieves a title for a FooConvert popup from a given post object.
 *
 * If the post has a title, it will be returned as is. If the post has no title,
 * the function will return a string in the format "Post Type #<post ID>".
 *
 * @param WP_Post $post The post object to fetch the title from.
 * @return string The title for the FooConvert popup.
 */
function fooconvert_get_popup_title( $post ) {
    // Return an empty string if no valid post is found
    if ( !$post ) {
        return '';
    }

    // Check if the post has a title
    if ( !empty( $post->post_title ) ) {
        return $post->post_title;
    }

    return fooconvert_get_popup_type_label( $post ) . ' #' . $post->ID;
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
function fooconvert_get_popup_type_label( $thing ) {
    if ( !$thing ) {
        return '';
    }

    $popup_type = fooconvert_get_popup_type( $thing );
    if ( $popup_type !== '' ) {
        return fooconvert_get_popup_type_name( $popup_type );
    }

    if ( $thing instanceof WP_Post ) {
        $post_type = get_post_type_object( $thing->post_type );
        return $post_type ? $post_type->labels->singular_name : 'Post';
    } else if ( is_string( $thing ) ) {
        $post_type = get_post_type_object( $thing );
        return $post_type ? $post_type->labels->singular_name : '';
    }

    return '';
}

/**
 * Checks if the current page is a FooConvert popup stats page.
 *
 * This function checks if the current page is an admin page and if the
 * page is a FooConvert popup stats page. The function will return true
 * if the page meets both conditions.
 *
 * @return bool True if the current page is a FooConvert popup stats page, false otherwise.
 */
function fooconvert_is_popup_stats_page() {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    $post_id = isset( $_GET['post_id'] ) ? absint( wp_unslash( $_GET['post_id'] ) ) : 0;

    return is_admin() &&
        $page === 'fooconvert-popup-stats' &&
        $post_id > 0;
}

/**
 * Checks if the current request is the dedicated popup preview page.
 *
 * @return bool True if the current request is a popup preview, false otherwise.
 */
function fooconvert_is_popup_preview_request() {
    $preview_id = isset( $_GET['fooconvert_popup_preview'] ) ? absint( wp_unslash( $_GET['fooconvert_popup_preview'] ) ) : 0;

    return !is_admin() &&
        $preview_id > 0;
}

/**
 * Returns an array of PRO features for FooConvert.
 * Each feature has a 'title', 'feature', and optional 'link'.
 *
 * @return array[]
 */
function fooconvert_pro_features_list() {
    return [
        'leads' => [
            'title'   => __( 'Lead Integrations', 'fooconvert' ),
            'feature' => __( 'Send captured leads to Mailchimp, MailPoet, or custom webhook integrations.', 'fooconvert' ),
            'link'    => 'https://fooplugins.com/documentation/fooconvert/pro-features-fooconvert/gathering-leads/',
        ],
        'retention' => [
            'title'   => __( 'Longer Retention', 'fooconvert' ),
            'feature' => __( 'Longer retention period for popup analytics. Track popup performance as long as you like!', 'fooconvert' ),
            'link'    => 'https://fooplugins.com/documentation/fooconvert/pro-features-fooconvert/pro-analytics/#retention',
        ],
        'analytics' => [
            'title'   => __( 'Advanced Analytics', 'fooconvert' ),
            'feature' => __( 'Advanced popup analytics: Clicks, click-through-rates, conversions, conversion rates, engagement sentiment, engagement ratios, daily activity charts.', 'fooconvert' ),
            'link'    => 'https://fooplugins.com/documentation/fooconvert/pro-features-fooconvert/pro-analytics/',
        ],
        'metrics' => [
            'title'   => __( 'More Dashboard Metrics', 'fooconvert' ),
            'feature' => __( 'Top Performers by Engagement Rate, Clicks, Click Rate, Conversions, Conversion Rate.', 'fooconvert' ),
            'link'    => 'https://fooplugins.com/documentation/fooconvert/pro-features-fooconvert/pro-analytics/',
        ],
        'exclusions' => [
            'title'   => __( 'Role Exclusion', 'fooconvert' ),
            'feature' => __( 'Exclude roles from logging popup events for analytics (e.g., exclude admin tests).', 'fooconvert' ),
            'link'    => 'https://fooplugins.com/documentation/fooconvert/pro-features-fooconvert/exclude-roles/',
        ],
    ];
}

/**
 * Tries to get the docs URL for a specific feature, with a fallback to the default.
 */
function fooconvert_pro_feature_docs_url( $feature ) {
    $features = fooconvert_pro_features_list();

    if ( isset( $features[ $feature ] ) ) {
        return $features[ $feature ]['link'];
    }

    return FOOCONVERT_DOCS_URL;
}
