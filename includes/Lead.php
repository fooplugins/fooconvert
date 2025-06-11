<?php

namespace FooPlugins\FooConvert;

if ( !class_exists( __NAMESPACE__ . '\Lead' ) ) {

    /**
     * The lead class that manages creating and retrieving leads for a widget.
     */
    class Lead {
        /**
         * Creates a new lead and inserts it into the database.
         *
         * @param array $data
         * @return int|void|\WP_Error
         */
        public function create( $data ) {
            // Validate required fields
            if ( empty( $data['widget_id'] ) || empty( $data['email'] ) ) {
                return new \WP_Error( 'missing_required_fields', 'Widget ID and email are required fields' );
            }

            // Clean and sanitize data
            $data['widget_id'] = intval( $data['widget_id'] );
            $data['email'] = sanitize_email( $data['email'] );
            $data['name'] = !empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : null;
            $data['page_url'] = !empty( $data['page_url'] ) ? $this->clean_page_url( $data['page_url'] ) : null;
            $data['metadata'] = !empty( $data['metadata'] ) ? wp_json_encode( $data['metadata'] ) : null;

            // Convert empty values to null
            foreach ( $data as $key => $value ) {
                if ( is_array( $value ) && empty( $value ) ) {
                    $data[$key] = null;
                } else if ( $value === '' || $value === 0 || $value === '0' ) {
                    $data[$key] = null;
                }
            }

            // Allow others to alter the lead data
            $data = apply_filters( 'fooconvert_lead_data', $data );

            if ( !empty( $data ) && is_array( $data ) ) {
                return Data\QueryLead::insert_lead_data( $data );
            }

            return 0;
        }

        /**
         * Gets leads for a given widget.
         *
         * @param int $widget_id The ID of the widget to get leads for.
         * @param array $args Optional arguments to filter the leads.
         * @return array An associative array of lead data.
         */
        public function get_leads( $args = array() ) {
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

            return Data\QueryLead::get_leads( $args );
        }

        /**
         * Gets leads by IDs.
         *
         * @param array $ids Optional arguments to filter the leads.
         * @return array An array of lead data.
         */
        public function get_leads_by_ids( $ids ) {
            return Data\QueryLead::get_leads_by_ids( $ids );
        }

        /**
         * Gets lead metrics for a given widget.
         *
         * @param int $widget_id The ID of the widget to get metrics for.
         * @param int $days Number of days to fetch data for.
         * @return array An associative array of lead metrics.
         */
        public function get_widget_lead_metrics( $widget_id, $days = FOOCONVERT_METRICS_DAYS_DEFAULT ) {
            $metric_defaults = apply_filters( 'fooconvert_widget_lead_metrics_defaults', [
                'total_leads' => 0,
                'unique_leads' => 0,
                'unique_emails' => 0,
                'unique_visitors' => 0,
            ]);

            return apply_filters( 'fooconvert_widget_lead_metrics',
                array_merge( $metric_defaults, Data\QueryLead::get_leads_metrics( $widget_id, $days ) ),
                $widget_id );
        }

        /**
         * Deletes all leads from the database.
         *
         * @return int The number of leads deleted.
         */
        public function delete_all_leads() {
            return Data\QueryLead::delete_all_leads();
        }

        /**
         * Deletes a specific lead by ID.
         *
         * @param int $id The ID of the lead to delete.
         * @return int The number of rows deleted.
         */
        public function delete_lead( $id ) {
            return Data\QueryLead::delete_lead( $id );
        }

        /**
         * Deletes all leads for a given widget ID.
         *
         * @param int $widget_id The ID of the widget to delete leads for.
         * @return int The number of rows deleted.
         */
        public function delete_widget_leads( $widget_id ) {
            return Data\QueryLead::delete_widget_leads( $widget_id );
        }

        /**
         * Retrieves stats about the leads table.
         *
         * @return array An associative array of data about the leads table.
         */
        public function get_leads_table_stats() {
            return Data\QueryLead::get_leads_table_stats();
        }

        /**
         * Cleans the page URL by removing the domain from it.
         *
         * @param string $page_url The URL of the page to clean.
         * @return string The cleaned URL.
         */
        private function clean_page_url( $page_url ) {
            // strip the domain from the URL
            $home_url = home_url();

            if ( strpos( $page_url, $home_url ) === 0 ) {
                return '/' . ltrim( substr( $page_url, strlen( $home_url ) ), '/' );
            }

            return $page_url;
        }
    }
}
