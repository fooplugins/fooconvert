<?php

namespace FooPlugins\FooConvert;

if ( !class_exists( __NAMESPACE__ . '\Lead' ) ) {

    /**
     * Class Lead.
     */
    class Lead {
        /**
         * Handles create.
         */
        public function create( $data ) {
            if ( empty( $data['post_id'] ) || empty( $data['email'] ) ) {
                return new \WP_Error( 'missing_required_fields', 'Popup ID and email are required fields' );
            }

            $data['post_id'] = intval( $data['post_id'] );
            $data['email'] = sanitize_email( $data['email'] );
            $data['name'] = !empty( $data['name'] ) ? sanitize_text_field( $data['name'] ) : null;
            $data['page_url'] = !empty( $data['page_url'] ) ? $this->clean_page_url( $data['page_url'] ) : null;
            $data['metadata'] = !empty( $data['metadata'] ) ? wp_json_encode( $data['metadata'] ) : null;

            foreach ( $data as $key => $value ) {
                if ( is_array( $value ) && empty( $value ) ) {
                    $data[ $key ] = null;
                } else if ( $value === '' || $value === 0 || $value === '0' ) {
                    $data[ $key ] = null;
                }
            }

            $data = apply_filters( 'fooconvert_lead_data', $data );

            if ( !empty( $data ) && is_array( $data ) ) {
                $lead_id = Data\QueryLead::insert_lead_data( $data );

                if ( is_int( $lead_id ) ) {
                    $data['id'] = $lead_id;
                    do_action( 'fooconvert_lead_captured', $data );

                    return $lead_id;
                }
            }

            return 0;
        }

        /**
         * Returns the leads.
         */
        public function get_leads( $args = array() ) {
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

            return Data\QueryLead::get_leads( $args );
        }

        /**
         * Counts leads using the supplied query filters.
         *
         * @param array $args Lead query arguments.
         * @return int
         */
        public function count_leads( $args = array() ): int {
            $defaults = array(
                'email'      => '',
                'date_range' => '24hours',
                'page_url'   => '',
            );

            $args = wp_parse_args( $args, $defaults );

            return Data\QueryLead::count_leads( $args );
        }

        /**
         * Returns the leads by ids.
         */
        public function get_leads_by_ids( $ids ) {
            return Data\QueryLead::get_leads_by_ids( $ids );
        }

        /**
         * Returns lead metrics for a single popup over the requested time window.
         *
         * @param int $post_id Popup post ID.
         * @param int $days Number of days to include in the metrics window.
         * @return array<string,mixed>
         */
        public function get_popup_lead_metrics( $post_id, $days = FOOCONVERT_METRICS_DAYS_DEFAULT ) {
            $metric_defaults = apply_filters( 'fooconvert_popup_lead_metrics_defaults', [
                'total_leads' => 0,
                'unique_leads' => 0,
                'unique_emails' => 0,
                'unique_visitors' => 0,
            ] );

            return apply_filters(
                'fooconvert_popup_lead_metrics',
                array_merge( $metric_defaults, Data\QueryLead::get_leads_metrics( $post_id, $days ) ),
                $post_id
            );
        }

        /**
         * Deletes all leads.
         */
        public function delete_all_leads() {
            return Data\QueryLead::delete_all_leads();
        }

        /**
         * Deletes lead.
         */
        public function delete_lead( $id ) {
            return Data\QueryLead::delete_lead( $id );
        }

        /**
         * Deletes popup leads.
         */
        public function delete_popup_leads( $post_id ) {
            return Data\QueryLead::delete_popup_leads( $post_id );
        }

        /**
         * Returns the leads table stats.
         */
        public function get_leads_table_stats() {
            return Data\QueryLead::get_leads_table_stats();
        }

        /**
         * Cleans page url.
         */
        private function clean_page_url( $page_url ) {
            $home_url = home_url();

            if ( strpos( $page_url, $home_url ) === 0 ) {
                return '/' . ltrim( substr( $page_url, strlen( $home_url ) ), '/' );
            }

            return $page_url;
        }
    }
}
