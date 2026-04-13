<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Admin {
    class DemoContent {
        public function get_demo_content(): array {
            return array(
                array(
                    'post_title'   => 'Demo Popup',
                    'meta_input'   => array(
                        FOOCONVERT_META_KEY_POPUP_TYPE => FOOCONVERT_POPUP_TYPE_POPUP,
                    ),
                    'post_content' => '<!-- wp:fc/popup {"postId":0} --><div>Popup</div><!-- /wp:fc/popup -->',
                ),
                array(
                    'post_title'   => 'Demo Flyout',
                    'meta_input'   => array(
                        FOOCONVERT_META_KEY_POPUP_TYPE => FOOCONVERT_POPUP_TYPE_FLYOUT,
                    ),
                    'post_content' => '<!-- wp:fc/flyout {"postId":0} --><div>Flyout</div><!-- /wp:fc/flyout -->',
                ),
            );
        }
    }
}

namespace {
    use FooPlugins\FooConvert\AI\PopupBlueprint;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function sanitize_text_field( $value ): string {
        return trim( strip_tags( (string) $value ) );
    }

    function wp_kses_post( $value ): string {
        return (string) $value;
    }

    function esc_url_raw( $value ): string {
        return trim( (string) $value );
    }

    function wp_strip_all_tags( $value ): string {
        return strip_tags( (string) $value );
    }

    function trailingslashit( string $value ): string {
        return rtrim( $value, '/\\' ) . '/';
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBlueprint.php';

    $assert_strict_object_schema = static function( array $schema, string $path ) use ( &$assert_strict_object_schema ): void {
        if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
            $property_keys = array_keys( $schema['properties'] );
            $required_keys = isset( $schema['required'] ) && is_array( $schema['required'] ) ? $schema['required'] : array();
            sort( $property_keys );
            sort( $required_keys );

            Assertions::same(
                $property_keys,
                $required_keys,
                sprintf( 'Schema object `%s` should require every declared property for strict WP AI response validation.', $path )
            );

            foreach ( $schema['properties'] as $property_name => $property_schema ) {
                if ( is_array( $property_schema ) ) {
                    $assert_strict_object_schema( $property_schema, $path . '.' . $property_name );
                }
            }
        }

        if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
            $assert_strict_object_schema( $schema['items'], $path . '[]' );
        }

        if ( isset( $schema['anyOf'] ) && is_array( $schema['anyOf'] ) ) {
            foreach ( $schema['anyOf'] as $index => $variant_schema ) {
                if ( is_array( $variant_schema ) ) {
                    $assert_strict_object_schema( $variant_schema, sprintf( '%s.anyOf[%d]', $path, $index ) );
                }
            }
        }
    };

    $library = PopupBlueprint::get_template_library();

    Assertions::true(
        count( $library ) >= 10,
        'The AI popup builder template library should expose the bundled Fooconvert templates.'
    );

    $popup_only_templates = array_values(
        array_filter(
            $library,
            static function( array $template ): bool {
                return $template['popup_type'] === FOOCONVERT_POPUP_TYPE_POPUP;
            }
        )
    );

    Assertions::true(
        count( $popup_only_templates ) > 0,
        'The AI popup builder template library should include popup templates.'
    );

    $draft = PopupBlueprint::sanitize_popup_draft(
        array(
            'title'         => '  Launch Weekend Offer ',
            'popup_type'    => FOOCONVERT_POPUP_TYPE_POPUP,
            'goal'          => 'Grow the email list fast',
            'audience'      => 'First-time visitors',
            'offer'         => '15% off plus early access',
            'template_slug' => 'flyout__newsletter_subscribe',
            'content_blocks' => array(
                array(
                    'name'       => 'core/heading',
                    'attributes' => array(
                        'content' => '<strong>Unlock</strong> 15% off today',
                        'level'   => 2,
                    ),
                ),
                array(
                    'name' => 'unsupported/custom',
                ),
                array(
                    'name'       => 'fc/sign-up',
                    'attributes' => array(
                        'button' => array(
                            'settings' => array(
                                'text' => 'Get My Discount',
                            ),
                        ),
                    ),
                ),
            ),
        )
    );

    Assertions::same(
        'Launch Weekend Offer',
        $draft['title'],
        'Popup draft titles should be sanitized.'
    );

    Assertions::same(
        '',
        $draft['template_slug'],
        'Popup template slugs should be cleared when they do not match the requested popup type.'
    );

    Assertions::same(
        2,
        count( $draft['content_blocks'] ),
        'Unsupported content blocks should be removed from the AI popup draft.'
    );

    Assertions::same(
        'Get My Discount',
        $draft['content_blocks'][1]['attributes']['button']['settings']['text'],
        'Sign-up shorthand attributes should be normalized into the nested block attribute shape.'
    );

    Assertions::same(
        'exit_intent',
        $draft['trigger']['type'],
        'Popup drafts should default to an exit-intent trigger when none is provided.'
    );

    $normalized_draft = PopupBlueprint::sanitize_popup_draft(
        array(
            'popup_type'    => FOOCONVERT_POPUP_TYPE_POPUP,
            'content_blocks' => array(
                array(
                    'name'       => 'core/list',
                    'attributes' => array(
                        'values' => array(
                            'First benefit',
                            'Second benefit',
                        ),
                    ),
                ),
                array(
                    'name'       => 'fc/sign-up',
                    'attributes' => array(
                        'buttonText'       => 'Claim My Offer',
                        'successMessage'   => 'Check your inbox',
                        'closeOnSuccess'   => true,
                        'emailOnly'        => true,
                        'emailPlaceholder' => 'Enter your email',
                    ),
                ),
            ),
        )
    );

    Assertions::same(
        array( 'First benefit', 'Second benefit' ),
        $normalized_draft['content_blocks'][0]['attributes']['items'],
        'List shorthand arrays should be normalized into the items attribute.'
    );

    Assertions::true(
        ! isset( $normalized_draft['content_blocks'][0]['attributes']['values'] ),
        'Legacy list values should be removed after normalization.'
    );

    Assertions::same(
        'Claim My Offer',
        $normalized_draft['content_blocks'][1]['attributes']['button']['settings']['text'],
        'Sign-up buttonText aliases should map to the nested button settings.'
    );

    Assertions::same(
        'Enter your email',
        $normalized_draft['content_blocks'][1]['attributes']['inputs']['settings']['emailPlaceholder'],
        'Sign-up emailPlaceholder aliases should map to the nested input settings.'
    );

    $validation = PopupBlueprint::evaluate_popup_draft( $draft );

    Assertions::true(
        is_int( $validation['score'] ) && $validation['score'] > 0,
        'Popup draft validation should return a numeric score.'
    );

    Assertions::true(
        count( $validation['strengths'] ) > 0,
        'Popup draft validation should surface at least one strength for a viable draft.'
    );

    $assert_strict_object_schema(
        PopupBlueprint::get_assistant_response_schema(),
        'assistant_response'
    );

    $response_contract = PopupBlueprint::get_assistant_response_contract();

    Assertions::true(
        false !== strpos( $response_contract, 'assistant_message' ) &&
        false !== strpos( $response_contract, 'popup_draft' ) &&
        false !== strpos( $response_contract, 'content_blocks' ),
        'The assistant response contract should describe the required Fooconvert JSON payload.'
    );

    echo "ai-popup-blueprint: ok\n";
}
