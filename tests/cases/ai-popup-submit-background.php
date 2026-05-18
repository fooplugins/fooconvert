<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\AI\PopupBuilder\Media {
    class Attachments {
        public static function set_runtime_ai_settings( array $settings ): void {
            $GLOBALS['fc_submit_background_runtime_ai_settings'] = $settings;
        }

        public static function clear_runtime_ai_settings(): void {
            $GLOBALS['fc_submit_background_runtime_ai_settings_cleared'] = true;
        }

        public static function list_generated_images( int $limit = 12 ): array {
            return array();
        }

        public static function popup_draft_has_background( array $popup_draft ): bool {
            return ! empty( $popup_draft['root_attributes']['content']['styles']['background']['backgroundImage']['url'] );
        }

        public static function generate_popup_background( array $popup_draft, array $brand = array(), string $instructions = '' ): array {
            $GLOBALS['fc_generated_background_instructions'] = $instructions;

            return array(
                'prompt' => 'Generated background prompt',
                'image'  => array(
                    'id'    => 84,
                    'url'   => 'https://example.test/generated-background.jpg',
                    'title' => 'Generated Background',
                ),
            );
        }

        public static function inject_background_into_popup_draft( array $popup_draft, array $media_item ): array {
            $popup_draft['root_attributes']['content']['styles']['background'] = array(
                'backgroundImage' => array(
                    'id'  => $media_item['id'] ?? 0,
                    'url' => $media_item['url'] ?? '',
                ),
                'backgroundSize' => 'cover',
            );

            return $popup_draft;
        }
    }
}

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint {
    class DraftNormalizer {
        public static function get_assistant_response_schema( ?array $selected_block_names = null ): array {
            return array( 'type' => 'object' );
        }

        public static function get_assistant_response_contract(): string {
            return 'Return a structured popup builder JSON response.';
        }

        public static function sanitize_ai_response( $payload ): array {
            $payload = is_array( $payload ) ? $payload : array();

            return array(
                'assistant_message'   => (string) ( $payload['assistant_message'] ?? '' ),
                'clarifying_question' => (string) ( $payload['clarifying_question'] ?? '' ),
                'suggested_prompts'   => is_array( $payload['suggested_prompts'] ?? null ) ? $payload['suggested_prompts'] : array(),
                'popup_draft'         => is_array( $payload['popup_draft'] ?? null ) ? $payload['popup_draft'] : null,
                'validation'          => array(
                    'score' => 82,
                ),
                'media_items'         => array(),
            );
        }

        public static function evaluate_popup_draft( $draft ): array {
            return array(
                'score'       => 88,
                'strengths'   => array(),
                'warnings'    => array(),
                'suggestions' => array(),
            );
        }

        public static function get_conversion_playbook(): array {
            return array(
                'principles' => array( 'Focus on one CTA.' ),
            );
        }

        public static function sanitize_selected_block_names( $value ): array {
            return is_array( $value ) ? array_values( array_filter( array_map( 'strval', $value ) ) ) : array();
        }

        public static function get_default_selected_block_names(): array {
            return array();
        }

        public static function set_request_selected_block_names( $selected_block_names ): void {}

        public static function clear_request_selected_block_names(): void {}
    }
}

namespace FooPlugins\FooConvert\AI {
    class Abilities {
        public static function get_allowed_abilities( bool $include_background_generation = true ): array {
            $GLOBALS['fc_submit_background_ability_enabled'] = $include_background_generation;
            return array( 'fooconvert/get-conversion-playbook' );
        }
    }
}

namespace WordPress\AiClient\Messages\DTO {
    class MessagePartType {
        public function isFunctionCall(): bool {
            return false;
        }

        public function isFunctionResponse(): bool {
            return false;
        }
    }

    class MessagePart {
        private MessagePartType $type;

        public function __construct() {
            $this->type = new MessagePartType();
        }

        public function getType(): MessagePartType {
            return $this->type;
        }

        public function getFunctionCall() {
            return null;
        }

        public function getFunctionResponse() {
            return null;
        }
    }

    class Message {
        /** @var array<int,MessagePart> */
        protected array $parts;

        public function __construct( array $parts = array() ) {
            $this->parts = $parts;
        }

        public function getParts(): array {
            return $this->parts;
        }
    }

    class ModelMessage extends Message {}

    class UserMessage extends Message {}
}

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\ChatService;
    use FooPlugins\FooConvert\Tests\Support\Assertions;
    use WordPress\AiClient\Messages\DTO\Message;
    use WordPress\AiClient\Messages\DTO\MessagePart;

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

        public function execute_abilities( Message $message ): Message {
            return new Message();
        }

        public static function function_name_to_ability_name( string $name ): string {
            return $name;
        }
    }

    class PopupBuilderPromptResultStub {
        private Message $message;
        private string $text;

        public function __construct( Message $message, string $text ) {
            $this->message = $message;
            $this->text = $text;
        }

        public function getCandidates(): array {
            return array(
                new class( $this->message ) {
                    private Message $message;

                    public function __construct( Message $message ) {
                        $this->message = $message;
                    }

                    public function getMessage(): Message {
                        return $this->message;
                    }
                },
            );
        }

        public function toText(): string {
            return $this->text;
        }
    }

    class WP_AI_Client_Prompt_Builder {}

    class PopupBuilderPromptStub extends WP_AI_Client_Prompt_Builder {
        public function with_history( ...$history ): self {
            return $this;
        }

        public function using_temperature( float $temperature ): self {
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

        public function generate_text_result(): PopupBuilderPromptResultStub {
            return new PopupBuilderPromptResultStub(
                new Message( array( new MessagePart() ) ),
                json_encode(
                    array(
                        'assistant_message' => 'Here is your popup.',
                        'clarifying_question' => '',
                        'suggested_prompts' => array(),
                        'media_items' => array(),
                        'popup_draft'       => array(
                            'title'                => 'Launch Weekend Offer',
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
                )
            );
        }
    }

    function __( string $text, ?string $domain = null ): string {
        return $text;
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
        return trim( wp_strip_all_tags( (string) $value ) );
    }

    function sanitize_key( $value ): string {
        return strtolower( preg_replace( '/[^a-z0-9_\\-]/i', '', (string) $value ) ?? '' );
    }

    function fooconvert_get_setting( string $key, $default = null ) {
        return $default;
    }

    function fooconvert_get_settings(): array {
        return array();
    }

    function update_option( string $option, $value, $autoload = null ): bool {
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
    $response = $reflection->invoke(
        $builder,
        array(
            'messages'               => array(
                array(
                    'role'    => 'user',
                    'content' => 'Build a popup for a launch discount.',
                ),
            ),
            'popup_draft'            => array(),
            'existing_media'         => array(),
            'brand'                  => array(),
            'generate_images'        => true,
            'force_image_generation' => false,
            'settings'               => array(
                'override_image_model' => 'custom-image-model',
            ),
        )
    );

    Assertions::true(
        is_array( $response ),
        'Building a popup response should return a response array when submit-time background generation succeeds.'
    );

    Assertions::true(
        false !== stripos( (string) ( $response['assistant_message'] ?? '' ), 'generated a popup background' ),
        'Submit-time image generation should append a note when a popup background is applied automatically.'
    );

    Assertions::same(
        'https://example.test/generated-background.jpg',
        $response['popup_draft']['root_attributes']['content']['styles']['background']['backgroundImage']['url'] ?? '',
        'Submit-time image generation should inject the generated background into the popup draft.'
    );

    Assertions::same(
        'cover',
        $response['popup_draft']['root_attributes']['content']['styles']['background']['backgroundSize'] ?? '',
        'Submit-time image generation should apply cover sizing to the generated popup background.'
    );

    Assertions::true(
        (bool) ( $GLOBALS['fc_submit_background_ability_enabled'] ?? false ),
        'The popup background ability should be available on turns where submit-time image generation is enabled.'
    );

    Assertions::same(
        'Build a popup for a launch discount.',
        (string) ( $GLOBALS['fc_generated_background_instructions'] ?? '' ),
        'The automatic popup background generation should use the latest user message as additional direction.'
    );

    Assertions::same(
        'custom-image-model',
        $GLOBALS['fc_submit_background_runtime_ai_settings']['override_image_model'] ?? '',
        'Submit-time image generation should receive the request image model override.'
    );

    Assertions::true(
        ! empty( $GLOBALS['fc_submit_background_runtime_ai_settings_cleared'] ),
        'Submit-time image generation should clear request-scoped AI settings after the chat response.'
    );

    echo "ai-popup-submit-background: ok\n";
}
