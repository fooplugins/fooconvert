<?php

namespace FooPlugins\FooConvert\Data;

use wpdb;

/**
 * FooConvert Data Schema Class
 * Creates the database tables and indexes for the plugin.
 */

if ( !class_exists( 'FooPlugins\FooConvert\Data\Schema' ) ) {

    /**
     * Class Schema.
     */
    class Schema extends Base {
        const FOOCONVERT_TABLE = 'fooconvert_events';
        const FOOCONVERT_LEADS_TABLE = 'fooconvert_leads';

        /**
         * Create or upgrade the plugin tables when the stored schema version is out of date.
         *
         * @param string|null $target_version Target plugin version to migrate to.
         * @return array<array-key, mixed>|false Table creation results, or false.
         */
        public function create_event_table_if_needed( ?string $target_version = null ) {
            static $results = array();

            $current_version = is_string( $target_version ) && '' !== trim( $target_version )
                ? trim( $target_version )
                : ( defined( 'FOOCONVERT_VERSION' ) ? FOOCONVERT_VERSION : null );

            $cache_key = is_string( $current_version ) && '' !== $current_version ? $current_version : '__undefined__';
            if ( array_key_exists( $cache_key, $results ) ) {
                return $results[ $cache_key ];
            }

            if ( !is_string( $current_version ) || '' === $current_version ) {
                $results[ $cache_key ] = false;
                return $results[ $cache_key ];
            }

            $version_create_table = (string) get_option( FOOCONVERT_OPTION_VERSION_CREATE_TABLE, '0.0.0' );

            if ( $version_create_table === $current_version || version_compare( $current_version, $version_create_table, '<=' ) ) {
                $results[ $cache_key ] = false;
                return $results[ $cache_key ];
            }

            // Create the tables.
            $table_creation_results = $this->create_event_table_and_indexes();
            $leads_creation_results = $this->create_leads_table_and_indexes();

            // TODO : Run any necessary migrations.

            // Keep this option autoloaded because it is checked on every request.
            update_option( FOOCONVERT_OPTION_VERSION_CREATE_TABLE, $current_version, true );

            $results[ $cache_key ] = [
                'events' => $table_creation_results,
                'leads' => $leads_creation_results
            ];

            return $results[ $cache_key ];
        }

        /**
         * @return array<array-key, mixed> dbDelta result
         * @global wpdb $wpdb The WordPress database class instance.
         *
         */
        public function create_event_table_and_indexes() {
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();
            $table_name = parent::get_table_name( FOOCONVERT_DB_TABLE_EVENTS );
            $timestamp_default = parent::get_timestamp_default();

            /**
             * This is the Event table schema.
             *  - id is the primary key
             *  - widget_id is the id of the widget that created the event.
             *  - event_type is the type of event. This can be one of: 'open', 'click', 'close', 'update'.
             *  - event_subtype is the subtype of the event. This can be one of: 'engagement'.
             *  - conversion is a boolean that is true if the event is a conversion.
             *  - sentiment is a boolean that is true if the event sentiment is positive.
             *  - page_url is the url of the page that created the event
             *  - device_type is the type of device that was used for the event
             *  - user_id who was the user when the event happened. Will be null if not logged in.
             *  - session_id identifies the current browser session for this visitor. Will be null for server-side events.
             *  - anonymous_user_guid the unique id of an anonymous user from the frontend. Will be null if logged in.
             *  - event_value stores the optional numeric value associated with the event, such as WooCommerce sale revenue.
             *  - extra_data is all the extra data associated with the event.
             *  - timestamp is when the event happened
             */

            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                widget_id bigint(20) unsigned NOT NULL,
                event_type varchar(255) NOT NULL,
                event_subtype varchar(255) DEFAULT NULL,
                conversion tinyint(1) DEFAULT NULL,
                sentiment tinyint(1) DEFAULT NULL,
                page_url text DEFAULT NULL,
                device_type varchar(50) DEFAULT NULL,
                session_id varchar(255) DEFAULT NULL,
                anonymous_user_guid varchar(255) DEFAULT NULL,
                user_id bigint(20) unsigned DEFAULT NULL,
                event_value decimal(19,4) DEFAULT NULL,
                extra_data longtext DEFAULT NULL,
                timestamp datetime DEFAULT $timestamp_default,
                PRIMARY KEY (id)
            ) $charset_collate;";

            $db_delta_result = parent::safe_dbDelta( $sql );

            // Log the results of the table creation.
            $this->log_table_creation_results( $db_delta_result, $sql, $table_name );

            // Create all the indexes we need.
            parent::safe_create_index( $table_name, 'idx_widget', 'widget_id' ); // We need to query all events for a widget.

            /*
             * Purpose : many queries use widget_id and filter by event_type (e.g., counting views, conversions, dismissals).
             */
            parent::safe_create_index( $table_name, 'idx_widget_event_type', 'widget_id, event_type' );

            /*
             * Purpose : many queries also use widget_id and filter by event_subtype (e.g., counting interactions, bounces).
             */
            parent::safe_create_index( $table_name, 'idx_widget_event_subtype', 'widget_id, event_subtype' );

            /*
             * Purpose : many queries also use widget_id and filter by conversion.
             */
            parent::safe_create_index( $table_name, 'idx_widget_conversion', 'widget_id, conversion' );

            /*
             * Purpose : many queries also use widget_id and filter by sentiment.
             */
            parent::safe_create_index( $table_name, 'idx_widget_sentiment', 'widget_id, sentiment' );

            /*
             * Purpose: This index will be particularly helpful when filtering by both widget_id and timestamp,
             * especially for queries restricted to recent data (e.g., the last 30, 60, or 90 days).
             * This will allow the database to quickly find the events within the specified date range for a particular widget.
             */
            parent::safe_create_index( $table_name, 'idx_widget_timestamp', 'widget_id, timestamp' );

            /*
             * Purpose: This index supports queries that filter by widget_id and event_type while also filtering or ordering by timestamp.
             * This will be useful for metrics that need to count or filter specific event types (like view, click, conversion, and dismiss) within a time range.
             */
            parent::safe_create_index( $table_name, 'idx_widget_event_type_timestamp', 'widget_id, event_type, timestamp' );

            /*
             * Purpose : Session-based analytics need to efficiently group visits by widget and browser session.
             */
            parent::safe_create_index( $table_name, 'idx_widget_session', 'widget_id, session_id(191)' );

            /*
             * Purpose : Attribute WooCommerce sales using the latest qualifying session event before checkout.
             */
            parent::safe_create_index( $table_name, 'idx_session_event_lookup', 'session_id(191), event_type, sentiment, timestamp' );

            /*
             * Purpose : Attribute WooCommerce sales using logged-in customer history.
             */
            parent::safe_create_index( $table_name, 'idx_user_event_lookup', 'user_id, event_type, sentiment, timestamp' );

            /*
             * Purpose : Attribute WooCommerce sales using anonymous visitor history.
             */
            parent::safe_create_index( $table_name, 'idx_anonymous_event_lookup', 'anonymous_user_guid(191), event_type, sentiment, timestamp' );

            /*
             * Purpose : Deduplicate sale events by session or widget/session.
             */
            parent::safe_create_index( $table_name, 'idx_event_type_session', 'event_type, session_id(191)' );

            /*
             * Purpose : Many metrics, such as unique visitors, conversion rate, and dismissal rate, rely on distinct counts of either user_id or anonymous_user_guid for each widget_id.
             */
            parent::safe_create_index( $table_name, 'idx_widget_user', 'widget_id, user_id, anonymous_user_guid' );

            return $db_delta_result;
        }

        /**
         * @return array<array-key, mixed> dbDelta result
         * @global wpdb $wpdb The WordPress database class instance.
         *
         */
        public function create_leads_table_and_indexes() {
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();
            $table_name = parent::get_table_name( self::FOOCONVERT_LEADS_TABLE );
            $timestamp_default = parent::get_timestamp_default();

            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                widget_id bigint(20) unsigned NOT NULL,
                email varchar(255) NOT NULL,
                name varchar(255) DEFAULT NULL,
                metadata longtext DEFAULT NULL,
                page_url text DEFAULT NULL,
                timestamp datetime DEFAULT $timestamp_default,
                PRIMARY KEY (id)
            ) $charset_collate;";

            $db_delta_result = parent::safe_dbDelta( $sql );

            // Log the results of the table creation.
            $this->log_table_creation_results( $db_delta_result, $sql, $table_name );

            // Create indexes
            parent::safe_create_index( $table_name, 'idx_widget', 'widget_id' );
            parent::safe_create_index( $table_name, 'idx_email', 'email' );
            parent::safe_create_index( $table_name, 'idx_page_url', 'page_url(191)' ); // Using prefix length for text column

            return $db_delta_result;
        }

        /**
         * Logs the results of the table creation process.
         *
         * @param array $db_delta_result The results of the table creation process.
         * @param string $sql The SQL statement used to create the table.
         * @param string $table_name The name of the table that we tried to create.
         * @return void
         */
        public function log_table_creation_results( $db_delta_result, $sql, $table_name ) {
            global $wpdb;

            $table_exists = self::does_table_exist( $table_name );

            // If the table does not exist, or we got back from results from the dbDelta, then log the results.
            if ( !$table_exists || !empty( $db_delta_result ) ) {
                $data = [
                    'table_exists' => $table_exists,
                    'sql'          => $sql,
                    'result'       => $db_delta_result,
                    'error'        => $wpdb->last_error,
                    'server'       => $wpdb->db_server_info()
                ];

                update_option( FOOCONVERT_OPTION_DATABASEDATA, $data );
            }
        }

        /**
         * Checks if a table exists in the database.
         *
         * @param string $table_name The name of the table to check.
         * @return bool True if the table exists, false otherwise.
         */
        static function does_table_exist( $table_name ) {
            global $wpdb;

            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $table_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SHOW TABLES LIKE %s",
                    $table_name
                )
            );
            // phpcs:enable

            return $table_exists === $table_name;
        }
    }
}
