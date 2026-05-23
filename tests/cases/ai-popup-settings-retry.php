<?php
declare(strict_types=1);

namespace WordPress\AiClient\Messages\DTO {
    class Message {
        protected array $parts;

        public function __construct( array $parts = array() ) {
            $this->parts = $parts;
        }

        public function getParts(): array {
            return $this->parts;
        }
    }

    class MessagePart {
        private string $text;

        public function __construct( string $text = '' ) {
            $this->text = $text;
        }

        public function getText(): string {
            return $this->text;
        }
    }

    class ModelMessage extends Message {}

    class UserMessage extends Message {}
}

namespace WordPress\AiClient\Providers\Http\DTO {
    class RequestOptions {
        private int $timeout = 0;

        public function setTimeout( int $timeout ): void {
            $this->timeout = $timeout;
            $GLOBALS['fc_popup_builder_timeouts'][] = $timeout;
        }

        public function getTimeout(): int {
            return $this->timeout;
        }
    }
}

namespace FooPlugins\FooConvert\AI {
    class Abilities {
        public static function get_allowed_abilities( bool $enable_images = false ): array {
            return array( 'fooconvert/test_ability' );
        }
    }
}

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint {
    class DraftNormalizer {
        public static function get_assistant_response_schema( ?array $selected_block_names = null ): array {
            return array( 'type' => 'object' );
        }

        public static function get_assistant_response_contract(): string {
            return 'Return JSON.';
        }

        public static function sanitize_selected_block_names( $value ): array {
            return is_array( $value ) ? array_values( array_filter( array_map( 'strval', $value ) ) ) : array();
        }

        public static function get_default_selected_block_names(): array {
            return array();
        }

        public static function set_request_selected_block_names( $selected_block_names ): void {}

        public static function clear_request_selected_block_names(): void {}

        public static function sanitize_ai_response( array $response ): array {
            return $response;
        }

        public static function get_conversion_playbook(): array {
            return array(
                'principles' => array( 'Focus on one CTA.' ),
            );
        }
    }
}

namespace FooPlugins\FooConvert\AI\PopupBuilder\Media {
    class Attachments {
        public static function set_runtime_ai_settings( array $settings ): void {}

        public static function clear_runtime_ai_settings(): void {}

        public static function list_generated_images( int $limit ): array {
            return array();
        }
    }
}

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\ChatService;
    use FooPlugins\FooConvert\Tests\Support\Assertions;
    use WordPress\AiClient\Messages\DTO\Message;

    class WP_Error {
        private string $code;
        private string $message;
        private array $data;

        public function __construct( string $code, string $message, array $data = array() ) {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code(): string {
            return $this->code;
        }

        public function get_error_message(): string {
            return $this->message;
        }

        public function get_error_data(): array {
            return $this->data;
        }
    }

    class WP_AI_Client_Ability_Function_Resolver {
        public function __construct( string ...$abilities ) {}

        public function has_ability_calls( Message $message ): bool {
            return false;
        }
    }

    class PopupBuilderPromptResultStub {
        public function getCandidates(): array {
            return array(
                new class {
                    public function getMessage(): Message {
                        return new Message();
                    }
                },
            );
        }

        public function toText(): string {
            return json_encode(
                array(
                    'assistant_message' => 'Here is a popup direction.',
                    'clarifying_question' => '',
                    'suggested_prompts' => array(),
                    'media_items' => array(),
                    'popup_draft'       => array(
                        'title'                => 'Launch Offer',
                        'popup_type'           => 'popup',
                        'goal'                 => 'Promote the launch.',
                        'audience'             => 'Visitors',
                        'offer'                => 'Launch offer',
                        'template_slug'        => '',
                        'trigger'              => array(),
                        'root_attributes'      => array(),
                        'content_blocks'       => array(
                            array(
                                'name' => 'core/paragraph',
                            ),
                        ),
                        'conversion_rationale' => array(),
                        'notes'                => array(),
                    ),
                )
            );
        }
    }

    class WP_AI_Client_Prompt_Builder {}

    class PopupBuilderPromptStub extends WP_AI_Client_Prompt_Builder {
        private int $index;

        public function __construct() {
            $this->index = count( $GLOBALS['fc_popup_builder_prompt_calls'] ?? array() );
            $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ] = array(
                'temperature'     => false,
                'response_format' => false,
                'tools'           => false,
                'model'           => '',
                'timeout'         => 0,
            );
        }

        public function with_history( ...$history ): self {
            return $this;
        }

        public function using_temperature( float $temperature ): self {
            $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['temperature'] = true;
            return $this;
        }

        public function using_system_instruction( string $instruction ): self {
            return $this;
        }

        public function using_abilities( ...$abilities ): self {
            $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['tools'] = ! empty( $abilities );
            return $this;
        }

        public function as_json_response( ?array $schema = null ): self {
            $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['response_format'] = true;
            return $this;
        }

        public function using_model_preference( string ...$models ): self {
            $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['model'] = $models[0] ?? '';
            return $this;
        }

        public function using_request_options( $request_options ): self {
            $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['timeout'] = method_exists( $request_options, 'getTimeout' )
                ? $request_options->getTimeout()
                : 0;
            return $this;
        }

        public function generate_text_result() {
            $call_count = (int) ( $GLOBALS['fc_popup_builder_generate_count'] ?? 0 );
            $GLOBALS['fc_popup_builder_generate_count'] = $call_count + 1;
            $mode = (string) ( $GLOBALS['fc_popup_builder_prompt_mode'] ?? '' );

            if ( 'openai_missing_output_error' === $mode ) {
                return new WP_Error(
                    'ai_client_error',
                    'Unexpected OpenAI API response: Missing the "output" key.'
                );
            }

            if ( 'anthropic_missing_output_error' === $mode ) {
                return new WP_Error(
                    'ai_client_error',
                    'Unexpected Anthropic API response: Missing the "output" key.'
                );
            }

            if ( 'openai_missing_output_exception' === $mode ) {
                throw new \RuntimeException( 'Unexpected OpenAI API response: Missing the "output" key.' );
            }

            if ( 'openrouter_missing_choices_always_error' === $mode ) {
                return new WP_Error(
                    'ai_client_error',
                    'Unexpected OpenRouter API response: Missing the "choices" key.'
                );
            }

            if (
                'openrouter_missing_choices_error' === $mode
                && (
                    ! empty( $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['response_format'] )
                    || ! empty( $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['tools'] )
                )
            ) {
                return new WP_Error(
                    'ai_client_error',
                    'Unexpected OpenRouter API response: Missing the "choices" key.'
                );
            }

            if (
                'no_models_for_optional_params' === $mode
                && (
                    ! empty( $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['response_format'] )
                    || ! empty( $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['tools'] )
                )
            ) {
                return new WP_Error(
                    'prompt_invalid_argument',
                    'No models found that support text_generation for this prompt.'
                );
            }

            if ( 0 === $call_count && ! empty( $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['temperature'] ) ) {
                return new WP_Error(
                    'unsupported_parameter',
                    "Unsupported parameter: 'temperature' is not supported with this model."
                );
            }

            return new PopupBuilderPromptResultStub();
        }
    }

    class PopupBuilderStreamingPromptStub {
        private PopupBuilderPromptStub $prompt;
        private array $stream_args;

        public function __construct( PopupBuilderPromptStub $prompt, array $stream_args ) {
            $this->prompt      = $prompt;
            $this->stream_args = $stream_args;
        }

        public function generate_text_result() {
            if ( 'streaming_missing_choices_with_text' !== ( $GLOBALS['fc_popup_builder_prompt_mode'] ?? '' ) ) {
                return $this->prompt->generate_text_result();
            }

            $chunks = array(
                '{"assistant_message":"Built from stream.","clarifying_question":"","suggested_prompts":[],"media_items":[],"popup_draft":{"title":"Stream Offer","popup_type":"popup","goal":"Recover the streamed response.","audience":"Visitors","offer":"Streamed offer","template_slug":"","trigger":{},"root_attributes":{},"content_blocks":[{"name":"core/paragraph"}],"conversion_rationale":[],"notes":[]}}',
            );

            foreach ( $chunks as $chunk ) {
                if ( isset( $this->stream_args['on_event'] ) && is_callable( $this->stream_args['on_event'] ) ) {
                    call_user_func(
                        $this->stream_args['on_event'],
                        new WP_AI_Client_SSE_Event(
                            'message',
                            json_encode(
                                array(
                                    'choices' => array(
                                        array(
                                            'delta' => array(
                                                'content' => $chunk,
                                            ),
                                        ),
                                    ),
                                )
                            )
                        )
                    );
                }
            }

            return new WP_Error(
                'wp_ai_client_stream_error',
                'Unexpected OpenRouter API response: Missing the "choices" key.'
            );
        }
    }

    class WP_AI_Client_SSE_Event {
        private string $event;
        private string $data;

        public function __construct( string $event, string $data ) {
            $this->event = $event;
            $this->data  = $data;
        }

        public function get_event(): string {
            return $this->event;
        }

        public function is_done(): bool {
            return '[DONE]' === $this->data;
        }

        public function get_json_data() {
            return json_decode( $this->data, true );
        }
    }

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function current_user_can( string $capability ): bool {
        if ( 'manage_options' === $capability ) {
            return (bool) ( $GLOBALS['fc_popup_builder_can_manage_options'] ?? true );
        }

        return true;
    }

    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}

    function wp_ai_client_prompt(): PopupBuilderPromptStub {
        return new PopupBuilderPromptStub();
    }

    function wp_ai_client_stream( WP_AI_Client_Prompt_Builder $prompt, array $stream_args = array() ): PopupBuilderStreamingPromptStub {
        return new PopupBuilderStreamingPromptStub( $prompt, $stream_args );
    }

    function wp_json_encode( $value, int $flags = 0 ): string {
        return json_encode( $value, $flags );
    }

    function wp_strip_all_tags( $value ): string {
        return strip_tags( (string) $value );
    }

    function absint( $value ): int {
        return abs( (int) $value );
    }

    function is_wp_error( $thing ): bool {
        return $thing instanceof WP_Error;
    }

    function sanitize_text_field( $value ): string {
        return is_string( $value ) ? trim( strip_tags( $value ) ) : '';
    }

    function sanitize_key( $value ): string {
        return strtolower( preg_replace( '/[^a-z0-9_\\-]/i', '', (string) $value ) ?? '' );
    }

    function fooconvert_get_setting( string $key, $default = null ) {
        $settings = $GLOBALS['fc_popup_builder_saved_settings'] ?? array();
        return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
    }

    function fooconvert_get_settings(): array {
        return $GLOBALS['fc_popup_builder_saved_settings'] ?? array();
    }

    function update_option( string $option, $value, $autoload = null ): bool {
        $GLOBALS['fc_popup_builder_saved_settings'] = $value;
        return true;
    }

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
    }

    if ( ! defined( 'FOOCONVERT_OPTION_DATA' ) ) {
        define( 'FOOCONVERT_OPTION_DATA', 'fooconvert_settings' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL', 'ai_popup_builder_override_model' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL', 'ai_popup_builder_override_image_model' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS', 'ai_popup_builder_disabled_params' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OPTIMIZE_IMAGE_OUTPUT', 'ai_popup_builder_optimize_image_output' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_TIMEOUT', 'ai_popup_builder_timeout' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_MAX_TOOL_CALLS', 'ai_popup_builder_max_tool_calls' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_SELECTED_BLOCKS', 'ai_popup_builder_selected_blocks' );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Config.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';

    $builder = new ChatService();
    $reflection = new \ReflectionMethod( ChatService::class, 'build_chat_response' );
    $reflection->setAccessible( true );

    $request_payload = array(
        'messages'               => array(
            array(
                'role'    => 'user',
                'content' => 'Build a launch popup.',
            ),
        ),
        'popup_draft'            => array(),
        'existing_media'         => array(),
        'brand'                  => array(),
        'generate_images'        => false,
        'force_image_generation' => false,
        'settings'               => array(
            'override_model'  => 'custom-chat-model',
            'disabled_params' => array(),
            'timeout'         => 12,
            'max_tool_calls'  => 8,
        ),
    );

    $GLOBALS['fc_popup_builder_can_manage_options'] = true;

    $response = $reflection->invoke( $builder, $request_payload );

    Assertions::true(
        is_array( $response ),
        'The chat response should succeed after retrying without an unsupported parameter.'
    );

    Assertions::same(
        2,
        (int) ( $GLOBALS['fc_popup_builder_generate_count'] ?? 0 ),
        'The prompt should be retried once after the unsupported parameter error.'
    );

    Assertions::true(
        ! empty( $GLOBALS['fc_popup_builder_prompt_calls'][0]['temperature'] ),
        'The first request should include temperature before the model rejects it.'
    );

    Assertions::false(
        ! empty( $GLOBALS['fc_popup_builder_prompt_calls'][1]['temperature'] ),
        'The retried request should omit the auto-disabled temperature parameter.'
    );

    Assertions::same(
        array( 'custom-chat-model', 'custom-chat-model' ),
        array_column( $GLOBALS['fc_popup_builder_prompt_calls'], 'model' ),
        'The override model should be passed into both the initial request and retry.'
    );

    Assertions::same(
        array( 12, 12 ),
        array_column( $GLOBALS['fc_popup_builder_prompt_calls'], 'timeout' ),
        'The configured timeout should be passed into both the initial request and retry.'
    );

    Assertions::same(
        array( 'temperature' ),
        $response['settings']['disabledParams'],
        'The unsupported parameter should be returned in response settings.'
    );

    Assertions::same(
        8,
        $response['settings']['maxToolCalls'],
        'The configured max tool-call setting should be returned in response settings.'
    );

    Assertions::same(
        array( 'temperature' ),
        \FooPlugins\FooConvert\AI\PopupBuilder\Settings::get()['disabled_params'],
        'The unsupported parameter should be persisted to saved Disabled Params.'
    );

    $GLOBALS['fc_popup_builder_generate_count']      = 0;
    $GLOBALS['fc_popup_builder_prompt_calls']        = array();
    $GLOBALS['fc_popup_builder_saved_settings']      = array();
    $GLOBALS['fc_popup_builder_can_manage_options'] = false;

    $response_without_settings_cap = $reflection->invoke( $builder, $request_payload );

    Assertions::same(
        array( 'temperature' ),
        $response_without_settings_cap['settings']['disabledParams'],
        'The unsupported parameter should still be disabled for the current retry request.'
    );

    Assertions::same(
        array(),
        \FooPlugins\FooConvert\AI\PopupBuilder\Settings::get()['disabled_params'],
        'The unsupported parameter should not be persisted when the current user cannot manage settings.'
    );

    $GLOBALS['fc_popup_builder_can_manage_options'] = true;

    Assertions::same(
        'Disabled unsupported AI parameter: temperature',
        $response['activity_log'][2]['label'] ?? '',
        'The activity log should explain that the unsupported parameter was disabled.'
    );

    $GLOBALS['fc_popup_builder_prompt_mode']    = 'no_models_for_optional_params';
    $GLOBALS['fc_popup_builder_generate_count'] = 0;
    $GLOBALS['fc_popup_builder_prompt_calls']   = array();
    $GLOBALS['fc_popup_builder_saved_settings'] = array();

    $openrouter_metadata_gap_response = $reflection->invoke( $builder, $request_payload );

    Assertions::true(
        is_array( $openrouter_metadata_gap_response ),
        'The chat response should retry after relaxing optional model-discovery requirements.'
    );

    Assertions::same(
        3,
        (int) ( $GLOBALS['fc_popup_builder_generate_count'] ?? 0 ),
        'The no-model resolver error should retry once without response format and once without tools.'
    );

    Assertions::same(
        array( true, false, false ),
        array_column( $GLOBALS['fc_popup_builder_prompt_calls'], 'response_format' ),
        'The response format requirement should be disabled after the first no-model resolver error.'
    );

    Assertions::same(
        array( true, true, false ),
        array_column( $GLOBALS['fc_popup_builder_prompt_calls'], 'tools' ),
        'The tools requirement should be disabled after response format has already been disabled.'
    );

    Assertions::same(
        array( 'response_format', 'tools' ),
        $openrouter_metadata_gap_response['settings']['disabledParams'],
        'No-model resolver retries should return the optional parameters disabled for compatibility.'
    );

    Assertions::same(
        array( 'response_format', 'tools' ),
        \FooPlugins\FooConvert\AI\PopupBuilder\Settings::get()['disabled_params'],
        'No-model resolver retry parameters should be persisted to saved Disabled Params.'
    );

    $GLOBALS['fc_popup_builder_prompt_mode']    = 'openrouter_missing_choices_error';
    $GLOBALS['fc_popup_builder_generate_count'] = 0;
    $GLOBALS['fc_popup_builder_prompt_calls']   = array();
    $GLOBALS['fc_popup_builder_saved_settings'] = array();

    $openrouter_missing_choices_response = $reflection->invoke( $builder, $request_payload );

    Assertions::true(
        is_array( $openrouter_missing_choices_response ),
        'OpenRouter missing-choices parser errors should retry after relaxing optional request parameters.'
    );

    Assertions::same(
        3,
        (int) ( $GLOBALS['fc_popup_builder_generate_count'] ?? 0 ),
        'OpenRouter missing-choices errors should retry once without response format and once without tools.'
    );

    Assertions::same(
        array( 'response_format', 'tools' ),
        $openrouter_missing_choices_response['settings']['disabledParams'],
        'OpenRouter missing-choices retries should return the optional parameters disabled for compatibility.'
    );

    $GLOBALS['fc_popup_builder_prompt_mode']    = 'openai_missing_output_error';
    $GLOBALS['fc_popup_builder_generate_count'] = 0;
    $GLOBALS['fc_popup_builder_prompt_calls']   = array();
    $GLOBALS['fc_popup_builder_saved_settings'] = array();

    $openai_error = $reflection->invoke( $builder, $request_payload );

    Assertions::true(
        $openai_error instanceof WP_Error,
        'Missing-output provider errors should be returned as WP_Error instances.'
    );

    Assertions::same(
        'fooconvert_ai_popup_builder_no_output',
        $openai_error->get_error_code(),
        'Missing-output provider errors should use a generic popup-builder error code.'
    );

    Assertions::true(
        false !== strpos( $openai_error->get_error_message(), 'no remaining credits or quota' ),
        'Missing-output provider errors should explain the likely quota or credits issue.'
    );

    Assertions::true(
        false !== strpos( $openai_error->get_error_message(), 'The AI connector did not return any model output.' ),
        'Missing-output provider errors should use a provider-generic user-facing message.'
    );

    Assertions::same(
        'Unexpected OpenAI API response: Missing the "output" key.',
        $openai_error->get_error_data()['provider_error_message'] ?? '',
        'Missing-output errors should preserve the original provider message in error data.'
    );

    Assertions::same(
        'openai',
        $openai_error->get_error_data()['provider'] ?? '',
        'Missing-output errors should preserve the detected provider name in error data.'
    );

    $GLOBALS['fc_popup_builder_prompt_mode']    = 'anthropic_missing_output_error';
    $GLOBALS['fc_popup_builder_generate_count'] = 0;
    $GLOBALS['fc_popup_builder_prompt_calls']   = array();

    $anthropic_error = $reflection->invoke( $builder, $request_payload );

    Assertions::same(
        'fooconvert_ai_popup_builder_no_output',
        $anthropic_error->get_error_code(),
        'Non-OpenAI missing-output provider errors should use the same generic popup-builder error code.'
    );

    Assertions::same(
        'anthropic',
        $anthropic_error->get_error_data()['provider'] ?? '',
        'Non-OpenAI missing-output provider errors should preserve the detected provider name in error data.'
    );

    Assertions::true(
        false === strpos( $anthropic_error->get_error_message(), 'OpenAI connector' ),
        'Non-OpenAI missing-output provider errors should not show OpenAI-specific user-facing copy.'
    );

    $GLOBALS['fc_popup_builder_prompt_mode']    = 'openai_missing_output_exception';
    $GLOBALS['fc_popup_builder_generate_count'] = 0;
    $GLOBALS['fc_popup_builder_prompt_calls']   = array();

    $openai_exception = $reflection->invoke( $builder, $request_payload );

    Assertions::same(
        'fooconvert_ai_popup_builder_no_output',
        $openai_exception->get_error_code(),
        'Thrown missing-output exceptions should be normalized to the same popup-builder error.'
    );

    $GLOBALS['fc_popup_builder_prompt_mode']    = 'openrouter_missing_choices_always_error';
    $GLOBALS['fc_popup_builder_generate_count'] = 0;
    $GLOBALS['fc_popup_builder_prompt_calls']   = array();
    $GLOBALS['fc_popup_builder_saved_settings'] = array(
        FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS => "response_format\ntools",
    );

    $openrouter_choices_error = $reflection->invoke( $builder, $request_payload );

    Assertions::same(
        'fooconvert_ai_popup_builder_no_output',
        $openrouter_choices_error->get_error_code(),
        'OpenRouter missing-choices errors should become provider-generic no-output errors when retries are exhausted.'
    );

    Assertions::same(
        'openrouter',
        $openrouter_choices_error->get_error_data()['provider'] ?? '',
        'OpenRouter missing-choices errors should preserve the detected provider name in error data.'
    );

    $GLOBALS['wp_version']                       = '7.0';
    $GLOBALS['fc_popup_builder_prompt_mode']     = 'streaming_missing_choices_with_text';
    $GLOBALS['fc_popup_builder_generate_count']  = 0;
    $GLOBALS['fc_popup_builder_prompt_calls']    = array();
    $GLOBALS['fc_popup_builder_saved_settings']  = array();
    $streamed_assistant_deltas                   = array();

    $stream_recovered_response = $reflection->invoke(
        $builder,
        $request_payload,
        array(
            'on_assistant_delta' => static function( string $delta ) use ( &$streamed_assistant_deltas ): void {
                $streamed_assistant_deltas[] = $delta;
            },
        )
    );

    Assertions::true(
        is_array( $stream_recovered_response ),
        'Streaming missing-choices parser errors should use accumulated assistant text as a fallback result.'
    );

    Assertions::same(
        'Built from stream.',
        $stream_recovered_response['assistant_message'] ?? '',
        'The streamed-text fallback should preserve the assistant JSON that arrived before the provider parser failed.'
    );

    Assertions::same(
        1,
        count( $streamed_assistant_deltas ),
        'The streamed-text fallback should continue forwarding assistant deltas to the UI.'
    );

    fwrite( STDOUT, "ai-popup-settings-retry: ok\n" );
}
