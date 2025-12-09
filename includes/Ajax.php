<?php

namespace FooPlugins\FooConvert;


/**
 * FooConvert Ajax Class
 * Handles the AJAX calls for the plugin.
 */

if ( !class_exists( 'FooPlugins\FooConvert\Ajax' ) ) {

    class Ajax {

        /**
         * Init constructor.
         */
        function __construct() {
            add_action( 'wp_ajax_fooconvert_log_event', array( $this, 'handle_log_event' ) );
            add_action( 'wp_ajax_nopriv_fooconvert_log_event', array( $this, 'handle_log_event' ) );
        }

        /**
         * Get the AJAX endpoint data.
         *
         * @return array{
         *     url: string The URL of the AJAX endpoint.
         *     nonce: string A nonce token for use with the AJAX endpoint.
         * }
         * @since 1.0.0
         *
         */
        public function get_endpoint(): array {
            return array(
                'url'   => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'fooconvert_nonce' )
            );
        }

        /**
         * Handles the AJAX call to log an event.
         *
         * @since 1.0.0
         */
        public function handle_log_event(): void {
            // Verify nonce!
            check_ajax_referer( 'fooconvert_nonce', 'nonce' );

            // TODO : should we care about bots?

            // Validate the POST data
            if ( !isset( $_POST['data'] ) ) {
                wp_send_json_error( 'Invalid data format!' );
                exit;
            }

            //TODO : test if data can be sanitised
            $data = json_decode( wp_unslash( $_POST['data'] ), true );

            // Ensure $data is an array
            if ( !is_array( $data ) ) {
                wp_send_json_error( 'Invalid data format!' );
                exit;
            }

            // check the event type
            $event_type = is_string( $data['eventType'] ) ? sanitize_text_field( $data['eventType'] ) : null;
            if ( empty( $event_type ) ) {
                wp_send_json_error( 'Missing event type!' );
                exit;
            }

            // check the widget ID
            $widget_id = isset( $data['widgetId'] ) ? intval( $data['widgetId'] ) : 0;
            if ( $widget_id === 0 ) {
                wp_send_json_error( 'Missing ID!' );
                exit;
            }

            // get other data
            $device_type = is_string( $data['deviceType'] ) ? sanitize_text_field( $data['deviceType'] ) : null;
            $page_url = is_string( $data['pageURL'] ) ? esc_url_raw( $data['pageURL'] ) : null;
            $anonymous_user_guid = is_string( $data['uniqueID'] ) ? sanitize_text_field( $data['uniqueID'] ) : null;
            $post_type = is_string( $data['postType'] ) ? sanitize_text_field( $data['postType'] ) : null;
            $template = is_string( $data['template'] ) ? sanitize_text_field( $data['template'] ) : null;

            // TODO: sanitize?
            $extra_data = isset( $data['extraData'] ) && is_array( $data['extraData'] ) ? $data['extraData'] : null;

            // TODO: handle email sign-up
            if ( is_string( $extra_data['source'] ) && is_string( $extra_data['email'] ) ) {
                // we have the minimum required for sign-up

                $lead = new Lead();

                $lead_data = [
                    'widget_id' => $widget_id,
                    'email' => $extra_data['email'],
                    'name' => $extra_data['name'],
                    'metadata' => $extra_data,
                    'page_url' => $page_url
                ];

                $lead->create( $lead_data );

                // check for existence?
                $email_exists = false;
                if ( $email_exists === true ) {
                    wp_send_json_error( 'Email already exists!' );
                    exit;
                }
            }

            $data = [
                'widget_id'           => $widget_id,
                'event_type'          => $event_type,
                'page_url'            => $page_url,
                'device_type'         => $device_type,
                'anonymous_user_guid' => $anonymous_user_guid,
                'extra_data'          => $extra_data
            ];

            $meta = [
                'post_type' => $post_type,
                'template'  => $template
            ];

            $event = new Event();
            $event->create( $data, $meta );

            wp_send_json_success();
            exit;
        }
    }
}

