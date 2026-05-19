<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\SettingsPage as AiPopupBuilderSettings;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

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

    $settings_page = new AiPopupBuilderSettings();
    $settings      = $settings_page->add_settings_tab( array() );
    Assertions::same(
        'Override Text Model',
        $settings['ai_popup_builder']['fields'][ FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL ]['label'] ?? '',
        'The existing override model field should be relabeled for text models.'
    );

    Assertions::same(
        'Override Image Model',
        $settings['ai_popup_builder']['fields'][ FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL ]['label'] ?? '',
        'The settings tab should expose a separate image model override field.'
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
