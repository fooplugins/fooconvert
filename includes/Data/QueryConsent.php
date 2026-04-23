<?php

namespace FooPlugins\FooConvert\Data;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class QueryConsent.
 *
 * Database access for the `fooconvert_consent_log` table. Intentionally
 * shaped like `QueryLead` so it reads familiarly against the rest of the
 * data layer.
 */
class QueryConsent extends Base {
    /**
     * @codeCoverageIgnore
     */
    private function __construct() {
    }

    /**
     * Returns the prefixed consent log table name.
     */
    private static function get_consent_log_table_name(): string {
        return parent::get_table_name( Schema::FOOCONVERT_CONSENT_LOG_TABLE );
    }

    /**
     * Inserts a proof-of-consent record.
     *
     * @param array<string, mixed> $data Consent record data.
     * @return int|WP_Error Inserted row id, or a WP_Error.
     */
    public static function insert_consent_data( array $data ) {
        global $wpdb;

        if ( empty( $data ) ) {
            return new WP_Error( 'invalid_consent_data', 'The consent data is not valid.' );
        }

        if ( empty( $data['consent_id'] ) || !is_string( $data['consent_id'] ) ) {
            return new WP_Error( 'invalid_consent_data_id', 'The consent_id is required.' );
        }

        if ( empty( $data['event_type'] ) || !is_string( $data['event_type'] ) ) {
            return new WP_Error( 'invalid_consent_data_event_type', 'The event_type is required.' );
        }

        if ( !isset( $data['categories'] ) || !is_string( $data['categories'] ) ) {
            return new WP_Error( 'invalid_consent_data_categories', 'The categories string is required.' );
        }

        if ( !isset( $data['timestamp'] ) ) {
            $data['timestamp'] = current_time( 'mysql', true );
        }

        $table_name = self::get_consent_log_table_name();
        $result = $wpdb->insert( $table_name, $data );

        if ( $result === false ) {
            return new WP_Error( 'database_error', 'Error inserting data into ' . $table_name . ': ' . $wpdb->last_error );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Returns the most recent consent record for the given consent_id.
     *
     * @param string $consent_id Per-visitor consent identifier.
     * @return array<string, mixed>|null
     */
    public static function get_latest_for_consent_id( string $consent_id ) {
        global $wpdb;

        $table_name = self::get_consent_log_table_name();

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE consent_id = %s ORDER BY timestamp DESC, id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is built from the WP prefix and a hard-coded const.
                $consent_id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Returns the full consent history for the given consent_id, newest first.
     *
     * @param string $consent_id Per-visitor consent identifier.
     * @return array<int, array<string, mixed>>
     */
    public static function get_history_for_consent_id( string $consent_id ): array {
        global $wpdb;

        $table_name = self::get_consent_log_table_name();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE consent_id = %s ORDER BY timestamp DESC, id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- see above.
                $consent_id
            ),
            ARRAY_A
        ) ?: array();
    }

    /**
     * Returns recent consent records for admin display.
     *
     * @param int $limit Maximum rows to return.
     * @param int $offset Pagination offset.
     * @return array<int, array<string, mixed>>
     */
    public static function get_recent( int $limit = 100, int $offset = 0 ): array {
        global $wpdb;

        $table_name = self::get_consent_log_table_name();
        $limit = max( 1, min( 500, $limit ) );
        $offset = max( 0, $offset );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} ORDER BY timestamp DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- see above.
                $limit,
                $offset
            ),
            ARRAY_A
        ) ?: array();
    }

    /**
     * Deletes all consent records. Destructive; callers are expected to
     * guard this with an admin capability + confirmation prompt.
     *
     * @return int|false Rows deleted, or false on DB error.
     */
    public static function delete_all_consent_records() {
        global $wpdb;

        $table_name = self::get_consent_log_table_name();
        return $wpdb->query( "DELETE FROM {$table_name}" ); // phpcs:ignore WordPress.DB
    }

    /**
     * Deletes records older than the given number of days. The floor is
     * 30 days so a mis-configured setting can't wipe the proof-of-consent
     * retention below a legally meaningful window.
     *
     * @param int $retention_days Retention window in days.
     * @return int|false Rows deleted, or false on DB error.
     */
    public static function delete_old_consent_records( int $retention_days ) {
        global $wpdb;

        $retention_days = max( 30, (int) $retention_days );

        $table_name = self::get_consent_log_table_name();

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is built from the WP prefix and a hard-coded const.
                $retention_days
            )
        );
    }

    /**
     * Returns aggregate row/size statistics for the consent log table.
     *
     * @return array<string, mixed>
     */
    public static function get_consent_log_table_stats(): array {
        global $wpdb;

        $table_name = self::get_consent_log_table_name();

        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) as Number_of_Rows,
                    COUNT(DISTINCT consent_id) as Unique_Visitors
                    FROM %i",
                $table_name
            ),
            ARRAY_A
        );

        $table_size = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as Size_in_MB
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                    AND table_name = %s",
                $table_name
            ),
            ARRAY_A
        );

        return array_merge(
            array( 'Table' => $table_name ),
            is_array( $stats ) ? $stats : array(),
            is_array( $table_size ) ? $table_size : array()
        );
    }
}
