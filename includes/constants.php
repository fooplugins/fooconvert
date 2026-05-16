<?php
if ( !defined( 'ABSPATH' ) ) exit;                                              // Exit if accessed directly
/**
 * Contains the Global constants used throughout the plugin
 */

//options
define( 'FOOCONVERT_OPTION_DATA', 'fooconvert-settings' );
define( 'FOOCONVERT_OPTION_VERSION', 'fooconvert-version' );
define( 'FOOCONVERT_OPTION_VERSION_CREATE_TABLE', 'fooconvert-version-create-table' );
define( 'FOOCONVERT_OPTION_CONTENT_MIGRATIONS', 'fooconvert-content-migrations' );
define( 'FOOCONVERT_OPTION_UPGRADE_MIGRATIONS', 'fooconvert-upgrade-migrations' );
define( 'FOOCONVERT_OPTION_DATABASEDATA', 'fooconvert-database-data' );
define( 'FOOCONVERT_OPTION_TOP_PERFORMERS_SORT', 'fooconvert_top_performers_sort' );
define( 'FOOCONVERT_OPTION_STATS_LAST_UPDATED', 'fooconvert_stats_last_updated' );
define( 'FOOCONVERT_OPTION_RECENT_ACTIVITY_DAYS', 'fooconvert_recent_activity_days' );
define( 'FOOCONVERT_OPTION_DISPLAY_RULES', 'fooconvert_display_rules' );

// Post meta
define( 'FOOCONVERT_META_KEY_DISPLAY_RULES', '_fooconvert_display_rules' );     // Meta key for the popup display rules.
define( 'FOOCONVERT_META_KEY_COMPATIBILITY', '_fooconvert_compatibility' );     // Meta key for the compatibility settings.
define( 'FOOCONVERT_META_KEY_DEMO_CONTENT', '_fooconvert_demo_content' );       // Meta key for the demo content.
define( 'FOOCONVERT_META_KEY_METRICS', '_fooconvert_metrics' );                 // Meta key for the popup metrics.
define( 'FOOCONVERT_META_KEY_METRIC_VIEWS', '_fooconvert_metric_views' );
define( 'FOOCONVERT_META_KEY_METRIC_ENGAGEMENTS', '_fooconvert_metric_engagements' );
define( 'FOOCONVERT_META_KEY_POPUP_TYPE', '_fooconvert_popup_type' );
define( 'FOOCONVERT_META_KEY_USED_FONTS', '_fooconvert_popup_used_fonts' );

//CPT's
define( 'FOOCONVERT_CPT_BAR', 'fc-bar' );
define( 'FOOCONVERT_CPT_FLYOUT', 'fc-flyout' );
define( 'FOOCONVERT_CPT_POPUP', 'fc-popup' );

// Popup types
define( 'FOOCONVERT_POPUP_TYPE_BAR', 'bar' );
define( 'FOOCONVERT_POPUP_TYPE_FLYOUT', 'flyout' );
define( 'FOOCONVERT_POPUP_TYPE_OVERLAY', 'overlay' );
define( 'FOOCONVERT_POPUP_TYPE_POPUP', 'popup' );

//EVENT TYPE
define( 'FOOCONVERT_EVENT_TYPE_OPEN', 'open' );
define( 'FOOCONVERT_EVENT_TYPE_CLOSE', 'close' );
define( 'FOOCONVERT_EVENT_TYPE_CLICK', 'click' );
define( 'FOOCONVERT_EVENT_TYPE_UPDATE', 'update' );
define( 'FOOCONVERT_EVENT_TYPE_CONVERSION', 'conversion' );
define( 'FOOCONVERT_EVENT_TYPE_SALE', 'sale' );
define( 'FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT', 'engagement' );

//other
define( 'FOOCONVERT_DB_TABLE_EVENTS', 'fooconvert_events' );
define( 'FOOCONVERT_EDITOR_ASSET_HANDLE', 'fc-editor' );
define( 'FOOCONVERT_FRONTEND_ASSET_HANDLE', 'fc-frontend' );
define( 'FOOCONVERT_MENU_SLUG', 'fooconvert' );
define( 'FOOCONVERT_MENU_SLUG_DASHBOARD', 'fooconvert-dashboard' );
define( 'FOOCONVERT_MENU_SLUG_POPUP_STATS', 'fooconvert-popup-stats' );
define( 'FOOCONVERT_MENU_SLUG_POPUP_CHOOSER', 'fooconvert-popup-chooser' );
define( 'FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER', 'fooconvert-ai-popup-builder' );
define( 'FOOCONVERT_RETENTION_DEFAULT', 14 );
define( 'FOOCONVERT_METRICS_DAYS_DEFAULT', 7 );

// AI options and metadata.
define( 'FOOCONVERT_OPTION_AI_BRAND', 'fooconvert_ai_brand' );
define( 'FOOCONVERT_OPTION_BRAND_CONTEXT', FOOCONVERT_OPTION_AI_BRAND );
define( 'FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES', 'fooconvert_ai_popup_builder_debug_responses' );
define( 'FOOCONVERT_META_KEY_AI_BUILDER_METADATA', '_fooconvert_ai_builder_metadata' );
define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL', 'ai_popup_builder_override_model' );
define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS', 'ai_popup_builder_disabled_params' );
define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_TIMEOUT', 'ai_popup_builder_timeout' );
define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_MAX_TOOL_CALLS', 'ai_popup_builder_max_tool_calls' );
define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_SELECTED_BLOCKS', 'ai_popup_builder_selected_blocks' );
define( 'FOOCONVERT_AI_POPUP_BUILDER_TIMEOUT_DEFAULT', 45 );
define( 'FOOCONVERT_AI_POPUP_BUILDER_MAX_TOOL_CALLS_DEFAULT', 10 );

//CRON
define( 'FOOCONVERT_CRON_CALC_STATS', 'fooconvert_calculate_popup_stats' );
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
