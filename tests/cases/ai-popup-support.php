<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\Config;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
    }

    require_once dirname( __DIR__ ) . '/support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Config.php';

    $GLOBALS['wp_version'] = '6.9';

    Assertions::same(
        '6.9',
        Config::get_wp_version(),
        'The AI popup builder support helper should expose the current WordPress version.'
    );

    Assertions::false(
        Config::is_wp7_or_newer(),
        'The AI popup builder should remain disabled below WordPress 7.'
    );

    Assertions::false(
        Config::supports_ai_popup_builder(),
        'The AI popup builder feature gate should be false below WordPress 7.'
    );

    $GLOBALS['wp_version'] = '7.0-alpha-2';

    Assertions::same(
        '7.0',
        Config::get_wp_version(),
        'Pre-release WordPress 7 versions should normalize to 7.0 for gating.'
    );

    Assertions::true(
        Config::is_wp7_or_newer(),
        'The AI popup builder should unlock on WordPress 7 prereleases and newer.'
    );

    Assertions::true(
        Config::supports_ai_popup_builder(),
        'The AI popup builder feature gate should be true on WordPress 7 and newer.'
    );

    Assertions::false(
        Config::has_valid_ai_connection(),
        'The AI popup builder should not report a valid AI connection when the AI client is unavailable.'
    );

    fwrite( STDOUT, "ai-popup-support passed\n" );
}
