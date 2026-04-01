<?php

namespace FooPlugins\FooConvert\Data;

use WP_Error;
use wpdb;

/**
 * FooConvert Data Query Class
 * Performs all queries for the database for the plugin.
 */

if ( !class_exists( 'FooPlugins\FooConvert\Data\Query' ) ) {

    class Query extends Base {

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

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        /**
         * Inserts event data into the database.
         *
         * @param array $data {
         *     An array of event data.
         *
         * @type int $widget_id The ID of the widget.
         * @type string $event_type The type of event (e.g. 'view', 'click', 'conversion', 'dismiss').
         * @type string|null $event_subtype The subtype of event (if applicable).
         * @type bool|null $conversion Whether the event is a conversion (true) or not (false).
         * @type bool|null $sentiment Whether the event sentiment is positive (true) or negative (false) or null (neutral).
         * @type string|null $page_url The URL of the page where the event occurred.
         * @type string|null $device_type The type of device (e.g. 'desktop', 'mobile', 'tablet').
         * @type int|null $user_id The ID of the user (if logged in).
         * @type string|null $session_id The ID of the browser session.
         * @type string|null $anonymous_user_guid The GUID of the anonymous user.
         * @type array|null $extra_data An array or extra event data.
         * @type float|null $event_value Optional numeric value associated with the event.
         * @type string $timestamp The timestamp of the event.
         * }
         *
         * @return int|WP_Error The ID of the inserted event, or a WP_Error object on failure.
         */
        public static function insert_event_data( $data ) {
            global $wpdb;

            // Validation rules and sanitization

            if ( !is_array( $data ) || empty( $data ) ) {
                return new WP_Error( 'invalid_event_data', 'The event data is not valid.' );
            }

            // 1. Validate widget_id (required and should be a positive integer)
            if ( !isset( $data['widget_id'] ) || !is_int( $data['widget_id'] ) || $data['widget_id'] <= 0 ) {
                return new WP_Error( 'invalid_event_data_widget_id', 'The widget ID must be a positive integer.' );
            }

            // 2. Validate event_type (required and should be a string)
            if ( !isset( $data['event_type'] ) || !is_string( $data['event_type'] ) ) {
                return new WP_Error( 'invalid_event_data_event_type', 'The event type is not valid.' );
            }

            // 3. Validate page_url if provided (should be a string)
            if ( isset( $data['page_url'] ) && !is_string( $data['page_url'] ) ) {
                return new WP_Error( 'invalid_event_data_page_url', 'The page URL is not a valid URL.' );
            }

            // 4. Validate device_type (should be one of the allowed device types)
            $allowed_device_types = [ 'desktop', 'mobile', 'tablet', 'unknown' ];
            if ( isset( $data['device_type'] ) && !in_array( $data['device_type'], $allowed_device_types, true ) ) {
                return new WP_Error( 'invalid_event_data_device_type', 'The device type is not valid.' );
            }

            // 5. Validate user_id if provided (should be a positive integer or null)
            if ( isset( $data['user_id'] ) && ( !is_int( $data['user_id'] ) || $data['user_id'] <= 0 ) ) {
                return new WP_Error( 'invalid_event_data_user_id', 'The user ID must be a positive integer or null.' );
            }

            // 6. Validate session_id if provided (should be a string)
            if ( isset( $data['session_id'] ) && !is_string( $data['session_id'] ) ) {
                return new WP_Error( 'invalid_event_data_session_id', 'The session ID is not valid.' );
            }

            // 7. Validate we have either a user_id or anonymous_user_guid
            if ( !isset( $data['user_id'] ) && !isset( $data['anonymous_user_guid'] ) ) {
                return new WP_Error( 'invalid_event_data_no_user', 'No user ID or anonymous user GUID was provided.' );
            }

            // 8. Validate event_value if provided.
            if ( isset( $data['event_value'] ) && null !== $data['event_value'] ) {
                if ( !is_numeric( $data['event_value'] ) ) {
                    return new WP_Error( 'invalid_event_data_event_value', 'The event value is not valid.' );
                }

                $data['event_value'] = round( (float) $data['event_value'], 4 );
            }

            // 9. Serialize extra_data if provided.
            if ( isset( $data['extra_data'] ) && is_array( $data['extra_data'] ) && !empty( $data['extra_data'] ) ) {
                $data['extra_data'] = maybe_serialize( $data['extra_data'] );
            } else {
                $data['extra_data'] = null;
            }

            // 10. Ensure timestamp is set.
            if ( !isset( $data['timestamp'] ) ) {
                $data['timestamp'] = current_time( 'mysql', true );
            }

            $table_name = self::get_events_table_name();

            // Insert the data into the database
            $result = $wpdb->insert( $table_name, $data );

            if ( $result === false ) {
                return new WP_Error( 'database_error', 'Error inserting data into ' . $table_name . ': ' . $wpdb->last_error );
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
         * @type int $total_events The total number of events.
         * @type int $total_views The total number of views.
         * @type int $total_clicks The total number of clicks.
         * @type int $total_unique_visitors The total number of unique visitors.
         * @type int $total_unique_sessions The total number of unique sessions.
         * @type int $total_returning_visitors The total number of visitors with two or more sessions.
         * }
         */
        public static function get_widget_metrics( $widget_id, $days = FOOCONVERT_METRICS_DAYS_DEFAULT ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $widget_id  = intval( $widget_id );
            $days       = intval( $days );
            $days_clause = $days > 0 ? ' AND timestamp >= NOW() - INTERVAL %d DAY' : '';

            $query = apply_filters(
                'fooconvert_get_widget_metrics_query',
                "SELECT 
                    metrics.total_events,
                    metrics.total_views,
                    metrics.total_engagements,
                    metrics.total_unique_visitors,
                    metrics.total_unique_sessions,
                    IFNULL(returning_visitors.total_returning_visitors, 0) as total_returning_visitors
                    FROM (
                        SELECT
                            COUNT(CASE WHEN event_type != 'update' THEN 1 END) as total_events,
                            COUNT(CASE WHEN event_type = 'open' THEN 1 END) as total_views,
                            COUNT(CASE WHEN event_subtype = 'engagement' THEN 1 END) as total_engagements,
                            COUNT(DISTINCT COALESCE(user_id, anonymous_user_guid)) as total_unique_visitors,
                            COUNT(DISTINCT CASE WHEN event_type != 'update' AND session_id IS NOT NULL THEN session_id END) as total_unique_sessions
                        FROM {$table_name}
                        WHERE widget_id = %d{$days_clause}
                    ) metrics
                    LEFT JOIN (
                        SELECT COUNT(*) as total_returning_visitors
                        FROM (
                            SELECT
                                CASE
                                    WHEN user_id IS NOT NULL THEN CONCAT('u:', user_id)
                                    WHEN anonymous_user_guid IS NOT NULL THEN CONCAT('a:', anonymous_user_guid)
                                    ELSE NULL
                                END as visitor_key
                            FROM {$table_name}
                            WHERE widget_id = %d
                                AND event_type != 'update'
                                AND session_id IS NOT NULL
                                AND ( user_id IS NOT NULL OR anonymous_user_guid IS NOT NULL ){$days_clause}
                            GROUP BY visitor_key
                            HAVING COUNT(DISTINCT session_id) >= 2
                        ) returning_visitor_groups
                    ) returning_visitors ON 1 = 1",
                $table_name,
                $days_clause
            );

            $query_args = [ $widget_id ];
            if ( $days > 0 ) {
                $query_args[] = $days;
            }
            $query_args[] = $widget_id;
            if ( $days > 0 ) {
                $query_args[] = $days;
            }

            // Prepare SQL query to return high-level statistics
            return $wpdb->get_row(
                $wpdb->prepare( $query, $query_args ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
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
        public static function get_widget_daily_activity( $widget_id, $days = FOOCONVERT_METRICS_DAYS_DEFAULT ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $widget_id = intval( $widget_id ); // Ensure $widget_id is an integer
            $days = intval( $days );           // Ensure $days is an integer

            $query = apply_filters( 'fooconvert_get_widget_daily_activity_query', "SELECT 
                    DATE(timestamp) as event_date,
                    COUNT(CASE WHEN event_type != 'update' THEN 1 END) as events,
                    COUNT(CASE WHEN event_type = 'open' THEN 1 END) as views,
                    COUNT(DISTINCT COALESCE(user_id, anonymous_user_guid)) as unique_visitors,
                    COUNT(CASE WHEN event_subtype = 'engagement' THEN 1 END) as engagements
                    FROM {$table_name}
                    WHERE widget_id = %d", $table_name );

            //Add a filter by days.
            if ( $days > 0 ) {
                $query .= " AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)";
            }

            $query .= ' GROUP BY event_date ORDER BY event_date ASC';
            $query_args = [ $widget_id ];
            if ( $days > 0 ) {
                $query_args[] = $days;
            }

            // Prepare recent activity for the last X days
            return $wpdb->get_results(
                $wpdb->prepare( $query, $query_args ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
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

            return $wpdb->query( "DELETE FROM {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }

        /**
         * Deletes all events older than the specified number of days.
         *
         * @param int $days The number of days to keep events for. Defaults to FOOCONVERT_RETENTION_DEFAULT which is 14.
         *
         * @return int The number of rows deleted.
         */
        public static function delete_old_events( $days = FOOCONVERT_RETENTION_DEFAULT ) {
            global $wpdb;

            $table_name = esc_sql( self::get_events_table_name() );
            $days = intval( $days );  // Ensure $days is an integer

            if ( $days <= 0 ) {
                return 0;
            }

            return $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$table_name} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $days
                )
            );
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

            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

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

            // phpcs:enable

            // Execute the query and get the result
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
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
            $events_table = esc_sql( self::get_events_table_name() );
            $posts_table = esc_sql( $wpdb->prefix . 'posts' );

            // Build the query with sanitized table names
            $query = "
                DELETE e
                FROM {$events_table} e
                LEFT JOIN {$posts_table} p ON e.widget_id = p.ID
                WHERE p.ID IS NULL
            ";

            // Execute the query and return number of rows deleted.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            return $wpdb->query( $query );
        }

        /**
         * Retrieves a list of all widgets that have at least one event.
         *
         * @return array An array of widget IDs (int) that have at least one event.
         */
        public static function get_widgets_with_events() {
            global $wpdb;

            $table_name = esc_sql( self::get_events_table_name() );
            $posts_table = esc_sql( $wpdb->prefix . 'posts' );

            $query = "
                SELECT e.widget_id
                FROM {$table_name} e
                INNER JOIN {$posts_table} p ON e.widget_id = p.ID
                GROUP BY e.widget_id
            ";

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            return $wpdb->get_results( $query, ARRAY_A );
        }

        /**
         * Retrieves a list of all widgets that have no events.
         *
         * @return array An array of widget IDs (int) that have zero events.
         */
        public static function get_widgets_with_no_events() {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $posts_table = $wpdb->prefix . 'posts';

            $post_types = fooconvert_get_post_types();

            // Build the base query with placeholders dynamically
            $placeholders = array_fill( 0, count( $post_types ), '%s' );
            $placeholders_string = implode( ', ', $placeholders );

            $query = "SELECT p.ID AS widget_id
                FROM %i p
                LEFT JOIN %i e ON p.ID = e.widget_id
                WHERE p.post_type IN ($placeholders_string)
                  AND e.widget_id IS NULL";

            // Combine post types into the prepared query
            $prepared_query = $wpdb->prepare(
                $query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $posts_table,
                $table_name,
                ...$post_types
            );

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            return $wpdb->get_results( $prepared_query, ARRAY_A );
        }

        /**
         * Retrieves a list of all events of a given type for a given widget.
         *
         * @param int $widget_id The ID of the widget to get events for.
         * @param string $event_type The type of events to get.
         * @param int $days The number of days to fetch events for. Defaults to FOOCONVERT_RETENTION_DEFAULT which is 14.
         *
         * @return array An array of events for the given widget and event type, with the following structure:
         *     'id' => int The ID of the event.
         *     'widget_id' => int The ID of the widget.
         *     'event_type' => string The type of event.
         *     'event_subtype' => string The subtype of event (if applicable).
         *     'conversion' => bool Whether the event is a conversion (true) or not (false).
         *     'sentiment' => bool Whether the event sentiment is positive (true) or negative (false).
         *     'page_url' => string The URL of the page where the event occurred.
         *     'device_type' => string The type of device (e.g. 'desktop', 'mobile', 'tablet').
         *     'user_id' => int The ID of the user who triggered the event (if applicable).
         *     'anonymous_user_guid' => string The GUID of the anonymous user who triggered the event (if applicable).
         *     'extra_data' => array An array of extra data associated with the event.
         *     'timestamp' => string The date of the event (format: 'Y-m-d')
         */
        public static function get_widget_events_of_type( $widget_id, $event_type, $days = FOOCONVERT_RETENTION_DEFAULT ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $widget_id = intval( $widget_id ); // Ensure $widget_id is an integer
            $days = intval( $days );           // Ensure $days is an integer

            $query = "SELECT *, DATE(timestamp) as event_date
                    FROM {$table_name}
                    WHERE widget_id = %d AND event_type = %s AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)
                    ORDER BY timestamp ASC";

            // Prepare event data for the last X days
            return $wpdb->get_results(
                $wpdb->prepare(
                    $query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $widget_id,
                    $event_type,
                    $days
                ),
                ARRAY_A
            );
        }

        /**
         * Retrieves a summary of all events for all widgets.
         *
         * This function returns an array of arrays, where each sub-array contains the
         * following metrics for a single widget:
         *     'widget_id' => int The ID of the widget.
         *     'total_events' => int The total number of events.
         *     'total_views' => int The total number of views.
         *     'total_engagements' => int The total number of engagements.
         *     'total_unique_visitors' => int The total number of unique visitors.
         *     'total_unique_sessions' => int The total number of unique sessions.
         *     'total_returning_visitors' => int The total number of visitors with two or more sessions.
         *
         * @return array[] An array of arrays, each containing the metrics for a single widget.
         */
        public static function get_all_widget_metrics() {
            global $wpdb;

            $table_name = self::get_events_table_name();

            $query = apply_filters( 'fooconvert_get_all_widget_metrics_query', "SELECT
                    widget_totals.widget_id,
                    widget_totals.total_events,
                    widget_totals.total_views,
                    widget_totals.total_engagements,
                    widget_totals.total_unique_visitors,
                    widget_totals.total_unique_sessions,
                    IFNULL(returning_visitors.total_returning_visitors, 0) as total_returning_visitors
                    FROM (
                        SELECT
                            widget_id,
                            COUNT(CASE WHEN event_type != 'update' THEN 1 END) as total_events,
                            COUNT(CASE WHEN event_type = 'open' THEN 1 END) as total_views,
                            COUNT(CASE WHEN event_subtype = 'engagement' THEN 1 END) as total_engagements,
                            COUNT(DISTINCT COALESCE(user_id, anonymous_user_guid)) as total_unique_visitors,
                            COUNT(DISTINCT CASE WHEN event_type != 'update' AND session_id IS NOT NULL THEN session_id END) as total_unique_sessions
                        FROM {$table_name}
                        GROUP BY widget_id
                    ) widget_totals
                    LEFT JOIN (
                        SELECT
                            widget_id,
                            COUNT(*) as total_returning_visitors
                        FROM (
                            SELECT
                                widget_id,
                                CASE
                                    WHEN user_id IS NOT NULL THEN CONCAT('u:', user_id)
                                    WHEN anonymous_user_guid IS NOT NULL THEN CONCAT('a:', anonymous_user_guid)
                                    ELSE NULL
                                END as visitor_key
                            FROM {$table_name}
                            WHERE event_type != 'update'
                                AND session_id IS NOT NULL
                                AND ( user_id IS NOT NULL OR anonymous_user_guid IS NOT NULL )
                            GROUP BY widget_id, visitor_key
                            HAVING COUNT(DISTINCT session_id) >= 2
                        ) returning_visitor_groups
                        GROUP BY widget_id
                    ) returning_visitors
                    ON widget_totals.widget_id = returning_visitors.widget_id", $table_name );

            // Prepare SQL query to return high-level statistics
            return $wpdb->get_results(
                $query, // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                ARRAY_A
            );
        }

        /**
         * Returns the latest qualifying attribution event before the supplied cutoff.
         *
         * @param array $criteria {
         *     Optional query criteria.
         *
         * @type int|null $user_id Logged-in user ID to match.
         * @type string|null $anonymous_user_guid Anonymous visitor GUID to match.
         * @type string|null $session_id Session ID to match.
         * @type string|null $cutoff_gmt Upper timestamp bound in GMT.
         * @type int|null $lookback_days Lookback window in days.
         * }
         *
         * @return array|null
         */
        public static function get_latest_qualifying_event( $criteria = array() ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $user_id = isset( $criteria['user_id'] ) ? intval( $criteria['user_id'] ) : 0;
            $anonymous_user_guid = isset( $criteria['anonymous_user_guid'] ) ? sanitize_text_field( $criteria['anonymous_user_guid'] ) : '';
            $session_id = isset( $criteria['session_id'] ) ? sanitize_text_field( $criteria['session_id'] ) : '';
            $cutoff_gmt = isset( $criteria['cutoff_gmt'] ) ? sanitize_text_field( $criteria['cutoff_gmt'] ) : current_time( 'mysql', true );
            $lookback_days = isset( $criteria['lookback_days'] ) ? max( 1, intval( $criteria['lookback_days'] ) ) : FOOCONVERT_METRICS_DAYS_DEFAULT;

            $where = array(
                "event_type IN ('" . esc_sql( FOOCONVERT_EVENT_TYPE_CLICK ) . "', '" . esc_sql( FOOCONVERT_EVENT_TYPE_CONVERSION ) . "')",
                'sentiment = 1',
                'timestamp <= %s',
                'timestamp >= DATE_SUB(%s, INTERVAL %d DAY)',
            );
            $args = array( $cutoff_gmt, $cutoff_gmt, $lookback_days );

            if ( $user_id > 0 ) {
                $where[] = 'user_id = %d';
                $args[] = $user_id;
            } elseif ( $anonymous_user_guid !== '' ) {
                $where[] = 'anonymous_user_guid = %s';
                $args[] = $anonymous_user_guid;
            } else {
                return null;
            }

            if ( $session_id !== '' ) {
                $where[] = 'session_id = %s';
                $args[] = $session_id;
            }

            $query = "SELECT *
                    FROM {$table_name}
                    WHERE " . implode( ' AND ', $where ) . '
                    ORDER BY timestamp DESC, id DESC
                    LIMIT 1';

            return $wpdb->get_row(
                $wpdb->prepare( $query, $args ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                ARRAY_A
            );
        }

        /**
         * Checks if a sale event already exists for the supplied dedupe scope.
         *
         * @param string $dedupe_mode Dedupe mode.
         * @param int $widget_id Widget ID.
         * @param string|null $session_id Session ID.
         * @return bool
         */
        public static function sale_exists_for_scope( $dedupe_mode, $widget_id, $session_id = null ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $widget_id = intval( $widget_id );
            $session_id = is_string( $session_id ) ? sanitize_text_field( $session_id ) : '';

            if ( $session_id === '' ) {
                return false;
            }

            $query = "SELECT id
                    FROM {$table_name}
                    WHERE event_type = %s
                      AND session_id = %s";
            $args = array( FOOCONVERT_EVENT_TYPE_SALE, $session_id );

            if ( $dedupe_mode === FOOCONVERT_SALE_DEDUPE_MODE_WIDGET_SESSION ) {
                $query .= ' AND widget_id = %d';
                $args[] = $widget_id;
            }

            $query .= ' LIMIT 1';

            $result = $wpdb->get_var(
                $wpdb->prepare( $query, $args ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            );

            return !empty( $result );
        }

        /**
         * Retrieves sale events for a widget.
         *
         * @param int $widget_id Widget ID.
         * @param int $days Lookback in days. Values less than 1 mean all time.
         * @return array<int,array<string,mixed>>
         */
        public static function get_widget_sales( $widget_id, $days = FOOCONVERT_METRICS_DAYS_DEFAULT ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $widget_id = intval( $widget_id );
            $days = intval( $days );

            $query = "SELECT *
                    FROM {$table_name}
                    WHERE widget_id = %d
                      AND event_type = %s";
            $args = array( $widget_id, FOOCONVERT_EVENT_TYPE_SALE );

            if ( $days > 0 ) {
                $query .= ' AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)';
                $args[] = $days;
            }

            $query .= ' ORDER BY timestamp DESC, id DESC';

            return $wpdb->get_results(
                $wpdb->prepare( $query, $args ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                ARRAY_A
            );
        }

        /**
         * Retrieves the most recent sale events globally.
         *
         * @param int $limit Max number of sale events to return.
         * @return array<int,array<string,mixed>>
         */
        public static function get_recent_sales( $limit = 10 ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $limit = max( 1, intval( $limit ) );

            $query = $wpdb->prepare(
                "SELECT *
                FROM {$table_name}
                WHERE event_type = %s
                ORDER BY timestamp DESC, id DESC
                LIMIT %d",
                FOOCONVERT_EVENT_TYPE_SALE,
                $limit
            );

            return $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        /**
         * Retrieves revenue totals grouped by widget.
         *
         * @param int $limit Max number of widgets to return.
         * @return array<int,array<string,mixed>>
         */
        public static function get_sales_totals_by_widget( $limit = 10 ) {
            global $wpdb;

            $table_name = self::get_events_table_name();
            $limit = max( 1, intval( $limit ) );

            $query = $wpdb->prepare(
                "SELECT widget_id, COUNT(*) as sale_count, COALESCE(SUM(event_value), 0) as total_sales
                FROM {$table_name}
                WHERE event_type = %s
                GROUP BY widget_id
                ORDER BY total_sales DESC, sale_count DESC
                LIMIT %d",
                FOOCONVERT_EVENT_TYPE_SALE,
                $limit
            );

            return $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        // phpcs:enable
    }
}
