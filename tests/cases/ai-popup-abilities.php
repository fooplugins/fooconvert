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

    if ( ! class_exists( 'WP_Block_Type', false ) ) {
        class WP_Block_Type {
            public string $title;
            public string $description;
            public array $attributes;
            public array $parent;
            public array $ancestor;
            public array $supports;
            public bool $inserter;

            public function __construct( string $title, string $description = '', array $attributes = array(), array $parent = array(), array $ancestor = array(), array $supports = array(), bool $inserter = true ) {
                $this->title = $title;
                $this->description = $description;
                $this->attributes = $attributes;
                $this->parent = $parent;
                $this->ancestor = $ancestor;
                $this->supports = $supports;
                $this->inserter = $inserter;
            }
        }
    }

    if ( ! class_exists( 'WP_Block_Type_Registry', false ) ) {
        class WP_Block_Type_Registry {
            private static ?WP_Block_Type_Registry $instance = null;

            public static function get_instance(): WP_Block_Type_Registry {
                if ( null === self::$instance ) {
                    self::$instance = new self();
                }

                return self::$instance;
            }

            public function get_all_registered(): array {
                return array(
                    'fc/sign-up' => new WP_Block_Type(
                        'FooConvert Sign Up',
                        'Lead capture form block.',
                        array(
                            'settings' => array( 'type' => 'object' ),
                            'inputs'   => array( 'type' => 'object' ),
                            'button'   => array( 'type' => 'object' ),
                        )
                    ),
                );
            }
        }
    }

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

    function absint( $value ): int {
        return abs( (int) $value );
    }

    function did_action( string $hook ): int {
        return 1;
    }

    function doing_action( string $hook = '' ): bool {
        return false;
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

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ );
    }

    if ( ! defined( 'FOOCONVERT_INCLUDES_PATH' ) ) {
        define( 'FOOCONVERT_INCLUDES_PATH', dirname( __DIR__, 2 ) . '/includes/' );
    }

    if ( ! defined( 'FOOCONVERT_ASSETS_URL' ) ) {
        define( 'FOOCONVERT_ASSETS_URL', 'https://example.test/wp-content/plugins/fooconvert/assets/' );
    }

    if ( ! defined( 'DAY_IN_SECONDS' ) ) {
        define( 'DAY_IN_SECONDS', 86400 );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Brand/Manager.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Blueprint/DraftNormalizer.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Media/Attachments.php';
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
            Abilities::ABILITY_CREATE_POPUP_BACKGROUND,
        ),
        array_keys( $GLOBALS['fc_registered_abilities'] ),
        'The AI popup builder should register the expected abilities.'
    );

    Assertions::false(
        in_array( Abilities::ABILITY_CREATE_POPUP_BACKGROUND, Abilities::get_allowed_abilities( false ), true ),
        'The popup background ability should be excluded when background generation is not enabled for the turn.'
    );

    Assertions::false(
        in_array(
            'root_attributes',
            $GLOBALS['fc_registered_abilities'][Abilities::ABILITY_CREATE_POPUP_BACKGROUND]['input_schema']['properties']['popup_draft']['required'] ?? array(),
            true
        ),
        'The popup background ability should accept partial popup draft context instead of requiring full root attributes.'
    );

    Assertions::false(
        in_array(
            'root_attributes',
            $GLOBALS['fc_registered_abilities'][Abilities::ABILITY_VALIDATE_POPUP_BLUEPRINT]['input_schema']['properties']['popup_draft']['required'] ?? array(),
            true
        ),
        'The popup blueprint validator ability should accept partial popup draft context instead of requiring full root attributes.'
    );

    $validate_block_schema_required = $GLOBALS['fc_registered_abilities'][Abilities::ABILITY_VALIDATE_POPUP_BLUEPRINT]['input_schema']['properties']['popup_draft']['properties']['content_blocks']['items']['required'] ?? array();
    Assertions::false(
        in_array( 'attributes', $validate_block_schema_required, true ) || in_array( 'inner_blocks', $validate_block_schema_required, true ),
        'The popup blueprint validator ability should accept partial content blocks instead of requiring attributes and inner_blocks.'
    );

    Assertions::true(
        isset( $GLOBALS['fc_registered_abilities'][Abilities::ABILITY_GENERATE_POPUP_IMAGE]['output_schema']['properties']['image']['properties']['mime_type'] ),
        'Generated popup image payloads should expose MIME type so follow-up imports can succeed.'
    );

    Assertions::true(
        is_object( $GLOBALS['fc_registered_abilities'][Abilities::ABILITY_CONVERSION_PLAYBOOK]['input_schema']['properties'] ),
        'The conversion playbook ability should expose an object-valued empty properties schema for the AI client.'
    );

    foreach ( $GLOBALS['fc_registered_abilities'] as $ability_name => $ability_args ) {
        Assertions::true(
            isset( $ability_args['input_schema']['default'] ) && is_object( $ability_args['input_schema']['default'] ),
            sprintf( 'Ability `%s` should default omitted tool input to an empty object.', $ability_name )
        );
    }

    $playbook = $abilities->execute_get_conversion_playbook( new \stdClass() );

    Assertions::true(
        is_array( $playbook['playbook'] ?? null ),
        'The conversion playbook ability should accept normalized empty object input.'
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
        (object) array(
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
