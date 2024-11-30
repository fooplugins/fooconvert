<?php

namespace FooPlugins\FooConvert\Data;

use WP_Error;
use wpdb;

/**
 * FooConvert Data Query Class
 * Performs all queries for the database for the plugin.
 */

if ( !class_exists( 'FooPlugins\FooConvert\Data\Query' ) ) {

    class Query extends Base
    {

        private function __construct() {
            // Prevent instantiation.
        }

        /**
         * Returns the events table name.
         *
         * @return string
         */
        private static function get_events_table_name() {
            return parent::get_table_name( FOOCONVERT_DB_TABLE_EVENTS );
        }

        /**
         * Inserts event data into the database.
         *
         * @param array $data {
         *     An array of event data.
         *
         *     @type int    $widget_id        The ID of the widget.
         *     @type string $event_type       The type of event (e.g. 'view', 'click', 'conversion', 'dismiss').
         *     @type string $page_url         The URL of the page where the event occurred.
         *     @type string $device_type      The type of device (e.g. 'desktop', 'mobile', 'tablet').
         *     @type int|null $user_id        The ID of the user (if logged in).
         *     @type string|null $anonymous_user_guid The GUID of the anonymous user.
         *     @type array|null $extra_data   An array or extra event data.
         *     @type string $timestamp        The timestamp of the event.
         * }
         *
         * @return int|WP_Error The ID of the inserted event, or a WP_Error object on failure.
         */
        public static function insert_event_data($data) {
            global $wpdb;

            // Validation rules and sanitization

            if ( !is_array( $data ) || empty( $data ) ) {
                return new WP_Error('invalid_event_data', 'The event data is not valid.');
            }

            // 1. Validate widget_id (required and should be a positive integer)
            if ( !isset( $data['widget_id'] ) || !is_int( $data['widget_id'] ) || $data['widget_id'] <= 0 ) {
                return new WP_Error('invalid_event_data_widget_id', 'The widget ID must be a positive integer.');
            }

            // 2. Validate event_type (required and should be a string)
            if ( !isset( $data['event_type'] ) || !is_string( $data['event_type'] ) ) {
                return new WP_Error('invalid_event_data_event_type', 'The event type is not valid.');
            }

            // 3. Validate page_url if provided (should be a string)
            if ( isset($data['page_url'] ) && !is_string( $data['page_url'] ) ) {
                return new WP_Error('invalid_event_data_page_url', 'The page URL is not a valid URL.');
            }

            // 4. Validate device_type (should be one of the allowed device types)
            $allowed_device_types = ['desktop', 'mobile', 'tablet', 'unknown'];
            if ( !isset( $data['device_type'] ) || !in_array( $data['device_type'], $allowed_device_types, true ) ) {
                return new WP_Error('invalid_event_data_device_type', 'The device type is not valid.');
            }

            // 5. Validate user_id if provided (should be a positive integer or null)
            if ( isset( $data['user_id'] ) && ( !is_int($data['user_id'] ) || $data['user_id'] <= 0 ) ) {
                return new WP_Error('invalid_event_data_user_id', 'The user ID must be a positive integer or null.');
            }

            // 6. Validate we have either a user_id or anonymous_user_guid
            if ( !isset( $data['user_id'] ) && !isset( $data['anonymous_user_guid'] ) ) {
                return new WP_Error('invalid_event_data_no_user', 'No user ID or anonymous user GUID was provided.');
            }

            // 7. Serialize extra_data if provided.
            if ( isset( $data['extra_data'] ) && is_array( $data['extra_data'] ) && !empty( $data['extra_data'] ) ) {
                $data['extra_data'] = maybe_serialize( $data['extra_data'] );
            } else {
                $data['extra_data'] = null;
            }

            // 8. Ensure timestamp is set.
            if ( !isset( $data['timestamp'] ) ) {
                $data['timestamp'] = current_time( 'mysql', true );
            }

            $table_name = self::get_events_table_name();

            // Insert the data into the database
            $result = $wpdb->insert( $table_name, $data );

            if ( $result === false ) {
                return new WP_Error('database_error', 'Error inserting data into ' . $table_name . ': ' . $wpdb->last_error);
            }

            return $wpdb->insert_id;
        }

        /**
         * Retrieves a summary of the events for the given widget.
         *
         * @param int $widget_id The ID of the widget.
         *
         * @return array {
         *     An array of summary data.
         *
         *     @type int $total_events The total number of events.
         *     @type int $total_views The total number of views.
         *     @type int $total_clicks The total number of clicks.
         *     @type int $total_unique_visitors The total number of unique visitors.
         * }
         */
        public static function get_widget_summary_data( $widget_id ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $widget_id = intval($widget_id);

            // Prepare SQL query to return high-level statistics
            return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT 
                    COUNT(*) as total_events,
                    COUNT(CASE WHEN event_type = 'open' THEN 1 END) as total_views,
                    COUNT(CASE WHEN event_type = 'close' THEN 1 END) as total_dismiss,
                    COUNT(CASE WHEN event_type = 'click' THEN 1 END) as total_clicks,
                    COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as total_conversions,
                    COUNT(CASE WHEN event_subtype = 'engagement' THEN 1 END) as total_engagements,
                    COUNT(CASE WHEN sentiment = 1 THEN 1 END) as total_positive_sentiment,
                    COUNT(CASE WHEN sentiment = 0 THEN 1 END) as total_negative_sentiment,
                    COUNT(DISTINCT COALESCE(user_id, anonymous_user_guid)) as total_unique_visitors
                    FROM {$table_name}
                    WHERE widget_id = %d",
                    $widget_id
                ),
                ARRAY_A
            );
        }

        /**
         * Returns an array of recent activity for the given widget.
         *
         * @param int $widget_id The ID of the widget.
         * @param int $days The number of days to fetch (default is 7).
         *
         * @return array An array of recent activity, with the following structure:
         *     'event_date' => string The date of the event (format: 'Y-m-d')
         *     'views' => int The number of views
         *     'clicks' => int The number of clicks
         *     'unique_visitors' => int The number of unique visitors
         */
        public static function get_widget_daily_activity( $widget_id, $days = 7 ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $widget_id = intval( $widget_id ); // Ensure $widget_id is an integer
            $days = intval( $days );  // Ensure $days is an integer

            // Prepare recent activity for the last 7 days
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT 
                    DATE(timestamp) as event_date,
                    COUNT(CASE WHEN event_type = 'view' THEN 1 END) as views,
                    COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
                    COUNT(DISTINCT COALESCE(user_id, anonymous_user_guid)) as unique_visitors
                    FROM {$table_name}
                    WHERE widget_id = %d AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
                    GROUP BY event_date
                    ORDER BY event_date ASC",
                    $widget_id,
                    $days
                ),
                ARRAY_A
            );
        }

        /**
         * Deletes all events for a given widget ID.
         *
         * @param int $widget_id The ID of the widget to delete events for.
         *
         * @return int The number of rows deleted.
         */
        public static function delete_widget_events( $widget_id ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $widget_id = intval( $widget_id ); // Ensure $widget_id is an integer

            return $wpdb->delete( $table_name, array( 'widget_id' => $widget_id ) );
        }

        /**
         * Deletes all events from the database.
         *
         * @return int The number of rows deleted.
         */
        public static function delete_all_events() {
            global $wpdb;

            $table_name = self::get_events_table_name();

            return $wpdb->query( "DELETE FROM {$table_name}" );
        }

        /**
         * Retrieves stats we care about for the events table.
         *
         * @return array An array of data about the events table, with the following keys:
         *     'Table' => string The name of the table.
         *     'Size_in_MB' => float The size of the table in megabytes.
         *     'Number_of_Rows' => int The number of rows in the table.
         *     'Unique_Widgets' => int The number of unique widgets represented in the table.
         *     'Orphaned_Events' => int The number of events that are not associated with a widget in the posts table.
         *     'Unique_Orphaned_Widgets' => int The number of unique widgets that are not associated with a widget in the posts table.
         */
        public static function get_events_table_stats() {
            global $wpdb;

            $schema_name = DB_NAME; // The current database name

            $table_name = self::get_events_table_name();

            $wpdb->query( "ANALYZE TABLE {$table_name};" );

            // Prepare the SQL query safely.
            $query = $wpdb->prepare( "SELECT
                    table_name AS `Table`,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `Size_in_MB`,
                    (
                        SELECT COUNT(*)
                        FROM {$table_name}
                    ) AS `Number_of_Rows`,                    
                    (
                        SELECT COUNT(DISTINCT widget_id)
                        FROM {$table_name}
                    ) AS `Unique_Widgets`,
                    (
                        SELECT COUNT(*)
                        FROM {$table_name} e
                        LEFT JOIN {$wpdb->prefix}posts p ON e.widget_id = p.ID
                        WHERE p.ID IS NULL
                    ) AS `Orphaned_Events`,
                    (
                        SELECT COUNT(DISTINCT e.widget_id)
                        FROM {$table_name} e
                        LEFT JOIN {$wpdb->prefix}posts p ON e.widget_id = p.ID
                        WHERE p.ID IS NULL
                    ) AS `Unique_Orphaned_Widgets`
                FROM
                    information_schema.TABLES
                WHERE
                    table_schema = %s
                    AND table_name = %s",
                $schema_name,
                $table_name
            );

            // Execute the query and get the result
            return $wpdb->get_row( $query, ARRAY_A );
        }

        /**
         * Deletes all events that are not associated with a widget in the posts table.
         *
         * @return int The number of events deleted.
         */
        public static function delete_orphaned_events() {
            global $wpdb;

            // Table names
            $events_table = self::get_events_table_name();
            $posts_table = $wpdb->prefix . 'posts';

            // SQL query
            $query = $wpdb->prepare( "
                DELETE e
                FROM {$events_table} e
                LEFT JOIN {$posts_table} p ON e.widget_id = p.ID
                WHERE p.ID IS NULL"
            );

            // Execute the query and return number of rows deleted.
            return $wpdb->query( $query );
        }
    }
}