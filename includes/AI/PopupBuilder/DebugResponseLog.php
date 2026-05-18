<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

use WP_Error;

defined( 'ABSPATH' ) || exit;

class DebugResponseLog {

    /**
     * Maximum raw response length stored per entry.
     */
    private const PAYLOAD_LIMIT = 65535;

    /**
     * Maximum stored debug entries.
     */
    private const ENTRY_LIMIT = 25;

    /**
     * Stores an invalid AI response for inspection when FooConvert debug mode is enabled.
     *
     * @param string                          $payload Raw model text.
     * @param WP_Error                        $error Response error.
     * @param array<int,array<string,string>> $messages Conversation messages.
     * @param array<string,mixed>             $settings AI request settings.
     * @param string                          $failure_type Failure category.
     * @param int                             $attempt Attempt number.
     * @param bool                            $will_retry Whether the builder will request a corrected response.
     * @param string                          $repaired_payload Repaired payload candidate.
     * @param string                          $repair_type Repair category.
     * @return void
     */
    public static function log_invalid_response( string $payload, WP_Error $error, array $messages, array $settings, string $failure_type, int $attempt, bool $will_retry, string $repaired_payload = '', string $repair_type = '' ): void {
        $entry = array(
            'failure_type'           => $failure_type,
            'attempt'                => max( 1, $attempt ),
            'will_retry'             => $will_retry,
            'error_code'             => $error->get_error_code(),
            'error_message'          => $error->get_error_message(),
            'error_data'             => self::sanitize_value( $error->get_error_data() ),
            'response_preview'       => self::get_response_preview( $payload, 500 ),
            'raw_response'           => self::truncate_payload( $payload ),
            'raw_response_length'    => strlen( $payload ),
            'raw_response_truncated' => strlen( $payload ) > self::PAYLOAD_LIMIT,
            'latest_user_message'    => self::get_latest_user_message( $messages ),
            'settings'               => self::get_settings_snapshot( $settings ),
        );

        if ( '' !== $repaired_payload && $repaired_payload !== $payload ) {
            $entry['repair_type']                 = $repair_type;
            $entry['repaired_response']           = self::truncate_payload( $repaired_payload );
            $entry['repaired_response_length']    = strlen( $repaired_payload );
            $entry['repaired_response_truncated'] = strlen( $repaired_payload ) > self::PAYLOAD_LIMIT;
        }

        self::store_entry( $entry );
    }

    /**
     * Stores a repaired AI response for inspection when FooConvert debug mode is enabled.
     *
     * @param string                          $payload Raw model text.
     * @param string                          $repaired_payload Repaired model text.
     * @param array<int,array<string,string>> $messages Conversation messages.
     * @param array<string,mixed>             $settings AI request settings.
     * @param string                          $repair_type Repair category.
     * @param int                             $attempt Attempt number.
     * @return void
     */
    public static function log_repaired_response( string $payload, string $repaired_payload, array $messages, array $settings, string $repair_type, int $attempt ): void {
        if ( '' === $repaired_payload || $repaired_payload === $payload ) {
            return;
        }

        self::store_entry(
            array(
                'failure_type'                => 'repaired_json',
                'attempt'                     => max( 1, $attempt ),
                'will_retry'                  => false,
                'error_code'                  => 'fooconvert_ai_popup_builder_repaired_json',
                'error_message'               => __( 'The AI response JSON was repaired and accepted.', 'fooconvert' ),
                'error_data'                  => array(
                    'repair_type' => $repair_type,
                ),
                'repair_type'                 => $repair_type,
                'response_preview'            => self::get_response_preview( $payload, 500 ),
                'raw_response'                => self::truncate_payload( $payload ),
                'raw_response_length'         => strlen( $payload ),
                'raw_response_truncated'      => strlen( $payload ) > self::PAYLOAD_LIMIT,
                'repaired_response'           => self::truncate_payload( $repaired_payload ),
                'repaired_response_length'    => strlen( $repaired_payload ),
                'repaired_response_truncated' => strlen( $repaired_payload ) > self::PAYLOAD_LIMIT,
                'latest_user_message'         => self::get_latest_user_message( $messages ),
                'settings'                    => self::get_settings_snapshot( $settings ),
            )
        );
    }

    /**
     * Returns whether invalid response logging is enabled.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return function_exists( 'fooconvert_is_debug' ) && (bool) fooconvert_is_debug();
    }

    /**
     * Returns the stored invalid response log.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function get_entries(): array {
        if ( ! defined( 'FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES' ) ) {
            return array();
        }

        $entries = get_option( FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES, array() );
        if ( ! is_array( $entries ) ) {
            return array();
        }

        return array_values(
            array_map(
                array( self::class, 'sanitize_entry' ),
                array_filter( $entries, 'is_array' )
            )
        );
    }

    /**
     * Clears all stored debug entries.
     *
     * @return void
     */
    public static function clear(): void {
        if ( defined( 'FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES' ) ) {
            delete_option( FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES );
        }
    }

    /**
     * Returns a cleaned response preview.
     *
     * @param string $payload Raw model text.
     * @param int    $limit Maximum preview length.
     * @return string
     */
    public static function get_response_preview( string $payload, int $limit = 500 ): string {
        $preview = trim( wp_strip_all_tags( $payload ) );
        $preview = preg_replace( '/\s+/', ' ', $preview );

        if ( ! is_string( $preview ) || '' === $preview ) {
            return '';
        }

        $limit = max( 80, $limit );

        if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
            if ( mb_strlen( $preview ) <= $limit ) {
                return $preview;
            }

            return rtrim( mb_substr( $preview, 0, $limit - 3 ) ) . '...';
        }

        if ( strlen( $preview ) <= $limit ) {
            return $preview;
        }

        return rtrim( substr( $preview, 0, $limit - 3 ) ) . '...';
    }

    /**
     * Truncates text with a multibyte-safe fallback.
     *
     * @param string $value Text to truncate.
     * @param int    $limit Maximum character count.
     * @return string
     */
    public static function truncate_text( string $value, int $limit ): string {
        $limit = max( 0, $limit );

        if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
            return mb_strlen( $value ) > $limit ? mb_substr( $value, 0, $limit ) : $value;
        }

        return strlen( $value ) > $limit ? substr( $value, 0, $limit ) : $value;
    }

    /**
     * Stores a debug response entry.
     *
     * @param array<string,mixed> $entry Debug entry.
     * @return void
     */
    private static function store_entry( array $entry ): void {
        if ( ! self::is_enabled() || ! defined( 'FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES' ) ) {
            return;
        }

        $entry['id']         = $entry['id'] ?? self::generate_id();
        $entry['created_at'] = $entry['created_at'] ?? gmdate( 'c' );

        $entries = self::get_entries();
        array_unshift( $entries, self::sanitize_entry( $entry ) );

        $entries = array_slice( $entries, 0, self::ENTRY_LIMIT );
        update_option( FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES, $entries, false );
    }

    /**
     * Returns a sanitized snapshot of AI request settings for debug entries.
     *
     * @param array<string,mixed> $settings AI request settings.
     * @return array<string,mixed>
     */
    private static function get_settings_snapshot( array $settings ): array {
        return array(
            'override_model'       => Settings::sanitize_model( $settings['override_model'] ?? '' ),
            'override_image_model' => Settings::sanitize_model( $settings['override_image_model'] ?? '' ),
            'disabled_params'      => Settings::sanitize_disabled_params( $settings['disabled_params'] ?? array() ),
            'selected_block_names' => Settings::sanitize_selected_block_names( $settings['selected_block_names'] ?? array() ),
            'timeout'              => Settings::sanitize_timeout( $settings['timeout'] ?? Settings::get_default_timeout() ),
            'max_tool_calls'       => Settings::sanitize_max_tool_calls( $settings['max_tool_calls'] ?? Settings::get_default_max_tool_calls() ),
            'response_schema_sent' => ! Settings::is_param_disabled( $settings, 'response_format' ),
        );
    }

    /**
     * Sanitizes one stored debug response entry.
     *
     * @param array<string,mixed> $entry Raw entry.
     * @return array<string,mixed>
     */
    private static function sanitize_entry( array $entry ): array {
        $raw_response = isset( $entry['raw_response'] ) && is_string( $entry['raw_response'] )
            ? self::truncate_payload( $entry['raw_response'] )
            : '';
        $repaired_response = isset( $entry['repaired_response'] ) && is_string( $entry['repaired_response'] )
            ? self::truncate_payload( $entry['repaired_response'] )
            : '';

        return array(
            'id'                          => isset( $entry['id'] ) && is_string( $entry['id'] ) ? sanitize_key( $entry['id'] ) : self::generate_id(),
            'created_at'                  => isset( $entry['created_at'] ) && is_string( $entry['created_at'] ) ? sanitize_text_field( $entry['created_at'] ) : gmdate( 'c' ),
            'failure_type'                => isset( $entry['failure_type'] ) && is_string( $entry['failure_type'] ) ? sanitize_key( $entry['failure_type'] ) : 'invalid_response',
            'repair_type'                 => isset( $entry['repair_type'] ) && is_string( $entry['repair_type'] ) ? sanitize_key( $entry['repair_type'] ) : '',
            'attempt'                     => max( 1, absint( $entry['attempt'] ?? 1 ) ),
            'will_retry'                  => ! empty( $entry['will_retry'] ),
            'error_code'                  => isset( $entry['error_code'] ) && is_string( $entry['error_code'] ) ? sanitize_key( $entry['error_code'] ) : '',
            'error_message'               => isset( $entry['error_message'] ) && is_string( $entry['error_message'] ) ? wp_strip_all_tags( $entry['error_message'] ) : '',
            'error_data'                  => self::sanitize_value( $entry['error_data'] ?? array() ),
            'response_preview'            => isset( $entry['response_preview'] ) && is_string( $entry['response_preview'] ) ? wp_strip_all_tags( $entry['response_preview'] ) : self::get_response_preview( $raw_response ),
            'raw_response'                => $raw_response,
            'raw_response_length'         => max( 0, absint( $entry['raw_response_length'] ?? strlen( $raw_response ) ) ),
            'raw_response_truncated'      => ! empty( $entry['raw_response_truncated'] ),
            'repaired_response'           => $repaired_response,
            'repaired_response_length'    => max( 0, absint( $entry['repaired_response_length'] ?? strlen( $repaired_response ) ) ),
            'repaired_response_truncated' => ! empty( $entry['repaired_response_truncated'] ),
            'latest_user_message'         => isset( $entry['latest_user_message'] ) && is_string( $entry['latest_user_message'] ) ? self::truncate_text( wp_strip_all_tags( $entry['latest_user_message'] ), 500 ) : '',
            'settings'                    => is_array( $entry['settings'] ?? null ) ? self::sanitize_value( $entry['settings'] ) : array(),
        );
    }

    /**
     * Sanitizes debug metadata while preserving structured arrays for inspection.
     *
     * @param mixed $value Raw value.
     * @return mixed
     */
    private static function sanitize_value( $value ) {
        if ( is_array( $value ) ) {
            $sanitized = array();
            foreach ( $value as $key => $item ) {
                $sanitized_key = is_int( $key ) ? $key : sanitize_key( (string) $key );
                $sanitized[ $sanitized_key ] = self::sanitize_value( $item );
            }

            return $sanitized;
        }

        if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
            return $value;
        }

        return is_scalar( $value ) ? wp_strip_all_tags( (string) $value ) : '';
    }

    /**
     * Truncates a raw response for option storage.
     *
     * @param string $payload Raw model text.
     * @return string
     */
    private static function truncate_payload( string $payload ): string {
        return self::truncate_text( $payload, self::PAYLOAD_LIMIT );
    }

    /**
     * Returns the latest user prompt for debug context.
     *
     * @param array<int,array<string,string>> $messages Conversation messages.
     * @return string
     */
    private static function get_latest_user_message( array $messages ): string {
        foreach ( array_reverse( $messages ) as $message ) {
            if ( is_array( $message ) && 'user' === ( $message['role'] ?? '' ) ) {
                $content = $message['content'] ?? '';
                if ( ! is_scalar( $content ) ) {
                    continue;
                }

                return self::truncate_text( wp_strip_all_tags( (string) $content ), 500 );
            }
        }

        return '';
    }

    /**
     * Generates a stable-ish debug response identifier.
     *
     * @return string
     */
    private static function generate_id(): string {
        if ( function_exists( 'wp_generate_uuid4' ) ) {
            return sanitize_key( wp_generate_uuid4() );
        }

        return sanitize_key( uniqid( 'fc_ai_', true ) );
    }
}
