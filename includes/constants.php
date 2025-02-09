<?php
if ( !defined( 'ABSPATH' ) ) exit;                                              // Exit if accessed directly
/**
 * Contains the Global constants used throughout the plugin
 */

//options
define( 'FOOCONVERT_OPTION_DATA', 'fooconvert-settings' );
define( 'FOOCONVERT_OPTION_VERSION', 'fooconvert-version' );
define( 'FOOCONVERT_OPTION_VERSION_CREATE_TABLE', 'fooconvert-version-create-table' );
define( 'FOOCONVERT_OPTION_DATABaseDATA', 'fooconvert-database-data' );
define( 'FOOCONVERT_OPTION_TOP_PERFORMERS_SORT', 'fooconvert_top_performers_sort' );
define( 'FOOCONVERT_OPTION_STATS_LAST_UPDATED', 'fooconvert_stats_last_updated' );
define( 'FOOCONVERT_OPTION_RECENT_ACTIVITY_DAYS', 'fooconvert_recent_activity_days' );

// Post meta
define( 'FOOCONVERT_META_KEY_DISPLAY_RULES', '_fooconvert_display_rules' );     // Meta key for the widget display rules.
define( 'FOOCONVERT_META_KEY_COMPATIBILITY', '_fooconvert_compatibility' );     // Meta key for the compatibility settings.
define( 'FOOCONVERT_META_KEY_DEMO_CONTENT', '_fooconvert_demo_content' );       // Meta key for the demo content.
define( 'FOOCONVERT_META_KEY_METRICS', '_fooconvert_metrics' );                 // Meta key for the widget metrics.
define( 'FOOCONVERT_META_KEY_METRIC_VIEWS', '_fooconvert_metric_views' );
define( 'FOOCONVERT_META_KEY_METRIC_ENGAGEMENTS', '_fooconvert_metric_engagements' );

//CPT's
define( 'FOOCONVERT_CPT_BAR', 'fc-bar' );
define( 'FOOCONVERT_CPT_FLYOUT', 'fc-flyout' );
define( 'FOOCONVERT_CPT_POPUP', 'fc-popup' );

//EVENT TYPE
define( 'FOOCONVERT_EVENT_TYPE_OPEN', 'open' );
define( 'FOOCONVERT_EVENT_TYPE_CLOSE', 'close' );
define( 'FOOCONVERT_EVENT_TYPE_CLICK', 'click' );
define( 'FOOCONVERT_EVENT_TYPE_UPDATE', 'update' );
define( 'FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT', 'engagement' );

//other
define( 'FOOCONVERT_DB_TABLE_EVENTS', 'fooconvert_events' );
define( 'FOOCONVERT_EDITOR_ASSET_HANDLE', 'fc-editor' );
define( 'FOOCONVERT_FRONTEND_ASSET_HANDLE', 'fc-frontend' );
define( 'FOOCONVERT_MENU_SLUG', 'fooconvert' );
define( 'FOOCONVERT_MENU_SLUG_DASHBOARD', 'fooconvert-dashboard' );
define( 'FOOCONVERT_MENU_SLUG_WIDGET_STATS', 'fooconvert-widget-stats' );
define( 'FOOCONVERT_RETENTION_DEFAULT', 14 );
define( 'FOOCONVERT_RECENT_ACTIVITY_DAYS_DEFAULT', 7 );

//CRON
define( 'FOOCONVERT_CRON_CALC_STATS', 'fooconvert_calculate_widget_stats' );
define( 'FOOCONVERT_CRON_DELETE_EVENTS', 'fooconvert_delete_old_events' );

//SVG KSES
/**
 * An array of SVG elements and attributes to use in KSES operations.
 *
 * GENERATED: Do not modify, see 'src/create-kses-svg-elements.php'.
 */
define( 'FOOCONVERT_SVG_ALLOWED_HTML', require 'constant-svg.php' );
/**
 * A string array of SVG CSS properties to use in KSES operations.
 */
define( 'FOOCONVERT_SVG_SAFE_CSS', array(
    'alignment-baseline',
    'baseline-shift',
    'clip',
    'clip-path',
    'clip-rule',
    'color',
    'color-interpolation',
    'color-interpolation-filters',
    'cursor',
    'd',
    'direction',
    'display',
    'dominant-baseline',
    'fill',
    'fill-opacity',
    'fill-rule',
    'filter',
    'flood-color',
    'flood-opacity',
    'font-family',
    'font-size',
    'font-size-adjust',
    'font-stretch',
    'font-style',
    'font-variant',
    'font-weight',
    'glyph-orientation-horizontal',
    'glyph-orientation-vertical',
    'image-rendering',
    'letter-spacing',
    'lighting-color',
    'marker-end',
    'marker-mid',
    'marker-start',
    'mask',
    'opacity',
    'overflow',
    'pointer-events',
    'shape-rendering',
    'stop-color',
    'stop-opacity',
    'stroke',
    'stroke-dasharray',
    'stroke-dashoffset',
    'stroke-linecap',
    'stroke-linejoin',
    'stroke-miterlimit',
    'stroke-opacity',
    'stroke-width',
    'text-anchor',
    'text-decoration',
    'text-rendering',
    'transform',
    'transform-origin',
    'unicode-bidi',
    'vector-effect',
    'visibility',
    'word-spacing',
    'writing-mode',
) );