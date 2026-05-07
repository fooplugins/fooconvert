<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Fonts;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    /** @var array<string,mixed> */
    $GLOBALS['fc_test_options'] = array();

    /** @var array<int,string> */
    $GLOBALS['fc_test_post_content'] = array();

    /** @var array<string,array<string,mixed>> */
    $GLOBALS['fc_test_enqueued_styles'] = array();

    /** @var array<string,string> */
    $GLOBALS['fc_test_inline_styles'] = array();

    /** @var array<int,array<string,mixed>> */
    $GLOBALS['fc_test_post_meta'] = array();

    if ( !class_exists( 'WP_Post', false ) ) {
        class WP_Post {
            /** @var int */
            public $ID = 0;

            /** @var string */
            public $post_type = '';

            /** @var string */
            public $post_content = '';
        }
    }

    /**
     * @param string $text
     * @param string|null $domain
     * @return string
     */
    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @return bool
     */
    function is_admin(): bool {
        return false;
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
     * @param string $hook
     * @param mixed $value
     * @return mixed
     */
    function apply_filters( string $hook, $value ) {
        return $value;
    }

    /**
     * @param string $title
     * @return string
     */
    function sanitize_title( string $title ): string {
        $title = strtolower( trim( $title ) );
        $title = preg_replace( '/[^a-z0-9]+/', '-', $title ) ?? '';

        return trim( $title, '-' );
    }

    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    function get_option( string $option, $default = false ) {
        return $GLOBALS['fc_test_options'][ $option ] ?? $default;
    }

    /**
     * @param string $field
     * @param int $post_id
     * @return string
     */
    function get_post_field( string $field, int $post_id ): string {
        if ( $field !== 'post_content' ) {
            return '';
        }

        return $GLOBALS['fc_test_post_content'][ $post_id ] ?? '';
    }

    /**
     * @param int $post_id
     * @param string $meta_key
     * @param bool $single
     * @return mixed
     */
    function get_post_meta( int $post_id, string $meta_key, bool $single = false ) {
        $meta = $GLOBALS['fc_test_post_meta'][ $post_id ][ $meta_key ] ?? array();

        return $single ? $meta : array( $meta );
    }

    /**
     * @param int $post_id
     * @param string $meta_key
     * @param mixed $meta_value
     * @return bool
     */
    function update_post_meta( int $post_id, string $meta_key, $meta_value ): bool {
        if ( !isset( $GLOBALS['fc_test_post_meta'][ $post_id ] ) || !is_array( $GLOBALS['fc_test_post_meta'][ $post_id ] ) ) {
            $GLOBALS['fc_test_post_meta'][ $post_id ] = array();
        }

        $GLOBALS['fc_test_post_meta'][ $post_id ][ $meta_key ] = $meta_value;

        return true;
    }

    /**
     * @param string $content
     * @return array<int,array<string,mixed>>
     */
    function parse_blocks( string $content ): array {
        if ( $content === ( $GLOBALS['fc_test_post_content'][ 42 ] ?? '' ) ) {
            return array(
                array(
                    'blockName'   => 'fc/flyout',
                    'attrs'       => array(
                        'content' => array(
                            'styles' => array(
                                'typography' => array(
                                    'fontFamily' => array(
                                        'key'   => 'open-sans',
                                        'name'  => 'Open Sans',
                                        'style' => array(
                                            'fontFamily' => 'Open Sans',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'innerBlocks' => array(),
                ),
            );
        }

        return array();
    }

    /**
     * @param array<string,string> $args
     * @param string $url
     * @return string
     */
    function add_query_arg( array $args, string $url ): string {
        return $url . '?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
    }

    /**
     * @param string $handle
     * @param string $src
     * @param array<int|string,mixed> $deps
     * @param mixed $ver
     * @param string $media
     * @return void
     */
    function wp_enqueue_style( string $handle, string $src, array $deps = array(), $ver = null, string $media = 'all' ): void {
        $GLOBALS['fc_test_enqueued_styles'][ $handle ] = array(
            'src'   => $src,
            'deps'  => $deps,
            'ver'   => $ver,
            'media' => $media,
        );
    }

    /**
     * @param string $handle
     * @param string $data
     * @return bool
     */
    function wp_add_inline_style( string $handle, string $data ): bool {
        $GLOBALS['fc_test_inline_styles'][ $handle ] = $data;

        return true;
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Fonts.php';

    $GLOBALS['fc_test_options'][ FOOCONVERT_OPTION_DATA ] = array(
        'fonts' => array(
            array(
                'name' => 'Open Sans',
                'url'  => 'Open+Sans:wght@400;700',
            ),
        ),
    );

    $GLOBALS['fc_test_post_content'][42] = '<!-- wp:fc/flyout {"content":{"styles":{"typography":{"fontFamily":{"key":"open-sans","name":"Open Sans","style":{"fontFamily":"Open Sans"}}}}} /-->';

    $fonts = new Fonts();
    $post = new WP_Post();
    $post->ID = 42;
    $post->post_type = FOOCONVERT_CPT_POPUP;
    $post->post_content = $GLOBALS['fc_test_post_content'][42];

    $fonts->after_insert_should_compile( 42, $post, true, null );

    Assertions::same(
        array( 'open-sans' ),
        $GLOBALS['fc_test_post_meta'][42][FOOCONVERT_META_KEY_USED_FONTS] ?? array(),
        'Popup saves should compile used font slugs into popup meta.'
    );

    $queueable = $fonts->append_font_slugs(
        array(
            'post_id' => 42,
            'content' => '<fc-flyout id="fc-flyout-42"></fc-flyout>',
        ),
        42,
        array()
    );

    Assertions::same(
        array( 'open-sans' ),
        $queueable['fontSlugs'] ?? array(),
        'Queueable popups should carry the compiled font slugs.'
    );

    $fonts->enqueue_assets( array( $queueable ) );

    Assertions::true(
        isset( $GLOBALS['fc_test_enqueued_styles']['fooconvert-google-fonts'] ),
        'Frontend font enqueue should use compiled popup font metadata.'
    );

    $enqueued_src = $GLOBALS['fc_test_enqueued_styles']['fooconvert-google-fonts']['src'] ?? '';
    Assertions::true(
        strpos( $enqueued_src, 'Open%2BSans%3Awght%40400%3B700' ) !== false,
        'The Google Fonts request should include the configured family.'
    );

    $inline_css = $GLOBALS['fc_test_inline_styles']['fooconvert-google-fonts'] ?? '';
    Assertions::true(
        strpos( $inline_css, '.uses-open-sans-font-family' ) !== false,
        'Frontend font utilities should include FooConvert host classes as well as core has-* classes.'
    );
}
