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
                array(
                    'post_title'   => 'Demo Flyout',
                    'meta_input'   => array(
                        FOOCONVERT_META_KEY_POPUP_TYPE => FOOCONVERT_POPUP_TYPE_FLYOUT,
                    ),
                    'post_content' => '<!-- wp:fc/flyout {"postId":0} --><div>Flyout</div><!-- /wp:fc/flyout -->',
                ),
            );
        }
    }
}

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\DraftNormalizer as PopupBlueprint;
    use FooPlugins\FooConvert\AI\PopupBuilder\Settings;
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

    function get_option( string $key, $default = false ) {
        return $GLOBALS['fc_test_options'][ $key ] ?? $default;
    }

    function update_option( string $key, $value, $autoload = null ): bool {
        $GLOBALS['fc_test_options'][ $key ] = $value;
        return true;
    }

    function current_user_can( string $capability ): bool {
        return true;
    }

    function did_action( string $hook ): int {
        return 1;
    }

    function doing_action( string $hook = '' ): bool {
        return false;
    }

    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_test_filters'][ $hook ][ $priority ][] = array(
            'callback'      => $callback,
            'accepted_args' => $accepted_args,
        );
    }

    function apply_filters( string $hook, $value, ...$args ) {
        if ( empty( $GLOBALS['fc_test_filters'][ $hook ] ) || ! is_array( $GLOBALS['fc_test_filters'][ $hook ] ) ) {
            return $value;
        }

        ksort( $GLOBALS['fc_test_filters'][ $hook ] );

        foreach ( $GLOBALS['fc_test_filters'][ $hook ] as $callbacks ) {
            foreach ( $callbacks as $filter ) {
                $accepted_args = isset( $filter['accepted_args'] ) ? (int) $filter['accepted_args'] : 1;
                $filter_args   = array_slice( array_merge( array( $value ), $args ), 0, $accepted_args );
                $value         = call_user_func_array( $filter['callback'], $filter_args );
            }
        }

        return $value;
    }

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ );
    }

    if ( ! defined( 'FOOCONVERT_INCLUDES_PATH' ) ) {
        define( 'FOOCONVERT_INCLUDES_PATH', dirname( __DIR__, 2 ) . '/includes/' );
    }

    if ( ! defined( 'FOOCONVERT_PRO_INCLUDES_PATH' ) ) {
        define( 'FOOCONVERT_PRO_INCLUDES_PATH', dirname( __DIR__, 2 ) . '/pro/includes/' );
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
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Blueprint/DraftNormalizer.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Media/Attachments.php';

    $assert_strict_object_schema = static function( array $schema, string $path ) use ( &$assert_strict_object_schema ): void {
        if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
            $property_keys = array_keys( $schema['properties'] );
            $required_keys = isset( $schema['required'] ) && is_array( $schema['required'] ) ? $schema['required'] : array();
            sort( $property_keys );
            sort( $required_keys );

            Assertions::same(
                $property_keys,
                $required_keys,
                sprintf( 'Schema object `%s` should require every declared property for strict WP AI response validation.', $path )
            );

            foreach ( $schema['properties'] as $property_name => $property_schema ) {
                if ( is_array( $property_schema ) ) {
                    $assert_strict_object_schema( $property_schema, $path . '.' . $property_name );
                }
            }
        }

        if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
            $assert_strict_object_schema( $schema['items'], $path . '[]' );
        }

        if ( isset( $schema['anyOf'] ) && is_array( $schema['anyOf'] ) ) {
            foreach ( $schema['anyOf'] as $index => $variant_schema ) {
                if ( is_array( $variant_schema ) ) {
                    $assert_strict_object_schema( $variant_schema, sprintf( '%s.anyOf[%d]', $path, $index ) );
                }
            }
        }
    };

    $library = PopupBlueprint::get_template_library();

    Assertions::true(
        count( $library ) >= 20,
        'The AI popup builder template library should expose the bundled free and PRO templates.'
    );

    $template_slugs = array_column( $library, 'slug' );

    Assertions::false(
        in_array( '', $template_slugs, true ),
        'The AI popup builder template library should not expose unusable empty template slugs.'
    );

    Assertions::true(
        in_array( 'flyout__smart_exit_offer', $template_slugs, true ),
        'The AI popup builder template library should fall back to the template attribute when a variation name is empty.'
    );

    foreach (
        array(
            'bar__almost_free_shipping',
            'flyout__add_to_cart_unlock',
            'popup__cart_idle_rescue',
            'popup__checkout_exit_save',
            'popup__high_intent_offer',
        ) as $expected_template_slug
    ) {
        Assertions::true(
            in_array( $expected_template_slug, $template_slugs, true ),
            sprintf( 'The AI popup builder template library should include the PRO `%s` template.', $expected_template_slug )
        );
    }

    $popup_only_templates = array_values(
        array_filter(
            $library,
            static function( array $template ): bool {
                return $template['popup_type'] === FOOCONVERT_POPUP_TYPE_POPUP;
            }
        )
    );

    Assertions::true(
        count( $popup_only_templates ) > 0,
        'The AI popup builder template library should include popup templates.'
    );

    $generated_block_metadata     = require dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Blueprint/generated-fooconvert-blocks.php';
    $generated_pro_block_metadata = require dirname( __DIR__, 2 ) . '/pro/includes/AI/PopupBuilder/Blueprint/generated-fooconvert-pro-blocks.php';

    Assertions::true(
        isset( $generated_block_metadata['fc/example-block'] ),
        'The generated free FooConvert block metadata file should include every free source block before AI support filtering.'
    );

    Assertions::true(
        isset( $generated_block_metadata['fc/bar'] ),
        'The generated free FooConvert block metadata file should include free popup shell blocks before AI support filtering.'
    );

    foreach (
        array(
            'fc/confetti',
            'fc/apply-coupon',
            'fc/free-shipping-progress',
            'fc/free-shipping-progress-content',
            'fc/free-shipping-progress-bar',
        ) as $expected_pro_block_name
    ) {
        Assertions::false(
            isset( $generated_block_metadata[ $expected_pro_block_name ] ),
            sprintf( 'The generated free FooConvert block metadata file should not include the PRO `%s` block.', $expected_pro_block_name )
        );

        Assertions::true(
            isset( $generated_pro_block_metadata[ $expected_pro_block_name ] ),
            sprintf( 'The generated PRO FooConvert block metadata file should include the PRO `%s` block.', $expected_pro_block_name )
        );
    }

    $free_block_catalog = PopupBlueprint::get_block_catalog();
    $free_block_names   = array_column( $free_block_catalog, 'name' );

    foreach ( array_keys( $generated_pro_block_metadata ) as $pro_block_name ) {
        Assertions::false(
            in_array( $pro_block_name, $free_block_names, true ),
            sprintf( 'The free AI block catalog should not include the PRO `%s` block before PRO filters run.', $pro_block_name )
        );
    }

    $popup_blueprint_reflection = new ReflectionClass( PopupBlueprint::class );
    $block_catalog_property     = $popup_blueprint_reflection->getProperty( 'block_catalog' );
    $block_catalog_property->setAccessible( true );
    $block_catalog_property->setValue( null, null );

    add_filter(
        'fooconvert_ai_popup_builder_block_metadata',
        static function( array $metadata_map ) use ( $generated_pro_block_metadata ): array {
            return array_merge( $metadata_map, $generated_pro_block_metadata );
        }
    );

    $block_catalog   = PopupBlueprint::get_block_catalog();
    $block_names     = array_column( $block_catalog, 'name' );
    $catalog_by_name = array();

    foreach ( $block_catalog as $block ) {
        if ( isset( $block['name'] ) && is_string( $block['name'] ) ) {
            $catalog_by_name[ $block['name'] ] = $block;
        }
    }

    foreach (
        array(
            'fc/sign-up',
            'fc/countdown',
            'fc/coupon',
            'fc/split-layout',
            'fc/split-layout-panel',
            'fc/confetti',
            'fc/apply-coupon',
            'fc/free-shipping-progress',
            'fc/free-shipping-progress-content',
            'fc/free-shipping-progress-bar',
        ) as $expected_block_name
    ) {
        Assertions::true(
            in_array( $expected_block_name, $block_names, true ),
            sprintf( 'The block catalog should include `%s` from FooConvert block metadata.', $expected_block_name )
        );
    }

    Assertions::false(
        in_array( 'fc/bar', $block_names, true ),
        'The AI block catalog should still hide popup shell blocks after generated metadata is loaded.'
    );

    Assertions::false(
        in_array( 'fc/example-block', $block_names, true ),
        'The AI block catalog should still hide unsupported example blocks after generated metadata is loaded.'
    );

    Assertions::same(
        array( 'fc/split-layout-panel' ),
        $catalog_by_name['fc/split-layout']['allowed_children'] ?? array(),
        'FooConvert generated metadata should infer parent-child block relationships.'
    );

    $free_shipping_children = $catalog_by_name['fc/free-shipping-progress']['allowed_children'] ?? array();
    sort( $free_shipping_children );

    Assertions::same(
        array( 'fc/free-shipping-progress-bar', 'fc/free-shipping-progress-content' ),
        $free_shipping_children,
        'Free shipping progress catalog metadata should expose the current child block names.'
    );

    Assertions::same(
        array(
            'woocommerce/all-reviews',
            'woocommerce/cart-link',
            'woocommerce/coupon-code',
            'woocommerce/featured-category',
            'woocommerce/featured-product',
            'woocommerce/mini-cart',
            'woocommerce/payment-method-icons',
            'woocommerce/product-collection',
            'woocommerce/product-filters',
            'woocommerce/product-image-gallery',
            'woocommerce/product-meta',
            'woocommerce/product-search',
            'woocommerce/reviews-by-category',
            'woocommerce/reviews-by-product',
            'woocommerce/single-product',
            'woocommerce/product-details',
            'woocommerce/product-reviews',
            'woocommerce/product-review-form',
            'woocommerce/cart',
            'woocommerce/checkout',
        ),
        PopupBlueprint::get_default_woocommerce_context_block_names(),
        'The default WooCommerce AI context block list should stay popup-focused.'
    );

    $selected_block_catalog = PopupBlueprint::get_block_catalog( array( 'core/heading', 'fc/sign-up', 'core/paragraph' ) );

    Assertions::same(
        array( 'core/heading', 'core/paragraph', 'fc/sign-up' ),
        array_column( $selected_block_catalog, 'name' ),
        'The block catalog should support filtering to saved selected block names.'
    );

    $selected_schema_block_names = PopupBlueprint::get_popup_draft_schema(
        array( 'core/heading', 'fc/sign-up' )
    )['properties']['content_blocks']['items']['properties']['name']['enum'];

    Assertions::same(
        array( 'core/heading', 'fc/sign-up' ),
        $selected_schema_block_names,
        'The assistant content-block schema should use the selected block names.'
    );

    $selected_draft = PopupBlueprint::sanitize_popup_draft(
        array(
            'content_blocks' => array(
                array(
                    'name'       => 'core/heading',
                    'attributes' => array(
                        'content' => 'Selected block',
                    ),
                ),
                array(
                    'name'       => 'core/paragraph',
                    'attributes' => array(
                        'content' => 'Unselected block',
                    ),
                ),
            ),
        ),
        array( 'core/heading' )
    );

    Assertions::same(
        array( 'core/heading' ),
        array_column( $selected_draft['content_blocks'], 'name' ),
        'Popup drafts should drop content blocks that are not selected for the current AI context.'
    );

    PopupBlueprint::set_request_selected_block_names( array( 'core/heading' ) );
    $sanitized_selection_during_request = PopupBlueprint::sanitize_selected_block_names( array( 'core/paragraph' ) );
    PopupBlueprint::clear_request_selected_block_names();

    Assertions::same(
        array( 'core/paragraph' ),
        $sanitized_selection_during_request,
        'Selected block sanitization should validate against the full available catalog, not the current request filter.'
    );

    $saved_settings = Settings::save(
        array(
            'selectedBlockNames' => array( 'core/heading', 'fc/sign-up', 'unsupported/block' ),
        )
    );

    Assertions::same(
        array( 'core/heading', 'fc/sign-up' ),
        $saved_settings['selected_block_names'],
        'Saved AI popup builder settings should sanitize selected block names against the available catalog.'
    );

    Assertions::same(
        array( 'core/heading', 'fc/sign-up' ),
        $GLOBALS['fc_test_options'][ FOOCONVERT_OPTION_DATA ][ FOOCONVERT_SETTING_AI_POPUP_BUILDER_SELECTED_BLOCKS ] ?? array(),
        'Selected AI context blocks should be persisted in FooConvert settings.'
    );

    Assertions::same(
        array( 'core/heading', 'fc/sign-up' ),
        Settings::to_response()['selectedBlockNames'],
        'AI popup builder settings responses should expose selected block names for the modal.'
    );

    $draft = PopupBlueprint::sanitize_popup_draft(
        array(
            'title'         => '  Launch Weekend Offer ',
            'popup_type'    => FOOCONVERT_POPUP_TYPE_POPUP,
            'goal'          => 'Grow the email list fast',
            'audience'      => 'First-time visitors',
            'offer'         => '15% off plus early access',
            'template_slug' => 'flyout__newsletter_subscribe',
            'content_blocks' => array(
                array(
                    'name'       => 'core/heading',
                    'attributes' => array(
                        'content' => '<strong>Unlock</strong> 15% off today',
                        'level'   => 2,
                    ),
                ),
                array(
                    'name' => 'unsupported/custom',
                ),
                array(
                    'name'       => 'fc/sign-up',
                    'attributes' => array(
                        'button' => array(
                            'settings' => array(
                                'text' => 'Get My Discount',
                            ),
                        ),
                    ),
                ),
            ),
        )
    );

    Assertions::same(
        'Launch Weekend Offer',
        $draft['title'],
        'Popup draft titles should be sanitized.'
    );

    Assertions::same(
        '',
        $draft['template_slug'],
        'Popup template slugs should be cleared when they do not match the requested popup type.'
    );

    Assertions::same(
        2,
        count( $draft['content_blocks'] ),
        'Unsupported content blocks should be removed from the AI popup draft.'
    );

    Assertions::same(
        'Get My Discount',
        $draft['content_blocks'][1]['attributes']['button']['settings']['text'],
        'Sign-up shorthand attributes should be normalized into the nested block attribute shape.'
    );

    Assertions::same(
        'exit_intent',
        $draft['trigger']['type'],
        'Popup drafts should default to an exit-intent trigger when none is provided.'
    );

    $close_button_margin_draft = PopupBlueprint::sanitize_popup_draft(
        array(
            'popup_type'      => FOOCONVERT_POPUP_TYPE_POPUP,
            'root_attributes' => array(
                'content'     => array(
                    'styles' => array(
                        'border'     => array(
                            'radius' => '18px',
                        ),
                        'dimensions' => array(
                            'margin' => '24px',
                        ),
                    ),
                ),
                'closeButton' => array(
                    'styles' => array(
                        'dimensions' => array(
                            'margin' => '4px',
                        ),
                    ),
                ),
            ),
        )
    );

    Assertions::same(
        '24px',
        $close_button_margin_draft['root_attributes']['closeButton']['styles']['dimensions']['margin'] ?? '',
        'Close button margins should match content margins when AI popup content is inset.'
    );

    $rounded_close_button_draft = PopupBlueprint::sanitize_popup_draft(
        array(
            'popup_type'      => FOOCONVERT_POPUP_TYPE_POPUP,
            'root_attributes' => array(
                'content' => array(
                    'styles' => array(
                        'border' => array(
                            'radius' => '16px',
                        ),
                    ),
                ),
            ),
        )
    );

    Assertions::same(
        '10px',
        $rounded_close_button_draft['root_attributes']['closeButton']['styles']['dimensions']['margin'] ?? '',
        'Close button margins should get a small inset when AI popup content has rounded corners.'
    );

    $normalized_draft = PopupBlueprint::sanitize_popup_draft(
        array(
            'popup_type'    => FOOCONVERT_POPUP_TYPE_POPUP,
            'content_blocks' => array(
                array(
                    'name'       => 'core/list',
                    'attributes' => array(
                        'values' => array(
                            'First benefit',
                            'Second benefit',
                        ),
                    ),
                ),
                array(
                    'name'       => 'core/image',
                    'attributes' => array(
                        'src'        => 'https://example.test/generated-image.jpg',
                        'mediaId'    => 14,
                        'altText'    => 'Generated popup image',
                    ),
                ),
                array(
                    'name'       => 'fc/sign-up',
                    'attributes' => array(
                        'buttonText'       => 'Claim My Offer',
                        'successMessage'   => 'Check your inbox',
                        'closeOnSuccess'   => true,
                        'emailOnly'        => true,
                        'emailPlaceholder' => 'Enter your email',
                    ),
                ),
            ),
        )
    );

    Assertions::same(
        array( 'First benefit', 'Second benefit' ),
        $normalized_draft['content_blocks'][0]['attributes']['items'],
        'List shorthand arrays should be normalized into the items attribute.'
    );

    Assertions::true(
        ! isset( $normalized_draft['content_blocks'][0]['attributes']['values'] ),
        'Legacy list values should be removed after normalization.'
    );

    Assertions::same(
        'Claim My Offer',
        $normalized_draft['content_blocks'][2]['attributes']['button']['settings']['text'],
        'Sign-up buttonText aliases should map to the nested button settings.'
    );

    Assertions::same(
        'Enter your email',
        $normalized_draft['content_blocks'][2]['attributes']['inputs']['settings']['emailPlaceholder'],
        'Sign-up emailPlaceholder aliases should map to the nested input settings.'
    );

    Assertions::same(
        'https://example.test/generated-image.jpg',
        $normalized_draft['content_blocks'][1]['attributes']['url'],
        'Image shorthand src aliases should map to the core/image url attribute.'
    );

    Assertions::same(
        14,
        $normalized_draft['content_blocks'][1]['attributes']['id'],
        'Image shorthand media IDs should map to the core/image id attribute.'
    );

    $entity_draft = PopupBlueprint::sanitize_popup_draft(
        array(
            'popup_type'     => FOOCONVERT_POPUP_TYPE_POPUP,
            'content_blocks' => array(
                array(
                    'name'       => 'core/button',
                    'attributes' => array(
                        'text' => 'Copy code &amp; save 65%',
                    ),
                ),
                array(
                    'name'       => 'core/paragraph',
                    'attributes' => array(
                        'content' => '<strong>Copy code &amp; save 65%</strong>',
                    ),
                ),
                array(
                    'name'       => 'fc/sign-up',
                    'attributes' => array(
                        'buttonText'     => 'Join &amp; save',
                        'successMessage' => 'Code copied &amp; sent',
                    ),
                ),
            ),
        )
    );

    Assertions::same(
        'Copy code & save 65%',
        $entity_draft['content_blocks'][0]['attributes']['text'],
        'AI popup drafts should decode ampersand entities in core button text before block serialization.'
    );

    Assertions::same(
        '<strong>Copy code & save 65%</strong>',
        $entity_draft['content_blocks'][1]['attributes']['content'],
        'AI popup drafts should decode ampersand entities in rich text content without removing markup.'
    );

    Assertions::same(
        'Join & save',
        $entity_draft['content_blocks'][2]['attributes']['button']['settings']['text'],
        'AI popup drafts should decode ampersand entities in sign-up button aliases.'
    );

    Assertions::same(
        'Code copied & sent',
        $entity_draft['content_blocks'][2]['attributes']['settings']['successMessage'],
        'AI popup drafts should decode ampersand entities in sign-up success messages.'
    );

    $aligned_text_draft = PopupBlueprint::sanitize_popup_draft(
        array(
            'popup_type'     => FOOCONVERT_POPUP_TYPE_POPUP,
            'content_blocks' => array(
                array(
                    'name'       => 'core/paragraph',
                    'attributes' => array(
                        'content'   => 'For regular readers',
                        'align'     => 'center',
                        'textAlign' => 'right',
                        'style'     => array(
                            'typography' => array(
                                'fontSize' => '12px',
                            ),
                        ),
                    ),
                ),
                array(
                    'name'       => 'core/heading',
                    'attributes' => array(
                        'content' => 'Stay in the loop',
                        'align'   => 'center',
                        'level'   => 2,
                    ),
                ),
            ),
        )
    );

    Assertions::same(
        'center',
        $aligned_text_draft['content_blocks'][0]['attributes']['style']['typography']['textAlign'] ?? '',
        'Paragraph align attributes should also set style.typography.textAlign for serialized frontend alignment.'
    );

    Assertions::false(
        isset( $aligned_text_draft['content_blocks'][0]['attributes']['textAlign'] ),
        'Paragraph textAlign aliases should be removed after mapping to align.'
    );

    Assertions::same(
        'center',
        $aligned_text_draft['content_blocks'][1]['attributes']['textAlign'] ?? '',
        'Heading align aliases should map to the canonical textAlign attribute.'
    );

    Assertions::same(
        'center',
        $aligned_text_draft['content_blocks'][1]['attributes']['style']['typography']['textAlign'] ?? '',
        'Heading textAlign attributes should also set style.typography.textAlign for serialized frontend alignment.'
    );

    $saved_metadata = PopupBlueprint::sanitize_builder_metadata(
        array(
            'messages'           => array(
                array(
                    'role'    => 'user',
                    'content' => 'Build a popup',
                ),
                array(
                    'role'    => 'assistant',
                    'content' => 'Here is a popup draft.',
                ),
            ),
            'assistant_message'  => 'Here is a popup draft.',
            'popup_draft'        => $normalized_draft,
            'validation'         => array(
                'score' => 91,
            ),
            'suggested_prompts'  => array( 'Make it seasonal' ),
            'options'            => array(
                'generate_images' => true,
            ),
        )
    );

    $resanitized_metadata = PopupBlueprint::sanitize_builder_metadata( $saved_metadata );

    Assertions::same(
        'Here is a popup draft.',
        $resanitized_metadata['response']['assistant_message'],
        'Saved AI metadata should preserve the assistant summary when sanitized again.'
    );

    Assertions::same(
        FOOCONVERT_POPUP_TYPE_POPUP,
        $resanitized_metadata['response']['popup_draft']['popup_type'],
        'Saved AI metadata should preserve the popup draft when sanitized again.'
    );

    Assertions::same(
        91,
        $resanitized_metadata['response']['validation']['score'],
        'Saved AI metadata should preserve validation details when sanitized again.'
    );

    $validation = PopupBlueprint::evaluate_popup_draft( $draft );

    Assertions::true(
        is_int( $validation['score'] ) && $validation['score'] > 0,
        'Popup draft validation should return a numeric score.'
    );

    Assertions::true(
        count( $validation['strengths'] ) > 0,
        'Popup draft validation should surface at least one strength for a viable draft.'
    );

    $assert_strict_object_schema(
        PopupBlueprint::get_assistant_response_schema(),
        'assistant_response'
    );

    $popup_draft_schema = PopupBlueprint::get_popup_draft_schema();

    Assertions::true(
        in_array( FOOCONVERT_POPUP_TYPE_POPUP, $popup_draft_schema['properties']['popup_type']['enum'], true ),
        'The AI popup draft schema should accept the builder-facing `popup` type.'
    );

    Assertions::false(
        in_array( FOOCONVERT_POPUP_TYPE_OVERLAY, $popup_draft_schema['properties']['popup_type']['enum'], true ),
        'The AI popup draft schema should not expose the canonical overlay type to the builder.'
    );

    $saved_metadata_schema = PopupBlueprint::get_saved_ai_metadata_schema();
    $saved_popup_type_enum = $saved_metadata_schema['properties']['response']['properties']['popup_draft']['properties']['popup_type']['enum'];

    Assertions::true(
        in_array( FOOCONVERT_POPUP_TYPE_POPUP, $saved_popup_type_enum, true ),
        'The saved AI metadata schema should accept the builder-facing `popup` type.'
    );

    Assertions::true(
        in_array( FOOCONVERT_POPUP_TYPE_OVERLAY, $saved_popup_type_enum, true ),
        'The saved AI metadata schema should remain compatible with legacy overlay-valued popup metadata.'
    );

    $response_contract = PopupBlueprint::get_assistant_response_contract();

    Assertions::true(
        false !== strpos( $response_contract, 'assistant_message' ) &&
        false !== strpos( $response_contract, 'popup_draft' ) &&
        false !== strpos( $response_contract, 'content_blocks' ) &&
        false !== strpos( $response_contract, 'media_items' ),
        'The assistant response contract should describe the required Fooconvert JSON payload.'
    );

    Assertions::true(
        false !== strpos( $response_contract, FOOCONVERT_POPUP_TYPE_POPUP ) &&
        false === strpos( $response_contract, FOOCONVERT_POPUP_TYPE_OVERLAY ),
        'The assistant response contract should describe the builder-facing popup types.'
    );

    Assertions::true(
        false !== strpos( $response_contract, 'cart.add' ) &&
        false !== strpos( $response_contract, 'product.high_intent' ) &&
        false !== strpos( $response_contract, 'page, session, visit' ) &&
        false !== strpos( $response_contract, 'once, repeat' ),
        'The assistant response contract should describe the supported popup trigger options.'
    );

    Assertions::true(
        in_array( 'cart.add', PopupBlueprint::get_supported_trigger_events(), true ) &&
        in_array( 'product.high_intent', PopupBlueprint::get_supported_trigger_events(), true ),
        'The AI popup builder should expose Pro trigger events.'
    );

    $advanced_trigger_draft = PopupBlueprint::sanitize_popup_draft(
        array(
            'popup_type' => FOOCONVERT_POPUP_TYPE_FLYOUT,
            'trigger'    => array(
                'type'      => 'cart.add',
                'where'     => array(
                    'productIds' => array( 42 ),
                ),
                'frequency' => 'repeat',
            ),
        )
    );

    Assertions::same(
        'cart.add',
        $advanced_trigger_draft['trigger']['event'],
        'Popup drafts should preserve supported Pro trigger events.'
    );

    Assertions::same(
        array( 42 ),
        $advanced_trigger_draft['trigger']['where']['productIds'],
        'Popup drafts should preserve Pro trigger event options.'
    );

    $saved_metadata = PopupBlueprint::sanitize_builder_metadata(
        array(
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
            'clarifying_question' => 'Should this popup show on exit intent?',
            'suggested_prompts' => array( 'Tighten the copy' ),
            'popup_draft'       => $normalized_draft,
            'validation'        => array(
                'score'       => 89,
                'strengths'   => array( 'Focused CTA' ),
                'warnings'    => array(),
                'suggestions' => array( 'Add proof' ),
            ),
            'media_items'       => array(
                array(
                    'id'    => 99,
                    'url'   => 'https://example.test/generated-popup-image.jpg',
                    'title' => 'Generated popup image',
                    'alt'   => 'Popup visual',
                ),
            ),
            'options'          => array(
                'generate_images'       => true,
                'force_image_generation' => false,
            ),
        )
    );

    Assertions::same(
        'ai-popup-builder',
        $saved_metadata['source'],
        'Saved AI metadata should record the popup builder source.'
    );

    Assertions::same(
        'https://example.test/generated-popup-image.jpg',
        $saved_metadata['response']['media_items'][0]['url'],
        'Saved AI metadata should retain generated popup media items.'
    );

    Assertions::same(
        'Should this popup show on exit intent?',
        $saved_metadata['response']['clarifying_question'],
        'Saved AI metadata should retain the latest clarifying question from the AI chat.'
    );

    $saved_defaults = PopupBlueprint::get_saved_ai_metadata_defaults();

    Assertions::same(
        '',
        $saved_defaults['source'],
        'Saved AI metadata defaults should remain empty until a popup is generated by the AI builder.'
    );

    Assertions::same(
        null,
        $saved_defaults['response']['popup_draft'],
        'Saved AI metadata defaults should not invent a popup draft.'
    );

    echo "ai-popup-blueprint: ok\n";
}
