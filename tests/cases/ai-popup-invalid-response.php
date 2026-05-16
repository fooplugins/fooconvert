<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint {
    class DraftNormalizer {
        public static function get_assistant_response_schema( ?array $selected_block_names = null ): array {
            return array(
                'type'       => 'object',
                'required'   => array( 'assistant_message', 'popup_draft' ),
                'properties' => array(
                    'assistant_message' => array(
                        'type' => 'string',
                    ),
                    'popup_draft'       => array(
                        'type' => array( 'object', 'null' ),
                    ),
                ),
            );
        }

        public static function get_assistant_response_contract( ?array $selected_block_names = null ): string {
            return 'Return one valid popup JSON object.';
        }

        public static function get_conversion_playbook(): array {
            return array(
                'principles' => array( 'Focus on one CTA.' ),
            );
        }

        public static function sanitize_selected_block_names( $value ): array {
            return is_array( $value ) ? array_values( array_filter( array_map( 'strval', $value ) ) ) : array();
        }
    }
}

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\ChatService;
    use FooPlugins\FooConvert\AI\PopupBuilder\DebugResponseLog;
    use FooPlugins\FooConvert\AI\PopupBuilder\ResponseParser;
    use FooPlugins\FooConvert\AI\PopupBuilder\RestController as PopupBuilder;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    class WP_Error {
        private string $code;
        private string $message;
        private array $data;

        public function __construct( string $code, string $message, array $data = array() ) {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
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

    class WP_REST_Response {
        /** @var array<string,mixed> */
        private array $data;

        public function __construct( array $data = array() ) {
            $this->data = $data;
        }

        public function get_data(): array {
            return $this->data;
        }
    }

    class WP_AI_Client_Prompt_Builder {}

    class PopupBuilderSchemaPromptStub extends WP_AI_Client_Prompt_Builder {
        public function with_history( ...$history ): self {
            return $this;
        }

        public function using_temperature( float $temperature ): self {
            return $this;
        }

        public function using_system_instruction( string $instruction ): self {
            $GLOBALS['fc_popup_builder_schema_system_instruction'] = $instruction;
            return $this;
        }

        public function using_abilities( ...$abilities ): self {
            return $this;
        }

        public function as_json_response( ?array $schema = null ): self {
            $GLOBALS['fc_popup_builder_schema_argument'] = $schema;
            return $this;
        }
    }

    $GLOBALS['fc_debug_enabled'] = false;
    $GLOBALS['fc_options'] = array();

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}

    function wp_ai_client_prompt(): PopupBuilderSchemaPromptStub {
        return new PopupBuilderSchemaPromptStub();
    }

    function get_option( string $option, $default = false ) {
        return $GLOBALS['fc_options'][ $option ] ?? $default;
    }

    function update_option( string $option, $value, $autoload = null ): bool {
        $GLOBALS['fc_options'][ $option ] = $value;
        return true;
    }

    function delete_option( string $option ): bool {
        unset( $GLOBALS['fc_options'][ $option ] );
        return true;
    }

    function sanitize_key( $value ): string {
        return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) ?? '' );
    }

    function sanitize_text_field( $value ): string {
        return trim( wp_strip_all_tags( (string) $value ) );
    }

    function wp_strip_all_tags( $value ): string {
        return strip_tags( (string) $value );
    }

    function wp_json_encode( $value, int $flags = 0 ): string {
        return json_encode( $value, $flags );
    }

    function absint( $value ): int {
        return abs( (int) $value );
    }

    function fooconvert_is_debug(): bool {
        return ! empty( $GLOBALS['fc_debug_enabled'] );
    }

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
    }

    if ( ! defined( 'FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES' ) ) {
        define( 'FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES', 'fooconvert_ai_popup_builder_debug_responses' );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Config.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/RestController.php';

    $builder      = new PopupBuilder();
    $chat_service = new ChatService();

    $schema_prompt_reflection = new \ReflectionMethod( ChatService::class, 'build_prompt_from_settings' );
    $schema_prompt_reflection->setAccessible( true );
    $schema_prompt_reflection->invoke( $chat_service, array(), array(), false, false, array() );

    Assertions::same(
        \FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\DraftNormalizer::get_assistant_response_schema(),
        $GLOBALS['fc_popup_builder_schema_argument'] ?? null,
        'The AI prompt request should pass the exact popup response schema to as_json_response.'
    );

    Assertions::true(
        false !== strpos( $GLOBALS['fc_popup_builder_schema_system_instruction'] ?? '', 'Return one valid popup JSON object.' ),
        'The system instruction should still include the plain-text response contract.'
    );

    Assertions::true(
        false !== strpos( $GLOBALS['fc_popup_builder_schema_system_instruction'] ?? '', 'Conversion playbook JSON' )
            && false !== strpos( $GLOBALS['fc_popup_builder_schema_system_instruction'] ?? '', 'Focus on one CTA.' ),
        'The system instruction should include the conversion playbook context.'
    );

    $error = ResponseParser::get_invalid_popup_response_error( "Here is the popup:\n{\"assistant_message\":" );

    Assertions::same(
        'fooconvert_ai_popup_builder_invalid_json',
        $error->get_error_code(),
        'Invalid model JSON should return the invalid JSON error code.'
    );

    Assertions::true(
        false !== strpos( $error->get_error_message(), 'JSON parse detail: Syntax error' ),
        'Invalid model JSON should include the JSON parser error in the message.'
    );

    Assertions::true(
        false !== strpos( $error->get_error_message(), 'Expected top-level keys: assistant_message, clarifying_question, suggested_prompts, media_items, popup_draft.' ),
        'Invalid model JSON should include the required response keys in the message.'
    );

    Assertions::true(
        false !== strpos( $error->get_error_message(), 'Response preview: Here is the popup: {"assistant_message":' ),
        'Invalid model JSON should include a clipped response preview in the message.'
    );

    Assertions::same(
        'Syntax error',
        $error->get_error_data()['json_error'] ?? '',
        'Invalid model JSON should expose the parser error in error data.'
    );

    Assertions::same(
        array( 'assistant_message', 'clarifying_question', 'suggested_prompts', 'media_items', 'popup_draft' ),
        $error->get_error_data()['expected_top_level_keys'] ?? array(),
        'Invalid model JSON should expose the expected response keys in error data.'
    );

    $markdown_error = ResponseParser::get_invalid_popup_response_error( "### Mobile-Friendly Announcement Bar Design\n\nUse `bar__special_offer` for this launch." );

    Assertions::true(
        false !== strpos( $markdown_error->get_error_message(), 'Markdown or prose instead of the required machine-readable JSON object' ),
        'Markdown model responses should explain that prose was returned instead of JSON.'
    );

    Assertions::true(
        false !== strpos( $markdown_error->get_error_message(), 'The first non-whitespace character must be' )
            || false !== strpos( $markdown_error->get_error_message(), 'Expected a single JSON object' ),
        'Markdown model responses should include a response-format hint.'
    );

    Assertions::true(
        false !== strpos( $markdown_error->get_error_data()['problem_detail'] ?? '', 'Markdown or prose' ),
        'Markdown model responses should expose the response shape problem in error data.'
    );

    $empty_json_error = ResponseParser::validate_decoded_popup_response(
        array(),
        '{}'
    );

    Assertions::true(
        $empty_json_error instanceof WP_Error,
        'Valid JSON without a draft or clarifying question should be rejected.'
    );

    Assertions::same(
        'fooconvert_ai_popup_builder_invalid_response_contract',
        $empty_json_error->get_error_code(),
        'Valid JSON without a usable builder payload should return the response contract error code.'
    );

    Assertions::true(
        false !== strpos( $empty_json_error->get_error_message(), 'did not include popup_draft and did not ask a clarifying_question' ),
        'Empty JSON responses should explain why nothing appeared in preview or details.'
    );

    Assertions::true(
        false !== strpos( $empty_json_error->get_error_message(), 'Missing top-level keys: assistant_message, clarifying_question, suggested_prompts, media_items, popup_draft.' ),
        'Empty JSON responses should list the missing top-level keys.'
    );

    $empty_draft_error = ResponseParser::validate_decoded_popup_response(
        array(
            'assistant_message'   => 'I prepared a popup direction.',
            'clarifying_question' => '',
            'suggested_prompts'   => array(),
            'media_items'         => array(),
            'popup_draft'         => array(),
        ),
        '{"assistant_message":"I prepared a popup direction.","clarifying_question":"","suggested_prompts":[],"media_items":[],"popup_draft":{}}'
    );

    Assertions::true(
        $empty_draft_error instanceof WP_Error,
        'Valid JSON with an empty popup_draft should be rejected.'
    );

    Assertions::true(
        false !== strpos( $empty_draft_error->get_error_message(), 'popup_draft.content_blocks must contain at least one supported block' ),
        'Empty popup drafts should explain that content blocks are required for preview rendering.'
    );

    $clarifying_response = ResponseParser::validate_decoded_popup_response(
        array(
            'assistant_message'   => '',
            'clarifying_question' => 'What offer should this popup promote?',
            'suggested_prompts'   => array(),
            'media_items'         => array(),
            'popup_draft'         => null,
        ),
        '{"clarifying_question":"What offer should this popup promote?","popup_draft":null}'
    );

    Assertions::same(
        null,
        $clarifying_response,
        'Clarifying-question responses may omit a popup draft.'
    );

    $decoded_prose_wrapped_details = ResponseParser::decode_json_response_with_metadata( "Here is the JSON:\n```json\n{\"assistant_message\":\"Done\",\"clarifying_question\":\"\",\"suggested_prompts\":[],\"media_items\":[],\"popup_draft\":null}\n```\nThanks." );
    $decoded_prose_wrapped_response = $decoded_prose_wrapped_details['response'];

    Assertions::same(
        'Done',
        $decoded_prose_wrapped_response['assistant_message'] ?? '',
        'Decoder should extract a valid JSON object from prose or fenced output.'
    );

    $decoded_completed_details = ResponseParser::decode_json_response_with_metadata( '{"assistant_message":"Done","clarifying_question":"What offer should this promote?","suggested_prompts":[],"media_items":[],"popup_draft":null' );
    $decoded_completed_response = $decoded_completed_details['response'];

    Assertions::same(
        'What offer should this promote?',
        $decoded_completed_response['clarifying_question'] ?? '',
        'Decoder should complete a response that is only missing structural closing braces.'
    );

    $direct_draft = array(
        'title'                => 'Direct Draft',
        'popup_type'           => 'bar',
        'goal'                 => 'Promote the launch offer',
        'audience'             => 'Mobile visitors',
        'offer'                => '20% off launch offer',
        'template_slug'        => 'bar__special_offer',
        'trigger'              => array(),
        'root_attributes'      => array(),
        'content_blocks'       => array(
            array(
                'name'         => 'core/paragraph',
                'attributes'   => array(
                    'content' => 'Save 20% during launch week.',
                ),
                'inner_blocks' => array(),
            ),
        ),
        'conversion_rationale' => array( 'One concise offer and one CTA.' ),
        'notes'                => array(),
    );

    $normalized_direct_draft = ResponseParser::normalize_decoded_popup_response( $direct_draft );

    Assertions::same(
        $direct_draft,
        $normalized_direct_draft['popup_draft'] ?? array(),
        'Draft-shaped external LLM responses should be wrapped as popup_draft.'
    );

    Assertions::same(
        null,
        ResponseParser::validate_decoded_popup_response( $normalized_direct_draft, wp_json_encode( $direct_draft ) ),
        'A complete direct draft should validate after normalization.'
    );

    $empty_error = ResponseParser::get_invalid_popup_response_error( " \n " );

    Assertions::true(
        false !== strpos( $empty_error->get_error_message(), 'The response body was empty.' ),
        'Empty model responses should explain that no response body was returned.'
    );

    Assertions::false(
        isset( $empty_error->get_error_data()['response_preview'] ),
        'Empty model responses should not expose an empty preview value.'
    );

    $limit_error = ResponseParser::get_iteration_limit_error( 12 );

    Assertions::same(
        'fooconvert_ai_popup_builder_iteration_limit',
        $limit_error->get_error_code(),
        'Tool-call limit failures should return the iteration limit error code.'
    );

    Assertions::true(
        false !== strpos( $limit_error->get_error_message(), 'Current limit: 12.' ),
        'Tool-call limit errors should include the configured limit.'
    );

    Assertions::true(
        false !== strpos( $limit_error->get_error_message(), 'Increase the Max Tool Calls setting near Timeout' ),
        'Tool-call limit errors should suggest increasing the Max Tool Calls setting.'
    );

    Assertions::same(
        12,
        $limit_error->get_error_data()['max_tool_calls'] ?? 0,
        'Tool-call limit errors should expose the configured limit in error data.'
    );

    $GLOBALS['fc_debug_enabled'] = true;
    $GLOBALS['fc_options'] = array();

    $malformed_payload = '{"assistant_message":"Broken';
    $malformed_error = new WP_Error(
        'fooconvert_ai_popup_builder_invalid_json',
        'The AI returned an invalid popup response.',
        array(
            'json_error'     => 'Control character error',
            'problem_detail' => 'Malformed JSON.',
        )
    );

    DebugResponseLog::log_invalid_response(
        $malformed_payload,
        $malformed_error,
        array(
            array(
                'role'    => 'system',
                'content' => 'System prompt',
            ),
            array(
                'role'    => 'user',
                'content' => 'Build a launch bar.',
            ),
        ),
        array(
            'override_model'  => 'gpt-test',
            'disabled_params' => array(),
            'timeout'         => 42,
            'max_tool_calls'  => 6,
        ),
        'invalid_json',
        2,
        false
    );

    $stored_debug_responses = get_option( FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES, array() );

    Assertions::same(
        1,
        count( $stored_debug_responses ),
        'Debug mode should store invalid AI responses for inspection.'
    );

    Assertions::same(
        $malformed_payload,
        $stored_debug_responses[0]['raw_response'] ?? '',
        'Stored debug responses should include the raw model response.'
    );

    Assertions::same(
        'fooconvert_ai_popup_builder_invalid_json',
        $stored_debug_responses[0]['error_code'] ?? '',
        'Stored debug responses should include the error code.'
    );

    Assertions::same(
        'Build a launch bar.',
        $stored_debug_responses[0]['latest_user_message'] ?? '',
        'Stored debug responses should include the latest user message.'
    );

    Assertions::true(
        ! empty( $stored_debug_responses[0]['settings']['response_schema_sent'] ),
        'Stored debug responses should record whether the schema was sent.'
    );

    $debug_response = $builder->handle_get_debug_responses()->get_data();

    Assertions::true(
        ! empty( $debug_response['enabled'] ),
        'Debug response endpoint should report that debug logging is enabled.'
    );

    Assertions::same(
        1,
        count( $debug_response['responses'] ?? array() ),
        'Debug response endpoint should return stored invalid responses.'
    );

    $clear_response = $builder->handle_clear_debug_responses()->get_data();

    Assertions::same(
        array(),
        get_option( FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES, array() ),
        'Clear endpoint should remove stored invalid responses.'
    );

    Assertions::same(
        array(),
        $clear_response['responses'] ?? null,
        'Clear endpoint should return an empty response list.'
    );

    $GLOBALS['fc_options'] = array();
    $repair_raw_payload = '{"assistant_message":"Done","clarifying_question":"What offer?","suggested_prompts":[],"media_items":[],"popup_draft":null';
    $repair_completed_payload = $repair_raw_payload . '}';

    DebugResponseLog::log_repaired_response(
        $repair_raw_payload,
        $repair_completed_payload,
        array(
            array(
                'role'    => 'user',
                'content' => 'Build a signup popup.',
            ),
        ),
        array(
            'override_model'  => 'gpt-test',
            'disabled_params' => array(),
            'timeout'         => 42,
            'max_tool_calls'  => 6,
        ),
        'completed_object',
        1
    );

    $stored_repaired_responses = get_option( FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES, array() );

    Assertions::same(
        'repaired_json',
        $stored_repaired_responses[0]['failure_type'] ?? '',
        'Debug mode should store repaired AI responses for inspection.'
    );

    Assertions::same(
        $repair_raw_payload,
        $stored_repaired_responses[0]['raw_response'] ?? '',
        'Repaired debug responses should include the raw model response.'
    );

    Assertions::same(
        $repair_completed_payload,
        $stored_repaired_responses[0]['repaired_response'] ?? '',
        'Repaired debug responses should include the repaired model response.'
    );

    Assertions::same(
        'completed_object',
        $stored_repaired_responses[0]['repair_type'] ?? '',
        'Repaired debug responses should include the repair type.'
    );

    $GLOBALS['fc_debug_enabled'] = false;
    $GLOBALS['fc_options'] = array();

    DebugResponseLog::log_invalid_response(
        $malformed_payload,
        $malformed_error,
        array(),
        array(),
        'invalid_json',
        1,
        false
    );

    Assertions::same(
        array(),
        get_option( FOOCONVERT_OPTION_AI_POPUP_BUILDER_DEBUG_RESPONSES, array() ),
        'Invalid responses should not be stored when FooConvert debug mode is disabled.'
    );

    fwrite( STDOUT, "ai-popup-invalid-response passed\n" );
}
