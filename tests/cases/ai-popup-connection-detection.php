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

    $GLOBALS['wp_version'] = '7.0';
    $GLOBALS['fc_ai_popup_connectors'] = array();
    $GLOBALS['fc_ai_popup_options'] = array();

    class PopupBuilderMagicSupportStub {
        public static bool $text_generation_supported = false;

        public function __call( string $name, array $arguments ) {
            if ( 'is_supported_for_text_generation' === $name ) {
                return self::$text_generation_supported;
            }

            return null;
        }
    }

    function wp_ai_client_prompt( string $content = '' ): PopupBuilderMagicSupportStub {
        return new PopupBuilderMagicSupportStub();
    }

    function wp_get_connectors(): array {
        return $GLOBALS['fc_ai_popup_connectors'];
    }

    function get_option( string $name, $default = false ) {
        return $GLOBALS['fc_ai_popup_options'][ $name ] ?? $default;
    }

    Assertions::true(
        Config::has_ai_client(),
        'The AI client should be available when the WordPress AI prompt function exists.'
    );

    Assertions::false(
        method_exists( wp_ai_client_prompt( 'Test' ), 'is_supported_for_text_generation' ),
        'The WordPress AI prompt builder exposes support checks through __call, not method_exists().'
    );

    Assertions::true(
        is_callable( array( wp_ai_client_prompt( 'Test' ), 'is_supported_for_text_generation' ) ),
        'The WordPress AI prompt builder support check should still be callable through __call.'
    );

    PopupBuilderMagicSupportStub::$text_generation_supported = true;
    Assertions::false(
        Config::has_valid_ai_connection(),
        'The AI popup builder should not report a valid connection when no AI provider connector is registered.'
    );

    $GLOBALS['fc_ai_popup_connectors'] = array(
        'openai' => array(
            'type'           => 'ai_provider',
            'authentication' => array(
                'method'       => 'api_key',
                'setting_name' => 'connectors_ai_openai_api_key',
            ),
        ),
    );
    Assertions::false(
        Config::has_valid_ai_connection(),
        'The AI popup builder should not report a valid connection when an AI provider connector has no credentials.'
    );

    $GLOBALS['fc_ai_popup_options']['connectors_ai_openai_api_key'] = 'sk-test';
    PopupBuilderMagicSupportStub::$text_generation_supported = false;
    Assertions::false(
        Config::has_valid_ai_connection(),
        'The AI popup builder should not report a valid connection when text generation is unsupported.'
    );

    PopupBuilderMagicSupportStub::$text_generation_supported = true;
    Assertions::true(
        Config::has_valid_ai_connection(),
        'The AI popup builder should report a valid connection when text generation is supported.'
    );

    fwrite( STDOUT, "ai-popup-connection-detection: ok\n" );
}
