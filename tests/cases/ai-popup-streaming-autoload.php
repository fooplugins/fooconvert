<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\Config;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
    }

    $GLOBALS['wp_version'] = '7.0';

    function wp_ai_client_prompt() {}

    require_once dirname( __DIR__ ) . '/support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/vendor/bradvin/wp-ai-client-streaming/load.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Config.php';

    Assertions::true(
        function_exists( 'wp_ai_client_streaming_dependencies_available' ),
        'The free plugin autoloader should load the streaming dependency safe autoloader.'
    );

    Assertions::false(
        wp_ai_client_streaming_dependencies_available(),
        'The streaming dependency should report unavailable dependencies instead of fatalling.'
    );

    Assertions::true(
        function_exists( 'wp_ai_client_stream' ),
        'The streaming package may expose lazy proxy helpers before WordPress AI client dependencies are available.'
    );

    Assertions::false(
        Config::supports_streaming(),
        'The popup builder should keep streaming disabled while the streaming package dependencies are unavailable.'
    );

    fwrite( STDOUT, "ai-popup-streaming-autoload: ok\n" );
}
