<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

use WP_Error;

defined( 'ABSPATH' ) || exit;

class ResponseParser {

    /**
     * Decodes a JSON response and returns the payload variant that was accepted.
     *
     * @param string $payload Raw model text.
     * @return array{response:?array<string,mixed>,decoded_payload:string,repair_type:string}
     */
    public static function decode_json_response_with_metadata( string $payload ): array {
        $payload = trim( $payload );

        $empty = array(
            'response'        => null,
            'decoded_payload' => '',
            'repair_type'     => '',
        );

        if ( '' === $payload ) {
            return $empty;
        }

        $attempts = array();
        self::add_json_decode_attempt( $attempts, $payload, '' );

        if ( 0 === strpos( $payload, '```' ) ) {
            $stripped_payload = preg_replace( '/^```(?:json)?\s*|\s*```$/', '', $payload );
            if ( is_string( $stripped_payload ) ) {
                self::add_json_decode_attempt( $attempts, trim( $stripped_payload ), 'markdown_fence' );
            }
        }

        $object_payload = self::extract_json_object_payload( $payload );
        if ( '' !== $object_payload ) {
            self::add_json_decode_attempt( $attempts, $object_payload, 'extracted_object' );
        }

        $completed_payload = self::complete_incomplete_json_object_payload( $payload );
        if ( '' !== $completed_payload ) {
            self::add_json_decode_attempt( $attempts, $completed_payload, 'completed_object' );
        }

        if ( isset( $stripped_payload ) && is_string( $stripped_payload ) ) {
            $completed_stripped_payload = self::complete_incomplete_json_object_payload( trim( $stripped_payload ) );
            if ( '' !== $completed_stripped_payload ) {
                self::add_json_decode_attempt( $attempts, $completed_stripped_payload, 'completed_markdown_fence_object' );
            }
        }

        foreach ( $attempts as $attempt ) {
            $decoded = json_decode( $attempt['payload'], true );
            if ( is_array( $decoded ) ) {
                return array(
                    'response'        => $decoded,
                    'decoded_payload' => $attempt['payload'],
                    'repair_type'     => $attempt['repair_type'],
                );
            }
        }

        return $empty;
    }

    /**
     * Normalizes useful external-LLM response variants into the builder contract.
     *
     * @param array<string,mixed> $response Decoded model response.
     * @return array<string,mixed>
     */
    public static function normalize_decoded_popup_response( array $response ): array {
        $normalized = $response;

        $aliases = array(
            'assistant_message'   => array( 'message', 'assistantMessage', 'assistant_response', 'assistantResponse' ),
            'clarifying_question' => array( 'question', 'clarifyingQuestion', 'clarifying_prompt', 'clarifyingPrompt' ),
            'suggested_prompts'   => array( 'suggestions', 'suggestedPrompts', 'followups', 'follow_ups' ),
            'media_items'         => array( 'media', 'mediaItems', 'images' ),
            'popup_draft'         => array( 'draft', 'popup', 'popupDraft', 'popup_blueprint', 'popupBlueprint', 'blueprint' ),
        );

        foreach ( $aliases as $target_key => $source_keys ) {
            if ( array_key_exists( $target_key, $normalized ) ) {
                continue;
            }

            foreach ( $source_keys as $source_key ) {
                if ( array_key_exists( $source_key, $normalized ) ) {
                    $normalized[ $target_key ] = $normalized[ $source_key ];
                    break;
                }
            }
        }

        if (
            ! array_key_exists( 'popup_draft', $normalized )
            && self::is_popup_draft_like_array( $response )
        ) {
            $normalized['popup_draft'] = $response;
        }

        return $normalized;
    }

    /**
     * Validates decoded model JSON before it is normalized into an empty-looking response.
     *
     * @param array<string,mixed> $response Decoded model response.
     * @param string              $payload Raw model text.
     * @return WP_Error|null
     */
    public static function validate_decoded_popup_response( array $response, string $payload ): ?WP_Error {
        $issues            = array();
        $missing_top_level = array_values( array_diff( self::get_expected_response_keys(), array_keys( $response ) ) );
        $clarifying_question = isset( $response['clarifying_question'] ) && is_string( $response['clarifying_question'] )
            ? trim( wp_strip_all_tags( $response['clarifying_question'] ) )
            : '';

        if ( ! array_key_exists( 'popup_draft', $response ) || null === $response['popup_draft'] ) {
            if ( '' === $clarifying_question ) {
                $issues[] = __( 'The response did not include popup_draft and did not ask a clarifying_question, so there is no popup to preview or show in Popup Details.', 'fooconvert' );
            }
        } elseif ( ! is_array( $response['popup_draft'] ) ) {
            $issues[] = __( 'popup_draft must be a JSON object or null.', 'fooconvert' );
        } else {
            $draft              = $response['popup_draft'];
            $missing_draft_keys = array_values( array_diff( self::get_expected_popup_draft_keys(), array_keys( $draft ) ) );
            $popup_type         = isset( $draft['popup_type'] ) && is_string( $draft['popup_type'] ) ? trim( $draft['popup_type'] ) : '';
            $content_blocks     = $draft['content_blocks'] ?? null;

            if ( ! empty( $missing_draft_keys ) ) {
                $issues[] = sprintf(
                    /* translators: %s: comma-separated missing popup draft keys. */
                    __( 'Missing popup_draft keys: %s.', 'fooconvert' ),
                    implode( ', ', $missing_draft_keys )
                );
            }

            if ( '' === $popup_type ) {
                $issues[] = __( 'popup_draft.popup_type is required so the builder knows whether to render a bar, flyout, or popup.', 'fooconvert' );
            }

            if ( ! is_array( $content_blocks ) || empty( $content_blocks ) ) {
                $issues[] = __( 'popup_draft.content_blocks must contain at least one supported block; otherwise the preview and Popup Details tabs are empty.', 'fooconvert' );
            }
        }

        if ( empty( $issues ) ) {
            return null;
        }

        return self::get_invalid_decoded_popup_response_error( $issues, $payload, $missing_top_level );
    }

    /**
     * Returns the final response contract that is repeated near the latest user turn.
     *
     * @return string
     */
    public static function get_final_response_format_requirement(): string {
        return sprintf(
            /* translators: 1: response keys. 2: popup draft keys. */
            __( 'Final response format requirement: return only one valid JSON object with these top-level keys: %1$s. Do not return an empty object. If you can build a popup, popup_draft must be a complete object containing these keys: %2$s, and popup_draft.content_blocks must contain at least one supported block. If you truly cannot build a popup, set popup_draft to null and put one specific question in clarifying_question. The first non-whitespace character must be `{`. Do not include Markdown headings, bullet lists, explanations, or fenced code outside the JSON object.', 'fooconvert' ),
            implode( ', ', self::get_expected_response_keys() ),
            implode( ', ', self::get_expected_popup_draft_keys() )
        );
    }

    /**
     * Builds the error returned when the model text is not valid response JSON.
     *
     * @param string $payload Raw model text.
     * @return WP_Error
     */
    public static function get_invalid_popup_response_error( string $payload ): WP_Error {
        $json_error     = self::get_json_response_decode_error( $payload );
        $problem_detail = self::get_invalid_response_problem_detail( $payload );
        $preview        = DebugResponseLog::get_response_preview( $payload );
        $expected_keys  = self::get_expected_response_keys();
        $format_hint    = __( 'Expected a single JSON object with no Markdown or prose outside it.', 'fooconvert' );
        $message        = __( 'The AI returned an invalid popup response.', 'fooconvert' );

        if ( '' !== $json_error ) {
            $message .= ' ' . sprintf(
                /* translators: %s: JSON parser error detail. */
                __( 'JSON parse detail: %s', 'fooconvert' ),
                $json_error
            );
        }

        if ( '' !== $problem_detail ) {
            $message .= ' ' . sprintf(
                /* translators: %s: AI response shape detail. */
                __( 'Problem detail: %s', 'fooconvert' ),
                $problem_detail
            );
        }

        $message .= ' ' . $format_hint . ' ' . sprintf(
            /* translators: %s: comma-separated expected JSON keys. */
            __( 'Expected top-level keys: %s.', 'fooconvert' ),
            implode( ', ', $expected_keys )
        );

        if ( '' !== $preview ) {
            $message .= ' ' . sprintf(
                /* translators: %s: clipped AI response preview. */
                __( 'Response preview: %s', 'fooconvert' ),
                $preview
            );
        }

        $data = array(
            'status' => 500,
        );

        if ( '' !== $json_error ) {
            $data['json_error'] = $json_error;
        }

        if ( '' !== $problem_detail ) {
            $data['problem_detail'] = $problem_detail;
        }

        $data['expected_top_level_keys'] = $expected_keys;
        $data['response_format_hint']    = $format_hint;

        if ( '' !== $preview ) {
            $data['response_preview'] = $preview;
        }

        return new WP_Error(
            'fooconvert_ai_popup_builder_invalid_json',
            $message,
            $data
        );
    }

    /**
     * Explains the response shape problem in user-facing terms.
     *
     * @param string $payload Raw model text.
     * @return string
     */
    public static function get_invalid_response_problem_detail( string $payload ): string {
        $payload = ltrim( $payload );

        if ( '' === $payload ) {
            return '';
        }

        if ( 0 === strpos( $payload, '```' ) ) {
            return __( 'The response was wrapped in Markdown fences, but the fenced content was not a valid JSON object.', 'fooconvert' );
        }

        if ( preg_match( '/^(#{1,6}\s+|\*\*|[-*]\s+)/', $payload ) ) {
            return __( 'The response appears to be Markdown or prose instead of the required machine-readable JSON object.', 'fooconvert' );
        }

        $first_character = substr( $payload, 0, 1 );

        if ( '{' === $first_character ) {
            return __( 'The response started as a JSON object but could not be parsed. It may contain trailing prose, invalid quoting, unescaped line breaks, or truncated JSON.', 'fooconvert' );
        }

        if ( '[' === $first_character ) {
            return __( 'The response starts with a JSON array, but the builder requires one JSON object.', 'fooconvert' );
        }

        return sprintf(
            /* translators: %s: first non-whitespace character in the AI response. */
            __( 'The response starts with "%s" instead of "{".', 'fooconvert' ),
            $first_character
        );
    }

    /**
     * Returns the error used when the model keeps requesting tools beyond the configured limit.
     *
     * @param int $max_tool_calls Current maximum tool-call rounds.
     * @return WP_Error
     */
    public static function get_iteration_limit_error( int $max_tool_calls ): WP_Error {
        $max_tool_calls = Settings::sanitize_max_tool_calls( $max_tool_calls );

        return new WP_Error(
            'fooconvert_ai_popup_builder_iteration_limit',
            sprintf(
                /* translators: %d: current maximum tool-call rounds. */
                __( 'The AI popup builder reached its tool-call limit before completing the popup. Current limit: %d. Increase the Max Tool Calls setting near Timeout in AI Popup Builder settings if this model needs more tool calls for complex prompts.', 'fooconvert' ),
                $max_tool_calls
            ),
            array(
                'status'         => 500,
                'max_tool_calls' => $max_tool_calls,
            )
        );
    }

    /**
     * Adds a unique JSON decode attempt.
     *
     * @param array<int,array{payload:string,repair_type:string}> $attempts Decode attempts.
     * @param string                                              $payload Payload variant.
     * @param string                                              $repair_type Repair type.
     * @return void
     */
    private static function add_json_decode_attempt( array &$attempts, string $payload, string $repair_type ): void {
        if ( '' === $payload ) {
            return;
        }

        foreach ( $attempts as $attempt ) {
            if ( $attempt['payload'] === $payload ) {
                return;
            }
        }

        $attempts[] = array(
            'payload'     => $payload,
            'repair_type' => $repair_type,
        );
    }

    /**
     * Extracts the first balanced JSON object from model text.
     *
     * @param string $payload Raw model text.
     * @return string
     */
    private static function extract_json_object_payload( string $payload ): string {
        $start = strpos( $payload, '{' );
        if ( false === $start ) {
            return '';
        }

        $length     = strlen( $payload );
        $depth      = 0;
        $in_string  = false;
        $is_escaped = false;

        for ( $index = $start; $index < $length; $index++ ) {
            $char = $payload[ $index ];

            if ( $in_string ) {
                if ( $is_escaped ) {
                    $is_escaped = false;
                    continue;
                }

                if ( '\\' === $char ) {
                    $is_escaped = true;
                    continue;
                }

                if ( '"' === $char ) {
                    $in_string = false;
                }

                continue;
            }

            if ( '"' === $char ) {
                $in_string = true;
                continue;
            }

            if ( '{' === $char ) {
                $depth++;
                continue;
            }

            if ( '}' !== $char ) {
                continue;
            }

            $depth--;
            if ( 0 === $depth ) {
                return substr( $payload, $start, $index - $start + 1 );
            }
        }

        return '';
    }

    /**
     * Appends missing structural JSON closers when the object is otherwise coherent.
     *
     * @param string $payload Raw model text.
     * @return string
     */
    private static function complete_incomplete_json_object_payload( string $payload ): string {
        $payload = trim( $payload );
        $start   = strpos( $payload, '{' );
        if ( false === $start ) {
            return '';
        }

        $candidate  = substr( $payload, $start );
        $length     = strlen( $candidate );
        $stack      = array();
        $in_string  = false;
        $is_escaped = false;

        for ( $index = 0; $index < $length; $index++ ) {
            $char = $candidate[ $index ];

            if ( $in_string ) {
                if ( $is_escaped ) {
                    $is_escaped = false;
                    continue;
                }

                if ( '\\' === $char ) {
                    $is_escaped = true;
                    continue;
                }

                if ( '"' === $char ) {
                    $in_string = false;
                }

                continue;
            }

            if ( '"' === $char ) {
                $in_string = true;
                continue;
            }

            if ( '{' === $char ) {
                $stack[] = '}';
                continue;
            }

            if ( '[' === $char ) {
                $stack[] = ']';
                continue;
            }

            if ( '}' !== $char && ']' !== $char ) {
                continue;
            }

            $expected = array_pop( $stack );
            if ( $expected !== $char ) {
                return '';
            }

            if ( empty( $stack ) ) {
                return '';
            }
        }

        if ( $in_string || $is_escaped || empty( $stack ) ) {
            return '';
        }

        return $candidate . implode( '', array_reverse( $stack ) );
    }

    /**
     * Checks whether a decoded object looks like a popup draft without the wrapper.
     *
     * @param array<string,mixed> $value Decoded JSON object.
     * @return bool
     */
    private static function is_popup_draft_like_array( array $value ): bool {
        if ( empty( $value ) ) {
            return false;
        }

        $draft_keys = self::get_expected_popup_draft_keys();
        $matches    = array_intersect( $draft_keys, array_keys( $value ) );

        return count( $matches ) >= 3
            || array_key_exists( 'popup_type', $value )
            || array_key_exists( 'content_blocks', $value )
            || array_key_exists( 'root_attributes', $value );
    }

    /**
     * Returns the expected top-level AI response keys.
     *
     * @return array<int,string>
     */
    private static function get_expected_response_keys(): array {
        return array(
            'assistant_message',
            'clarifying_question',
            'suggested_prompts',
            'media_items',
            'popup_draft',
        );
    }

    /**
     * Returns the popup draft keys needed for a renderable response.
     *
     * @return array<int,string>
     */
    private static function get_expected_popup_draft_keys(): array {
        return array(
            'title',
            'popup_type',
            'goal',
            'audience',
            'offer',
            'template_slug',
            'trigger',
            'root_attributes',
            'content_blocks',
            'conversion_rationale',
            'notes',
        );
    }

    /**
     * Builds the error returned when parsed JSON is not a usable popup response.
     *
     * @param array<int,string> $issues Response contract issues.
     * @param string            $payload Raw model text.
     * @param array<int,string> $missing_top_level Missing top-level response keys.
     * @return WP_Error
     */
    private static function get_invalid_decoded_popup_response_error( array $issues, string $payload, array $missing_top_level = array() ): WP_Error {
        $issues        = array_values( array_filter( array_map( 'strval', $issues ) ) );
        $preview       = DebugResponseLog::get_response_preview( $payload );
        $expected_keys = self::get_expected_response_keys();
        $message       = __( 'The AI returned valid JSON, but it was not a usable popup builder response.', 'fooconvert' );

        if ( ! empty( $issues ) ) {
            $message .= ' ' . sprintf(
                /* translators: %s: AI response shape detail. */
                __( 'Problem detail: %s', 'fooconvert' ),
                implode( ' ', $issues )
            );
        }

        if ( ! empty( $missing_top_level ) ) {
            $message .= ' ' . sprintf(
                /* translators: %s: comma-separated missing response keys. */
                __( 'Missing top-level keys: %s.', 'fooconvert' ),
                implode( ', ', $missing_top_level )
            );
        }

        $message .= ' ' . sprintf(
            /* translators: %s: comma-separated expected JSON keys. */
            __( 'Expected top-level keys: %s.', 'fooconvert' ),
            implode( ', ', $expected_keys )
        );

        if ( '' !== $preview ) {
            $message .= ' ' . sprintf(
                /* translators: %s: clipped AI response preview. */
                __( 'Response preview: %s', 'fooconvert' ),
                $preview
            );
        }

        $data = array(
            'status'                   => 500,
            'problem_detail'           => implode( ' ', $issues ),
            'response_contract_issues' => $issues,
            'expected_top_level_keys'  => $expected_keys,
        );

        if ( ! empty( $missing_top_level ) ) {
            $data['missing_top_level_keys'] = array_values( $missing_top_level );
        }

        if ( '' !== $preview ) {
            $data['response_preview'] = $preview;
        }

        return new WP_Error(
            'fooconvert_ai_popup_builder_invalid_response_contract',
            $message,
            $data
        );
    }

    /**
     * Returns the most useful JSON parser error for the response payload.
     *
     * @param string $payload Raw model text.
     * @return string
     */
    private static function get_json_response_decode_error( string $payload ): string {
        $payload = trim( $payload );

        if ( '' === $payload ) {
            return __( 'The response body was empty.', 'fooconvert' );
        }

        json_decode( $payload, true );
        $json_error = JSON_ERROR_NONE !== json_last_error() ? json_last_error_msg() : '';

        if ( 0 !== strpos( $payload, '```' ) ) {
            return $json_error;
        }

        $stripped_payload = preg_replace( '/^```(?:json)?\s*|\s*```$/', '', $payload );
        if ( ! is_string( $stripped_payload ) ) {
            return $json_error;
        }

        json_decode( trim( $stripped_payload ), true );
        $stripped_error = JSON_ERROR_NONE !== json_last_error() ? json_last_error_msg() : '';

        if ( '' === $stripped_error ) {
            return $json_error;
        }

        return sprintf(
            /* translators: %s: JSON parser error after removing Markdown fences. */
            __( 'After removing Markdown fences: %s', 'fooconvert' ),
            $stripped_error
        );
    }
}
