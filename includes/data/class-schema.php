<?php

namespace FooPlugins\FooConvert\Admin;

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
         * @return string
         * @global wpdb $wpdb The WordPress database class instance.
         *
         */
        public function table_name()
        {
            global $wpdb;

            return $wpdb->prefix . self::FOOCONVERT_TABLE;
        }


        /**
         * @return array<array-key, mixed>|false Table creation results, or false.
         */
        public function create_table_if_needed()
        {
            if (defined('FOOCONVERT_VERSION')) {
                $current_version = FOOCONVERT_VERSION;
                $version_create_table = get_option(FOOCONVERT_OPTION_VERSION_CREATE_TABLE, '0.0.10');
                if (version_compare($current_version, $version_create_table, '>')) {

                    // Create the table.
                    $table_creation_results = $this->create_table();

                    // TODO : Run any necessary migrations.

                    // update the version in the database
                    update_option(FOOCONVERT_OPTION_VERSION_CREATE_TABLE, $current_version);

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
        public function create_table()
        {
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();
            $table_name = $this->table_name();
            $timestamp_default = parent::get_timestamp_default();

            /**
             * This is the Event table schema.
             *  - id is the primary key
             *  - widget_id is the id of the widget that created the event
             *  - event_type is the type of event
             *  - page_url is the url of the page that created the event
             *  - device_type is the type of device that was used for the event
             *  - user_id who was the user when the event happened
             *  - event_json is all the data associated with the event
             *  - timestamp is when the event happened
             */

            $sql = "CREATE TABLE $table_name (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                widget_id BIGINT(20) UNSIGNED NOT NULL,
                event_type VARCHAR(255) NOT NULL,
                page_url TEXT DEFAULT NULL,
                device_type VARCHAR(50) DEFAULT NULL,
                user_id BIGINT(20) UNSIGNED DEFAULT NULL,
                event_json longtext DEFAULT NULL,
                timestamp DATETIME DEFAULT $timestamp_default,
                PRIMARY KEY (id)
            ) $charset_collate;";

            $db_delta_result = parent::safe_dbDelta($sql);

            // Create all the indexes we need.
            parent::safe_create_index($table_name, 'idx_widget_id', 'widget_id');
            parent::safe_create_index($table_name, 'idx_event_type', 'event_type');
            parent::safe_create_index($table_name, 'idx_user_id', 'user_id');
            parent::safe_create_index($table_name, 'idx_timestamp', 'timestamp');
            parent::safe_create_index($table_name, 'idx_widget_id_timestamp', 'widget_id, timestamp');

            return $db_delta_result;
        }
    }
}
