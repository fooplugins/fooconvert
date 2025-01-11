<?php

namespace FooPlugins\FooConvert\Data;

use wpdb;

/**
 * FooConvert Data Base Class
 * Contains common database functions for the plugin.
 * Kudos to AnalyticsWP as a lot of this code is from there.
 */

if ( !class_exists( 'FooPlugins\FooConvert\Data\Base' ) ) {

    abstract class Base
    {
        /**
         * Returns the table name including the prefix.
         *
         * @return string
         * @global wpdb $wpdb The WordPress database class instance.
         *
         */
        public static function get_table_name( $table_name )
        {
            global $wpdb;

            return $wpdb->prefix . $table_name;
        }

        /**
         * Returns true if the database version is greater than or equal to the minimum version required.
         *
         * @param $min_version
         * @return bool
         */
        public static function db_version_minimum( $min_version ) {
            global $wpdb;

            $mysql_version = $wpdb->db_version();

            return !is_null( $mysql_version ) && version_compare( $mysql_version, $min_version, '>=' );
        }

        /**
         * Returns the MySQL timestamp default value when creating a table.
         *
         * Support for versions below 5.6, we can't use the `DEFAULT CURRENT_TIMESTAMP` syntax
         * So, we check the version and use the appropriate syntax
         *
         * @return string
         */
        public static function get_timestamp_default() {
            if ( self::db_version_minimum( '5.6' ) ) {
                return 'CURRENT_TIMESTAMP';
            }

            return 'NULL';
        }

        /**
         * Includes the WP upgrade file, as it contains the `dbDelta` function.
         *
         * @return void
         */
        private static function include_required_for_upgrade() {
            if ( defined( 'ABSPATH' ) ) {
                $path = ABSPATH . 'wp-admin/includes/upgrade.php';
                if ( file_exists( $path ) ) {
                    require_once( $path );
                }
            }
        }

        /**
         * Safely calls `dbDelta` after including the WP upgrade file.
         *
         * @param $sql
         * @return array
         */
        public static function safe_dbDelta( $sql ) {
            self::include_required_for_upgrade();
            return dbDelta( $sql );
        }

        // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        /**
         * Checks if an index exists in the database.
         *
         * @param string $table_name
         * @param string $index_name
         *
         * @global wpdb $wpdb The WordPress database class instance.
         *
         * @return bool
         */
        public static function index_exists( $table_name, $index_name )
        {
            global $wpdb;

            $query = (string)$wpdb->prepare(
                "SELECT COUNT(1) indexIsThere 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE table_schema=DATABASE() 
            AND table_name=%s 
            AND index_name=%s;",
                $table_name,
                $index_name
            );

            // If the result is 0, then the index doesn't exist
            return ( $wpdb->get_var( $query ) == 0 ) ? false : true;
        }

        /**
         * Safely create an index in the database.
         *
         * @param $table_name
         * @param $index_name
         * @param $columns
         * @return void
         */
        public static function safe_create_index( $table_name, $index_name, $columns ) {
            global $wpdb;

            if ( !self::index_exists( $table_name, $index_name ) ) {
                $index_query = "CREATE INDEX {$index_name} ON {$table_name} ({$columns})";
                $wpdb->query($index_query);
            }
        }

        /**
         * Safely create a partial index in the database.
         *
         * @param $table_name
         * @param $index_name
         * @param $columns
         * @param $partial_query
         * @return void
         */
        public static function safe_create_partial_index( $table_name, $index_name, $columns, $partial_query ) {
            global $wpdb;

            if ( self::db_version_minimum( '8.0' ) ) {

                if (!self::index_exists($table_name, $index_name)) {
                    $index_query = "CREATE INDEX {$index_name} ON {$table_name} ({$columns}) {$partial_query}";
                    $wpdb->query($index_query);
                }
            }
        }

        // phpcs:enable
    }
}