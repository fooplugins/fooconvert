<?php

namespace FooPlugins\FooConvert\Admin;

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
         * Returns the MySQL timestamp default value when creating a table.
         *
         * Support for versions below 5.6, we can't use the `DEFAULT CURRENT_TIMESTAMP` syntax
         * So, we check the version and use the appropriate syntax
         *
         * @return string
         */
        public static function get_timestamp_default() {
            global $wpdb;

            $timestamp_default = 'CURRENT_TIMESTAMP';
            $mysql_version = $wpdb->db_version();
            if ( !is_null( $mysql_version ) && version_compare( $mysql_version, '5.6', '<' ) ) {
                return 'NULL';
            }

            return $timestamp_default;
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

        /**
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

        public static function safe_create_index( $table_name, $index_name, $columns ) {
            global $wpdb;

            if ( !self::index_exists( $table_name, $index_name ) ) {
                $index_query = "CREATE INDEX {$index_name} ON {$table_name} ({$columns})";
                $wpdb->query($index_query);
            }
        }
    }
}