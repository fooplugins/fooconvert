<?php

namespace FooPlugins\FooConvert;

use FooPlugins\FooConvert\Data\Base;
use FooPlugins\FooConvert\Data\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( __NAMESPACE__ . '\UpgradeMigration' ) ) {

    /**
     * Runs one-time upgrade migrations for legacy popup storage.
     */
    class UpgradeMigration {

        /**
         * Migration key used for the free-plugin widget-to-popup upgrade.
         */
        private const MIGRATION_WIDGET_TO_POPUP = 'widget_to_popup_free';

        /**
         * Legacy cron hook from the pre-popup naming scheme.
         */
        private const LEGACY_STATS_CRON_HOOK = 'fooconvert_calculate_widget_stats';

        /**
         * Register the upgrade migration check.
         */
        public function __construct() {
            $this->maybe_run();
        }

        /**
         * Run the free-plugin upgrade migration once.
         */
        public function maybe_run(): void {
            if ( !function_exists( 'get_option' ) || !function_exists( 'update_option' ) || !isset( $GLOBALS['wpdb'] ) ) {
                return;
            }

            if ( $this->is_completed( self::MIGRATION_WIDGET_TO_POPUP ) ) {
                return;
            }

            $successful = true;
            $successful = $this->migrate_widget_id_column( Base::get_table_name( FOOCONVERT_DB_TABLE_EVENTS ) ) && $successful;
            $successful = $this->migrate_widget_id_column( Base::get_table_name( Schema::FOOCONVERT_LEADS_TABLE ) ) && $successful;
            $successful = $this->migrate_legacy_stats_schedule() && $successful;

            if ( $successful ) {
                $this->mark_completed( self::MIGRATION_WIDGET_TO_POPUP );
            }
        }

        /**
         * Rename the legacy analytics column to post_id when needed.
         *
         * @param string $table_name Fully qualified table name.
         * @return bool
         */
        private function migrate_widget_id_column( string $table_name ): bool {
            global $wpdb;

            if ( !$this->table_exists( $table_name ) ) {
                return true;
            }

            $this->drop_index_if_exists( $table_name, 'idx_widget' );

            if ( !$this->column_exists( $table_name, 'widget_id' ) || $this->column_exists( $table_name, 'post_id' ) ) {
                return true;
            }

            $query = "ALTER TABLE {$table_name} CHANGE widget_id post_id bigint(20) unsigned NOT NULL";

            return false !== $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        }

        /**
         * Remove the legacy stats cron schedule so the renamed hook can take over.
         *
         * @return bool
         */
        private function migrate_legacy_stats_schedule(): bool {
            if ( !function_exists( 'wp_next_scheduled' ) || !function_exists( 'wp_unschedule_event' ) ) {
                return true;
            }

            $hook = self::LEGACY_STATS_CRON_HOOK;

            while ( $timestamp = wp_next_scheduled( $hook ) ) {
                wp_unschedule_event( $timestamp, $hook );
            }

            return true;
        }

        /**
         * Drop a legacy index if it exists.
         *
         * @param string $table_name Table name.
         * @param string $index_name Index name.
         * @return void
         */
        private function drop_index_if_exists( string $table_name, string $index_name ): void {
            global $wpdb;

            if ( !Base::index_exists( $table_name, $index_name ) ) {
                return;
            }

            $wpdb->query( "DROP INDEX {$index_name} ON {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        }

        /**
         * Determine whether a table exists.
         *
         * @param string $table_name Fully qualified table name.
         * @return bool
         */
        private function table_exists( string $table_name ): bool {
            global $wpdb;

            $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );

            return (string) $wpdb->get_var( $query ) === $table_name; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        }

        /**
         * Determine whether a table column exists.
         *
         * @param string $table_name Fully qualified table name.
         * @param string $column_name Column name.
         * @return bool
         */
        private function column_exists( string $table_name, string $column_name ): bool {
            global $wpdb;

            $query = $wpdb->prepare( "SHOW COLUMNS FROM {$table_name} LIKE %s", $column_name );

            return !empty( $wpdb->get_row( $query ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        }

        /**
         * Read the completed upgrade migration keys.
         *
         * @return string[]
         */
        private function get_completed(): array {
            if ( !function_exists( 'get_option' ) ) {
                return array();
            }

            $completed = get_option( FOOCONVERT_OPTION_UPGRADE_MIGRATIONS, array() );

            return is_array( $completed ) ? $completed : array();
        }

        /**
         * Determine whether an upgrade migration has already been completed.
         *
         * @param string $migration_key Migration identifier.
         * @return bool
         */
        private function is_completed( string $migration_key ): bool {
            return in_array( $migration_key, $this->get_completed(), true );
        }

        /**
         * Mark an upgrade migration as completed.
         *
         * @param string $migration_key Migration identifier.
         * @return void
         */
        private function mark_completed( string $migration_key ): void {
            if ( !function_exists( 'update_option' ) ) {
                return;
            }

            $completed = $this->get_completed();
            if ( in_array( $migration_key, $completed, true ) ) {
                return;
            }

            $completed[] = $migration_key;
            update_option( FOOCONVERT_OPTION_UPGRADE_MIGRATIONS, $completed, false );
        }
    }
}
