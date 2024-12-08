<?php

namespace FooPlugins\FooConvert;

if ( ! class_exists( __NAMESPACE__ . '\Event' ) ) {

    /**
     * The event class that manages creating events for a widget.
     */
    class Event
    {
        /**
         * Creates a new event and inserts it into the database.
         *
         * @param array $data
         * @return int|void|\WP_Error
         */
        public function create( $data, $meta = array() ) {
            if ( $this->can_create_event() ) {
                $user_id = isset( $data['user_id'] ) ? intval( $data['user_id'] ) : null;
                $anonymous_user_guid = isset( $data['anonymous_user_guid'] ) ? $data['anonymous_user_guid'] : null;

                if ( isset( $data['page_url'] ) ) {
                    $data['page_url'] = $this->clean_page_url( $data['page_url'] );
                }

                if ( is_null( $user_id ) && is_user_logged_in() ) {
                    $user_id = get_current_user_id();
                    $anonymous_user_guid = null; //TODO : check if this should be null.
                }

                if ( $user_id > 0 ) {
                    $data['user_id'] = $user_id;
                    $data['anonymous_user_guid'] = null;
                } else {
                    $data['user_id'] = 0;
                    if ( empty( $anonymous_user_guid ) && isset( $_SERVER['REMOTE_ADDR'] ) && isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
                        // We could not determine the anonymous user GUID using the localStorage or cookie.
                        // Try and create a random GUID from the IP address and user agent.
                        $anonymous_user_guid = hash( 'sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] );
                    }
                    $data['anonymous_user_guid'] = $anonymous_user_guid;
                }

                // Convert empty values to null.
                foreach ( $data as $key => $value ) {
                    if ( is_array( $value ) && empty( $value ) ) {
                        $data[$key] = null;
                    } else if ( $value === '' || $value === 0 || $value === '0' ) {
                        $data[$key] = null;
                    }
                }

                $post_type = null;
                $template = null;

                if ( is_array( $meta ) ) {
                    $post_type = isset( $meta['post_type'] ) ? $meta['post_type'] : null;
                    $template = isset( $meta['template'] ) ? $meta['template'] : null;
                }

                // Allow others to alter the event data.
                $data = apply_filters( 'fooconvert_event_data', $data, $meta );

                if ( !empty( $post_type ) ) {
                    $data = apply_filters('fooconvert_event_data_by_post_type-' . $post_type, $data, $meta);
                }

                if ( !empty( $template ) ) {
                    $data = apply_filters( 'fooconvert_event_data_by_template-' . $template, $data, $meta );
                }

                if ( !empty( $data ) && is_array( $data ) ) {
                    return Data\Query::insert_event_data( $data );
                }

                return 0;
            }
        }

        /**
         * Will validate if the event can be created for a number of criteria.
         *
         * TODO : implement checks so that certain users do not create events. (eg. admins)
         * TODO : should we look into blocking bots?
         *
         * @return true
         */
        private function can_create_event() {
            return true;
        }

        /**
         * Cleans the page URL by removing the domain from it.
         *
         * @param string $page_url The URL of the page to clean.
         * @return string The cleaned URL.
         */
        private function clean_page_url($page_url)
        {
            // strip the domain from the URL
            $home_url = home_url();

            if ( strpos( $page_url, $home_url ) === 0 ) {
                return '/' . ltrim( substr( $page_url, strlen( $home_url ) ), '/' );
            }

            return $page_url;
        }

        /**
         * Get a summary of the events for a given widget.
         *
         * @param int $widget_id The ID of the widget to get the summary for.
         * @param bool $force
         * @return array An associative array of event metric data.
         */
        public function get_widget_metrics( $widget_id, $force = false) {

            if ( !$force ) {
                $data = get_post_meta($widget_id, FOOCONVERT_META_KEY_METRICS, true);

                if ( !empty( $data ) && is_array( $data ) ) {
                    $timestamp = isset( $data['timestamp'] ) ? intval( $data['timestamp'] ) : null;

                    // Check if the timestamp is within last day.
                    if ( time() - $timestamp < DAY_IN_SECONDS ) {
                        return $data['metrics'];
                    }
                }
            }

            $metric_defaults = apply_filters( 'fooconvert_widget_metrics_defaults', [
                'total_events' => 0,
                'total_views' => 0,
                'total_unique_visitors' => 0,
                'total_engagements' => 0,
            ] );

            $metrics = apply_filters( 'fooconvert_widget_metrics',
                array_merge( $metric_defaults, Data\Query::get_widget_metrics( $widget_id ) ),
                $widget_id );

            $data = [
                'timestamp' => time(),
                'metrics' => $metrics,
            ];

            // Store performance score
            update_post_meta( $widget_id, FOOCONVERT_META_KEY_METRICS, $data );

            return $metrics;
        }

        /**
         * Deletes all events from the database.
         *
         * @return int The number of events deleted.
         */
        public function delete_all_events()
        {
            return Data\Query::delete_all_events();
        }

        /**
         * Deletes all events for a given widget ID.
         *
         * @param int $widget_id The ID of the widget to delete events for.
         *
         * @return int The number of rows deleted.
         */
        public function delete_widget_events( $widget_id ) {
            return Data\Query::delete_widget_events( $widget_id );
        }


        /**
         * Deletes all events that are not associated with a widget in the posts table.
         *
         * @return int The number of events deleted.
         */
        public function delete_orphaned_events()
        {
            return Data\Query::delete_orphaned_events();
        }


        /**
         * Retrieves stats we care about for the events table.
         *
         * @return array An associative array of data about the events table, with the following keys:
         *     'Table' => string The name of the table.
         *     'Size_in_MB' => float The size of the table in megabytes.
         *     'Number_of_Rows' => int The number of rows in the table.
         *     'Unique_Widgets' => int The number of unique widgets represented in the table.
         *     'Orphaned_Events' => int The number of events that are not associated with a widget in the posts table.
         *     'Unique_Orphaned_Widgets' => int The number of unique widgets that are not associated with a widget in the posts table.
         */
        public function get_event_table_stats() {
            return Data\Query::get_events_table_stats();
        }

        /**
         * Returns an array of daily activity for the given widget.
         *
         * @param int $widget_id The ID of the widget.
         * @param int $days The number of days to fetch (default is 7).
         *
         * @return array An array of daily activity, with the following structure:
         *     'event_date' => string The date of the event (format: 'Y-m-d')
         *     'views' => int The number of views
         *     'clicks' => int The number of clicks
         *     'unique_visitors' => int The number of unique visitors
         */
        public function get_widget_daily_activity( $widget_id, $days = 7 ) {
            // Sanitize input
            $widget_id = intval( $widget_id );
            $days = max( 1, (int)$days ); // Ensure days is at least 1

            $results = Data\Query::get_widget_daily_activity( $widget_id, $days );

            // Loop through the data and ensure dates with no data are set to 0
            $final_data = [];
            for ( $i = $days - 1; $i >= 0; $i-- ) {
                $date = date( 'Y-m-d', strtotime( "-$i days" ) );

                $matching_data = $this->find_row_from_results( $results, 'event_date', $date );

                $default_data = apply_filters( 'fooconvert_widget_daily_activity_default', [
                    'event_date' => $date,
                    'events' => 0,
                    'views' => 0,
                    'engagements' => 0,
                    'unique_visitors' => 0,
                ] );

                $final_data[] = $matching_data ?? $default_data;
            }

            return apply_filters( 'fooconvert_widget_daily_activity', $final_data, $widget_id, $days );
        }

        /**
         * Search for data matching a specific row, based on the key and value.
         *
         * @param array $results The array of data to search.
         * @param string $key The key to search for.
         * @param string $value The value to search for.
         * @return array|null The matching data or null if not found.
         */
        private function find_row_from_results( $results, $key, $value ) {
            foreach ( $results as $row ) {
                if ( isset( $row[$key] ) && $row[$key] === $value ) {
                    return $row;
                }
            }
            return null;
        }

        /**
         * Gets all widget IDs with events.
         *
         * @return int[] The IDs of all widgets with events.
         */
        public function get_all_widgets_with_events() {
            $widgets = Data\Query::get_widgets_with_events();
            if ( empty( $widgets ) ) {
                return [];
            }

            return array_column( $widgets, 'widget_id' );
        }

        /**
         * Deletes events older than the retention period.
         *
         * @return boolean True on success, false on failure.
         */
        public function delete_old_events() {
            $retention = fooconvert_retention();

            return Data\Query::delete_old_events( $retention );
        }
    }
}
