<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\AI\PopupBuilder\Media {
    class Attachments {
        public static function list_generated_images( int $limit = 12 ): array {
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
            return 'Return a structured popup builder JSON response.';
        }

        public static function get_conversion_playbook(): array {
            return array(
                'principles' => array( 'Focus on one CTA.' ),
            );
        }

        public static function sanitize_ai_response( $payload ): array {
            $payload = is_array( $payload ) ? $payload : array();

            return array(
                'assistant_message'   => (string) ( $payload['assistant_message'] ?? '' ),
                'clarifying_question' => (string) ( $payload['clarifying_question'] ?? '' ),
                'suggested_prompts'   => is_array( $payload['suggested_prompts'] ?? null ) ? $payload['suggested_prompts'] : array(),
                'popup_draft'         => is_array( $payload['popup_draft'] ?? null ) ? $payload['popup_draft'] : null,
                'validation'          => array(
                    'score' => 91,
                ),
                'media_items'         => array(),
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
            $GLOBALS['fc_popup_builder_background_ability_enabled'] = $include_background_generation;
            return array( 'fooconvert/get-conversion-playbook' );
        }
    }
}

namespace WordPress\AiClient\Messages\DTO {
    class MessagePartType {
        private bool $function_call;
        private bool $function_response;

        public function __construct( bool $function_call = false, bool $function_response = false ) {
            $this->function_call = $function_call;
            $this->function_response = $function_response;
        }

        public function isFunctionCall(): bool {
            return $this->function_call;
        }

        public function isFunctionResponse(): bool {
            return $this->function_response;
        }
    }

    class FunctionCall {
        private string $name;
        private array $args;

        public function __construct( string $name, array $args ) {
            $this->name = $name;
            $this->args = $args;
        }

        public function getName(): string {
            return $this->name;
        }

        public function getArgs(): array {
            return $this->args;
        }
    }

    class FunctionResponse {
        private string $name;
        private array $response;

        public function __construct( string $name, array $response ) {
            $this->name = $name;
            $this->response = $response;
        }

        public function getName(): string {
            return $this->name;
        }

        public function getResponse(): array {
            return $this->response;
        }
    }

    class MessagePart {
        private MessagePartType $type;
        private ?FunctionCall $function_call;
        private ?FunctionResponse $function_response;

        public function __construct( string $content = '', ?MessagePartType $type = null, ?FunctionCall $function_call = null, ?FunctionResponse $function_response = null ) {
            $this->type = $type ?: new MessagePartType();
            $this->function_call = $function_call;
            $this->function_response = $function_response;
        }

        public function getType(): MessagePartType {
            return $this->type;
        }

        public function getFunctionCall(): ?FunctionCall {
            return $this->function_call;
        }

        public function getFunctionResponse(): ?FunctionResponse {
            return $this->function_response;
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
    use FooPlugins\FooConvert\AI\PopupBuilder\StreamSupport;
    use FooPlugins\FooConvert\Tests\Support\Assertions;
    use WordPress\AiClient\Messages\DTO\FunctionCall;
    use WordPress\AiClient\Messages\DTO\FunctionResponse;
    use WordPress\AiClient\Messages\DTO\Message;
    use WordPress\AiClient\Messages\DTO\MessagePart;
    use WordPress\AiClient\Messages\DTO\MessagePartType;

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
            foreach ( $message->getParts() as $part ) {
                if ( $part->getType()->isFunctionCall() ) {
                    return true;
                }
            }

            return false;
        }

        public function execute_abilities( Message $message ): Message {
            return new Message(
                array(
                    new MessagePart(
                        '',
                        new MessagePartType( false, true ),
                        null,
                        new FunctionResponse(
                            'wpab__fooconvert__get-conversion-playbook',
                            array(
                                'playbook' => array(
                                    'principles' => array( 'Focus on one CTA.' ),
                                ),
                            )
                        )
                    ),
                )
            );
        }

        public static function function_name_to_ability_name( string $name ): string {
            return 'fooconvert/get-conversion-playbook';
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
            $GLOBALS['fc_popup_builder_prompt_abilities'] = $abilities;
            return $this;
        }

        public function as_json_response( ?array $schema = null ): self {
            return $this;
        }

        public function generate_text_result(): PopupBuilderPromptResultStub {
            $call_count = (int) ( $GLOBALS['fc_popup_builder_prompt_count'] ?? 0 );
            $GLOBALS['fc_popup_builder_prompt_count'] = $call_count + 1;
            $mode       = (string) ( $GLOBALS['fc_popup_builder_prompt_mode'] ?? 'default' );

            if ( 'clarifying' === $mode ) {
                return new PopupBuilderPromptResultStub(
                    new Message( array( new MessagePart( 'Clarifying question' ) ) ),
                    json_encode(
                        array(
                            'assistant_message'   => '',
                            'clarifying_question' => 'What exact product name, discount, and final destination URL should be inserted before launch?',
                            'suggested_prompts'   => array(),
                            'media_items'         => array(),
                            'popup_draft'         => null,
                        )
                    )
                );
            }

            if ( 0 === $call_count ) {
                return new PopupBuilderPromptResultStub(
                    new Message(
                        array(
                            new MessagePart(
                                '',
                                new MessagePartType( true, false ),
                                new FunctionCall(
                                    'wpab__fooconvert__get-conversion-playbook',
                                    array(
                                        'goal' => 'Grow the email list',
                                    )
                                )
                            ),
                        )
                    ),
                    '{}'
                );
            }

            return new PopupBuilderPromptResultStub(
                new Message( array( new MessagePart( 'Final response' ) ) ),
                json_encode(
                    array(
                        'assistant_message' => 'Here is a popup direction.',
                        'clarifying_question' => '',
                        'suggested_prompts' => array(),
                        'media_items' => array(),
                        'popup_draft'       => array(
                            'title'                => 'Launch Weekend Offer',
                            'popup_type'           => 'popup',
                            'goal'                 => 'Grow the email list',
                            'audience'             => 'Visitors',
                            'offer'                => 'Launch weekend offer',
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

    $reasoning_reflection = new \ReflectionMethod( StreamSupport::class, 'extract_reasoning_summary_delta' );
    $reasoning_reflection->setAccessible( true );
    $assistant_delta_reflection = new \ReflectionMethod( StreamSupport::class, 'extract_delta_text' );
    $assistant_delta_reflection->setAccessible( true );

    $reasoning_delta_event = new WP_AI_Client_SSE_Event(
        'message',
        json_encode(
            array(
                'type'  => 'response.reasoning_summary_text.delta',
                'delta' => 'Reviewed the conversion playbook.',
            )
        )
    );

    Assertions::same(
        'Reviewed the conversion playbook.',
        $reasoning_reflection->invoke( null, $reasoning_delta_event ),
        'Reasoning summary delta events should stream provider-supplied summary text.'
    );

    Assertions::same(
        '',
        $assistant_delta_reflection->invoke( null, $reasoning_delta_event ),
        'Reasoning summary deltas should not be treated as assistant response text.'
    );

    Assertions::same(
        'Reviewed the conversion playbook and draft constraints.',
        $reasoning_reflection->invoke(
            null,
            new WP_AI_Client_SSE_Event(
                'message',
                json_encode(
                    array(
                        'type' => 'response.reasoning_summary_text.done',
                        'text' => 'Reviewed the conversion playbook and draft constraints.',
                    )
                )
            )
        ),
        'Reasoning summary done events should expose completed provider summary text.'
    );

    Assertions::same(
        '',
        $reasoning_reflection->invoke(
            null,
            new WP_AI_Client_SSE_Event(
                'message',
                json_encode(
                    array(
                        'type'  => 'response.reasoning_text.delta',
                        'delta' => 'raw reasoning should not be shown',
                    )
                )
            )
        ),
        'Raw or unsupported reasoning events should not be exposed in the chat UI.'
    );

    $stream_items = array();
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
            'generate_images'        => false,
            'force_image_generation' => false,
        ),
        array(
            'on_status' => static function( array $item ) use ( &$stream_items ): void {
                $stream_items[] = $item;
            },
            'on_activity' => static function( array $item ) use ( &$stream_items ): void {
                $stream_items[] = $item;
            },
        )
    );

    Assertions::true(
        is_array( $response ),
        'Building a popup response should return a response array when the prompt succeeds.'
    );

    Assertions::same(
        array(
            'Preparing popup context',
            'Calling AI model',
            'fooconvert/get-conversion-playbook',
            'fooconvert/get-conversion-playbook',
            'Building popup draft',
        ),
        array_column( $response['activity_log'], 'label' ),
        'The popup builder activity log should preserve the full ordered timeline for a completed response.'
    );

    Assertions::same(
        array(
            'status',
            'status',
            'tool_call',
            'tool_result',
            'status',
        ),
        array_column( $response['activity_log'], 'type' ),
        'The popup builder activity log should keep status and tool rows in the order they occurred.'
    );

    Assertions::false(
        (bool) ( $GLOBALS['fc_popup_builder_background_ability_enabled'] ?? true ),
        'The popup background ability should not be enabled for standard chat turns when image generation on submit is off.'
    );

    Assertions::same(
        array( 'fooconvert/get-conversion-playbook' ),
        $GLOBALS['fc_popup_builder_prompt_abilities'] ?? array(),
        'The prompt should receive only the allowed abilities for the current turn.'
    );

    Assertions::same(
        $response['activity_log'],
        $stream_items,
        'Streamed activity items should match the canonical final activity log exactly.'
    );

    $GLOBALS['fc_popup_builder_prompt_count'] = 1;
    unset( $GLOBALS['fc_popup_builder_prompt_mode'] );
    $update_response = $reflection->invoke(
        $builder,
        array(
            'messages'               => array(
                array(
                    'role'    => 'user',
                    'content' => 'Make the CTA softer.',
                ),
            ),
            'popup_draft'            => array(
                'popup_type' => 'popup',
                'title'      => 'Existing draft',
            ),
            'existing_media'         => array(),
            'brand'                  => array(),
            'generate_images'        => false,
            'force_image_generation' => false,
        )
    );

    Assertions::true(
        is_array( $update_response ),
        'Updating a popup response should return a response array when the prompt succeeds.'
    );

    Assertions::same(
        array(
            'Calling AI model',
            'Updating popup draft',
        ),
        array_column( $update_response['activity_log'], 'label' ),
        'Follow-up draft edits should skip repeated context preparation and use the updating status.'
    );

    $GLOBALS['fc_popup_builder_prompt_count'] = 0;
    $GLOBALS['fc_popup_builder_prompt_mode']  = 'clarifying';
    $clarifying_response = $reflection->invoke(
        $builder,
        array(
            'messages'               => array(
                array(
                    'role'    => 'user',
                    'content' => 'Swap in the exact product name, discount, and final destination URL before publishing.',
                ),
            ),
            'popup_draft'            => array(
                'popup_type' => 'popup',
                'title'      => 'Existing draft',
            ),
            'existing_media'         => array(),
            'brand'                  => array(),
            'generate_images'        => false,
            'force_image_generation' => false,
        )
    );

    Assertions::true(
        is_array( $clarifying_response ),
        'A clarifying follow-up should still return a response array.'
    );

    Assertions::same(
        'What exact product name, discount, and final destination URL should be inserted before launch?',
        $clarifying_response['clarifying_question'],
        'Clarifying follow-ups should preserve the model question.'
    );

    Assertions::same(
        array(
            'Calling AI model',
        ),
        array_column( $clarifying_response['activity_log'], 'label' ),
        'Clarifying follow-ups should only keep activity for work that actually happened.'
    );

    unset( $GLOBALS['fc_popup_builder_prompt_mode'] );

    fwrite( STDOUT, "ai-popup-activity-log passed\n" );
}
