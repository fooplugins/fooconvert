<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
    }

    require_once dirname( __DIR__ ) . '/support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/vendor/bradvin/wp-ai-client-streaming/load.php';

    Assertions::true(
        function_exists( 'wp_ai_client_streaming_dependencies_available' ),
        'The free plugin autoloader should load the streaming dependency safe autoloader.'
    );

    Assertions::false(
        wp_ai_client_streaming_dependencies_available(),
        'The streaming dependency should report unavailable dependencies instead of fatalling.'
    );

    Assertions::false(
        function_exists( 'wp_ai_client_stream' ),
        'Streaming helpers should remain unavailable when WordPress AI client dependencies are unavailable.'
    );

    fwrite( STDOUT, "ai-popup-streaming-autoload: ok\n" );
}
