<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\RestController as PopupBuilder;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( ! class_exists( 'WP_Post', false ) ) {
        class WP_Post {
            public int $ID;
            public string $post_type;
            public string $post_status;
            public string $post_title;
            public string $post_content;

            public function __construct( int $id, string $title, string $content, string $status = 'draft', string $post_type = 'fc-popup' ) {
                $this->ID           = $id;
                $this->post_type    = $post_type;
                $this->post_status  = $status;
                $this->post_title   = $title;
                $this->post_content = $content;
            }
        }
    }

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
                $this->title       = $title;
                $this->description = $description;
                $this->attributes  = $attributes;
                $this->parent      = $parent;
                $this->ancestor    = $ancestor;
                $this->supports    = $supports;
                $this->inserter    = $inserter;
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
                return array();
            }
        }
    }

    class WP_Error {
        private string $code;
        private string $message;
        private $data;

        public function __construct( string $code, string $message, $data = null ) {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
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

    $GLOBALS['fc_posts'] = array(
        11 => new WP_Post( 11, 'Bar Campaign', 'BAR_MARKUP', 'publish' ),
        12 => new WP_Post( 12, 'Flyout Campaign', 'FLYOUT_MARKUP', 'draft' ),
        13 => new WP_Post( 13, 'Overlay Campaign', 'OVERLAY_MARKUP', 'draft' ),
        14 => new WP_Post( 14, 'Unsupported Campaign', 'UNSUPPORTED_MARKUP', 'draft' ),
        15 => new WP_Post( 15, 'Regular Post', 'OVERLAY_MARKUP', 'draft', 'post' ),
        16 => new WP_Post( 16, 'Raw HTML Campaign', 'RAW_HTML_MARKUP', 'draft' ),
        17 => new WP_Post( 17, 'Legacy Popup Campaign', 'LEGACY_POPUP_MARKUP', 'draft' ),
        18 => new WP_Post( 18, 'Shell Unsupported Campaign', 'SHELL_UNSUPPORTED_MARKUP', 'draft' ),
        19 => new WP_Post( 19, 'Nested List Campaign', 'NESTED_LIST_MARKUP', 'draft' ),
        20 => new WP_Post( 20, 'Top Level Sibling Campaign', 'TOP_LEVEL_SIBLING_MARKUP', 'draft' ),
        21 => new WP_Post( 21, 'Duplicate Content Campaign', 'DUPLICATE_CONTENT_MARKUP', 'draft' ),
        22 => new WP_Post( 22, 'Legacy Scroll Trigger Campaign', 'LEGACY_SCROLL_TRIGGER_MARKUP', 'draft' ),
        23 => new WP_Post( 23, 'Legacy Timer Trigger Campaign', 'LEGACY_TIMER_TRIGGER_MARKUP', 'draft' ),
        24 => new WP_Post( 24, 'Legacy Anchor Trigger Campaign', 'LEGACY_ANCHOR_TRIGGER_MARKUP', 'draft' ),
        25 => new WP_Post( 25, 'Legacy Element Trigger Campaign', 'LEGACY_ELEMENT_TRIGGER_MARKUP', 'draft' ),
        26 => new WP_Post( 26, 'Legacy Visible Trigger Campaign', 'LEGACY_VISIBLE_TRIGGER_MARKUP', 'draft' ),
        27 => new WP_Post( 27, 'Legacy Exit Trigger Campaign', 'LEGACY_EXIT_TRIGGER_MARKUP', 'draft' ),
        28 => new WP_Post( 28, 'Image Caption Campaign', 'IMAGE_CAPTION_MARKUP', 'draft' ),
        29 => new WP_Post( 29, 'Custom Trigger Campaign', 'UNSUPPORTED_TRIGGER_MARKUP', 'draft' ),
        30 => new WP_Post( 30, 'Unknown Legacy Trigger Campaign', 'UNKNOWN_LEGACY_TRIGGER_MARKUP', 'draft' ),
        31 => new WP_Post( 31, 'Invalid Frequency Campaign', 'INVALID_FREQUENCY_TRIGGER_MARKUP', 'draft' ),
        32 => new WP_Post( 32, 'Raw Content Container Campaign', 'RAW_CONTENT_CONTAINER_MARKUP', 'draft' ),
        33 => new WP_Post( 33, 'Raw Shell Campaign', 'RAW_SHELL_MARKUP', 'draft' ),
        34 => new WP_Post( 34, 'Negative Within Trigger Campaign', 'NEGATIVE_WITHIN_TRIGGER_MARKUP', 'draft' ),
        35 => new WP_Post( 35, 'Mixed Raw Content Campaign', 'MIXED_RAW_CONTENT_CONTAINER_MARKUP', 'draft' ),
        36 => new WP_Post( 36, 'Mixed Raw Shell Campaign', 'MIXED_RAW_SHELL_MARKUP', 'draft' ),
        37 => new WP_Post( 37, 'Mixed Raw Root Campaign', 'MIXED_RAW_ROOT_MARKUP', 'draft' ),
        38 => new WP_Post( 38, 'Mixed Raw Wrapper Campaign', 'MIXED_RAW_WRAPPER_MARKUP', 'draft' ),
        39 => new WP_Post( 39, 'Invalid Lifetime Campaign', 'INVALID_LIFETIME_TRIGGER_MARKUP', 'draft' ),
        40 => new WP_Post( 40, 'Safe Group Wrapper Campaign', 'SAFE_GROUP_WRAPPER_MARKUP', 'draft' ),
        41 => new WP_Post( 41, 'Legacy Timer Default Campaign', 'LEGACY_TIMER_DEFAULT_TRIGGER_MARKUP', 'draft' ),
    );
    $GLOBALS['fc_popup_type_meta'] = array(
        11 => 'bar',
        12 => 'flyout',
        13 => 'overlay',
        14 => 'overlay',
    );
    $GLOBALS['fc_ai_meta'] = array(
        11 => array(
            'messages' => array(
                array(
                    'role'    => 'user',
                    'content' => 'Make the bar sharper',
                ),
            ),
            'assistant_message' => 'Loaded from prior AI context.',
            'suggested_prompts' => array( 'Make it shorter' ),
            'media_items'       => array(
                array(
                    'id'  => 99,
                    'url' => 'https://example.test/image.jpg',
                ),
            ),
        ),
    );
    $GLOBALS['fc_can_edit_post'] = true;
    $GLOBALS['fc_updated_post'] = array();
    $GLOBALS['fc_saved_meta'] = array();

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}

    function register_rest_route( string $namespace, string $route, array $args ): void {}

    function register_post_meta( string $post_type, string $meta_key, array $args ): void {}

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

    function current_user_can( string $capability, int $post_id = 0 ): bool {
        if ( 'edit_post' === $capability ) {
            return (bool) $GLOBALS['fc_can_edit_post'];
        }

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

    function get_post( int $post_id ) {
        return $GLOBALS['fc_posts'][ $post_id ] ?? null;
    }

    function get_post_meta( int $post_id, string $meta_key, bool $single = false ) {
        if ( $meta_key === FOOCONVERT_META_KEY_POPUP_TYPE ) {
            return $GLOBALS['fc_popup_type_meta'][ $post_id ] ?? '';
        }

        if ( $meta_key === FOOCONVERT_META_KEY_AI_BUILDER_METADATA ) {
            return $GLOBALS['fc_ai_meta'][ $post_id ] ?? array();
        }

        return '';
    }

    function get_the_title( int $post_id ): string {
        return isset( $GLOBALS['fc_posts'][ $post_id ] ) ? $GLOBALS['fc_posts'][ $post_id ]->post_title : '';
    }

    function parse_blocks( string $content ): array {
        $content_block = function( string $name, array $children ): array {
            return array(
                'blockName'    => $name,
                'attrs'        => array(),
                'innerBlocks'  => $children,
                'innerHTML'    => '',
                'innerContent' => array(),
            );
        };
        $heading = static function( string $content ): array {
            return array(
                'blockName'    => 'core/heading',
                'attrs'        => array(),
                'innerBlocks'  => array(),
                'innerHTML'    => '<h2>' . $content . '</h2>',
                'innerContent' => array(),
            );
        };
        $button = static function( string $text ): array {
            return array(
                'blockName'    => 'core/buttons',
                'attrs'        => array(),
                'innerBlocks'  => array(
                    array(
                        'blockName'    => 'core/button',
                        'attrs'        => array(),
                        'innerBlocks'  => array(),
                        'innerHTML'    => '<div class="wp-block-button"><a class="wp-block-button__link" href="/shop">' . $text . '</a></div>',
                        'innerContent' => array(),
                    ),
                ),
                'innerHTML'    => '',
                'innerContent' => array(),
            );
        };
        $image = static function(): array {
            return array(
                'blockName'    => 'core/image',
                'attrs'        => array(),
                'innerBlocks'  => array(),
                'innerHTML'    => '<figure class="wp-block-image"><img src="https://example.test/captioned.jpg" alt="Captioned image"/><figcaption>Caption with <strong>proof</strong></figcaption></figure>',
                'innerContent' => array( '<figure class="wp-block-image"><img src="https://example.test/captioned.jpg" alt="Captioned image"/><figcaption>Caption with <strong>proof</strong></figcaption></figure>' ),
            );
        };
        $list = static function(): array {
            return array(
                'blockName'    => 'core/list',
                'attrs'        => array(),
                'innerBlocks'  => array(
                    array(
                        'blockName'    => 'core/list-item',
                        'attrs'        => array(),
                        'innerBlocks'  => array(),
                        'innerHTML'    => '<li>Fast setup</li>',
                        'innerContent' => array(),
                    ),
                ),
                'innerHTML'    => '',
                'innerContent' => array(),
            );
        };
        $legacy_list = static function(): array {
            return array(
                'blockName'    => 'core/list',
                'attrs'        => array(),
                'innerBlocks'  => array(),
                'innerHTML'    => '<ul><li>Legacy list item</li></ul>',
                'innerContent' => array( '<ul><li>Legacy list item</li></ul>' ),
            );
        };
        $trigger = array(
            'version'   => 2,
            'lifetime'  => 'page',
            'frequency' => array(
                'mode'            => 'repeat',
                'cooldownSeconds' => 30,
            ),
            'steps'     => array(
                array(
                    'event' => 'fc.timer.elapsed',
                    'where' => array( 'seconds' => 3 ),
                ),
            ),
        );
        $overlay_with_trigger = static function( array $trigger ) use ( $content_block, $heading ): array {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(
                        'settings' => array( 'trigger' => $trigger ),
                    ),
                    'innerBlocks'  => array(
                        $content_block( 'fc/overlay-content', array( $heading( 'Triggered offer' ) ) ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        };

        if ( 'BAR_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/bar',
                    'attrs'        => array(
                        'viewState'     => 'open',
                        'variation'     => 'bar-variant',
                        'customRootAttr' => array( 'enabled' => true ),
                        'settings' => array( 'trigger' => $trigger ),
                        'content'  => array(
                            'styles' => array(
                                'color' => array( 'background' => '#111111' ),
                            ),
                        ),
                    ),
                    'innerBlocks'  => array(
                        $content_block( 'fc/bar-content', array( $heading( 'Save today' ), $button( 'Shop now' ), $list(), $legacy_list() ) ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'FLYOUT_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/flyout',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block( 'fc/flyout-content', array( $heading( 'Join the list' ) ) ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'OVERLAY_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block( 'fc/overlay-content', array( $heading( 'Exit offer' ) ) ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'MIXED_RAW_ROOT_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block( 'fc/overlay-content', array( $heading( 'Safe content' ) ) ),
                    ),
                    'innerHTML'    => '<p>Raw root offer before child</p>',
                    'innerContent' => array( '<p>Raw root offer before child</p>', null ),
                ),
            );
        }

        if ( 'LEGACY_POPUP_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/popup',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block( 'fc/popup-content', array( $heading( 'Legacy popup offer' ) ) ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'UNSUPPORTED_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block(
                            'fc/overlay-content',
                            array(
                                array(
                                    'blockName'    => 'core/html',
                                    'attrs'        => array( 'content' => '<strong>Unsupported</strong>' ),
                                    'innerBlocks'  => array(),
                                    'innerHTML'    => '',
                                    'innerContent' => array(),
                                ),
                            )
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'RAW_HTML_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block(
                            'fc/overlay-content',
                            array(
                                array(
                                    'blockName'    => null,
                                    'attrs'        => array(),
                                    'innerBlocks'  => array(),
                                    'innerHTML'    => '<div>Loose raw content</div>',
                                    'innerContent' => array( '<div>Loose raw content</div>' ),
                                ),
                            )
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'SHELL_UNSUPPORTED_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        array(
                            'blockName'    => 'fc/overlay-container',
                            'attrs'        => array(),
                            'innerBlocks'  => array(
                                $content_block( 'fc/overlay-content', array( $heading( 'Safe content' ) ) ),
                                array(
                                    'blockName'    => 'core/html',
                                    'attrs'        => array( 'content' => '<strong>Outside content container</strong>' ),
                                    'innerBlocks'  => array(),
                                    'innerHTML'    => '',
                                    'innerContent' => array(),
                                ),
                            ),
                            'innerHTML'    => '',
                            'innerContent' => array(),
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'RAW_CONTENT_CONTAINER_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        array(
                            'blockName'    => 'fc/overlay-content',
                            'attrs'        => array(),
                            'innerBlocks'  => array(),
                            'innerHTML'    => '<p>Raw offer</p>',
                            'innerContent' => array( '<p>Raw offer</p>' ),
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'MIXED_RAW_CONTENT_CONTAINER_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        array(
                            'blockName'    => 'fc/overlay-content',
                            'attrs'        => array(),
                            'innerBlocks'  => array( $heading( 'Supported heading' ) ),
                            'innerHTML'    => '<p>Raw offer before child</p>',
                            'innerContent' => array( '<p>Raw offer before child</p>', null ),
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'MIXED_RAW_WRAPPER_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block(
                            'fc/overlay-content',
                            array(
                                array(
                                    'blockName'    => 'core/group',
                                    'attrs'        => array(),
                                    'innerBlocks'  => array( $heading( 'Supported group heading' ) ),
                                    'innerHTML'    => '<div class="wp-block-group"><p>Loose wrapper raw</p></div>',
                                    'innerContent' => array( '<div class="wp-block-group"><p>Loose wrapper raw</p>', null, '</div>' ),
                                ),
                            )
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'SAFE_GROUP_WRAPPER_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block(
                            'fc/overlay-content',
                            array(
                                array(
                                    'blockName'    => 'core/group',
                                    'attrs'        => array( 'tagName' => 'header' ),
                                    'innerBlocks'  => array( $heading( 'Safe group heading' ) ),
                                    'innerHTML'    => '<header class="wp-block-group"></header>',
                                    'innerContent' => array( '<header class="wp-block-group">', null, '</header>' ),
                                ),
                            )
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'RAW_SHELL_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        array(
                            'blockName'    => 'fc/overlay-container',
                            'attrs'        => array(),
                            'innerBlocks'  => array(),
                            'innerHTML'    => '<p>Raw shell offer</p>',
                            'innerContent' => array( '<p>Raw shell offer</p>' ),
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'MIXED_RAW_SHELL_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        array(
                            'blockName'    => 'fc/overlay-container',
                            'attrs'        => array(),
                            'innerBlocks'  => array(
                                $content_block( 'fc/overlay-content', array( $heading( 'Safe content' ) ) ),
                            ),
                            'innerHTML'    => '<p>Raw shell offer before child</p>',
                            'innerContent' => array( '<p>Raw shell offer before child</p>', null ),
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'NESTED_LIST_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block(
                            'fc/overlay-content',
                            array(
                                array(
                                    'blockName'    => 'core/list',
                                    'attrs'        => array(),
                                    'innerBlocks'  => array(
                                        array(
                                            'blockName'    => 'core/list-item',
                                            'attrs'        => array(),
                                            'innerBlocks'  => array(
                                                array(
                                                    'blockName'    => 'core/list',
                                                    'attrs'        => array(),
                                                    'innerBlocks'  => array(),
                                                    'innerHTML'    => '<ul><li>Nested detail</li></ul>',
                                                    'innerContent' => array( '<ul><li>Nested detail</li></ul>' ),
                                                ),
                                            ),
                                            'innerHTML'    => '<li>Parent item</li>',
                                            'innerContent' => array( '<li>Parent item</li>' ),
                                        ),
                                    ),
                                    'innerHTML'    => '',
                                    'innerContent' => array(),
                                ),
                            )
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'TOP_LEVEL_SIBLING_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block( 'fc/overlay-content', array( $heading( 'Safe content' ) ) ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
                array(
                    'blockName'    => 'core/html',
                    'attrs'        => array( 'content' => '<strong>Top level sibling</strong>' ),
                    'innerBlocks'  => array(),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'DUPLICATE_CONTENT_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        array(
                            'blockName'    => 'fc/overlay-container',
                            'attrs'        => array(),
                            'innerBlocks'  => array(
                                $content_block( 'fc/overlay-content', array( $heading( 'First content' ) ) ),
                                $content_block( 'fc/overlay-content', array( $heading( 'Second content' ) ) ),
                            ),
                            'innerHTML'    => '',
                            'innerContent' => array(),
                        ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'IMAGE_CAPTION_MARKUP' === $content ) {
            return array(
                array(
                    'blockName'    => 'fc/overlay',
                    'attrs'        => array(),
                    'innerBlocks'  => array(
                        $content_block( 'fc/overlay-content', array( $image() ) ),
                    ),
                    'innerHTML'    => '',
                    'innerContent' => array(),
                ),
            );
        }

        if ( 'LEGACY_SCROLL_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'type' => 'scroll',
                    'data' => 50,
                    'once' => false,
                )
            );
        }

        if ( 'LEGACY_TIMER_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'type' => 'timer',
                    'data' => 9,
                    'once' => true,
                )
            );
        }

        if ( 'LEGACY_TIMER_DEFAULT_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'type' => 'timer',
                    'once' => true,
                )
            );
        }

        if ( 'LEGACY_ANCHOR_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'type' => 'anchor',
                    'data' => 'claim, hero',
                    'once' => false,
                )
            );
        }

        if ( 'LEGACY_ELEMENT_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'type' => 'element',
                    'data' => '.promo-button',
                    'once' => false,
                )
            );
        }

        if ( 'LEGACY_VISIBLE_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'type' => 'visible',
                    'data' => 'pricing,footer',
                    'once' => true,
                )
            );
        }

        if ( 'LEGACY_EXIT_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'type' => 'exit-intent',
                    'data' => 12,
                    'once' => false,
                )
            );
        }

        if ( 'UNSUPPORTED_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'version'   => 2,
                    'lifetime'  => 'page',
                    'frequency' => array(
                        'mode'            => 'once',
                        'cooldownSeconds' => 0,
                    ),
                    'steps'     => array(
                        array(
                            'event' => 'vendor.future.trigger',
                            'where' => array( 'id' => 'campaign' ),
                        ),
                    ),
                )
            );
        }

        if ( 'UNKNOWN_LEGACY_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'type' => 'future-trigger',
                    'data' => 'campaign',
                )
            );
        }

        if ( 'INVALID_FREQUENCY_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'version'   => 2,
                    'lifetime'  => 'page',
                    'frequency' => array(
                        'mode'            => 'sometimes',
                        'cooldownSeconds' => -1,
                    ),
                    'steps'     => array(
                        array(
                            'event' => 'fc.timer.elapsed',
                            'where' => array( 'seconds' => 5 ),
                        ),
                    ),
                )
            );
        }

        if ( 'INVALID_LIFETIME_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'version'   => 2,
                    'lifetime'  => 'future',
                    'frequency' => array(
                        'mode'            => 'once',
                        'cooldownSeconds' => 0,
                    ),
                    'steps'     => array(
                        array(
                            'event' => 'fc.timer.elapsed',
                            'where' => array( 'seconds' => 5 ),
                        ),
                    ),
                )
            );
        }

        if ( 'NEGATIVE_WITHIN_TRIGGER_MARKUP' === $content ) {
            return $overlay_with_trigger(
                array(
                    'version'   => 2,
                    'lifetime'  => 'page',
                    'frequency' => array(
                        'mode'            => 'once',
                        'cooldownSeconds' => 0,
                    ),
                    'steps'     => array(
                        array(
                            'event'         => 'fc.timer.elapsed',
                            'where'         => array( 'seconds' => 5 ),
                            'withinSeconds' => -5,
                        ),
                    ),
                )
            );
        }

        return array();
    }

    function serialize_blocks( array $blocks ): string {
        return wp_json_encode( $blocks );
    }

    function wp_insert_post( array $postarr, bool $wp_error = false ) {
        return 99;
    }

    function wp_update_post( array $postarr, bool $wp_error = false ) {
        $GLOBALS['fc_updated_post'] = $postarr;
        return $postarr['ID'];
    }

    function wp_delete_post( int $post_id, bool $force_delete = false ): void {}

    function update_post_meta( int $post_id, string $meta_key, $value ): void {
        $GLOBALS['fc_saved_meta'][ $meta_key ] = $value;
    }

    function is_wp_error( $thing ): bool {
        return $thing instanceof WP_Error;
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
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Config.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Blueprint/DraftNormalizer.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Media/Attachments.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/RestController.php';

    $builder = new PopupBuilder();

    $bar = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 11 ) ) );

    Assertions::true(
        $bar instanceof WP_REST_Response,
        'Loading an existing bar should return a REST response.'
    );

    Assertions::same(
        FOOCONVERT_POPUP_TYPE_BAR,
        $bar->data['draft']['popup_type'],
        'Loading an existing bar should preserve the builder popup type.'
    );

    Assertions::same(
        'Save today',
        $bar->data['draft']['content_blocks'][0]['attributes']['content'],
        'Loading an existing popup should preserve source-backed heading content from block HTML.'
    );

    Assertions::same(
        'Shop now',
        $bar->data['draft']['content_blocks'][1]['inner_blocks'][0]['attributes']['text'],
        'Loading an existing popup should preserve source-backed button text from block HTML.'
    );

    Assertions::same(
        array( 'Fast setup' ),
        $bar->data['draft']['content_blocks'][2]['attributes']['items'],
        'Loading an existing popup should preserve core/list item copy.'
    );

    Assertions::same(
        array( 'Legacy list item' ),
        $bar->data['draft']['content_blocks'][3]['attributes']['items'],
        'Loading an existing popup should preserve legacy core/list inner HTML item copy.'
    );

    Assertions::same(
        30,
        $bar->data['draft']['trigger']['frequency']['cooldownSeconds'],
        'Loading an existing popup should preserve trigger cooldowns.'
    );

    Assertions::same(
        'bar-variant',
        $bar->data['draft']['root_attributes']['variation'],
        'Loading an existing popup should preserve non-dynamic root attributes.'
    );

    Assertions::same(
        true,
        $bar->data['draft']['root_attributes']['customRootAttr']['enabled'],
        'Loading an existing popup should preserve custom root attribute data.'
    );

    Assertions::same(
        'Make the bar sharper',
        $bar->data['messages'][0]['content'],
        'Loading an existing popup should include prior AI metadata as supplemental context.'
    );

    foreach ( array( 12 => FOOCONVERT_POPUP_TYPE_FLYOUT, 13 => FOOCONVERT_POPUP_TYPE_POPUP ) as $post_id => $expected_type ) {
        $response = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => $post_id ) ) );

        Assertions::true(
            $response instanceof WP_REST_Response,
            'Loading an existing popup type should return a REST response.'
        );

        Assertions::same(
            $expected_type,
            $response->data['draft']['popup_type'],
            'Loading an existing popup should normalize every root block type into the AI builder type.'
        );
    }

    $legacy = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 17 ) ) );

    Assertions::true(
        $legacy instanceof WP_REST_Response,
        'Loading a legacy popup root should return a REST response.'
    );

    Assertions::same(
        'Legacy popup offer',
        $legacy->data['draft']['content_blocks'][0]['attributes']['content'],
        'Loading a legacy popup root should read from the legacy popup content container.'
    );

    $unsupported = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 14 ) ) );

    Assertions::same(
        'fooconvert_ai_popup_builder_cannot_load_popup',
        $unsupported->get_error_code(),
        'Loading should reject popups that would drop unsupported content blocks.'
    );

    $raw_html = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 16 ) ) );

    Assertions::same(
        'fooconvert_ai_popup_builder_cannot_load_popup',
        $raw_html->get_error_code(),
        'Loading should reject raw freeform HTML instead of silently dropping it.'
    );

    $shell_unsupported = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 18 ) ) );

    Assertions::same(
        'fooconvert_ai_popup_builder_cannot_load_popup',
        $shell_unsupported->get_error_code(),
        'Loading should reject unsupported popup shell siblings instead of silently dropping them.'
    );

    $legacy_scroll_trigger = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 22 ) ) );

    Assertions::same(
        'fc.scroll.percent',
        $legacy_scroll_trigger->data['draft']['trigger']['steps'][0]['event'],
        'Loading should convert a legacy scroll trigger into the V2 trigger event.'
    );

    Assertions::same(
        50,
        $legacy_scroll_trigger->data['draft']['trigger']['steps'][0]['where']['percent'],
        'Loading should preserve a legacy scroll trigger percent.'
    );

    Assertions::same(
        'repeat',
        $legacy_scroll_trigger->data['draft']['trigger']['frequency']['mode'],
        'Loading should preserve legacy trigger repeat behavior.'
    );

    $legacy_timer_trigger = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 23 ) ) );

    Assertions::same(
        9,
        $legacy_timer_trigger->data['draft']['trigger']['steps'][0]['where']['seconds'],
        'Loading should preserve legacy timer trigger seconds.'
    );

    $legacy_timer_default_trigger = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 41 ) ) );

    Assertions::same(
        15,
        $legacy_timer_default_trigger->data['draft']['trigger']['steps'][0]['where']['seconds'],
        'Loading should synthesize the editor default for legacy timer triggers without data.'
    );

    Assertions::same(
        'once',
        $legacy_timer_default_trigger->data['draft']['trigger']['frequency']['mode'],
        'Loading should preserve legacy timer once behavior when data is omitted.'
    );

    $legacy_anchor_trigger = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 24 ) ) );

    Assertions::same(
        array( 'claim', 'hero' ),
        $legacy_anchor_trigger->data['draft']['trigger']['steps'][0]['where']['ids'],
        'Loading should preserve legacy anchor trigger IDs.'
    );

    $legacy_element_trigger = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 25 ) ) );

    Assertions::same(
        '.promo-button',
        $legacy_element_trigger->data['draft']['trigger']['steps'][0]['where']['selector'],
        'Loading should preserve legacy element trigger selectors.'
    );

    $legacy_visible_trigger = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 26 ) ) );

    Assertions::same(
        array( 'pricing', 'footer' ),
        $legacy_visible_trigger->data['draft']['trigger']['steps'][0]['where']['ids'],
        'Loading should preserve legacy visible trigger IDs.'
    );

    $legacy_exit_trigger = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 27 ) ) );

    Assertions::same(
        12,
        $legacy_exit_trigger->data['draft']['trigger']['steps'][0]['where']['delaySeconds'],
        'Loading should preserve legacy exit-intent trigger delay.'
    );

    $image_caption = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 28 ) ) );

    Assertions::same(
        'https://example.test/captioned.jpg',
        $image_caption->data['draft']['content_blocks'][0]['attributes']['url'],
        'Loading should preserve a source-backed image URL.'
    );

    Assertions::same(
        'Caption with <strong>proof</strong>',
        $image_caption->data['draft']['content_blocks'][0]['attributes']['caption'],
        'Loading should preserve a source-backed image caption.'
    );

    $safe_group_wrapper = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 40 ) ) );

    Assertions::true(
        $safe_group_wrapper instanceof WP_REST_Response,
        'Loading should allow structural core/group wrapper markup when content is represented by child blocks.'
    );

    Assertions::same(
        'header',
        $safe_group_wrapper->data['draft']['content_blocks'][0]['attributes']['tagName'],
        'Loading should preserve structural core/group tag names.'
    );

    foreach ( array( 19, 20, 21, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39 ) as $post_id ) {
        $unsafe = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => $post_id ) ) );

        Assertions::same(
            'fooconvert_ai_popup_builder_cannot_load_popup',
            $unsafe->get_error_code(),
            'Loading should reject existing markup shapes that cannot be represented without dropping content.'
        );
    }

    Assertions::false(
        $builder->can_manage_popups( new WP_REST_Request( array( 'post_id' => 15 ) ) ),
        'AI builder save/chat permissions should not be granted through a non-popup post_id.'
    );

    $GLOBALS['fc_can_edit_post'] = false;
    $forbidden = $builder->handle_get_popup( new WP_REST_Request( array( 'post_id' => 11 ) ) );
    $GLOBALS['fc_can_edit_post'] = true;

    Assertions::same(
        'fooconvert_ai_popup_builder_cannot_edit_popup',
        $forbidden->get_error_code(),
        'Loading should reject users who cannot edit the popup.'
    );

    $saved = $builder->handle_save(
        new WP_REST_Request(
            array(
                'post_id'      => 11,
                'title'        => 'Updated Bar Campaign',
                'popup_type'   => FOOCONVERT_POPUP_TYPE_BAR,
                'post_content' => 'BAR_MARKUP',
            )
        )
    );

    Assertions::true(
        $saved instanceof WP_REST_Response,
        'Saving an AI update to an existing popup should return a REST response.'
    );

    Assertions::same(
        'publish',
        $GLOBALS['fc_updated_post']['post_status'],
        'Saving an AI update to a published popup should preserve publish status.'
    );

    Assertions::same(
        'publish',
        $saved->data['status'],
        'The save response should include the preserved existing status.'
    );

    echo "ai-popup-load-existing: ok\n";
}
