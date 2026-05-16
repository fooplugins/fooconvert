<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\SettingsPage as AiPopupBuilderSettings;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
    }

    require_once dirname( __DIR__ ) . '/support/Assertions.php';

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

    fwrite( STDOUT, "ai-popup-settings-route: ok\n" );
}
