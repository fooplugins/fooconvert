<?php

namespace FooPlugins\FooConvert\Data;

use WP_Error;
use wpdb;

/**
 * FooConvert Data QueryLead Class
 * Performs all lead-related queries for the database for the plugin.
 */

class QueryLead extends Base {
    private function __construct() {
        // Prevent instantiation.
    }

    /**
     * Returns the leads table name.
     *
     * @return string
     */
    private static function get_leads_table_name() {
        return parent::get_table_name( Schema::FOOCONVERT_LEADS_TABLE );
    }

    /**
     * Inserts lead data into the database.
     *
     * @param array $data {
     *     An array of lead data.
     *
     * @type int $widget_id The ID of the widget.
     * @type string $email The email address of the lead.
     * @type string|null $name The name of the lead.
     * @type string|null $metadata Additional metadata about the lead.
     * @type string|null $page_url The URL of the page where the lead was captured.
     * @type string $timestamp The timestamp of the lead capture.
     * }
     *
     * @return int|WP_Error The ID of the inserted lead, or a WP_Error object on failure.
     */
    public static function insert_lead_data( $data ) {
        global $wpdb;

        if ( !is_array( $data ) || empty( $data ) ) {
            return new WP_Error( 'invalid_lead_data', 'The lead data is not valid.' );
        }

        // Validate required fields
        if ( !isset( $data['widget_id'] ) || !is_int( $data['widget_id'] ) || $data['widget_id'] <= 0 ) {
            return new WP_Error( 'invalid_lead_data_widget_id', 'The widget ID must be a positive integer.' );
        }

        if ( !isset( $data['email'] ) || !is_string( $data['email'] ) || !is_email( $data['email'] ) ) {
            return new WP_Error( 'invalid_lead_data_email', 'The email address is not valid.' );
        }

        // Clean and sanitize data
        $data['widget_id'] = intval( $data['widget_id'] );
        $data['email'] = sanitize_email( $data['email'] );
        $data['name'] = !empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : null;
        $data['page_url'] = !empty( $data['page_url'] ) ? $data['page_url'] : null;
        $data['metadata'] = !empty( $data['metadata'] ) ? maybe_serialize( $data['metadata'] ) : null;

        // Ensure timestamp is set
        if ( !isset( $data['timestamp'] ) ) {
            $data['timestamp'] = current_time( 'mysql', true );
        }

        $table_name = self::get_leads_table_name();

        // Insert the data into the database
        $result = $wpdb->insert( $table_name, $data );

        if ( $result === false ) {
            return new WP_Error( 'database_error', 'Error inserting data into ' . $table_name . ': ' . $wpdb->last_error );
        }

        return $wpdb->insert_id;
    }

    /**
     * Retrieves leads by IDs.
     *
     * @param array $ids Optional arguments to filter the leads.
     * @return array An array of lead data.
     */
    public static function get_leads_by_ids( $ids ) {
        global $wpdb;
    
        $table_name = self::get_leads_table_name();
    
        // Sanitize input: ensure all are integers
        $ids = array_map( 'intval', array_filter( $ids ) );
    
        if ( empty( $ids ) ) {
            return [];
        }
    
        // Build the placeholder string like "%d, %d, %d"
        $placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
    
        $query = "
            SELECT 
                {$table_name}.*, 
                {$wpdb->posts}.post_title AS widget_title
            FROM {$table_name}
            LEFT JOIN {$wpdb->posts} ON {$table_name}.widget_id = {$wpdb->posts}.ID
            WHERE {$table_name}.id IN ($placeholders)
        ";
    
        return $wpdb->get_results( $wpdb->prepare( $query, $ids ), ARRAY_A );
    }

    /**
     * Retrieves leads.
     *
     * @param array $args Optional arguments to filter the leads.
     * @return array An array of lead data.
     */
    public static function get_leads( $args = array() ) {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        
        $defaults = array(
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'timestamp',
            'order' => 'DESC',
            'email' => '',
            'date_range' => '24hours',
            'page_url' => '',
        );

        $args = wp_parse_args( $args, $defaults );

        $query = "SELECT 
                    {$table_name}.*, 
                    {$wpdb->posts}.post_title as widget_title
                    FROM {$table_name}
                    LEFT JOIN {$wpdb->posts} ON {$table_name}.widget_id = {$wpdb->posts}.ID
                    WHERE (1=1)";
        
        $where_clauses = array();
        $where_params = array();

        if ( !empty( $args['email'] ) ) {
            $where_clauses[] = "email = %s";
            $where_params[] = $args['email'];
        }

        if ( !empty( $args['page_url'] ) ) {
            $where_clauses[] = "page_url = %s";
            $where_params[] = $args['page_url'];
        }

        if ( !empty( $args['date_range'] ) ) {
            // Handle date range
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
                    // No date restriction
                    break;
            }
        }

        if ( !empty( $where_clauses ) ) {
            $query .= " AND " . implode( " AND ", $where_clauses );
        }

        if (!empty($args['orderby'])) {
            $query .= " ORDER BY {$args['orderby']} {$args['order']}";
        }

        $query .= " LIMIT %d OFFSET %d";
        $where_params[] = $args['limit'];
        $where_params[] = $args['offset'];

        return $wpdb->get_results(
            $wpdb->prepare(
                $query,
                $where_params
            ),
            ARRAY_A
        );
    }

    /**
     * Gets lead metrics.
     *
     * @param array $args Optional arguments including date range filter.
     * @return array An associative array of lead metrics.
     */
    public static function get_leads_metrics( $args = array() ) {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        
        // Set default date range if not provided
        $args = wp_parse_args( $args, array(
            'date_range' => '24hours'
        ));

        $query = "SELECT 
                    COUNT(*) as total_leads,
                    COUNT(DISTINCT email) as unique_emails
                    FROM {$table_name}
                    LEFT JOIN {$wpdb->posts} ON {$table_name}.widget_id = {$wpdb->posts}.ID
                    WHERE (1=1)";
        
        $where_clauses = array();
        $where_params = array();

        // Handle date range
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
                // No date restriction
                break;
        }

        if ( !empty( $where_clauses ) ) {
            $query .= ' AND ' . implode( ' AND ', $where_clauses );
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                $query,
                $where_params
            ),
            ARRAY_A
        );
    }

    /**
     * Deletes all leads from the database.
     *
     * @return int The number of leads deleted.
     */
    public static function delete_all_leads() {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        return $wpdb->delete( $table_name, array() );
    }

    /**
     * Deletes all leads for a given widget ID.
     *
     * @param int $widget_id The ID of the widget to delete leads for.
     * @return int The number of rows deleted.
     */
    public static function delete_widget_leads( $widget_id ) {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        return $wpdb->delete( $table_name, array( 'widget_id' => $widget_id ) );
    }

    /**
     * Deletes a specific lead by ID.
     *
     * @param int $id The ID of the lead to delete.
     * @return int The number of rows deleted.
     */
    public static function delete_lead( $id ) {
        global $wpdb;

        $table_name = self::get_leads_table_name();
        return $wpdb->delete( $table_name, array( 'id' => $id ) );
    }

    /**
     * Retrieves stats about the leads table.
     *
     * @return array An associative array of data about the leads table.
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

        // Get table size
        $table_size_query = "SELECT 
                            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as Size_in_MB 
                            FROM information_schema.tables 
                            WHERE table_schema = DATABASE() 
                            AND table_name = %s";

        $table_size = $wpdb->get_row(
            $wpdb->prepare(
                $table_size_query,
                $table_name
            ),
            ARRAY_A
        );

        return array_merge( $stats, $table_size );
    }
}
