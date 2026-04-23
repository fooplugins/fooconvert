<?php

namespace FooPlugins\FooConvert;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FooConvert Ajax Class
 * Handles the AJAX calls for the plugin.
 */

if ( !class_exists( 'FooPlugins\FooConvert\Ajax' ) ) {

    /**
     * Class Ajax.
     */
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
            check_ajax_referer( 'fooconvert_nonce', 'nonce' );
            if ( !isset( $_POST['data'] ) ) {
                wp_send_json_error( 'Invalid data format!' );
                exit;
            }

            $data = json_decode( wp_unslash( $_POST['data'] ), true );

            if ( !is_array( $data ) ) {
                wp_send_json_error( 'Invalid data format!' );
                exit;
            }

            $event_type = isset( $data['eventType'] ) && is_string( $data['eventType'] ) ? sanitize_text_field( $data['eventType'] ) : null;
            if ( empty( $event_type ) ) {
                wp_send_json_error( 'Missing event type!' );
                exit;
            }

            $post_id = isset( $data['postId'] ) ? intval( $data['postId'] ) : 0;
            if ( $post_id === 0 ) {
                wp_send_json_error( 'Missing ID!' );
                exit;
            }

            $device_type = isset( $data['deviceType'] ) && is_string( $data['deviceType'] ) ? sanitize_text_field( $data['deviceType'] ) : null;
            $page_url = isset( $data['pageURL'] ) && is_string( $data['pageURL'] ) ? esc_url_raw( $data['pageURL'] ) : null;
            $session_id = isset( $data['sessionID'] ) && is_string( $data['sessionID'] ) ? sanitize_text_field( $data['sessionID'] ) : null;
            $anonymous_user_guid = isset( $data['uniqueID'] ) && is_string( $data['uniqueID'] ) ? sanitize_text_field( $data['uniqueID'] ) : null;
            $template = isset( $data['template'] ) && is_string( $data['template'] ) ? sanitize_text_field( $data['template'] ) : null;

            // `extraData` carries feature-specific payloads that we persist as-is for
            // lead metadata and event analytics.
            $extra_data = isset( $data['extraData'] ) && is_array( $data['extraData'] ) ? $data['extraData'] : null;

            if (
                is_array( $extra_data )
                && isset( $extra_data['source'], $extra_data['email'] )
                && is_string( $extra_data['source'] )
                && is_string( $extra_data['email'] )
            ) {
                // Sign-up submissions are logged through the same endpoint and are
                // identified by the extra-data payload.
                $lead = new Lead();

                $lead_data = [
                    'post_id' => $post_id,
                    'email' => $extra_data['email'],
                    'name' => $extra_data['name'],
                    'metadata' => $extra_data,
                    'page_url' => $page_url
                ];

                $lead->create( $lead_data );
            }

            if (
                ( $event_type === FOOCONVERT_EVENT_TYPE_CONSENT_GRANT
                    || $event_type === FOOCONVERT_EVENT_TYPE_CONSENT_WITHDRAW )
                && is_array( $extra_data )
                && !empty( $extra_data['consentId'] )
                && isset( $extra_data['categories'] )
                && is_array( $extra_data['categories'] )
            ) {
                // Consent decisions are logged through the same endpoint as every
                // other popup event, but the authoritative proof-of-consent record
                // lands in `fooconvert_consent_log` rather than `fooconvert_events`
                // so it can have its own retention and deletion semantics.
                $consent = new Consent();

                $consent->record( array(
                    'consent_id' => (string) $extra_data['consentId'],
                    'event_type' => $event_type,
                    'categories' => $extra_data['categories'],
                    'version'    => isset( $extra_data['version'] ) ? (int) $extra_data['version'] : 1,
                    'page_url'   => $page_url,
                    'source'     => isset( $extra_data['source'] ) ? (string) $extra_data['source'] : 'banner',
                    'ip'         => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
                    'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
                ) );
            }

            $data = [
                'post_id'           => $post_id,
                'event_type'          => $event_type,
                'page_url'            => $page_url,
                'device_type'         => $device_type,
                'session_id'          => $session_id,
                'anonymous_user_guid' => $anonymous_user_guid,
                'extra_data'          => $extra_data
            ];

            $meta = [
                'template'  => $template
            ];

            $event = new Event();
            $event->create( $data, $meta );

            wp_send_json_success();
            exit;
        }
    }
}
