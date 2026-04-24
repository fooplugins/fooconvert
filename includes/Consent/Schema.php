<?php

namespace FooPlugins\FooConvert\Consent;

use FooPlugins\FooConvert\Data\Base;
use FooPlugins\FooConvert\Data\Schema as CoreSchema;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( __NAMESPACE__ . '\Schema' ) ) {

    /**
     * Class Schema.
     *
     * Owns the `fooconvert_consent_log` table. Self-contained — reuses only
     * the `Data\Base` helpers (prefixing, dbDelta wrappers, safe index
     * creation) that every database-owning module is expected to share.
     */
    class Schema extends Base {
        const TABLE = 'fooconvert_consent_log';

        /**
         * Returns the prefixed consent log table name.
         */
        public static function get_consent_log_table_name(): string {
            return parent::get_table_name( self::TABLE );
        }

        /**
         * Creates or migrates the consent log table and its indexes.
         *
         * Called from the core `fooconvert_create_tables` hook. Idempotent —
         * `dbDelta()` handles re-runs, and `safe_create_index()` skips indexes
         * that already exist.
         *
         * @return array<array-key, mixed> dbDelta result
         */
        public static function create_consent_log_table_and_indexes(): array {
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();
            $table_name = self::get_consent_log_table_name();
            $timestamp_default = parent::get_timestamp_default();

            /**
             * Consent log table schema.
             *  - id is the primary key.
             *  - consent_id is a per-visitor UUID echoed in the first-party `fc_consent`
             *    cookie; multiple rows may share the same consent_id as the visitor
             *    updates or withdraws their choice.
             *  - event_type is 'consent_grant' or 'consent_withdraw'.
             *  - version is the banner/config version at the moment of capture.
             *    Bumping the site-wide version invalidates older records and
             *    triggers a re-prompt.
             *  - categories is a compact category→state string, e.g. "necessary=1,…".
             *  - page_url is the page the consent was given on.
             *  - user_agent_hash is sha256 of the raw UA with the site salt applied.
             *  - ip_hash is sha256(truncated_ip + site_salt) — IPv4 to /24,
             *    IPv6 to /64, so the record is proportionate and not a raw IP log.
             *  - user_id is the logged-in user id when available.
             *  - source is 'banner', 'settings', or 'withdraw'.
             *  - timestamp is when the record was captured.
             */

            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                consent_id char(36) NOT NULL,
                event_type varchar(32) NOT NULL,
                version smallint(5) unsigned NOT NULL DEFAULT 1,
                categories varchar(255) NOT NULL,
                page_url text DEFAULT NULL,
                user_agent_hash char(64) DEFAULT NULL,
                ip_hash char(64) DEFAULT NULL,
                user_id bigint(20) unsigned DEFAULT NULL,
                source varchar(20) DEFAULT NULL,
                timestamp datetime DEFAULT $timestamp_default,
                PRIMARY KEY (id)
            ) $charset_collate;";

            $db_delta_result = parent::safe_dbDelta( $sql );

            // Log table-creation diagnostics through the shared core helper so
            // admin "Database" tab surfaces consent-log errors the same way.
            ( new CoreSchema() )->log_table_creation_results( $db_delta_result, $sql, $table_name );

            // Latest-state-for-visitor lookups.
            parent::safe_create_index( $table_name, 'idx_consent_id', 'consent_id' );

            // DSAR lookup: "give me everything you hold for this visitor".
            parent::safe_create_index( $table_name, 'idx_consent_id_timestamp', 'consent_id, timestamp' );

            // Admin log views filter/sort by recency.
            parent::safe_create_index( $table_name, 'idx_timestamp', 'timestamp' );

            // Retention sweep: delete rows older than N days.
            parent::safe_create_index( $table_name, 'idx_version_timestamp', 'version, timestamp' );

            return $db_delta_result;
        }

        /**
         * @return bool True if the table exists.
         */
        public static function does_table_exist(): bool {
            return CoreSchema::does_table_exist( self::get_consent_log_table_name() );
        }
    }
}
