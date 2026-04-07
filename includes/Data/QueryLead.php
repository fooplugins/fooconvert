<?php

namespace FooPlugins\FooConvert\Data;

use WP_Error;

/**
 * Class QueryLead.
 */
class QueryLead extends Base {
    /**
     * Initializes the QueryLead.
     */
    private function __construct() {
    }

    /**
     * Returns the leads table name.
     */
    private static function get_leads_table_name() {
        return parent::get_table_name( Schema::FOOCONVERT_LEADS_TABLE );
    }

    /**
     * Builds the SQL WHERE fragments used by lead queries.
     *
     * @param array $args Lead query arguments.
     * @return array{0:array<int,string>,1:array<int,mixed>}
     */
    private static function get_where_parts( array $args ): array {
        $where_clauses = array();
        $where_params = array();

        if ( !empty( $args['email'] ) ) {
            $where_clauses[] = 'email = %s';
            $where_params[] = $args['email'];
        }

        if ( !empty( $args['page_url'] ) ) {
            $where_clauses[] = 'page_url = %s';
            $where_params[] = $args['page_url'];
        }

        if ( !empty( $args['date_range'] ) ) {
            switch ( $args['date_range'] ) {
                case '24hours':
                    $where_clauses[] = 'timestamp >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
                    break;
                case '7days':
                    $where_clauses[] = 'timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                    break;
                case '30days':
                    $where_clauses[] = 'timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                    break;
                case 'forever':
                default:
                    break;
            }
        }

        return array( $where_clauses, $where_params );
    }

    /**
     * Handles insert lead data.
     */
    public static function insert_lead_data( $data ) {
        global $wpdb;

        if ( !is_array( $data ) || empty( $data ) ) {
            return new WP_Error( 'invalid_lead_data', 'The lead data is not valid.' );
        }

        if ( !isset( $data['widget_id'] ) || !is_int( $data['widget_id'] ) || $data['widget_id'] <= 0 ) {
            return new WP_Error( 'invalid_lead_data_widget_id', 'The widget ID must be a positive integer.' );
        }

        if ( !isset( $data['email'] ) || !is_string( $data['email'] ) || !is_email( $data['email'] ) ) {
            return new WP_Error( 'invalid_lead_data_email', 'The email address is not valid.' );
        }

        $data['widget_id'] = intval( $data['widget_id'] );
        $data['email'] = sanitize_email( $data['email'] );
        $data['name'] = !empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : null;
        $data['page_url'] = !empty( $data['page_url'] ) ? $data['page_url'] : null;
        $data['metadata'] = !empty( $data['metadata'] ) ? maybe_serialize( $data['metadata'] ) : null;

        if ( !isset( $data['timestamp'] ) ) {
            $data['timestamp'] = current_time( 'mysql', true );
        }

        $table_name = self::get_leads_table_name();
        $result = $wpdb->insert( $table_name, $data );

        if ( $result === false ) {
            return new WP_Error( 'database_error', 'Error inserting data into ' . $table_name . ': ' . $wpdb->last_error );
        }

        return $wpdb->insert_id;
    }

    /**
     * Returns the leads by ids.
     */
    public static function get_leads_by_ids( $ids ) {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        $ids = array_map( 'intval', array_filter( $ids ) );

        if ( empty( $ids ) ) {
            return array();
        }

        $placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
        $query = "
            SELECT
                {$table_name}.*,
                {$wpdb->posts}.post_title AS widget_title
            FROM {$table_name}
            LEFT JOIN {$wpdb->posts} ON {$table_name}.widget_id = {$wpdb->posts}.ID
            WHERE {$table_name}.id IN ({$placeholders})
        ";

        return $wpdb->get_results( $wpdb->prepare( $query, $ids ), ARRAY_A );
    }

    /**
     * Returns leads that match the supplied filters and pagination arguments.
     *
     * @param array $args Lead query arguments.
     * @return array<int,array<string,mixed>>
     */
    public static function get_leads( $args = array() ) {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        $defaults = array(
            'limit'      => 100,
            'offset'     => 0,
            'orderby'    => 'timestamp',
            'order'      => 'DESC',
            'email'      => '',
            'date_range' => '24hours',
            'page_url'   => '',
        );
        $args = wp_parse_args( $args, $defaults );

        $allowed_orderby = array( 'id', 'email', 'name', 'timestamp', 'widget_title', 'page_url' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'timestamp';
        $order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

        $query = "SELECT
                {$table_name}.*,
                {$wpdb->posts}.post_title as widget_title
            FROM {$table_name}
            LEFT JOIN {$wpdb->posts} ON {$table_name}.widget_id = {$wpdb->posts}.ID
            WHERE (1=1)";

        list( $where_clauses, $where_params ) = self::get_where_parts( $args );

        if ( !empty( $where_clauses ) ) {
            $query .= ' AND ' . implode( ' AND ', $where_clauses );
        }

        $query .= " ORDER BY {$orderby} {$order}";
        $query .= ' LIMIT %d OFFSET %d';
        $where_params[] = (int) $args['limit'];
        $where_params[] = (int) $args['offset'];

        return $wpdb->get_results( $wpdb->prepare( $query, $where_params ), ARRAY_A );
    }

    /**
     * Counts leads that match the supplied filters.
     *
     * @param array $args Lead query arguments.
     * @return int
     */
    public static function count_leads( $args = array() ): int {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        $defaults = array(
            'email'      => '',
            'date_range' => '24hours',
            'page_url'   => '',
        );
        $args = wp_parse_args( $args, $defaults );

        $query = "SELECT COUNT(*) FROM {$table_name} WHERE (1=1)";
        list( $where_clauses, $where_params ) = self::get_where_parts( $args );

        if ( !empty( $where_clauses ) ) {
            $query .= ' AND ' . implode( ' AND ', $where_clauses );
        }

        if ( empty( $where_params ) ) {
            return (int) $wpdb->get_var( $query );
        }

        return (int) $wpdb->get_var( $wpdb->prepare( $query, $where_params ) );
    }

    /**
     * Returns the leads metrics.
     */
    public static function get_leads_metrics( $args = array() ) {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        $args = wp_parse_args( $args, array( 'date_range' => '24hours' ) );

        $query = "SELECT
                    COUNT(*) as total_leads,
                    COUNT(DISTINCT email) as unique_emails
                    FROM {$table_name}
                    LEFT JOIN {$wpdb->posts} ON {$table_name}.widget_id = {$wpdb->posts}.ID
                    WHERE (1=1)";

        list( $where_clauses, $where_params ) = self::get_where_parts( $args );

        if ( !empty( $where_clauses ) ) {
            $query .= ' AND ' . implode( ' AND ', $where_clauses );
        }

        if ( empty( $where_params ) ) {
            return $wpdb->get_row( $query, ARRAY_A );
        }

        return $wpdb->get_row( $wpdb->prepare( $query, $where_params ), ARRAY_A );
    }

    /**
     * Deletes all leads.
     */
    public static function delete_all_leads() {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        return $wpdb->delete( $table_name, array() );
    }

    /**
     * Deletes all leads associated with a single widget.
     *
     * @param int $widget_id Widget post ID.
     * @return int|false
     */
    public static function delete_widget_leads( $widget_id ) {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        return $wpdb->delete( $table_name, array( 'widget_id' => $widget_id ) );
    }

    /**
     * Deletes a single lead by its database ID.
     *
     * @param int $id Lead ID.
     * @return int|false
     */
    public static function delete_lead( $id ) {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        return $wpdb->delete( $table_name, array( 'id' => $id ) );
    }

    /**
     * Returns aggregate row, widget, email, and table size statistics.
     *
     * @return array<string,mixed>
     */
    public static function get_leads_table_stats() {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        $query = "SELECT
                    COUNT(*) as Number_of_Rows,
                    COUNT(DISTINCT widget_id) as Unique_Widgets,
                    COUNT(DISTINCT email) as Unique_Emails
                    FROM {$table_name}";

        $stats = $wpdb->get_row( $query, ARRAY_A );
        $table_size_query = "SELECT
                            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as Size_in_MB
                            FROM information_schema.tables
                            WHERE table_schema = DATABASE()
                            AND table_name = %s";

        $table_size = $wpdb->get_row(
            $wpdb->prepare( $table_size_query, $table_name ),
            ARRAY_A
        );

        return array_merge( $stats, $table_size );
    }
}
