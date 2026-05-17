<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Lead;
use WP_List_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( __NAMESPACE__ . '\LeadsTable' ) ) {

    /**
     * Displays captured leads in a WordPress list table.
     */
    class LeadsTable extends WP_List_Table {
        /**
         * Lead service used to fetch and mutate rows.
         *
         * @var Lead
         */
        private Lead $lead;

        /**
         * Total number of leads matching the current filters.
         *
         * @var int
         */
        private int $total_items = 0;

        /**
         * Sets up the list table labels and lead service.
         */
        public function __construct() {
            parent::__construct( array(
                'singular' => 'lead',
                'plural'   => 'leads',
                'ajax'     => false,
            ) );

            $this->lead = new Lead();
        }

        /**
         * Returns the columns displayed in the leads table.
         *
         * @return array<string,string>
         */
        public function get_columns() {
            return array(
                'cb'           => '<input type="checkbox" />',
                'id'           => __( 'ID', 'fooconvert' ),
                'email'        => __( 'Email', 'fooconvert' ),
                'name'         => __( 'Name', 'fooconvert' ),
                'popup_title' => __( 'Popup', 'fooconvert' ),
                'page_url'     => __( 'Page', 'fooconvert' ),
                'timestamp'    => __( 'Date Added', 'fooconvert' ),
            );
        }

        /**
         * Returns the columns that should remain hidden by default.
         *
         * @return string[]
         */
        public function get_hidden_columns() {
            return array( 'id' );
        }

        /**
         * Returns the sortable columns for the leads table.
         *
         * @return array<string,array{0:string,1:bool}>
         */
        public function get_sortable_columns() {
            return array(
                'email'        => array( 'email', false ),
                'name'         => array( 'name', false ),
                'popup_title' => array( 'popup_title', false ),
                'timestamp'    => array( 'timestamp', true ),
            );
        }

        /**
         * Builds sanitized query arguments from the current request.
         *
         * @param bool $with_pagination Whether pagination arguments should be added.
         * @return array<string,mixed>
         */
        private function get_query_args( bool $with_pagination = true ): array {
            $per_page = max( 1, $this->get_items_per_page( 'leads_per_page', 20 ) );

            $args = array(
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table sorting parameter.
                'orderby'    => isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'timestamp',
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table sorting parameter.
                'order'      => isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'DESC',
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table search parameter.
                'email'      => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list table date filter.
                'date_range' => isset( $_GET['date_range'] ) ? sanitize_text_field( wp_unslash( $_GET['date_range'] ) ) : '24hours',
            );

            if ( $with_pagination ) {
                $args['limit'] = $per_page;
                $args['offset'] = ( $this->get_pagenum() - 1 ) * $per_page;
            }

            return $args;
        }

        /**
         * Fetches the current page of leads for the table.
         *
         * @return array<int,array<string,mixed>>
         */
        private function table_data(): array {
            return $this->lead->get_leads( $this->get_query_args() );
        }

        /**
         * Populates the list table items and pagination state.
         *
         * @return void
         */
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

        /**
         * Returns the bulk actions available for lead rows.
         *
         * @return array<string,string>
         */
        protected function get_bulk_actions() {
            return array(
                'delete' => __( 'Delete', 'fooconvert' ),
                'export' => __( 'Export', 'fooconvert' ),
            );
        }

        /**
         * Renders the default output for a table column.
         *
         * @param array<string,mixed> $item The current lead row.
         * @param string              $column_name The column being rendered.
         * @return string
         */
        public function column_default( $item, $column_name ) {
            switch ( $column_name ) {
                case 'email':
                case 'name':
                case 'popup_title':
                case 'page_url':
                    return $item[ $column_name ] ?? '';
                case 'timestamp':
                    return date_i18n( get_option( 'date_format' ), strtotime( $item['timestamp'] ) ) . ' ' . date_i18n( get_option( 'time_format' ), strtotime( $item['timestamp'] ) );
                default:
                    return $item[ $column_name ] ?? '';
            }
        }

        /**
         * Renders the checkbox column for bulk actions.
         *
         * @param array<string,mixed> $item The current lead row.
         * @return string
         */
        public function column_cb( $item ) {
            return sprintf(
                '<input type="checkbox" name="leads[]" value="%s" />',
                (int) $item['id']
            );
        }

        /**
         * Handles delete and export bulk actions for the selected leads.
         *
         * @return void
         */
        public function process_bulk_action() {
            $action = $this->current_action();

            if ( !in_array( $action, array( 'delete', 'export' ), true ) ) {
                return;
            }

            check_admin_referer( 'bulk-leads' );

            $lead_ids = isset( $_REQUEST['leads'] ) ? array_map( 'intval', (array) wp_unslash( $_REQUEST['leads'] ) ) : array();

            if ( 'delete' === $action && !empty( $lead_ids ) ) {
                foreach ( $lead_ids as $lead_id ) {
                    $this->lead->delete_lead( $lead_id );
                }
            }

            if ( 'export' === $action && !empty( $lead_ids ) ) {
                $this->export_leads( $lead_ids );
            }
        }

        /**
         * Streams the selected leads as a CSV download.
         *
         * @param int[] $lead_ids Lead IDs to export.
         * @return void
         */
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

            fputcsv( $fp, array( 'ID', 'Email', 'Name', 'Popup', 'Page URL', 'Date' ) );

            foreach ( $leads as $lead ) {
                fputcsv( $fp, array(
                    $lead['id'],
                    $lead['email'],
                    $lead['name'] ?? '',
                    $lead['popup_title'] ?? '',
                    $lead['page_url'] ?? '',
                    date_i18n( get_option( 'date_format' ), strtotime( $lead['timestamp'] ) ),
                ) );
            }

            fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing php://output, not a filesystem file handle.
            exit;
        }
    }
}
