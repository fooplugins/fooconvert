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
    use FooPlugins\FooConvert\AI\PopupMedia;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

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

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupMedia.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBlueprint.php';

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

    Assertions::true(
        true === PopupMedia::delete_generated_image( 77 ),
        'Generated popup media should be deletable from the builder media panel.'
    );

    echo "ai-popup-media: ok\n";
}
