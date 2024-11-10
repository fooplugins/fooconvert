<?php

namespace FooPlugins\FooConvert;


/**
 * FooConvert Ajax Class
 * Handles the AJAX calls for the plugin.
 */

if ( !class_exists( 'FooPlugins\FooConvert\Ajax' ) ) {

    class Ajax
    {

        /**
         * Init constructor.
         */
        function __construct()
        {
            add_action('wp_ajax_fooconvert_log_event', array( $this, 'handle_log_event' ) );
            add_action('wp_ajax_nopriv_fooconvert_log_event', array( $this, 'handle_log_event' ) );
        }

        public function handle_log_event(): void
        {
            // TODO Check nonce!
            // check_ajax_referer( 'fooconvert_nonce', 'nonce' );

            // TODO : if the user is logged in, check if their role is excluded from tracking

            // TODO : should we care about bots?

            // Validate the POST data
            if ( !isset( $_POST['data'] ) ) {
                wp_send_json_error( array( 'message' => 'Invalid data format!' ) );
                exit;
            }

            $data = json_decode( stripslashes( $_POST['data'] ), true );

            // Ensure $data is an array
            if ( !is_array( $data ) ) {
                wp_send_json_error( array( 'message' => 'Invalid data format!' ) );
                exit;
            }

            // check the event type
            $event_type = is_string( $data['eventType'] ) ? sanitize_text_field( $data['eventType'] ) : null;
            if ( is_null( $event_type ) || empty( $event_type ) ) {
                wp_send_json_error( array( 'message' => 'Missing event type!' ) );
                exit;
            }

            // check the widget ID
            $widget_id = isset( $data['widgetId'] ) ? intval( $data['widgetId'] ) : 0;
            if ( $widget_id === 0 ) {
                wp_send_json_error( array( 'message' => 'Missing widget ID!' ) );
                exit;
            }

            // get other data
            $device_type = is_string($data['deviceType']) ? sanitize_text_field($data['deviceType']) : null;
            $page_url = is_string($data['pageURL']) ? esc_url_raw($data['pageURL']) : null;
            $anonymous_user_guid = is_string( $data['uniqueID'] ) ? sanitize_text_field( $data['uniqueID'] ) : null;

            $event = new Event();
            $event->create( $widget_id, $event_type, $page_url, $device_type, null, $anonymous_user_guid );

            echo json_encode( array( 'status' => 'success' ) );
            wp_die();
        }
    }
}

