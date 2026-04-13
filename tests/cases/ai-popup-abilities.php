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
            );
        }
    }
}

namespace {
    use FooPlugins\FooConvert\AI\Abilities;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    class WP_Ability {}

    class WP_Error {
        private string $code;
        private string $message;

        public function __construct( string $code, string $message ) {
            $this->code = $code;
            $this->message = $message;
        }

        public function get_error_code(): string {
            return $this->code;
        }

        public function get_error_message(): string {
            return $this->message;
        }
    }

    $GLOBALS['fc_registered_categories'] = array();
    $GLOBALS['fc_registered_abilities'] = array();

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}

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

    function wp_register_ability_category( string $slug, array $args ) {
        $GLOBALS['fc_registered_categories'][ $slug ] = $args;
        return (object) array(
            'slug' => $slug,
            'args' => $args,
        );
    }

    function wp_register_ability( string $name, array $args ) {
        $GLOBALS['fc_registered_abilities'][ $name ] = $args;
        return (object) array(
            'name' => $name,
            'args' => $args,
        );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBlueprint.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/Abilities.php';

    $abilities = new Abilities();
    $abilities->register_main_category();
    $abilities->register_abilities();

    Assertions::true(
        isset( $GLOBALS['fc_registered_categories'][Abilities::CATEGORY] ),
        'The AI popup builder should register its own ability category.'
    );

    Assertions::same(
        array(
            Abilities::ABILITY_LIST_TEMPLATES,
            Abilities::ABILITY_BLOCK_CATALOG,
            Abilities::ABILITY_CONVERSION_PLAYBOOK,
            Abilities::ABILITY_VALIDATE_POPUP_BLUEPRINT,
        ),
        array_keys( $GLOBALS['fc_registered_abilities'] ),
        'The AI popup builder should register the expected abilities.'
    );

    Assertions::true(
        is_object( $GLOBALS['fc_registered_abilities'][Abilities::ABILITY_CONVERSION_PLAYBOOK]['input_schema']['properties'] ),
        'The conversion playbook ability should expose an object-valued empty properties schema for the AI client.'
    );

    $templates = $abilities->execute_list_templates(
        array(
            'popup_type' => FOOCONVERT_POPUP_TYPE_POPUP,
            'limit'      => 2,
        )
    );

    Assertions::true(
        count( $templates['templates'] ) > 0 && $templates['templates'][0]['popup_type'] === FOOCONVERT_POPUP_TYPE_POPUP,
        'Template listing should support popup type filtering.'
    );

    $block_catalog = $abilities->execute_get_block_catalog(
        array(
            'block_name' => 'fc/sign-up',
        )
    );

    Assertions::same(
        'fc/sign-up',
        $block_catalog['blocks'][0]['name'],
        'The block catalog ability should support fetching a single block definition.'
    );

    $validation = $abilities->execute_validate_popup_blueprint(
        array(
            'popup_draft' => array(
                'title'         => 'List Builder',
                'popup_type'    => FOOCONVERT_POPUP_TYPE_POPUP,
                'goal'          => 'Grow email subscribers',
                'template_slug' => '',
                'trigger'       => array(
                    'type'      => 'exit_intent',
                    'lifetime'  => 'page',
                    'frequency' => 'once',
                ),
                'content_blocks' => array(
                    array(
                        'name'       => 'core/heading',
                        'attributes' => array(
                            'content' => 'Join the list',
                            'level'   => 2,
                        ),
                    ),
                    array(
                        'name' => 'fc/sign-up',
                    ),
                ),
            ),
        )
    );

    Assertions::true(
        is_array( $validation['validation'] ) && isset( $validation['validation']['score'] ),
        'The popup blueprint validator ability should return validation details.'
    );

    echo "ai-popup-abilities: ok\n";
}
