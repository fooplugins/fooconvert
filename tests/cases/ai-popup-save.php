<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

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

    function fooconvert_normalize_popup_type( $value ): string {
        return in_array( $value, array( FOOCONVERT_POPUP_TYPE_BAR, FOOCONVERT_POPUP_TYPE_FLYOUT, FOOCONVERT_POPUP_TYPE_POPUP ), true )
            ? (string) $value
            : '';
    }

    function fooconvert_get_popup_type_block_name( $popup_type ): string {
        switch ( $popup_type ) {
            case FOOCONVERT_POPUP_TYPE_BAR:
                return 'fc/bar';
            case FOOCONVERT_POPUP_TYPE_FLYOUT:
                return 'fc/flyout';
            case FOOCONVERT_POPUP_TYPE_POPUP:
            default:
                return 'fc/overlay';
        }
    }

    function fooconvert_get_popup_type_label( $popup_type ): string {
        return ucfirst( (string) $popup_type );
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

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupMedia.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBlueprint.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder.php';

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
        FOOCONVERT_POPUP_TYPE_POPUP,
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
