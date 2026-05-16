<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\RestController as PopupBuilder;
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

    class WP_Error {
        private string $code;
        private string $message;
        private $data;

        public function __construct( string $code, string $message, $data = null ) {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code(): string {
            return $this->code;
        }

        public function get_error_message(): string {
            return $this->message;
        }

        public function get_error_data() {
            return $this->data;
        }
    }

    class WP_REST_Request {
        /** @var array<string,mixed> */
        private array $params;

        public function __construct( array $params ) {
            $this->params = $params;
        }

        public function get_param( string $key ) {
            return $this->params[ $key ] ?? null;
        }
    }

    class WP_REST_Response {
        /** @var array<string,mixed> */
        public array $data;

        public function __construct( array $data ) {
            $this->data = $data;
        }
    }

    $GLOBALS['fc_saved_post_id'] = 321;
    $GLOBALS['fc_updated_post'] = array();
    $GLOBALS['fc_saved_meta'] = array();
    $GLOBALS['fc_registered_meta'] = array();

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}

    function register_rest_route( string $namespace, string $route, array $args ): void {}

    function register_post_meta( string $post_type, string $meta_key, array $args ): void {
        $GLOBALS['fc_registered_meta'][ $meta_key ] = $args;
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

    function current_user_can( string $capability ): bool {
        return true;
    }

    function get_post_type_object( string $post_type ) {
        return (object) array(
            'cap' => (object) array(
                'create_posts' => 'edit_posts',
            ),
        );
    }

    function admin_url( string $path = '' ): string {
        return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
    }

    function home_url( string $path = '' ): string {
        return 'https://example.test/' . ltrim( $path, '/' );
    }

    function add_query_arg( array $args, string $url ): string {
        return rtrim( $url, '?' ) . '?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
    }

    function wp_create_nonce( string $action ): string {
        return 'nonce-' . $action;
    }

    function absint( $value ): int {
        return abs( (int) $value );
    }

    function wp_json_encode( $value ): string {
        return json_encode( $value );
    }

    function did_action( string $hook ): int {
        return 1;
    }

    function doing_action( string $hook = '' ): bool {
        return false;
    }

    function parse_blocks( string $content ): array {
        if ( false !== strpos( $content, 'fc/overlay' ) ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(),
                    'innerHTML'    => '',
                    'innerContent' => array( '<div>Popup</div>' ),
                ),
            );
        }

        if ( false !== strpos( $content, 'fc/flyout' ) ) {
            return array(
                array(
                    'blockName'    => 'fc/flyout',
                    'attrs'        => array(),
                    'innerBlocks'  => array(),
                    'innerHTML'    => '',
                    'innerContent' => array( '<div>Flyout</div>' ),
                ),
            );
        }

        return array();
    }

    function serialize_blocks( array $blocks ): string {
        return wp_json_encode( $blocks );
    }

    function wp_insert_post( array $postarr, bool $wp_error = false ) {
        return $GLOBALS['fc_saved_post_id'];
    }

    function is_wp_error( $thing ): bool {
        return $thing instanceof WP_Error;
    }

    function wp_update_post( array $postarr, bool $wp_error = false ) {
        $GLOBALS['fc_updated_post'] = $postarr;
        return $postarr['ID'];
    }

    function wp_delete_post( int $post_id, bool $force_delete = false ): void {}

    function update_post_meta( int $post_id, string $meta_key, $value ): void {
        if ( isset( $GLOBALS['fc_registered_meta'][ $meta_key ]['sanitize_callback'] ) && is_callable( $GLOBALS['fc_registered_meta'][ $meta_key ]['sanitize_callback'] ) ) {
            $value = call_user_func( $GLOBALS['fc_registered_meta'][ $meta_key ]['sanitize_callback'], $value );
        }

        $GLOBALS['fc_saved_meta'][ $meta_key ] = $value;
    }

    function fooconvert_admin_url_widget_edit( int $widget_id ): string {
        return 'https://example.test/wp-admin/post.php?post=' . $widget_id . '&action=edit';
    }

    function get_the_title( int $post_id ): string {
        return 'AI Popup Draft';
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
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Config.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Blueprint/DraftNormalizer.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Media/Attachments.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/RestController.php';

    $builder = new PopupBuilder();
    $builder->register_saved_meta();

    $response = $builder->handle_save(
        new WP_REST_Request(
            array(
                'title'        => 'AI Popup Draft',
                'popup_type'   => FOOCONVERT_POPUP_TYPE_POPUP,
                'post_content' => '<!-- wp:fc/overlay --><div>Popup</div><!-- /wp:fc/overlay -->',
                'ai_metadata'  => array(
                    'messages' => array(
                        array(
                            'role'    => 'user',
                            'content' => 'Build a popup',
                        ),
                        array(
                            'role'    => 'assistant',
                            'content' => 'Here is a popup draft.',
                        ),
                    ),
                    'assistant_message' => 'Here is a popup draft.',
                    'clarifying_question' => 'Should this be exit intent?',
                    'suggested_prompts' => array( 'Tighten the copy' ),
                    'popup_draft'       => array(
                        'popup_type' => FOOCONVERT_POPUP_TYPE_POPUP,
                    ),
                    'validation'        => array(
                        'score' => 88,
                    ),
                    'media_items'       => array(
                        array(
                            'id'  => 22,
                            'url' => 'https://example.test/generated-popup-image.jpg',
                        ),
                    ),
                    'options'          => array(
                        'generate_images' => true,
                    ),
                ),
            )
        )
    );

    Assertions::true(
        $response instanceof WP_REST_Response,
        'Saving a valid AI popup draft should return a REST response.'
    );

    Assertions::same(
        $GLOBALS['fc_saved_post_id'],
        $response->data['postId'],
        'The save response should return the newly created popup ID.'
    );

    Assertions::same(
        'https://example.test/?fooconvert_popup_preview=321&_fcpreviewnonce=nonce-fooconvert-popup-preview-321',
        $response->data['previewUrl'],
        'Saving an AI popup should return the shared frontend popup preview URL.'
    );

    Assertions::same(
        FOOCONVERT_POPUP_TYPE_OVERLAY,
        $GLOBALS['fc_saved_meta'][FOOCONVERT_META_KEY_POPUP_TYPE],
        'Saving an AI popup should store the logical popup type meta.'
    );

    Assertions::true(
        false !== strpos( $GLOBALS['fc_updated_post']['post_content'], '"postId":321' ),
        'Saving an AI popup should inject the saved post ID into the root block attributes.'
    );

    Assertions::true(
        false !== strpos( $GLOBALS['fc_updated_post']['post_content'], '"postType":"fc-popup"' ),
        'Saving an AI popup should inject the popup post type into the root block attributes.'
    );

    Assertions::same(
        'ai-popup-builder',
        $GLOBALS['fc_saved_meta'][FOOCONVERT_META_KEY_AI_BUILDER_METADATA]['source'],
        'Saving an AI popup should persist the AI builder metadata alongside the popup.'
    );

    Assertions::same(
        'https://example.test/generated-popup-image.jpg',
        $GLOBALS['fc_saved_meta'][FOOCONVERT_META_KEY_AI_BUILDER_METADATA]['response']['media_items'][0]['url'],
        'Saving an AI popup should persist generated popup media metadata.'
    );

    Assertions::same(
        'Should this be exit intent?',
        $GLOBALS['fc_saved_meta'][FOOCONVERT_META_KEY_AI_BUILDER_METADATA]['response']['clarifying_question'],
        'Saving an AI popup should preserve the latest clarifying question in post meta.'
    );

    $invalid = $builder->handle_save(
        new WP_REST_Request(
            array(
                'title'        => 'Wrong Markup',
                'popup_type'   => FOOCONVERT_POPUP_TYPE_POPUP,
                'post_content' => '<!-- wp:fc/flyout --><div>Flyout</div><!-- /wp:fc/flyout -->',
            )
        )
    );

    Assertions::same(
        'fooconvert_ai_popup_builder_invalid_markup',
        $invalid->get_error_code(),
        'Saving should reject block HTML that does not match the selected popup type.'
    );

    echo "ai-popup-save: ok\n";
}
