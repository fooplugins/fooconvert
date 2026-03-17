<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Lead;
use WP_List_Table;

if ( !class_exists( __NAMESPACE__ . '\LeadsTable' ) ) {

    class LeadsTable extends WP_List_Table {
        private Lead $lead;
        private int $total_items = 0;

        public function __construct() {
            parent::__construct( array(
                'singular' => 'lead',
                'plural'   => 'leads',
                'ajax'     => false,
            ) );

            $this->lead = new Lead();
        }

        public function get_columns() {
            return array(
                'cb'           => '<input type="checkbox" />',
                'id'           => __( 'ID', 'fooconvert' ),
                'email'        => __( 'Email', 'fooconvert' ),
                'name'         => __( 'Name', 'fooconvert' ),
                'widget_title' => __( 'Widget', 'fooconvert' ),
                'page_url'     => __( 'Page', 'fooconvert' ),
                'timestamp'    => __( 'Date Added', 'fooconvert' ),
            );
        }

        public function get_hidden_columns() {
            return array( 'id' );
        }

        public function get_sortable_columns() {
            return array(
                'email'        => array( 'email', false ),
                'name'         => array( 'name', false ),
                'widget_title' => array( 'widget_title', false ),
                'timestamp'    => array( 'timestamp', true ),
            );
        }

        private function get_query_args( bool $with_pagination = true ): array {
            $per_page = max( 1, $this->get_items_per_page( 'leads_per_page', 20 ) );

            $args = array(
                'orderby'    => isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'timestamp',
                'order'      => isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'DESC',
                'email'      => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
                'date_range' => isset( $_GET['date_range'] ) ? sanitize_text_field( wp_unslash( $_GET['date_range'] ) ) : '24hours',
            );

            if ( $with_pagination ) {
                $args['limit'] = $per_page;
                $args['offset'] = ( $this->get_pagenum() - 1 ) * $per_page;
            }

            return $args;
        }

        private function table_data(): array {
            return $this->lead->get_leads( $this->get_query_args() );
        }

        public function prepare_items() {
            $this->_column_headers = array(
                $this->get_columns(),
                $this->get_hidden_columns(),
                $this->get_sortable_columns(),
            );

            $this->process_bulk_action();

            $this->items = $this->table_data();
            $this->total_items = $this->lead->count_leads( $this->get_query_args( false ) );

            $per_page = max( 1, $this->get_items_per_page( 'leads_per_page', 20 ) );
            $this->set_pagination_args( array(
                'total_items' => $this->total_items,
                'per_page'    => $per_page,
                'total_pages' => (int) ceil( $this->total_items / $per_page ),
            ) );
        }

        protected function get_bulk_actions() {
            return array(
                'delete' => __( 'Delete', 'fooconvert' ),
                'export' => __( 'Export', 'fooconvert' ),
            );
        }

        public function column_default( $item, $column_name ) {
            switch ( $column_name ) {
                case 'email':
                case 'name':
                case 'widget_title':
                case 'page_url':
                    return $item[ $column_name ] ?? '';
                case 'timestamp':
                    return date_i18n( get_option( 'date_format' ), strtotime( $item['timestamp'] ) ) . ' ' . date_i18n( get_option( 'time_format' ), strtotime( $item['timestamp'] ) );
                default:
                    return $item[ $column_name ] ?? '';
            }
        }

        public function column_cb( $item ) {
            return sprintf(
                '<input type="checkbox" name="leads[]" value="%s" />',
                (int) $item['id']
            );
        }

        public function process_bulk_action() {
            $lead_ids = isset( $_REQUEST['leads'] ) ? array_map( 'intval', (array) wp_unslash( $_REQUEST['leads'] ) ) : array();

            if ( 'delete' === $this->current_action() && !empty( $lead_ids ) ) {
                foreach ( $lead_ids as $lead_id ) {
                    $this->lead->delete_lead( $lead_id );
                }
            }

            if ( 'export' === $this->current_action() && !empty( $lead_ids ) ) {
                $this->export_leads( $lead_ids );
            }
        }

        private function export_leads( array $lead_ids ) {
            if ( ob_get_length() ) {
                ob_end_clean();
            }

            $leads = $this->lead->get_leads_by_ids( $lead_ids );
            $filename = 'fooconvert-leads-' . gmdate( 'Y-m-d' ) . '.csv';

            header( 'Content-Type: text/csv' );
            header( 'Content-Disposition: attachment; filename=' . $filename );
            header( 'Pragma: no-cache' );
            header( 'Expires: 0' );

            $fp = fopen( 'php://output', 'w' );

            fputcsv( $fp, array( 'ID', 'Email', 'Name', 'Widget', 'Page URL', 'Date' ) );

            foreach ( $leads as $lead ) {
                fputcsv( $fp, array(
                    $lead['id'],
                    $lead['email'],
                    $lead['name'] ?? '',
                    $lead['widget_title'] ?? '',
                    $lead['page_url'] ?? '',
                    date_i18n( get_option( 'date_format' ), strtotime( $lead['timestamp'] ) ),
                ) );
            }

            fclose( $fp );
            exit;
        }
    }
}
