<?php

namespace FooPlugins\FooConvert\Data;

use WP_Error;
use wpdb;

/**
 * FooConvert Data Query Class
 * Performs all queries for the database for the plugin.
 */

if ( !class_exists( 'FooPlugins\FooConvert\Data\Query' ) ) {

    class Query extends Base
    {
        /**
         * Inserts event data into the database.
         *
         * @param array $data {
         *     An array of event data.
         *
         *     @type int    $widget_id        The ID of the widget.
         *     @type string $event_type       The type of event (e.g. 'view', 'click', 'conversion', 'dismiss').
         *     @type string $page_url         The URL of the page where the event occurred.
         *     @type string $device_type      The type of device (e.g. 'desktop', 'mobile', 'tablet').
         *     @type int|null $user_id        The ID of the user (if logged in).
         *     @type string|null $anonymous_user_guid The GUID of the anonymous user.
         *     @type array|null $extra_data   An array or extra event data.
         *     @type string $timestamp        The timestamp of the event.
         * }
         *
         * @return int|WP_Error The ID of the inserted event, or a WP_Error object on failure.
         */
        function insert_event_data($data) {
            global $wpdb;

            // Validation rules and sanitization

            if ( !is_array( $data ) || empty( $data ) ) {
                return new WP_Error('invalid_event_data', 'The event data is not valid.');
            }

            // 1. Validate widget_id (required and should be a positive integer)
            if ( !isset( $data['widget_id'] ) || !is_int( $data['widget_id'] ) || $data['widget_id'] <= 0 ) {
                return new WP_Error('invalid_event_data_widget_id', 'The widget ID must be a positive integer.');
            }

            // 2. Validate event_type (required and should be a string)
            if ( !isset( $data['event_type'] ) || !is_string( $data['event_type'] ) ) {
                return new WP_Error('invalid_event_data_event_type', 'The event type is not valid.');
            }

            // 3. Validate page_url if provided (should be a valid URL)
            if ( isset($data['page_url'] ) && !filter_var( $data['page_url'], FILTER_VALIDATE_URL ) ) {
                return new WP_Error('invalid_event_data_page_url', 'The page URL is not a valid URL.');
            }

            // 4. Validate device_type (should be one of the allowed device types)
            $allowed_device_types = ['desktop', 'mobile', 'tablet', 'unknown'];
            if ( !isset( $data['device_type'] ) || !in_array( $data['device_type'], $allowed_device_types, true ) ) {
                return new WP_Error('invalid_event_data_device_type', 'The device type is not valid.');
            }

            // 5. Validate user_id if provided (should be a positive integer or null)
            if ( isset( $data['user_id'] ) && ( !is_int($data['user_id'] ) || $data['user_id'] <= 0 ) ) {
                return new WP_Error('invalid_event_data_user_id', 'The user ID must be a positive integer or null.');
            }

            // 6. Validate we have either a user_id or anonymous_user_guid
            if ( !isset( $data['user_id'] ) && !isset( $data['anonymous_user_guid'] ) ) {
                return new WP_Error('invalid_event_data_no_user', 'No user ID or anonymous user GUID was provided.');
            }

            // 7. Validate extra_data.
            if ( isset( $data['extra_data'] ) && !is_array( $data['extra_data'] ) ) {
                return new WP_Error('invalid_event_data_extra_data', 'The extra data was not valid.');
            }

            // 8. Ensure timestamp is set.
            if ( !isset( $data['timestamp'] ) ) {
                $data['timestamp'] = current_time( 'mysql', true );
            }

            $table_name = parent::get_table_name( FOOCONVERT_DB_TABLE_EVENTS );

            // Insert the data into the database
            $result = $wpdb->insert( $table_name, $data );

            if ( $result === false ) {
                return new WP_Error('database_error', 'Error inserting data into ' . $table_name . ': ' . $wpdb->last_error);
            }

            return $wpdb->insert_id;
        }
    }
}