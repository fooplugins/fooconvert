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
            return array();
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
                'temperature' => false,
                'model'       => '',
                'timeout'     => 0,
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
            return $this;
        }

        public function as_json_response( ?array $schema = null ): self {
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

            if ( 0 === $call_count && ! empty( $GLOBALS['fc_popup_builder_prompt_calls'][ $this->index ]['temperature'] ) ) {
                return new WP_Error(
                    'unsupported_parameter',
                    "Unsupported parameter: 'temperature' is not supported with this model."
                );
            }

            return new PopupBuilderPromptResultStub();
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
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS', 'ai_popup_builder_disabled_params' );
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

    fwrite( STDOUT, "ai-popup-settings-retry: ok\n" );
}
