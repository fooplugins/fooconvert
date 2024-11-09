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
         * @param $anonymous_user_guid
         * @param $extra_data
         * @param $timestamp
         * @return int|void|\WP_Error
         */
        public function create( $widget_id, $event_type, $page_url, $device_type,
                                $user_id=null, $anonymous_user_guid=null, $extra_data=null, $timestamp=null ) {
            if ( $this->can_create_event() ) {
                $data = array(
                    'widget_id'  => $widget_id,
                    'event_type' => $event_type,
                    'page_url'   => $page_url,
                    'device_type' => $device_type,
                    'extra_data' => $extra_data,
                    'timestamp' => $timestamp
                );

                if ( is_null( $user_id ) && is_user_logged_in() ) {
                    $user_id = get_current_user_id();
                    $anonymous_user_guid = null; //TODO : check if this should be null.
                }

                if ( $user_id > 0 ) {
                    $data['user_id'] = $user_id;
                    $data['anonymous_user_guid'] = null;
                } else {
                    $data['user_id'] = null;
                    if ( empty( $anonymous_user_guid ) && isset( $_SERVER['REMOTE_ADDR'] ) && isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
                        // We could not determine the anonymous user GUID using the localStorage or cookie.
                        // Try and create a random GUID from the IP address and user agent.
                        $anonymous_user_guid = hash( 'sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] );
                    }
                    $data['anonymous_user_guid'] = $anonymous_user_guid;
                }

                // Convert empty values to null.
                foreach ( $data as $key => $value ) {
                    if ( $value === '' || $value === 0 || $value === '0' ) {
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
    }
}
