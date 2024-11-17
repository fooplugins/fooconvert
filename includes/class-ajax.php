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

        /**
         * Get the AJAX endpoint data.
         *
         * @since 1.0.0
         *
         * @return array{
         *     url: string The URL of the AJAX endpoint.
         *     nonce: string A nonce token for use with the AJAX endpoint.
         * }
         */
        public function get_endpoint() : array {
            return array(
                'url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'fooconvert_nonce' )
            );
        }

        /**
         * Handles the AJAX call to log an event.
         *
         * @since 1.0.0
         */
        public function handle_log_event(): void
        {
            // Verify nonce!
            check_ajax_referer( 'fooconvert_nonce', 'nonce' );

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
            if ( empty( $event_type ) ) {
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
            $post_type = is_string($data['postType']) ? sanitize_text_field($data['postType']) : null;
            $template = is_string($data['template']) ? sanitize_text_field($data['template']) : null;

            // TODO: sanitize?
            $extra_data = isset( $data['extraData'] ) && is_array( $data['extraData'] ) ? $data['extraData'] : null;

            $event = new Event();

            // Allow others to alter the event data.
            $data = apply_filters( 'fooconvert_event_data', [
                'widget_id'           => $widget_id,
                'event_type'          => $event_type,
                'page_url'            => $page_url,
                'device_type'         => $device_type,
                'anonymous_user_guid' => $anonymous_user_guid,
                'extra_data'          => $extra_data
            ], $post_type, $template );

            $data = apply_filters( 'fooconvert_event_data_by_post_type-' . $post_type, $data, $template );

            $data = apply_filters( 'fooconvert_event_data_by_template-' . $template, $data, $post_type );

            $event->create( $data );

            echo json_encode( array( 'status' => 'success' ) );
            wp_die();
        }
    }
}

