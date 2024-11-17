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
         * @param $widget_id
         * @param $event_type
         * @param $page_url
         * @param $device_type
         * @param null $user_id
         * @param null $anonymous_user_guid
         * @param null $extra_data
         * @param null $timestamp
         * @return int|void|\WP_Error
         */
        public function create( $widget_id, $event_type, $page_url, $device_type,
                                $user_id=null, $anonymous_user_guid=null, $extra_data=null, $timestamp=null ) {
            if ( $this->can_create_event() ) {
                $data = array(
                    'widget_id'   => $widget_id,
                    'event_type'  => $event_type,
                    'page_url'    => $this->clean_page_url( $page_url ), // Clean the URL before inserting it into the database.
                    'device_type' => $device_type,
                    'extra_data'  => $extra_data,
                    'timestamp'   => $timestamp
                );

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

                $query = new Data\Query();
                return $query->insert_event_data( $data );
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
         * @return array An associative array of event summary data.
         *     - int total_events: The total number of events.
         *     - int total_views: The total number of views.
         *     - int total_clicks: The total number of clicks.
         *     - int total_unique_visitors: The total number of unique visitors.
         *     - array recent_activity: The number of views, clicks, and unique visitors for each of the last 7 days.
         */
        public function get_widget_summary_data( $widget_id ) {
            $query = new Data\Query();
            $event_summary = $query->get_widget_summary_data( $widget_id );

            // Combine data into a single response
            return [
                'total_events' => $event_summary['total_events'],
                'total_views' => $event_summary['total_views'],
                'total_clicks' => $event_summary['total_clicks'],
                'total_unique_visitors' => $event_summary['total_unique_visitors'],
                'recent_activity' => $this->get_widget_daily_activity( $widget_id, 17 ),
            ];
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
        private function get_widget_daily_activity( $widget_id, $days = 7 ) {
            // Sanitize input
            $widget_id = intval( $widget_id );
            $days = max( 1, (int)$days ); // Ensure days is at least 1

            $query = new Data\Query();
            $results = $query->get_widget_daily_activity( $widget_id, $days );

            // Loop through the data and ensure dates with no data are set to 0
            $final_data = [];
            for ( $i = $days - 1; $i >= 0; $i-- ) {
                $date = date( 'Y-m-d', strtotime( "-$i days" ) );

                $matching_data = $this->find_row_from_results( $results, 'event_date', $date );

                $final_data[] = $matching_data ?? [
                    'event_date' => $date,
                    'views' => 0,
                    'clicks' => 0,
                    'unique_visitors' => 0,
                ];
            }

            return $final_data;
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
    }
}
