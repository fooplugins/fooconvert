<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Admin {
    class DemoContent {
        public function get_demo_content(): array {
            return array();
        }
    }
}

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\Media\Attachments as PopupMedia;
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

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function sanitize_text_field( $value ): string {
        return trim( strip_tags( (string) $value ) );
    }

    function sanitize_title( $value ): string {
        return strtolower( preg_replace( '/[^a-z0-9]+/i', '-', trim( (string) $value ) ) );
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

    function get_post( int $post_id ) {
        if ( 77 !== $post_id ) {
            return null;
        }

        return (object) array(
            'ID'        => 77,
            'post_type' => 'attachment',
        );
    }

    function current_user_can( string $capability, int $post_id = 0 ): bool {
        return true;
    }

    function get_post_meta( int $post_id, string $meta_key, bool $single = false ) {
        if ( 77 === $post_id && PopupMedia::META_GENERATED === $meta_key ) {
            return '1';
        }

        return '';
    }

    function wp_delete_attachment( int $post_id, bool $force_delete = false ): bool {
        return 77 === $post_id;
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
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Blueprint/DraftNormalizer.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Media/Attachments.php';

    $draft = PopupMedia::inject_media_into_popup_draft(
        array(
            'popup_type'    => FOOCONVERT_POPUP_TYPE_POPUP,
            'content_blocks' => array(
                array(
                    'name'       => 'core/heading',
                    'attributes' => array(
                        'content' => 'Launch Weekend Offer',
                    ),
                ),
                array(
                    'name' => 'fc/sign-up',
                ),
            ),
        ),
        array(
            'id'    => 77,
            'url'   => 'https://example.test/generated-popup-image.jpg',
            'alt'   => 'Generated popup image',
            'title' => 'Launch weekend image',
        )
    );

    Assertions::same(
        'core/image',
        $draft['content_blocks'][1]['name'],
        'Injected popup media should be inserted before the primary action block.'
    );

    Assertions::same(
        77,
        $draft['content_blocks'][1]['attributes']['id'],
        'Injected popup media should map the attachment ID to the image block.'
    );

    $draft_with_background = PopupMedia::inject_background_into_popup_draft(
        array(
            'popup_type'     => FOOCONVERT_POPUP_TYPE_POPUP,
            'root_attributes' => array(),
            'content_blocks' => array(
                array(
                    'name'       => 'core/heading',
                    'attributes' => array(
                        'content' => 'Launch Weekend Offer',
                    ),
                ),
            ),
        ),
        array(
            'id'    => 77,
            'url'   => 'https://example.test/generated-popup-image.jpg',
            'alt'   => 'Generated popup background',
            'title' => 'Launch weekend background',
        )
    );

    Assertions::same(
        'https://example.test/generated-popup-image.jpg',
        $draft_with_background['root_attributes']['content']['styles']['background']['backgroundImage']['url'],
        'Injected popup backgrounds should be written into the popup content background image settings.'
    );

    Assertions::same(
        'cover',
        $draft_with_background['root_attributes']['content']['styles']['background']['backgroundSize'],
        'Injected popup backgrounds should default to cover sizing for popup content.'
    );

    Assertions::true(
        true === PopupMedia::delete_generated_image( 77 ),
        'Generated popup media should be deletable from the builder media panel.'
    );

    echo "ai-popup-media: ok\n";
}
