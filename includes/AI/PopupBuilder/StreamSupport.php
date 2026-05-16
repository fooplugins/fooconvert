<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

defined( 'ABSPATH' ) || exit;

class StreamSupport {

    /**
     * Extracts assistant text deltas from a provider SSE event payload.
     *
     * @param \WP_AI_Client_SSE_Event $event Parsed SSE event.
     * @return string
     */
    public static function extract_delta_text( \WP_AI_Client_SSE_Event $event ): string {
        if ( $event->is_done() ) {
            return '';
        }

        $payload = $event->get_json_data();
        if ( ! is_array( $payload ) ) {
            return '';
        }

        if ( self::is_reasoning_summary_event( $event, $payload ) ) {
            return '';
        }

        $paths = array(
            array( 'choices', 0, 'delta', 'content' ),
            array( 'choices', 0, 'message', 'content' ),
            array( 'choices', 0, 'text' ),
            array( 'delta', 'text' ),
            array( 'delta', 'content' ),
            array( 'content_block', 'text' ),
            array( 'content_block', 'content' ),
            array( 'message', 'content' ),
            array( 'output_text' ),
            array( 'text' ),
            array( 'candidates', 0, 'content', 'parts', 0, 'text' ),
        );

        foreach ( $paths as $path ) {
            $value = self::get_nested_value( $payload, $path );
            $text  = self::normalize_text_value( $value );

            if ( '' !== $text ) {
                return $text;
            }
        }

        return '';
    }

    /**
     * Extracts provider-supplied reasoning summary deltas from stream events.
     *
     * @param \WP_AI_Client_SSE_Event $event Parsed SSE event.
     * @return string
     */
    public static function extract_reasoning_summary_delta( \WP_AI_Client_SSE_Event $event ): string {
        if ( $event->is_done() ) {
            return '';
        }

        $payload = $event->get_json_data();
        if ( ! is_array( $payload ) || ! self::is_reasoning_summary_event( $event, $payload ) ) {
            return '';
        }

        $event_type = self::get_event_type( $event, $payload );
        $value      = null;

        if ( 'response.reasoning_summary_text.delta' === $event_type ) {
            $value = $payload['delta'] ?? null;
        } elseif ( 'response.reasoning_summary_text.done' === $event_type ) {
            $value = $payload['text'] ?? $payload['summary'] ?? $payload['content'] ?? null;
        }

        return self::normalize_text_value( $value );
    }

    /**
     * Returns whether a stream event contains provider-supplied reasoning summary text.
     *
     * @param \WP_AI_Client_SSE_Event $event Parsed SSE event.
     * @param array<string,mixed>     $payload Decoded stream payload.
     * @return bool
     */
    private static function is_reasoning_summary_event( \WP_AI_Client_SSE_Event $event, array $payload ): bool {
        return in_array(
            self::get_event_type( $event, $payload ),
            array(
                'response.reasoning_summary_text.delta',
                'response.reasoning_summary_text.done',
            ),
            true
        );
    }

    /**
     * Returns the provider event type from a parsed stream event.
     *
     * @param \WP_AI_Client_SSE_Event $event Parsed SSE event.
     * @param array<string,mixed>     $payload Decoded stream payload.
     * @return string
     */
    private static function get_event_type( \WP_AI_Client_SSE_Event $event, array $payload ): string {
        if ( isset( $payload['type'] ) && is_string( $payload['type'] ) ) {
            return strtolower( trim( $payload['type'] ) );
        }

        $event_type = strtolower( trim( $event->get_event() ) );
        return '' !== $event_type ? $event_type : 'message';
    }

    /**
     * Returns a nested value from a streamed event payload when present.
     *
     * @param mixed                 $payload Stream payload.
     * @param array<int,int|string> $path Value path.
     * @return mixed|null
     */
    private static function get_nested_value( $payload, array $path ) {
        $current = $payload;

        foreach ( $path as $segment ) {
            if ( is_int( $segment ) ) {
                if ( ! is_array( $current ) || ! array_key_exists( $segment, $current ) ) {
                    return null;
                }
            } elseif ( ! is_array( $current ) || ! array_key_exists( $segment, $current ) ) {
                return null;
            }

            $current = $current[ $segment ];
        }

        return $current;
    }

    /**
     * Normalizes streamed payload values into plain text deltas.
     *
     * @param mixed $value Raw stream value.
     * @return string
     */
    private static function normalize_text_value( $value ): string {
        if ( is_string( $value ) ) {
            return $value;
        }

        if ( is_array( $value ) ) {
            $text = '';

            foreach ( $value as $part ) {
                if ( is_string( $part ) ) {
                    $text .= $part;
                    continue;
                }

                if ( ! is_array( $part ) ) {
                    continue;
                }

                foreach ( array( 'text', 'content', 'value' ) as $key ) {
                    if ( isset( $part[ $key ] ) && is_string( $part[ $key ] ) ) {
                        $text .= $part[ $key ];
                        break;
                    }
                }
            }

            return $text;
        }

        return '';
    }
}
