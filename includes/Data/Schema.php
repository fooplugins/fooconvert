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
         * @return array{events: array<array-key, mixed>, leads: array<array-key, mixed>}|false Table creation results, or false.
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

            $table_creation_results = $this->create_event_table_and_indexes();
            $leads_creation_results = $this->create_leads_table_and_indexes();

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
        public function create_event_table_and_indexes(): array {
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();
            $table_name = parent::get_table_name( FOOCONVERT_DB_TABLE_EVENTS );
            $timestamp_default = parent::get_timestamp_default();

            /**
             * This is the Event table schema.
             *  - id is the primary key
             *  - post_id is the id of the popup that created the event.
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
                post_id bigint(20) unsigned NOT NULL,
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

            $this->log_table_creation_results( $db_delta_result, $sql, $table_name );

            // Query all events for a popup.
            parent::safe_create_index( $table_name, 'idx_popup', 'post_id' );

            // Count/filter event types per popup, including time-bounded metrics.
            parent::safe_create_index( $table_name, 'idx_popup_event_type', 'post_id, event_type' );

            // Support popup metrics that segment by event subtype.
            parent::safe_create_index( $table_name, 'idx_popup_event_subtype', 'post_id, event_subtype' );

            // Support conversion-rate style queries per popup.
            parent::safe_create_index( $table_name, 'idx_popup_conversion', 'post_id, conversion' );

            // Support positive/negative sentiment rollups per popup.
            parent::safe_create_index( $table_name, 'idx_popup_sentiment', 'post_id, sentiment' );

            // Filter popup events by date range.
            parent::safe_create_index( $table_name, 'idx_popup_timestamp', 'post_id, timestamp' );

            // Filter a popup's event type within a date range.
            parent::safe_create_index( $table_name, 'idx_popup_event_type_timestamp', 'post_id, event_type, timestamp' );

            // Support session-based popup analytics.
            parent::safe_create_index( $table_name, 'idx_popup_session', 'post_id, session_id(191)' );

            // Attribute WooCommerce sales from session history.
            parent::safe_create_index( $table_name, 'idx_session_event_lookup', 'session_id(191), event_type, sentiment, timestamp' );

            // Attribute WooCommerce sales from logged-in customer history.
            parent::safe_create_index( $table_name, 'idx_user_event_lookup', 'user_id, event_type, sentiment, timestamp' );

            // Attribute WooCommerce sales from anonymous visitor history.
            parent::safe_create_index( $table_name, 'idx_anonymous_event_lookup', 'anonymous_user_guid(191), event_type, sentiment, timestamp' );

            // Deduplicate sale events by event type + session.
            parent::safe_create_index( $table_name, 'idx_event_type_session', 'event_type, session_id(191)' );

            // Count unique users/visitors per popup.
            parent::safe_create_index( $table_name, 'idx_popup_user', 'post_id, user_id, anonymous_user_guid' );

            return $db_delta_result;
        }

        /**
         * @return array<array-key, mixed> dbDelta result
         * @global wpdb $wpdb The WordPress database class instance.
         *
         */
        public function create_leads_table_and_indexes(): array {
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();
            $table_name = parent::get_table_name( self::FOOCONVERT_LEADS_TABLE );
            $timestamp_default = parent::get_timestamp_default();

            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                post_id bigint(20) unsigned NOT NULL,
                email varchar(255) NOT NULL,
                name varchar(255) DEFAULT NULL,
                metadata longtext DEFAULT NULL,
                page_url text DEFAULT NULL,
                timestamp datetime DEFAULT $timestamp_default,
                PRIMARY KEY (id)
            ) $charset_collate;";

            $db_delta_result = parent::safe_dbDelta( $sql );

            $this->log_table_creation_results( $db_delta_result, $sql, $table_name );

            // Fetch leads for a popup.
            parent::safe_create_index( $table_name, 'idx_popup', 'post_id' );

            // Look up leads by email.
            parent::safe_create_index( $table_name, 'idx_email', 'email' );

            // Prefix length is required for the text column.
            parent::safe_create_index( $table_name, 'idx_page_url', 'page_url(191)' );

            return $db_delta_result;
        }

        /**
         * Logs the results of the table creation process.
         *
         * @param array<array-key, mixed> $db_delta_result The results of the table creation process.
         * @param string $sql The SQL statement used to create the table.
         * @param string $table_name The name of the table that we tried to create.
         * @return void
         */
        public function log_table_creation_results( array $db_delta_result, string $sql, string $table_name ): void {
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
        static function does_table_exist( string $table_name ): bool {
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
