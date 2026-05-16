<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
    }

    require_once dirname( __DIR__ ) . '/support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';

    $payload = array(
        'tools' => array(
            array(
                'type'     => 'function',
                'function' => array(
                    'name'        => 'wpab__fooconvert__get-conversion-playbook',
                    'description' => 'Return popup conversion guidance.',
                    'parameters'  => array(
                        'type'       => 'object',
                        'properties' => array(),
                    ),
                ),
            ),
        ),
    );

    $normalized_payload = \FooPlugins\FooConvert\AI\PopupBuilder\Settings::restore_streaming_schema_objects( $payload );
    $prepared_body      = json_encode(
        array(
            'tools'  => $normalized_payload['tools'],
            'stream' => true,
        )
    );

    Assertions::true(
        is_string( $prepared_body ) && false !== strpos( $prepared_body, '"properties":{}' ),
        'The popup builder streaming schema helper should preserve empty object schemas instead of turning them into arrays.'
    );

    $prepared_payload = json_decode( $prepared_body );

    Assertions::true(
        is_object( $prepared_payload ),
        'The prepared streaming payload should remain valid JSON.'
    );

    Assertions::true(
        isset( $prepared_payload->tools[0]->function->parameters->properties ) && is_object( $prepared_payload->tools[0]->function->parameters->properties ),
        'The popup builder streaming schema helper should keep function parameter properties as an object for strict JSON schema providers.'
    );

    Assertions::true(
        isset( $prepared_payload->stream ) && true === $prepared_payload->stream,
        'Preparing a streamed request body should still inject the stream flag.'
    );

    fwrite( STDOUT, "ai-popup-streaming-context passed\n" );
}
