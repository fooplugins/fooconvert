<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

use FooPlugins\FooConvert\AI\Abilities;
use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\Catalog;
use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\DraftNormalizer as PopupBlueprint;
use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\Schema;
use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\Validator;
use FooPlugins\FooConvert\AI\PopupBuilder\Media\Attachments as PopupMedia;
use FooPlugins\FooConvert\AI\PopupBuilder\Media\DraftImages;
use FooPlugins\FooConvert\AI\PopupBuilder\Media\ImageGenerator;
use WP_AI_Client_Ability_Function_Resolver;
use WP_Error;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

defined( 'ABSPATH' ) || exit;

class ChatService {

    /**
     * Maximum media items returned to the builder.
     */
    private const MEDIA_ITEMS_LIMIT = 12;

    public function build_chat_response( array $chat_request, array $stream_callbacks = array() ) {
        $messages               = is_array( $chat_request['messages'] ?? null ) ? $chat_request['messages'] : array();
        $popup_draft            = is_array( $chat_request['popup_draft'] ?? null ) ? $chat_request['popup_draft'] : array();
        $existing_media         = is_array( $chat_request['existing_media'] ?? null ) ? $chat_request['existing_media'] : array();
        $brand                  = is_array( $chat_request['brand'] ?? null ) ? $chat_request['brand'] : array();
        $generate_images        = ! empty( $chat_request['generate_images'] );
        $force_image_generation = ! empty( $chat_request['force_image_generation'] );
        $settings               = $this->sanitize_ai_settings_payload( $chat_request['settings'] ?? array() );
        $activity_log           = array();
        $is_update_turn         = ! empty( $popup_draft );

        try {
            ImageGenerator::set_runtime_ai_settings( $settings );

            if ( ! $is_update_turn ) {
                ActivityLog::append_item(
                    $activity_log,
                    ActivityLog::preparing_context(),
                    $stream_callbacks,
                    'on_status'
                );
            }

            $response = $this->generate_ai_response(
                $messages,
                $popup_draft,
                $existing_media,
                $brand,
                $generate_images,
                $force_image_generation,
                $is_update_turn,
                $settings,
                $activity_log,
                $stream_callbacks
            );

            ImageGenerator::set_runtime_ai_settings( $settings );

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $response = $this->maybe_force_generate_popup_background(
                $response,
                $messages,
                $brand,
                $generate_images,
                $force_image_generation
            );

            $response = $this->maybe_force_generate_popup_image(
                $response,
                $messages,
                array_column( $existing_media, 'id' ),
                $force_image_generation
            );

            $response['media_items']  = PopupMedia::list_generated_images( self::MEDIA_ITEMS_LIMIT );
            $response['activity_log'] = $activity_log;
            $response['settings']     = $this->get_ai_settings_response( $settings );

            return $response;
        } finally {
            ImageGenerator::clear_runtime_ai_settings();
        }
    }

    /**
     * Generates a popup builder response from the AI client.
     *
     * @param array<int,array<string,string>> $messages Conversation messages.
     * @param array<string,mixed>             $popup_draft Current popup draft.
     * @param array<int,array<string,mixed>>  $media_items Existing generated popup media.
     * @param array<string,mixed>             $brand Brand context.
     * @param bool                            $generate_images Whether image generation is available for this turn.
     * @param bool                            $force_image_generation Whether this turn should explicitly generate a new image.
     * @param bool                            $is_update_turn Whether the request is revising an existing draft.
     * @param array<string,mixed>             $settings AI request settings.
     * @param array<int,array<string,string>> $activity_log Activity log.
     * @param array<string,callable>          $stream_callbacks Optional streaming callbacks.
     * @return array<string,mixed>|WP_Error
     */
    private function generate_ai_response( array $messages, array $popup_draft, array $media_items, array $brand, bool $generate_images, bool $force_image_generation, bool $is_update_turn, array &$settings, array &$activity_log, array $stream_callbacks = array() ) {
        $settings['selected_block_names'] = $this->sanitize_selected_block_names( $settings['selected_block_names'] ?? array() );
        Catalog::set_request_selected_block_names( $settings['selected_block_names'] );

        try {
            $history = PromptFactory::build_history(
                $messages,
                $popup_draft,
                $media_items,
                $brand,
                $generate_images,
                $force_image_generation,
                ResponseParser::get_final_response_format_requirement()
            );
            $abilities = Abilities::get_allowed_abilities( $generate_images || $force_image_generation );
            $resolver  = new WP_AI_Client_Ability_Function_Resolver( ...$abilities );

            ActivityLog::append_item(
                $activity_log,
                ActivityLog::calling_ai_model(),
                $stream_callbacks,
                'on_status'
            );

            $max_tool_calls = $this->sanitize_ai_max_tool_calls( $settings['max_tool_calls'] ?? $this->get_default_ai_max_tool_calls() );
            $response_format_retry_count = 0;

            for ( $iteration = 0; $iteration < $max_tool_calls; $iteration++ ) {
                $retry_count = 0;

                do {
                    $prompt = $this->build_prompt_from_settings(
                        $history,
                        $abilities,
                        $generate_images,
                        $force_image_generation,
                        $settings
                    );

                    $result = $this->generate_prompt_result( $prompt, $stream_callbacks, $settings );
                    if ( ! is_wp_error( $result ) ) {
                        break;
                    }

                    $unsupported_param = $this->extract_unsupported_parameter_from_error( $result );
                    if ( '' === $unsupported_param || $this->is_ai_param_disabled( $settings, $unsupported_param ) || $retry_count >= 4 ) {
                        return $result;
                    }

                    $settings = $this->add_disabled_param_to_request_settings( $settings, $unsupported_param );
                    ActivityLog::append_item(
                        $activity_log,
                        ActivityLog::disabled_param_retry( $unsupported_param ),
                        $stream_callbacks,
                        'on_activity'
                    );
                    $retry_count++;
                } while ( true );

                if ( is_wp_error( $result ) ) {
                    return $result;
                }

                $candidates = $result->getCandidates();
                if ( empty( $candidates ) ) {
                    return new WP_Error(
                        'fooconvert_ai_popup_builder_empty_result',
                        __( 'The AI client returned no candidates.', 'fooconvert' ),
                        array( 'status' => 500 )
                    );
                }

                $message   = $candidates[0]->getMessage();
                $history[] = $message;

                if ( $resolver->has_ability_calls( $message ) ) {
                    $ability_calls = ActivityLog::get_message_ability_calls( $message );
                    ActivityLog::append_items( $activity_log, $ability_calls, $stream_callbacks, 'on_activity' );
                    $ability_response = $resolver->execute_abilities( $message );
                    $ability_results = ActivityLog::get_message_ability_results( $ability_response );
                    ActivityLog::append_items( $activity_log, $ability_results, $stream_callbacks, 'on_activity' );
                    $history[] = $ability_response;
                    continue;
                }

                $response_text   = $result->toText();
                $decoded_details = ResponseParser::decode_json_response_with_metadata( $response_text );
                $response        = is_array( $decoded_details['response'] ?? null ) ? $decoded_details['response'] : null;
                if ( ! is_array( $response ) ) {
                    $response_error = ResponseParser::get_invalid_popup_response_error( $response_text );

                    if ( $iteration + 1 < $max_tool_calls && $this->maybe_retry_response_format( $history, $activity_log, $stream_callbacks, $response_format_retry_count, $response_text, ResponseParser::get_invalid_response_problem_detail( $response_text ) ) ) {
                        DebugResponseLog::log_invalid_response( $response_text, $response_error, $messages, $settings, 'invalid_json', $iteration + 1, true );
                        continue;
                    }

                    DebugResponseLog::log_invalid_response( $response_text, $response_error, $messages, $settings, 'invalid_json', $iteration + 1, false );

                    return $response_error;
                }

                $response = ResponseParser::normalize_decoded_popup_response( $response );

                $contract_error = ResponseParser::validate_decoded_popup_response( $response, $response_text );
                if ( $contract_error instanceof WP_Error ) {
                    $error_data     = $contract_error->get_error_data();
                    $problem_detail = is_array( $error_data ) ? (string) ( $error_data['problem_detail'] ?? '' ) : '';

                    if ( $iteration + 1 < $max_tool_calls && $this->maybe_retry_response_format( $history, $activity_log, $stream_callbacks, $response_format_retry_count, $response_text, $problem_detail ) ) {
                        DebugResponseLog::log_invalid_response( $response_text, $contract_error, $messages, $settings, 'invalid_contract', $iteration + 1, true, (string) ( $decoded_details['decoded_payload'] ?? '' ), (string) ( $decoded_details['repair_type'] ?? '' ) );
                        continue;
                    }

                    DebugResponseLog::log_invalid_response( $response_text, $contract_error, $messages, $settings, 'invalid_contract', $iteration + 1, false, (string) ( $decoded_details['decoded_payload'] ?? '' ), (string) ( $decoded_details['repair_type'] ?? '' ) );

                    return $contract_error;
                }

                if ( '' !== (string) ( $decoded_details['repair_type'] ?? '' ) ) {
                    DebugResponseLog::log_repaired_response(
                        $response_text,
                        (string) ( $decoded_details['decoded_payload'] ?? '' ),
                        $messages,
                        $settings,
                        (string) $decoded_details['repair_type'],
                        $iteration + 1
                    );
                }

                if ( is_array( $response['popup_draft'] ?? null ) ) {
                    ActivityLog::append_item(
                        $activity_log,
                        $is_update_turn
                            ? ActivityLog::updating_popup_draft()
                            : ActivityLog::building_popup_draft(),
                        $stream_callbacks,
                        'on_status'
                    );
                }

                return PopupBlueprint::sanitize_ai_response( $response, $settings['selected_block_names'] );
            }

            return ResponseParser::get_iteration_limit_error( $max_tool_calls );
        } finally {
            Catalog::clear_request_selected_block_names();
        }
    }

    /**
     * Generates one AI prompt result, optionally wrapped in the streaming adapter.
     *
     * @param \WP_AI_Client_Prompt_Builder $prompt Prompt builder.
     * @param array<string,callable>       $stream_callbacks Optional streaming callbacks.
     * @param array<string,mixed>          $settings AI request settings.
     * @return mixed
     */
    private function generate_prompt_result( \WP_AI_Client_Prompt_Builder $prompt, array $stream_callbacks, array $settings ) {
        if (
            ! isset( $stream_callbacks['on_assistant_delta'] )
            || ! is_callable( $stream_callbacks['on_assistant_delta'] )
            || ! Config::supports_streaming()
        ) {
            return $prompt->generate_text_result();
        }

        $stream_args = array(
            'streaming_enabled' => true,
            'payload_mutator'   => function ( array $payload ) use ( $settings ): array {
                $normalized = Settings::restore_streaming_schema_objects( $payload );
                $payload    = is_array( $normalized ) ? $normalized : $payload;

                return $this->remove_disabled_params_from_payload( $payload, $settings );
            },
            'on_event'          => function ( \WP_AI_Client_SSE_Event $event ) use ( $stream_callbacks ): void {
                $reasoning_delta = StreamSupport::extract_reasoning_summary_delta( $event );

                if (
                    '' !== $reasoning_delta
                    && isset( $stream_callbacks['on_reasoning_delta'] )
                    && is_callable( $stream_callbacks['on_reasoning_delta'] )
                ) {
                    call_user_func( $stream_callbacks['on_reasoning_delta'], $reasoning_delta );
                }

                $delta = StreamSupport::extract_delta_text( $event );

                if ( '' !== $delta ) {
                    call_user_func( $stream_callbacks['on_assistant_delta'], $delta );
                }
            },
        );

        if ( ! $this->is_ai_param_disabled( $settings, 'timeout' ) ) {
            $stream_args['request_timeout'] = $this->sanitize_ai_timeout( $settings['timeout'] ?? $this->get_default_ai_timeout() );
        }

        return wp_ai_client_stream(
            $prompt,
            $stream_args
        )->generate_text_result();
    }

    /**
     * Removes disabled parameters from a decoded streaming request payload.
     *
     * @param array<string,mixed> $payload Provider request payload.
     * @param array<string,mixed> $settings AI request settings.
     * @return array<string,mixed>
     */
    private function remove_disabled_params_from_payload( array $payload, array $settings ): array {
        $lookup = $this->get_disabled_param_lookup( $settings );

        if ( empty( $lookup ) ) {
            return $payload;
        }

        return $this->remove_disabled_params_from_array( $payload, $lookup );
    }

    /**
     * Recursively removes disabled parameter keys from a payload array.
     *
     * @param array<string|int,mixed> $payload Provider request payload.
     * @param array<string,bool>      $lookup Disabled parameter lookup.
     * @return array<string|int,mixed>
     */
    private function remove_disabled_params_from_array( array $payload, array $lookup ): array {
        foreach ( $payload as $key => $value ) {
            if ( is_string( $key ) ) {
                $normalized_key = $this->normalize_ai_param_name( $key );
                if ( isset( $lookup[ $normalized_key ] ) ) {
                    unset( $payload[ $key ] );
                    continue;
                }
            }

            if ( is_array( $value ) ) {
                $payload[ $key ] = $this->remove_disabled_params_from_array( $value, $lookup );
            }
        }

        return $payload;
    }

    /**
     * Returns the default AI request timeout in seconds.
     *
     * @return int
     */
    private function get_default_ai_timeout(): int {
        return Settings::get_default_timeout();
    }

    /**
     * Returns the default maximum number of tool-call rounds per response.
     *
     * @return int
     */
    private function get_default_ai_max_tool_calls(): int {
        return Settings::get_default_max_tool_calls();
    }

    /**
     * Normalizes a request parameter name.
     *
     * @param mixed $param Raw parameter name.
     * @return string
     */
    private function normalize_ai_param_name( $param ): string {
        return Settings::normalize_param_name( $param );
    }

    /**
     * Sanitizes a disabled parameter payload.
     *
     * @param mixed $value Raw disabled params payload.
     * @return array<int,string>
     */
    private function sanitize_disabled_ai_params( $value ): array {
        return Settings::sanitize_disabled_params( $value );
    }

    /**
     * Sanitizes an override model name.
     *
     * @param mixed $value Raw model value.
     * @return string
     */
    private function sanitize_ai_model_name( $value ): string {
        return Settings::sanitize_model( $value );
    }

    /**
     * Sanitizes an AI request timeout.
     *
     * @param mixed $value Raw timeout value.
     * @return int
     */
    private function sanitize_ai_timeout( $value ): int {
        return Settings::sanitize_timeout( $value );
    }

    /**
     * Sanitizes the maximum number of tool-call rounds.
     *
     * @param mixed $value Raw max tool calls value.
     * @return int
     */
    private function sanitize_ai_max_tool_calls( $value ): int {
        return Settings::sanitize_max_tool_calls( $value );
    }

    /**
     * Sanitizes selected block names for AI popup context.
     *
     * @param mixed $value Raw selected block names.
     * @return array<int,string>
     */
    private function sanitize_selected_block_names( $value ): array {
        return Settings::sanitize_selected_block_names( $value );
    }

    /**
     * Returns saved AI popup builder settings when available.
     *
     * @return array<string,mixed>
     */
    private function get_saved_ai_settings(): array {
        return Settings::get();
    }

    /**
     * Sanitizes AI popup builder settings from a mixed payload.
     *
     * @param mixed $payload Raw settings payload.
     * @return array<string,mixed>
     */
    private function sanitize_ai_settings_payload( $payload ): array {
        return Settings::sanitize_payload( $payload );
    }

    /**
     * Builds a prompt builder with the optional AI settings applied.
     *
     * @param array<int,Message>             $history Prompt history.
     * @param array<int,mixed>               $abilities Allowed AI abilities.
     * @param bool                           $generate_images Whether image generation is available for this turn.
     * @param bool                           $force_image_generation Whether this turn should explicitly generate a new image.
     * @param array<string,mixed>            $settings AI request settings.
     * @return \WP_AI_Client_Prompt_Builder
     */
    private function build_prompt_from_settings( array $history, array $abilities, bool $generate_images, bool $force_image_generation, array $settings ): \WP_AI_Client_Prompt_Builder {
        $prompt = wp_ai_client_prompt();
        $prompt = $prompt->with_history( ...$history );

        if ( ! $this->is_ai_param_disabled( $settings, 'temperature' ) && method_exists( $prompt, 'using_temperature' ) ) {
            $prompt = $prompt->using_temperature( 0.35 );
        }

        if ( ! $this->is_ai_param_disabled( $settings, 'system_instruction' ) && method_exists( $prompt, 'using_system_instruction' ) ) {
            $prompt = $prompt->using_system_instruction( PromptFactory::build_system_instruction( $generate_images, $force_image_generation, $settings['selected_block_names'] ?? array() ) );
        }

        if ( ! $this->is_ai_param_disabled( $settings, 'tools' ) && method_exists( $prompt, 'using_abilities' ) ) {
            $prompt = $prompt->using_abilities( ...$abilities );
        }

        if ( ! $this->is_ai_param_disabled( $settings, 'response_format' ) && method_exists( $prompt, 'as_json_response' ) ) {
            $prompt = $prompt->as_json_response( Schema::get_assistant_response_schema( $settings['selected_block_names'] ?? array() ) );
        }

        return $this->apply_prompt_request_settings( $prompt, $settings );
    }

    /**
     * Applies model and timeout settings to the prompt builder when supported.
     *
     * @param \WP_AI_Client_Prompt_Builder $prompt Prompt builder.
     * @param array<string,mixed>          $settings AI request settings.
     * @return \WP_AI_Client_Prompt_Builder
     */
    private function apply_prompt_request_settings( \WP_AI_Client_Prompt_Builder $prompt, array $settings ): \WP_AI_Client_Prompt_Builder {
        $model = $this->sanitize_ai_model_name( $settings['override_model'] ?? '' );
        if ( '' !== $model && ! $this->is_ai_param_disabled( $settings, 'model' ) && method_exists( $prompt, 'using_model_preference' ) ) {
            $prompt = $prompt->using_model_preference( $model );
        }

        if ( $this->is_ai_param_disabled( $settings, 'timeout' ) || ! class_exists( RequestOptions::class ) || ! method_exists( $prompt, 'using_request_options' ) ) {
            return $prompt;
        }

        $request_options = new RequestOptions();
        if ( method_exists( $request_options, 'setTimeout' ) ) {
            $request_options->setTimeout( $this->sanitize_ai_timeout( $settings['timeout'] ?? $this->get_default_ai_timeout() ) );
        }

        return $prompt->using_request_options( $request_options );
    }

    /**
     * Extracts an unsupported request parameter from a model error.
     *
     * @param WP_Error $error Error response.
     * @return string
     */
    private function extract_unsupported_parameter_from_error( WP_Error $error ): string {
        $message = $error->get_error_message();
        $patterns = array(
            '/Unsupported parameter:\s*[\'"]([^\'"]+)[\'"]/i',
            '/Unsupported parameter\s+([a-z0-9_.-]+)/i',
            '/unsupported[^.]*parameter[^\'"]*[\'"]([^\'"]+)[\'"]/i',
            '/[\'"]([^\'"]+)[\'"]\s+is not supported with this model/i',
        );

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $message, $matches ) ) {
                return $this->normalize_ai_param_name( $matches[1] ?? '' );
            }
        }

        return '';
    }

    /**
     * Adds a disabled parameter to in-memory settings and persists the saved disabled list.
     *
     * @param array<string,mixed> $settings AI request settings.
     * @param string              $param Parameter name.
     * @return array<string,mixed>
     */
    private function add_disabled_param_to_request_settings( array $settings, string $param ): array {
        $param = $this->normalize_ai_param_name( $param );
        if ( '' === $param ) {
            return $settings;
        }

        $settings = $this->sanitize_ai_settings_payload( $settings );
        $settings['disabled_params'][] = $param;
        $settings['disabled_params'] = $this->sanitize_disabled_ai_params( $settings['disabled_params'] );
        $settings['disabled_params_text'] = implode( "\n", $settings['disabled_params'] );

        if ( ! Settings::can_manage_settings() ) {
            return $settings;
        }

        $saved_settings = $this->get_saved_ai_settings();
        $saved_settings['disabled_params'] = $this->sanitize_disabled_ai_params(
            array_merge(
                is_array( $saved_settings['disabled_params'] ?? null ) ? $saved_settings['disabled_params'] : array(),
                $settings['disabled_params']
            )
        );
        $saved_settings['disabled_params_text'] = implode( "\n", $saved_settings['disabled_params'] );
        Settings::save( $saved_settings );

        return $settings;
    }

    /**
     * Formats settings for the REST response.
     *
     * @param array<string,mixed> $settings AI request settings.
     * @return array<string,mixed>
     */
    private function get_ai_settings_response( array $settings ): array {
        return Settings::to_response( $settings );
    }

    /**
     * Checks whether a prompt parameter is currently disabled.
     *
     * @param array<string,mixed> $settings AI request settings.
     * @param string              $param Parameter name.
     * @return bool
     */
    private function is_ai_param_disabled( array $settings, string $param ): bool {
        return Settings::is_param_disabled( $settings, $param );
    }

    /**
     * Builds a disabled parameter lookup with known provider aliases.
     *
     * @param array<string,mixed> $settings AI request settings.
     * @return array<string,bool>
     */
    private function get_disabled_param_lookup( array $settings ): array {
        return Settings::get_disabled_param_lookup( $settings );
    }

    /**
     * Sanitizes the UI message payload.
     *
     * @param mixed $messages Message payload.
     * @return array<int,array<string,string>>
     */
    public function sanitize_messages( $messages ): array {
        if ( ! is_array( $messages ) ) {
            return array();
        }

        $sanitized = array();
        $messages  = array_slice( array_values( $messages ), -12 );

        foreach ( $messages as $message ) {
            if ( ! is_array( $message ) ) {
                continue;
            }

            $role = isset( $message['role'] ) && 'assistant' === $message['role'] ? 'assistant' : 'user';
            $content = isset( $message['content'] ) && is_string( $message['content'] )
                ? trim( wp_strip_all_tags( $message['content'] ) )
                : '';

            if ( '' === $content ) {
                continue;
            }

            $sanitized[] = array(
                'role'    => $role,
                'content' => DebugResponseLog::truncate_text( $content, 2400 ),
            );
        }

        return $sanitized;
    }

    /**
     * Gives the model one corrective turn when the final response is not usable.
     *
     * @param array<int,Message>                 $history Prompt history.
     * @param array<int,array<string,string>>    $activity_log Activity log.
     * @param array<string,callable>             $stream_callbacks Optional streaming callbacks.
     * @param int                                $retry_count Current response-format retry count.
     * @param string                             $payload Raw model text.
     * @param string                             $problem_detail Response problem detail.
     * @return bool
     */
    private function maybe_retry_response_format( array &$history, array &$activity_log, array $stream_callbacks, int &$retry_count, string $payload, string $problem_detail = '' ): bool {
        if ( $retry_count >= 1 ) {
            return false;
        }

        $retry_count++;

        $history[] = new UserMessage(
            array(
                new MessagePart(
                    $this->get_response_format_retry_message( $payload, $problem_detail )
                ),
            )
        );

        ActivityLog::append_item(
            $activity_log,
            ActivityLog::repairing_response_format(),
            $stream_callbacks,
            'on_activity'
        );

        return true;
    }

    /**
     * Builds the corrective prompt used after an unusable model response.
     *
     * @param string $payload Raw model text.
     * @param string $problem_detail Response problem detail.
     * @return string
     */
    private function get_response_format_retry_message( string $payload, string $problem_detail = '' ): string {
        $message = __( 'The previous response cannot be used by the popup builder.', 'fooconvert' );

        if ( '' !== $problem_detail ) {
            $message .= ' ' . sprintf(
                /* translators: %s: AI response shape detail. */
                __( 'Problem detail: %s', 'fooconvert' ),
                $problem_detail
            );
        }

        $preview = DebugResponseLog::get_response_preview( $payload, 320 );
        if ( '' !== $preview ) {
            $message .= ' ' . sprintf(
                /* translators: %s: clipped AI response preview. */
                __( 'Previous response preview: %s', 'fooconvert' ),
                $preview
            );
        }

        return $message . "\n\n" . ResponseParser::get_final_response_format_requirement();
    }

    /**
     * Ensures submit-time image generation creates a popup background when the draft does not have one yet.
     *
     * @param array<string,mixed>             $response Builder response.
     * @param array<int,array<string,string>> $messages Conversation messages.
     * @param array<string,mixed>             $brand Brand payload.
     * @param bool                            $generate_images Whether image generation is enabled for the turn.
     * @param bool                            $force_image_generation Whether this turn explicitly requires a popup image.
     * @return array<string,mixed>
     */
    private function maybe_force_generate_popup_background( array $response, array $messages, array $brand, bool $generate_images, bool $force_image_generation ): array {
        if ( ! $generate_images || $force_image_generation || ! is_array( $response['popup_draft'] ?? null ) ) {
            return $response;
        }

        if ( DraftImages::popup_draft_has_background( $response['popup_draft'] ) ) {
            return $response;
        }

        $latest_user_message = '';
        foreach ( array_reverse( $messages ) as $message ) {
            if ( 'user' === $message['role'] ) {
                $latest_user_message = $message['content'];
                break;
            }
        }

        $generated_background = ImageGenerator::generate_popup_background( $response['popup_draft'], $brand, $latest_user_message );
        if ( is_wp_error( $generated_background ) || empty( $generated_background['image'] ) || ! is_array( $generated_background['image'] ) ) {
            return $response;
        }

        $response['popup_draft'] = DraftImages::inject_background_into_popup_draft( $response['popup_draft'], $generated_background['image'] );
        $response['validation']  = Validator::evaluate_popup_draft( $response['popup_draft'] );
        $response['assistant_message'] = trim(
            implode(
                ' ',
                array_filter(
                    array(
                        $response['assistant_message'] ?? '',
                        __( 'I also generated a popup background and applied it to the draft.', 'fooconvert' ),
                    )
                )
            )
        );

        return $response;
    }

    /**
     * Ensures a force-image request creates at least one new popup image.
     *
     * @param array<string,mixed>           $response Builder response.
     * @param array<int,array<string,string>> $messages Conversation messages.
     * @param array<int,int|string>         $existing_media_ids Media IDs present before the request.
     * @param bool                          $force_image_generation Whether a new image is required.
     * @return array<string,mixed>
     */
    private function maybe_force_generate_popup_image( array $response, array $messages, array $existing_media_ids, bool $force_image_generation ): array {
        if ( ! $force_image_generation || ! is_array( $response['popup_draft'] ?? null ) ) {
            return $response;
        }

        $latest_media_ids = array_column( PopupMedia::list_generated_images( self::MEDIA_ITEMS_LIMIT ), 'id' );
        $new_media_ids    = array_diff( array_map( 'intval', $latest_media_ids ), array_map( 'intval', $existing_media_ids ) );

        if ( ! empty( $new_media_ids ) ) {
            return $response;
        }

        $latest_user_message = '';
        foreach ( array_reverse( $messages ) as $message ) {
            if ( 'user' === $message['role'] ) {
                $latest_user_message = $message['content'];
                break;
            }
        }

        $generated_media = ImageGenerator::generate_popup_media( $response['popup_draft'], $latest_user_message );
        if ( is_wp_error( $generated_media ) || empty( $generated_media['image'] ) || ! is_array( $generated_media['image'] ) ) {
            return $response;
        }

        $response['popup_draft'] = DraftImages::inject_media_into_popup_draft( $response['popup_draft'], $generated_media['image'] );
        $response['validation']  = Validator::evaluate_popup_draft( $response['popup_draft'] );
        $response['assistant_message'] = trim(
            implode(
                ' ',
                array_filter(
                    array(
                        $response['assistant_message'] ?? '',
                        __( 'I also generated a popup image and wired it into the draft.', 'fooconvert' ),
                    )
                )
            )
        );

        return $response;
    }

}
