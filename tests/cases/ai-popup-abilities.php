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
                    'post_content' => '<!-- wp:fc/overlay {"postId":0} --><div>Popup</div><!-- /wp:fc/overlay -->',
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
    $GLOBALS['fc_popup_media_meta'] = array(
        41 => array(
            '_wp_attachment_image_alt' => 'Popup visual',
            '_fooconvert_ai_popup_prompt' => 'Lifestyle desk scene with promotional product.',
            '_fooconvert_ai_popup_type' => 'popup',
        ),
    );

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

    function admin_url( string $path = '' ): string {
        return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
    }

    function get_current_user_id(): int {
        return 7;
    }

    function get_posts( array $args ): array {
        return array( 41 );
    }

    function get_post( int $post_id ) {
        if ( 41 !== $post_id ) {
            return null;
        }

        return (object) array(
            'ID'           => 41,
            'post_type'    => 'attachment',
            'post_title'   => 'Generated Popup Image',
            'post_content' => 'Popup image description',
        );
    }

    function wp_get_attachment_url( int $attachment_id ): string {
        return 'https://example.test/generated-popup-image.jpg';
    }

    function wp_get_attachment_image_url( int $attachment_id, string $size ): string {
        return 'https://example.test/generated-popup-image-medium.jpg';
    }

    function wp_get_attachment_metadata( int $attachment_id ): array {
        return array(
            'width'  => 1200,
            'height' => 900,
        );
    }

    function get_attached_file( int $attachment_id ): string {
        return '/tmp/generated-popup-image.jpg';
    }

    function get_post_meta( int $post_id, string $meta_key, bool $single = false ) {
        return $GLOBALS['fc_popup_media_meta'][ $post_id ][ $meta_key ] ?? '';
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
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupMedia.php';
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
            Abilities::ABILITY_LIST_POPUP_MEDIA,
            Abilities::ABILITY_GENERATE_POPUP_IMAGE_PROMPT,
            Abilities::ABILITY_GENERATE_POPUP_IMAGE,
            Abilities::ABILITY_IMPORT_POPUP_IMAGE,
            Abilities::ABILITY_CREATE_POPUP_IMAGE,
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

    $media = $abilities->execute_list_popup_media( array( 'limit' => 4 ) );

    Assertions::same(
        41,
        $media['media_items'][0]['id'],
        'The popup media ability should list generated popup attachments.'
    );

    Assertions::same(
        'Popup visual',
        $media['media_items'][0]['alt'],
        'The popup media ability should prepare attachment alt text for the builder.'
    );

    echo "ai-popup-abilities: ok\n";
}
