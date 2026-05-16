<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

use WP_AI_Client_Ability_Function_Resolver;
use WordPress\AiClient\Messages\DTO\Message;

defined( 'ABSPATH' ) || exit;

class ActivityLog {

    /**
     * Returns the activity item used when the builder is packing the request.
     *
     * @return array<string,string>
     */
    public static function preparing_context(): array {
        return array(
            'type'    => 'status',
            'label'   => __( 'Preparing popup context', 'fooconvert' ),
            'summary' => __( 'Packing the current brand, draft, media, and conversation into the request.', 'fooconvert' ),
        );
    }

    /**
     * Returns the activity item used when the AI model is being called.
     *
     * @return array<string,string>
     */
    public static function calling_ai_model(): array {
        return array(
            'type'    => 'status',
            'label'   => __( 'Calling AI model', 'fooconvert' ),
            'summary' => __( 'The model may request template, block, validation, or media tools before it answers.', 'fooconvert' ),
        );
    }

    /**
     * Returns the activity item used when the final popup draft is being built.
     *
     * @return array<string,string>
     */
    public static function building_popup_draft(): array {
        return array(
            'type'    => 'status',
            'label'   => __( 'Building popup draft', 'fooconvert' ),
            'summary' => __( 'Creating the initial popup draft from the request.', 'fooconvert' ),
        );
    }

    /**
     * Returns the activity item used when an existing popup draft is being updated.
     *
     * @return array<string,string>
     */
    public static function updating_popup_draft(): array {
        return array(
            'type'    => 'status',
            'label'   => __( 'Updating popup draft', 'fooconvert' ),
            'summary' => __( 'Applying the requested changes to the current popup draft.', 'fooconvert' ),
        );
    }

    /**
     * Returns the activity item used when the model needs a response-format retry.
     *
     * @return array<string,string>
     */
    public static function repairing_response_format(): array {
        return array(
            'type'    => 'status',
            'label'   => __( 'Repairing AI response format', 'fooconvert' ),
            'summary' => __( 'The model returned JSON that did not match the popup builder contract, so the builder requested one corrected JSON object.', 'fooconvert' ),
        );
    }

    /**
     * Returns the activity item used when an unsupported model parameter is disabled and retried.
     *
     * @param string $param Parameter name.
     * @return array<string,string>
     */
    public static function disabled_param_retry( string $param ): array {
        return array(
            'type'    => 'status',
            'label'   => sprintf(
                /* translators: %s: AI request parameter name. */
                __( 'Disabled unsupported AI parameter: %s', 'fooconvert' ),
                $param
            ),
            'summary' => __( 'The model rejected this optional request parameter, so it was added to Disabled Params and the chat request was retried.', 'fooconvert' ),
        );
    }

    /**
     * Appends one activity item to the canonical log and emits it when streaming.
     *
     * @param array<int,array<string,string>> $activity_log Activity log.
     * @param array<string,string>            $item Activity item.
     * @param array<string,callable>          $stream_callbacks Optional streaming callbacks.
     * @param string                          $callback_key Streaming callback key.
     * @return void
     */
    public static function append_item( array &$activity_log, array $item, array $stream_callbacks = array(), string $callback_key = 'on_activity' ): void {
        $activity_log[] = $item;
        self::maybe_emit_stream_item( $stream_callbacks, $callback_key, $item );
    }

    /**
     * Appends multiple activity items to the canonical log and emits them when streaming.
     *
     * @param array<int,array<string,string>> $activity_log Activity log.
     * @param array<int,array<string,string>> $items Activity items.
     * @param array<string,callable>          $stream_callbacks Optional streaming callbacks.
     * @param string                          $callback_key Streaming callback key.
     * @return void
     */
    public static function append_items( array &$activity_log, array $items, array $stream_callbacks = array(), string $callback_key = 'on_activity' ): void {
        foreach ( $items as $item ) {
            if ( is_array( $item ) ) {
                self::append_item( $activity_log, $item, $stream_callbacks, $callback_key );
            }
        }
    }

    /**
     * Extracts activity entries for model tool calls.
     *
     * @param Message $message Model message.
     * @return array<int,array<string,string>>
     */
    public static function get_message_ability_calls( Message $message ): array {
        $entries = array();

        foreach ( $message->getParts() as $part ) {
            if ( ! $part->getType()->isFunctionCall() ) {
                continue;
            }

            $function_call = $part->getFunctionCall();
            if ( ! $function_call ) {
                continue;
            }

            $function_name = (string) $function_call->getName();
            $ability_name  = '' !== $function_name
                ? WP_AI_Client_Ability_Function_Resolver::function_name_to_ability_name( $function_name )
                : __( 'unknown ability', 'fooconvert' );

            $entries[] = array(
                'type'    => 'tool_call',
                'label'   => $ability_name,
                'summary' => self::summarize_payload( $function_call->getArgs() ),
            );
        }

        return $entries;
    }

    /**
     * Extracts activity entries for model tool results.
     *
     * @param Message $message Ability response message.
     * @return array<int,array<string,string>>
     */
    public static function get_message_ability_results( Message $message ): array {
        $entries = array();

        foreach ( $message->getParts() as $part ) {
            if ( ! $part->getType()->isFunctionResponse() ) {
                continue;
            }

            $function_response = $part->getFunctionResponse();
            if ( ! $function_response ) {
                continue;
            }

            $function_name = (string) $function_response->getName();
            $ability_name  = '' !== $function_name
                ? WP_AI_Client_Ability_Function_Resolver::function_name_to_ability_name( $function_name )
                : __( 'unknown ability', 'fooconvert' );

            $entries[] = array(
                'type'    => 'tool_result',
                'label'   => $ability_name,
                'summary' => self::summarize_payload( $function_response->getResponse() ),
            );
        }

        return $entries;
    }

    /**
     * Emits one streaming status or activity item when requested.
     *
     * @param array<string,callable> $stream_callbacks Optional streaming callbacks.
     * @param string                 $callback_key Callback key.
     * @param array<string,string>   $item Stream item.
     * @return void
     */
    private static function maybe_emit_stream_item( array $stream_callbacks, string $callback_key, array $item ): void {
        if ( ! isset( $stream_callbacks[ $callback_key ] ) || ! is_callable( $stream_callbacks[ $callback_key ] ) ) {
            return;
        }

        call_user_func( $stream_callbacks[ $callback_key ], $item );
    }

    /**
     * Produces a short human-readable summary for tool call/result payloads.
     *
     * @param mixed $payload Tool payload.
     * @return string
     */
    private static function summarize_payload( $payload ): string {
        if ( is_array( $payload ) ) {
            if ( isset( $payload['error'] ) && is_string( $payload['error'] ) ) {
                return $payload['error'];
            }

            if ( isset( $payload['templates'] ) && is_array( $payload['templates'] ) ) {
                /* translators: %d: number of returned popup templates. */
                return sprintf( __( 'Returned %d templates', 'fooconvert' ), count( $payload['templates'] ) );
            }

            if ( isset( $payload['blocks'] ) && is_array( $payload['blocks'] ) ) {
                /* translators: %d: number of returned content blocks. */
                return sprintf( __( 'Returned %d blocks', 'fooconvert' ), count( $payload['blocks'] ) );
            }

            if ( isset( $payload['media_items'] ) && is_array( $payload['media_items'] ) ) {
                /* translators: %d: number of returned media items. */
                return sprintf( __( 'Returned %d media items', 'fooconvert' ), count( $payload['media_items'] ) );
            }

            if ( isset( $payload['validation']['score'] ) ) {
                /* translators: %d: validation score from 0 to 100. */
                return sprintf( __( 'Validation score %d/100', 'fooconvert' ), absint( $payload['validation']['score'] ) );
            }

            if ( isset( $payload['prompt'] ) && is_string( $payload['prompt'] ) ) {
                return __( 'Generated an image prompt', 'fooconvert' );
            }

            if ( isset( $payload['image'] ) && is_array( $payload['image'] ) ) {
                return __( 'Prepared a popup image', 'fooconvert' );
            }

            if ( isset( $payload['playbook'] ) && is_array( $payload['playbook'] ) ) {
                return __( 'Loaded the conversion playbook', 'fooconvert' );
            }

            $keys = array_slice( array_keys( $payload ), 0, 4 );
            if ( ! empty( $keys ) ) {
                return sprintf(
                    /* translators: %s: comma-separated payload keys */
                    __( 'Payload keys: %s', 'fooconvert' ),
                    implode( ', ', array_map( 'strval', $keys ) )
                );
            }
        }

        if ( is_string( $payload ) ) {
            $text = trim( wp_strip_all_tags( $payload ) );
            return DebugResponseLog::truncate_text( $text, 140 );
        }

        if ( is_bool( $payload ) ) {
            return $payload ? __( 'Completed successfully', 'fooconvert' ) : __( 'Returned false', 'fooconvert' );
        }

        if ( is_numeric( $payload ) ) {
            return (string) $payload;
        }

        return __( 'Completed', 'fooconvert' );
    }
}
