<?php

namespace FooPlugins\FooConvert\Data;

use wpdb;

/**
 * FooConvert Data Schema Class
 * Creates the database tables and indexes for the plugin.
 */

if ( !class_exists( 'FooPlugins\FooConvert\Data\Schema' ) ) {

    class Schema extends Base
    {
        const FOOCONVERT_TABLE = 'fooconvert_events';

        /**
         * @return array<array-key, mixed>|false Table creation results, or false.
         */
        public function create_event_table_if_needed()
        {
            if ( defined( 'FOOCONVERT_VERSION' ) ) {
                $current_version = FOOCONVERT_VERSION;
                $version_create_table = get_option( FOOCONVERT_OPTION_VERSION_CREATE_TABLE, FOOCONVERT_VERSION );
                if ( version_compare( $current_version, $version_create_table, '>' ) ) {

                    // Create the table.
                    $table_creation_results = $this->create_event_table_and_indexes();

                    // TODO : Run any necessary migrations.

                    // update the version in the database
                    update_option( FOOCONVERT_OPTION_VERSION_CREATE_TABLE, $current_version );

                    return $table_creation_results;
                }
                return false;
            }
            return false;
        }

        /**
         * @return array<array-key, mixed> dbDelta result
         * @global wpdb $wpdb The WordPress database class instance.
         *
         */
        public function create_event_table_and_indexes()
        {
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();
            $table_name = parent::get_table_name( FOOCONVERT_DB_TABLE_EVENTS );
            $timestamp_default = parent::get_timestamp_default();

            /**
             * This is the Event table schema.
             *  - id is the primary key
             *  - widget_id is the id of the widget that created the event.
             *  - event_type is the type of event. This can be one of: 'open', 'click', 'close', etc.
             *  - page_url is the url of the page that created the event
             *  - device_type is the type of device that was used for the event
             *  - user_id who was the user when the event happened. Will be null if not logged in.
             *  - anonymous_user_guid the unique id of an anonymous user from the frontend. Will be null if logged in.
             *  - extra_data is all the extra data associated with the event.
             *      If event_type = 'conversion', then this will be the conversion data like the order id and value.
             *      The conversion will always be linked to the widget with the most recent interaction that was not a dismissal or view.
             *      This linking will be done using the user_id or anonymous_user_guid, whichever is present.
             *          eg. if an order is for a logged in user, then this is trivial.
             *          eg. if an order is for an anonymous user, then we need to use the anonymous_user_guid. If this is not available, then we do nothing.
             *  - timestamp is when the event happened
             */

            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                widget_id bigint(20) unsigned NOT NULL,
                event_type varchar(255) NOT NULL,
                event_subtype varchar(255) DEFAULT NULL,
                conversion boolean DEFAULT NULL,
                sentiment boolean DEFAULT NULL,
                page_url text DEFAULT NULL,
                device_type varchar(50) DEFAULT NULL,
                anonymous_user_guid varchar(255) DEFAULT NULL,
                user_id bigint(20) unsigned DEFAULT NULL,
                extra_data longtext DEFAULT NULL,
                timestamp datetime DEFAULT $timestamp_default,
                PRIMARY KEY (id)
            ) $charset_collate;";

            $db_delta_result = parent::safe_dbDelta($sql);

            // Create all the indexes we need.
            parent::safe_create_index($table_name, 'idx_widget', 'widget_id'); // We need to query all events for a widget.

            /*
             * Purpose : many queries use widget_id and filter by event_type (e.g., counting views, conversions, dismissals).
             */
            parent::safe_create_index($table_name, 'idx_widget_event_type', 'widget_id, event_type');

            /*
             * Purpose : many queries also use widget_id and filter by event_subtype (e.g., counting interactions, bounces).
             */
            parent::safe_create_index($table_name, 'idx_widget_event_subtype', 'widget_id, event_subtype');

            /*
             * Purpose : many queries also use widget_id and filter by conversion.
             */
            parent::safe_create_index($table_name, 'idx_widget_conversion', 'widget_id, conversion');

            /*
             * Purpose : many queries also use widget_id and filter by sentiment.
             */
            parent::safe_create_index($table_name, 'idx_widget_sentiment', 'widget_id, sentiment');

            /*
             * Purpose: This index will be particularly helpful when filtering by both widget_id and timestamp,
             * especially for queries restricted to recent data (e.g., the last 30, 60, or 90 days).
             * This will allow the database to quickly find the events within the specified date range for a particular widget.
             */
            parent::safe_create_index($table_name, 'idx_widget_timestamp', 'widget_id, timestamp');

            /*
             * Purpose: This index supports queries that filter by widget_id and event_type while also filtering or ordering by timestamp.
             * This will be useful for metrics that need to count or filter specific event types (like view, click, conversion, and dismiss) within a time range.
             */
            parent::safe_create_index($table_name, 'idx_widget_event_type_timestamp', 'widget_id, event_type, timestamp');

            /*
             * Purpose : Many metrics, such as unique visitors, conversion rate, and dismissal rate, rely on distinct counts of either user_id or anonymous_user_guid for each widget_id.
             */
            parent::safe_create_index($table_name, 'idx_widget_user', 'widget_id, user_id, anonymous_user_guid');

            return $db_delta_result;
        }
    }
}
