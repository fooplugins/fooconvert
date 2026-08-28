<?php
declare(strict_types=1);

namespace WordPress\AiClient {
    class AiClient {
        public static function defaultRegistry(): object {
            return new \FcAiSettingsModelRegistryStub();
        }
    }
}

namespace WordPress\AiClient\Providers\Models\DTO {
    class ModelConfig {
        public array $data = array();

        public static function fromArray( array $data ): self {
            $config       = new self();
            $config->data = $data;

            return $config;
        }
    }
}

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\SettingsPage as AiPopupBuilderSettings;
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

    class WP_REST_Request {
        private array $params;

        public function __construct( array $params = array() ) {
            $this->params = $params;
        }

        public function get_param( string $key ) {
            return $this->params[ $key ] ?? null;
        }
    }

    class WP_REST_Response {
        private array $data;

        public function __construct( array $data = array() ) {
            $this->data = $data;
        }

        public function get_data(): array {
            return $this->data;
        }
    }

    class WP_AI_Client_Prompt_Builder {}

    class FcAiSettingsPromptStub extends WP_AI_Client_Prompt_Builder {
        public function __call( string $method, array $args ) {
            if ( 'using_model' !== $method ) {
                throw new \BadMethodCallException( $method );
            }

            $GLOBALS['fc_ai_settings_prompt_forced_models'][] = $args[0] ?? null;

            return $this;
        }
    }

    class FcAiSettingsModelRegistryStub {
        public function getProviderModel( string $provider, string $model, \WordPress\AiClient\Providers\Models\DTO\ModelConfig $config ): object {
            if ( ! empty( $GLOBALS['fc_ai_settings_model_registry_fail'] ) ) {
                throw new \RuntimeException( 'Model metadata is not registered.' );
            }

            $GLOBALS['fc_ai_settings_resolved_models'][] = array(
                'provider' => $provider,
                'model'    => $model,
                'config'   => $config->data,
            );

            return (object) array(
                'provider' => $provider,
                'model'    => $model,
            );
        }
    }

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
    }

    if ( ! defined( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL' ) ) {
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL', 'ai_popup_builder_override_model' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL', 'ai_popup_builder_override_image_model' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS', 'ai_popup_builder_disabled_params' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OPTIMIZE_IMAGE_OUTPUT', 'ai_popup_builder_optimize_image_output' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_TIMEOUT', 'ai_popup_builder_timeout' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_MAX_TOOL_CALLS', 'ai_popup_builder_max_tool_calls' );
        define( 'FOOCONVERT_AI_POPUP_BUILDER_TIMEOUT_DEFAULT', 45 );
        define( 'FOOCONVERT_AI_POPUP_BUILDER_MAX_TOOL_CALLS_DEFAULT', 10 );
    }

    require_once dirname( __DIR__ ) . '/support/Assertions.php';

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function is_wp_error( $thing ): bool {
        return $thing instanceof WP_Error;
    }

    function wp_ai_client_prompt(): WP_AI_Client_Prompt_Builder {
        return new FcAiSettingsPromptStub();
    }

    function sanitize_text_field( $value ): string {
        return trim( strip_tags( (string) $value ) );
    }

    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_ai_settings_actions'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
    }

    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_ai_settings_filters'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
    }

    function is_admin(): bool {
        return false;
    }

    function register_rest_route( string $namespace, string $route, array $args ): void {
        $GLOBALS['fc_ai_settings_routes'][] = compact( 'namespace', 'route', 'args' );
    }

    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/SettingsPage.php';

    new AiPopupBuilderSettings();
    new AiPopupBuilderSettings();

    Assertions::same(
        1,
        count( $GLOBALS['fc_ai_settings_actions']['rest_api_init'] ?? array() ),
        'The AI popup builder settings route should be registered once outside admin requests.'
    );

    Assertions::false(
        isset( $GLOBALS['fc_ai_settings_filters']['fooconvert_admin_settings'] ),
        'The admin settings tab filter should not be registered on non-admin requests.'
    );

    $callback = $GLOBALS['fc_ai_settings_actions']['rest_api_init'][0]['callback'];
    call_user_func( $callback );

    Assertions::same(
        'fooconvert/v1',
        $GLOBALS['fc_ai_settings_routes'][0]['namespace'] ?? '',
        'The settings route should use the FooConvert REST namespace.'
    );

    Assertions::same(
        '/ai-popup-builder/settings',
        $GLOBALS['fc_ai_settings_routes'][0]['route'] ?? '',
        'The settings route should be available for REST requests.'
    );

    $route_args = $GLOBALS['fc_ai_settings_routes'][0]['args'] ?? array();

    Assertions::same(
        'GET',
        $route_args[0]['methods'] ?? '',
        'The settings route should expose the read endpoint.'
    );

    Assertions::same(
        'POST',
        $route_args[1]['methods'] ?? '',
        'The settings route should expose the save endpoint.'
    );

    Assertions::true(
        isset( $route_args[1]['args']['overrideImageModel'] ),
        'The settings route should accept the image model override setting.'
    );

    Assertions::true(
        isset( $route_args[1]['args']['optimizeImageOutput'] ),
        'The settings route should accept the generated image optimization setting.'
    );

    Assertions::same(
        '/ai-popup-builder/settings/test-model',
        $GLOBALS['fc_ai_settings_routes'][1]['route'] ?? '',
        'The settings test route should be available for model instantiation checks.'
    );

    $test_route_args = $GLOBALS['fc_ai_settings_routes'][1]['args'] ?? array();
    Assertions::same(
        'POST',
        $test_route_args[0]['methods'] ?? '',
        'The settings test route should expose a model test endpoint.'
    );

    Assertions::same(
        array( 'text', 'image' ),
        $test_route_args[0]['args']['type']['enum'] ?? array(),
        'The model test endpoint should accept text and image test types.'
    );

    Assertions::same(
        array( 'temperature', 'output_mime_type', 'output_compression' ),
        \FooPlugins\FooConvert\AI\PopupBuilder\Settings::sanitize_disabled_params(
            "temperature\nresponse_format\ntools\njson_schema\nfunction_declarations\noutput-mime-type\noutput_compression"
        ),
        'Disabled params should keep optional model/provider params and drop required tools/schema capabilities.'
    );

    Assertions::false(
        \FooPlugins\FooConvert\AI\PopupBuilder\Settings::is_param_disabled(
            array(
                'disabled_params' => array( 'response_format', 'tools' ),
            ),
            'response_format'
        ),
        'response_format should never be treated as disabled.'
    );

    Assertions::false(
        \FooPlugins\FooConvert\AI\PopupBuilder\Settings::is_param_disabled(
            array(
                'disabled_params' => array( 'response_format', 'tools' ),
            ),
            'tools'
        ),
        'tools should never be treated as disabled.'
    );

    $settings_page = new AiPopupBuilderSettings();
    $settings      = $settings_page->add_settings_tab( array() );

    $test_response = $settings_page->handle_test_model(
        new WP_REST_Request(
            array(
                'type'  => 'text',
                'model' => 'vercel-ai-gateway/moonshotai/kimi-k2.6',
            )
        )
    );
    Assertions::true(
        $test_response instanceof WP_REST_Response,
        'The settings test route should return a REST response when a model can be instantiated.'
    );

    $test_response_data = $test_response->get_data();
    Assertions::same(
        'vercel-ai-gateway/moonshotai/kimi-k2.6',
        $test_response_data['model'] ?? '',
        'The settings test route should report the exact tested provider/model value.'
    );

    Assertions::same(
        'Model test passed for "vercel-ai-gateway/moonshotai/kimi-k2.6".',
        $test_response_data['message'] ?? '',
        'The settings test route should return friendly success copy.'
    );

    Assertions::same(
        array(
            'provider' => 'vercel-ai-gateway',
            'model'    => 'moonshotai/kimi-k2.6',
            'config'   => array(),
        ),
        $GLOBALS['fc_ai_settings_resolved_models'][0] ?? array(),
        'The settings test route should instantiate the exact provider/model without discovery.'
    );

    Assertions::same(
        'moonshotai/kimi-k2.6',
        $GLOBALS['fc_ai_settings_prompt_forced_models'][0]->model ?? '',
        'The settings test route should force the resolved model through using_model(), including magic __call builders.'
    );

    $invalid_test_response = $settings_page->handle_test_model(
        new WP_REST_Request(
            array(
                'type'  => 'image',
                'model' => 'missing-provider-format',
            )
        )
    );
    Assertions::true(
        $invalid_test_response instanceof WP_Error,
        'The settings test route should return an error when the tested model is not provider/model format.'
    );

    Assertions::same(
        'fooconvert_ai_popup_builder_invalid_model_override',
        $invalid_test_response->get_error_code(),
        'The settings test route should use the strict provider/model validation error.'
    );

    Assertions::same(
        'Use provider/model-name format before testing this model.',
        $invalid_test_response->get_error_message(),
        'The settings test route should return friendly invalid model copy.'
    );

    $GLOBALS['fc_ai_settings_model_registry_fail'] = true;
    $failed_test_response = $settings_page->handle_test_model(
        new WP_REST_Request(
            array(
                'type'  => 'image',
                'model' => 'vercel-ai-gateway/moonshotai/kimi-k2.6',
            )
        )
    );
    unset( $GLOBALS['fc_ai_settings_model_registry_fail'] );

    Assertions::true(
        $failed_test_response instanceof WP_Error,
        'The settings test route should return an error when the exact model cannot be instantiated.'
    );

    Assertions::same(
        'fooconvert_ai_popup_builder_model_override_unavailable',
        $failed_test_response->get_error_code(),
        'The settings test route should not fall back to discovery when instantiation fails.'
    );

    Assertions::same(
        'Could not test "vercel-ai-gateway/moonshotai/kimi-k2.6". Check that the connector is active and the model name is available.',
        $failed_test_response->get_error_message(),
        'The settings test route should return friendly failed model copy.'
    );

    Assertions::same(
        'Override Text Model',
        $settings['ai_popup_builder']['fields'][ FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL ]['label'] ?? '',
        'The existing override model field should be relabeled for text models.'
    );

    Assertions::same(
        'provider/model-name',
        $settings['ai_popup_builder']['fields'][ FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL ]['placeholder'] ?? '',
        'The text model override should show the provider/model placeholder format.'
    );

    Assertions::same(
        'Override Image Model',
        $settings['ai_popup_builder']['fields'][ FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL ]['label'] ?? '',
        'The settings tab should expose a separate image model override field.'
    );

    Assertions::same(
        'provider/model-name',
        $settings['ai_popup_builder']['fields'][ FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL ]['placeholder'] ?? '',
        'The image model override should show the provider/model placeholder format.'
    );

    Assertions::same(
        array(
            'provider' => 'openrouter',
            'model'    => 'qwen/qwen3.7-max',
        ),
        \FooPlugins\FooConvert\AI\PopupBuilder\Settings::parse_model_override( 'openrouter/qwen/qwen3.7-max' ),
        'Model overrides should split only on the first slash so provider model IDs can contain slashes.'
    );

    Assertions::same(
        'Optimize Generated Images',
        $settings['ai_popup_builder']['fields'][ FOOCONVERT_SETTING_AI_POPUP_BUILDER_OPTIMIZE_IMAGE_OUTPUT ]['label'] ?? '',
        'The settings tab should expose a generated image optimization toggle.'
    );

    $optimize_image_field = $settings['ai_popup_builder']['fields'][ FOOCONVERT_SETTING_AI_POPUP_BUILDER_OPTIMIZE_IMAGE_OUTPUT ] ?? array();
    Assertions::same(
        'on',
        $optimize_image_field['default'] ?? '',
        'The generated image optimization toggle should default on for the FooFields checkbox.'
    );

    Assertions::same(
        'on',
        call_user_func( $optimize_image_field['value_decoder'], true ),
        'The generated image optimization field should decode boolean true to a checked checkbox value.'
    );

    Assertions::same(
        '',
        call_user_func( $optimize_image_field['value_decoder'], false ),
        'The generated image optimization field should decode boolean false to an unchecked checkbox value.'
    );

    Assertions::true(
        call_user_func( $optimize_image_field['value_encoder'], 'on' ),
        'The generated image optimization field should encode checked values as true.'
    );

    Assertions::false(
        call_user_func( $optimize_image_field['value_encoder'], 'off' ),
        'The generated image optimization field should encode unchecked values as false.'
    );

    fwrite( STDOUT, "ai-popup-settings-route: ok\n" );
}
