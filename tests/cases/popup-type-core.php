<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\ContentMigration;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( !class_exists( 'WP_Post', false ) ) {
        class WP_Post {
            /** @var int */
            public $ID = 0;

            /** @var string */
            public $post_type = '';

            /** @var string */
            public $post_content = '';

            /** @var string */
            public $post_title = '';
        }
    }

    if ( !class_exists( 'WP_REST_Request', false ) ) {
        class WP_REST_Request {}
    }

    if ( !class_exists( 'WP_REST_Response', false ) ) {
        class WP_REST_Response {}
    }

    /** @var array<int,WP_Post> */
    $GLOBALS['fc_test_posts'] = array();

    /** @var array<int,array<string,mixed>> */
    $GLOBALS['fc_test_post_meta'] = array();

    /**
     * @param string $text
     * @param string|null $domain
     * @return string
     */
    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @param string $hook
     * @param mixed $callback
     * @param int $priority
     * @param int $accepted_args
     * @return void
     */
    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}

    /**
     * @param string $hook
     * @param mixed $callback
     * @param int $priority
     * @param int $accepted_args
     * @return void
     */
    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}

    /**
     * @param int $post_id
     * @return WP_Post|null
     */
    function get_post( int $post_id ) {
        return $GLOBALS['fc_test_posts'][ $post_id ] ?? null;
    }

    /**
     * @param int $post_id
     * @param string $meta_key
     * @param bool $single
     * @return mixed
     */
    function get_post_meta( int $post_id, string $meta_key, bool $single = false ) {
        $meta = $GLOBALS['fc_test_post_meta'][ $post_id ][ $meta_key ] ?? '';

        return $single ? $meta : array( $meta );
    }

    /**
     * @param string $content
     * @return array<int,array<string,mixed>>
     */
    function parse_blocks( string $content ): array {
        if ( strpos( $content, 'wp:fc/bar' ) !== false ) {
            return array( array( 'blockName' => 'fc/bar' ) );
        }
        if ( strpos( $content, 'wp:fc/flyout' ) !== false ) {
            return array( array( 'blockName' => 'fc/flyout' ) );
        }
        if ( strpos( $content, 'wp:fc/popup' ) !== false ) {
            return array( array( 'blockName' => 'fc/popup' ) );
        }

        return array();
    }

    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    function get_option( string $option, $default = false ) {
        return $default;
    }

    /**
     * @param string $option
     * @param mixed $value
     * @param bool $autoload
     * @return bool
     */
    function update_option( string $option, $value, bool $autoload = true ): bool {
        return true;
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/ContentMigration.php';

    $meta_priority_post = new WP_Post();
    $meta_priority_post->ID = 101;
    $meta_priority_post->post_type = FOOCONVERT_CPT_POPUP;
    $meta_priority_post->post_content = '<!-- wp:fc/popup /-->';

    $content_fallback_post = new WP_Post();
    $content_fallback_post->ID = 102;
    $content_fallback_post->post_type = FOOCONVERT_CPT_POPUP;
    $content_fallback_post->post_content = '<!-- wp:fc/flyout /-->';

    $legacy_post = new WP_Post();
    $legacy_post->ID = 103;
    $legacy_post->post_type = FOOCONVERT_CPT_BAR;
    $legacy_post->post_content = '';

    $GLOBALS['fc_test_posts'][101] = $meta_priority_post;
    $GLOBALS['fc_test_posts'][102] = $content_fallback_post;
    $GLOBALS['fc_test_posts'][103] = $legacy_post;

    $GLOBALS['fc_test_post_meta'][101] = array(
        FOOCONVERT_META_KEY_POPUP_TYPE => FOOCONVERT_POPUP_TYPE_BAR,
    );
    $GLOBALS['fc_test_post_meta'][102] = array();
    $GLOBALS['fc_test_post_meta'][103] = array();

    Assertions::same(
        FOOCONVERT_POPUP_TYPE_BAR,
        fooconvert_get_widget_popup_type( 101 ),
        'Popup type resolution should prefer stored popup type meta.'
    );

    Assertions::same(
        FOOCONVERT_CPT_BAR,
        fooconvert_get_widget_logical_post_type( 101 ),
        'Logical widget post type should map popup type meta back to the legacy widget surface.'
    );

    Assertions::same(
        FOOCONVERT_POPUP_TYPE_FLYOUT,
        fooconvert_get_widget_popup_type( 102 ),
        'Popup type resolution should fall back to the root block when popup type meta is missing.'
    );

    Assertions::same(
        FOOCONVERT_CPT_FLYOUT,
        fooconvert_get_widget_logical_post_type( 102 ),
        'Logical widget post type should use the block fallback when popup type meta is missing.'
    );

    Assertions::same(
        FOOCONVERT_POPUP_TYPE_BAR,
        fooconvert_get_widget_popup_type( 'fc/bar' ),
        'Popup type normalization should accept legacy widget block names.'
    );

    Assertions::same(
        'Bar',
        fooconvert_get_widget_post_type_label( 101 ),
        'Widget labels should be derived from the resolved popup type.'
    );

    $migration = new ContentMigration();
    $reflection = new \ReflectionClass( $migration );
    $method = $reflection->getMethod( 'get_legacy_post_type_migration_map' );
    $method->setAccessible( true );

    Assertions::same(
        array(
            FOOCONVERT_CPT_BAR    => FOOCONVERT_POPUP_TYPE_BAR,
            FOOCONVERT_CPT_FLYOUT => FOOCONVERT_POPUP_TYPE_FLYOUT,
        ),
        $method->invoke( $migration ),
        'The CPT merge migration map should convert legacy bars and flyouts into popup types.'
    );

    echo "popup-type-core: ok\n";
}
