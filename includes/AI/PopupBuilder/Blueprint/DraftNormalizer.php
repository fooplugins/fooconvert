<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint;

defined( 'ABSPATH' ) || exit;

use FooPlugins\FooConvert\Admin\DemoContent;
use FooPlugins\FooConvert\AI\PopupBuilder\Media\Attachments as PopupMedia;

class DraftNormalizer {

    /**
     * Cached template library.
     *
     * @var array<int,array<string,mixed>>|null
     */
    private static ?array $template_library = null;

    /**
     * Cached runtime block catalog.
     *
     * @var array<string,array<string,mixed>>|null
     */
    private static ?array $block_catalog = null;

    /**
     * Request-scoped selected block names used by popup builder abilities.
     *
     * @var array<int,string>|null
     */
    private static ?array $request_selected_block_names = null;

    /**
     * Normalizes popup-type inputs for the AI builder's logical popup taxonomy.
     *
     * The builder still exposes `popup` in its schemas and templates even though
     * the saved block markup now serializes to `fc/overlay`.
     *
     * @param mixed $value Raw popup type value.
     * @return string
     */
    public static function normalize_builder_popup_type( $value ): string {
        $popup_type = fooconvert_normalize_popup_type( $value );

        if ( FOOCONVERT_POPUP_TYPE_OVERLAY === $popup_type ) {
            return FOOCONVERT_POPUP_TYPE_POPUP;
        }

        return $popup_type;
    }

    /**
     * Returns the logical popup types exposed by the AI builder.
     *
     * The builder still describes overlays as `popup` even though the saved
     * block markup uses the canonical overlay type.
     *
     * @return array<int,string>
     */
    private static function get_builder_popup_types(): array {
        return array(
            FOOCONVERT_POPUP_TYPE_BAR,
            FOOCONVERT_POPUP_TYPE_FLYOUT,
            FOOCONVERT_POPUP_TYPE_POPUP,
        );
    }

    /**
     * Returns the supported content block catalog for the AI popup builder.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function get_block_catalog( ?array $selected_block_names = null ): array {
        return array_values( self::get_block_catalog_map( $selected_block_names ) );
    }

    /**
     * Returns the WooCommerce blocks selected for AI context by default.
     *
     * @return array<int,string>
     */
    public static function get_default_woocommerce_context_block_names(): array {
        return array(
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
        );
    }

    /**
     * Returns the default block selection for AI context.
     *
     * @return array<int,string>
     */
    public static function get_default_selected_block_names(): array {
        $catalog             = self::get_block_catalog_map( null, false );
        $default_woo_blocks  = array_fill_keys( self::get_default_woocommerce_context_block_names(), true );
        $selected_block_names = array();

        foreach ( array_keys( $catalog ) as $block_name ) {
            if ( fooconvert_str_starts_with( $block_name, 'woocommerce/' ) && ! isset( $default_woo_blocks[ $block_name ] ) ) {
                continue;
            }

            $selected_block_names[] = $block_name;
        }

        return $selected_block_names;
    }

    /**
     * Sanitizes selected block names against the available AI block catalog.
     *
     * @param mixed $selected_block_names Raw selected block name payload.
     * @return array<int,string>
     */
    public static function sanitize_selected_block_names( $selected_block_names ): array {
        $selected_block_names = self::normalize_block_name_list( $selected_block_names );
        $available_lookup     = array_fill_keys( array_keys( self::get_block_catalog_map( null, false ) ), true );
        $sanitized            = array();

        foreach ( $selected_block_names as $block_name ) {
            if ( isset( $available_lookup[ $block_name ] ) ) {
                $sanitized[ $block_name ] = $block_name;
            }
        }

        if ( empty( $sanitized ) ) {
            return self::get_default_selected_block_names();
        }

        return array_values( $sanitized );
    }

    /**
     * Sets the request-scoped selected block context for AI abilities.
     *
     * @param mixed $selected_block_names Raw selected block name payload.
     * @return void
     */
    public static function set_request_selected_block_names( $selected_block_names ): void {
        self::$request_selected_block_names = self::sanitize_selected_block_names( $selected_block_names );
    }

    /**
     * Clears request-scoped selected block context.
     *
     * @return void
     */
    public static function clear_request_selected_block_names(): void {
        self::$request_selected_block_names = null;
    }

    /**
     * Returns the supported block catalog keyed by block name.
     *
     * @return array<string,array<string,mixed>>
     */
    private static function get_block_catalog_map( ?array $selected_block_names = null, bool $use_request_context = true ): array {
        $can_use_cached_catalog = ! doing_action( 'init' ) && did_action( 'init' ) > 0;

        if ( $can_use_cached_catalog && is_array( self::$block_catalog ) ) {
            $catalog = self::$block_catalog;
        } else {
            $catalog          = self::get_core_block_catalog_map();
            $overrides        = self::get_block_catalog_overrides();
            $has_woo_runtime  = false;

            if ( class_exists( '\WP_Block_Type_Registry' ) ) {
                $registered = \WP_Block_Type_Registry::get_instance()->get_all_registered();
                $child_map  = self::get_registered_block_child_map( $registered );

                foreach ( $registered as $block_name => $block_type ) {
                    if ( ! self::is_ai_supported_runtime_block( $block_name, $block_type ) ) {
                        continue;
                    }

                    if ( fooconvert_str_starts_with( $block_name, 'woocommerce/' ) ) {
                        $has_woo_runtime = true;
                    }

                    $override         = $overrides[ $block_name ] ?? array();
                    $allowed_children = isset( $override['allowed_children'] ) && is_array( $override['allowed_children'] )
                        ? array_values( array_unique( $override['allowed_children'] ) )
                        : array_values( array_unique( $child_map[ $block_name ] ?? array() ) );
                    $supports_children = array_key_exists( 'supports_children', $override )
                        ? ! empty( $override['supports_children'] )
                        : ! empty( $allowed_children );

                    $catalog[ $block_name ] = array_merge(
                        array(
                            'name'               => $block_name,
                            'label'              => isset( $block_type->title ) && is_string( $block_type->title ) && '' !== $block_type->title
                                ? $block_type->title
                                : $block_name,
                            'description'        => isset( $block_type->description ) && is_string( $block_type->description )
                                ? $block_type->description
                                : '',
                            'supports_children'  => $supports_children,
                            'allowed_children'   => $allowed_children,
                            'attribute_examples' => self::build_attribute_examples_from_block_type( $block_name, $block_type ),
                            'attribute_schema'   => self::get_block_attribute_schema( $block_type ),
                            'parent'             => isset( $block_type->parent ) && is_array( $block_type->parent )
                                ? array_values( $block_type->parent )
                                : array(),
                            'ancestor'           => isset( $block_type->ancestor ) && is_array( $block_type->ancestor )
                                ? array_values( $block_type->ancestor )
                                : array(),
                        ),
                        $override
                    );
                }
            }

            foreach ( self::get_fooconvert_metadata_catalog_map() as $block_name => $block ) {
                if ( ! isset( $catalog[ $block_name ] ) ) {
                    $catalog[ $block_name ] = $block;
                }
            }

            if ( ! $has_woo_runtime ) {
                $catalog = array_merge( $catalog, self::get_woocommerce_metadata_catalog_map() );
            }

            if ( $can_use_cached_catalog ) {
                self::$block_catalog = $catalog;
            }
        }

        if ( null === $selected_block_names && $use_request_context ) {
            $selected_block_names = self::$request_selected_block_names;
        }

        if ( is_array( $selected_block_names ) ) {
            $selected_lookup = array_fill_keys( self::normalize_block_name_list( $selected_block_names ), true );

            $catalog = array_filter(
                $catalog,
                static function( string $block_name ) use ( $selected_lookup ): bool {
                    return isset( $selected_lookup[ $block_name ] );
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        return $catalog;
    }

    /**
     * Normalizes a raw block-name list.
     *
     * @param mixed $value Raw block-name payload.
     * @return array<int,string>
     */
    private static function normalize_block_name_list( $value ): array {
        if ( is_string( $value ) ) {
            $items = preg_split( '/[\r\n,]+/', $value );
        } elseif ( is_array( $value ) ) {
            $items = $value;
        } else {
            $items = array();
        }

        $block_names = array();
        foreach ( $items as $item ) {
            if ( ! is_scalar( $item ) ) {
                continue;
            }

            $block_name = trim( (string) $item );
            if ( '' !== $block_name ) {
                $block_names[ $block_name ] = $block_name;
            }
        }

        return array_values( $block_names );
    }

    /**
     * Returns the core content blocks always supported by the builder.
     *
     * @return array<string,array<string,mixed>>
     */
    private static function get_core_block_catalog_map(): array {
        return array(
            'core/heading'   => array(
                'name'             => 'core/heading',
                'label'            => __( 'Heading', 'fooconvert' ),
                'description'      => __( 'Primary or secondary copy block for strong popup headlines.', 'fooconvert' ),
                'supports_children' => false,
                'allowed_children' => array(),
                'attribute_examples' => array(
                    'level'     => 2,
                    'content'   => 'Unlock 15% off your first order',
                    'textAlign' => 'center',
                ),
            ),
            'core/paragraph' => array(
                'name'             => 'core/paragraph',
                'label'            => __( 'Paragraph', 'fooconvert' ),
                'description'      => __( 'Support copy, urgency lines, benefit statements, or disclaimers.', 'fooconvert' ),
                'supports_children' => false,
                'allowed_children' => array(),
                'attribute_examples' => array(
                    'content' => 'Join thousands of subscribers getting launch-only offers and practical growth tips.',
                    'align'   => 'center',
                ),
            ),
            'core/list'      => array(
                'name'             => 'core/list',
                'label'            => __( 'List', 'fooconvert' ),
                'description'      => __( 'Bullet list for benefits, proof points, or offer details.', 'fooconvert' ),
                'supports_children' => false,
                'allowed_children' => array(),
                'attribute_examples' => array(
                    'items' => array(
                        'Free shipping on every order',
                        'Early access to new arrivals',
                        'One email a week, no spam',
                    ),
                ),
            ),
            'core/buttons'   => array(
                'name'             => 'core/buttons',
                'label'            => __( 'Buttons', 'fooconvert' ),
                'description'      => __( 'Container for one primary CTA and, if needed, a quieter secondary CTA.', 'fooconvert' ),
                'supports_children' => true,
                'allowed_children' => array( 'core/button' ),
                'attribute_examples' => array(
                    'layout' => array(
                        'type'           => 'flex',
                        'justifyContent' => 'center',
                    ),
                ),
            ),
            'core/button'    => array(
                'name'             => 'core/button',
                'label'            => __( 'Button', 'fooconvert' ),
                'description'      => __( 'Primary or secondary call-to-action button.', 'fooconvert' ),
                'supports_children' => false,
                'allowed_children' => array(),
                'attribute_examples' => array(
                    'text'      => 'Claim My Discount',
                    'url'       => '/shop',
                    'textAlign' => 'center',
                ),
            ),
            'core/group'     => array(
                'name'             => 'core/group',
                'label'            => __( 'Group', 'fooconvert' ),
                'description'      => __( 'Flexible layout wrapper for stacking content with spacing.', 'fooconvert' ),
                'supports_children' => true,
                'allowed_children' => array(),
                'attribute_examples' => array(
                    'layout' => array(
                        'type'            => 'flex',
                        'orientation'     => 'vertical',
                        'justifyContent'  => 'center',
                    ),
                ),
            ),
            'core/columns'   => array(
                'name'             => 'core/columns',
                'label'            => __( 'Columns', 'fooconvert' ),
                'description'      => __( 'Two-column content layout, useful for lead magnets or image-plus-copy popups.', 'fooconvert' ),
                'supports_children' => true,
                'allowed_children' => array( 'core/column' ),
                'attribute_examples' => array(),
            ),
            'core/column'    => array(
                'name'             => 'core/column',
                'label'            => __( 'Column', 'fooconvert' ),
                'description'      => __( 'Child column within a columns layout.', 'fooconvert' ),
                'supports_children' => true,
                'allowed_children' => array(),
                'attribute_examples' => array(
                    'width' => '50%',
                ),
            ),
            'core/image'     => array(
                'name'             => 'core/image',
                'label'            => __( 'Image', 'fooconvert' ),
                'description'      => __( 'Simple product, lead magnet, or promotional image block.', 'fooconvert' ),
                'supports_children' => false,
                'allowed_children' => array(),
                'attribute_examples' => array(
                    'url' => 'https://example.com/offer-cover.jpg',
                    'alt' => 'Offer preview image',
                ),
            ),
            'core/separator' => array(
                'name'             => 'core/separator',
                'label'            => __( 'Separator', 'fooconvert' ),
                'description'      => __( 'Visual divider between offer sections.', 'fooconvert' ),
                'supports_children' => false,
                'allowed_children' => array(),
                'attribute_examples' => array(),
            ),
            'core/spacer'    => array(
                'name'             => 'core/spacer',
                'label'            => __( 'Spacer', 'fooconvert' ),
                'description'      => __( 'Adds breathing room between popup sections.', 'fooconvert' ),
                'supports_children' => false,
                'allowed_children' => array(),
                'attribute_examples' => array(
                    'height' => '24px',
                ),
            ),
        );
    }

    /**
     * Returns manual catalog overrides and examples for custom blocks.
     *
     * @return array<string,array<string,mixed>>
     */
    private static function get_block_catalog_overrides(): array {
        return array(
            'fc/sign-up' => array(
                'label'       => __( 'Popup Sign Up', 'fooconvert' ),
                'description' => __( 'Lead capture form block with configurable placeholders and button copy.', 'fooconvert' ),
                'attribute_examples' => array(
                    'settings' => array(
                        'layout'         => 'stack',
                        'successMessage' => 'Thanks for joining!',
                        'closeOnSuccess' => true,
                    ),
                    'inputs'   => array(
                        'settings' => array(
                            'emailOnly'        => false,
                            'noLabels'         => true,
                            'emailPlaceholder' => 'Enter your email',
                            'namePlaceholder'  => 'Your name',
                        ),
                    ),
                    'button'   => array(
                        'settings' => array(
                            'text'   => 'Get My Offer',
                            'layout' => 'text-only',
                        ),
                    ),
                ),
            ),
            'fc/countdown' => array(
                'attribute_examples' => array(
                    'settings' => array(
                        'endDate' => gmdate( 'c', time() + DAY_IN_SECONDS * 7 ),
                    ),
                ),
            ),
            'fc/coupon' => array(
                'attribute_examples' => array(
                    'code' => array(
                        'settings' => array(
                            'content' => 'SAVE15',
                        ),
                    ),
                    'button' => array(
                        'settings' => array(
                            'text' => __( 'Copy Code', 'fooconvert' ),
                        ),
                    ),
                ),
            ),
            'fc/apply-coupon' => array(
                'attribute_examples' => array(
                    'code' => array(
                        'settings' => array(
                            'content' => 'SAVE15',
                        ),
                    ),
                    'button' => array(
                        'settings' => array(
                            'text' => __( 'Apply Coupon', 'fooconvert' ),
                        ),
                    ),
                ),
            ),
            'fc/free-shipping-progress-content' => array(
                'attribute_examples' => array(
                    'state' => 'locked',
                ),
            ),
        );
    }

    /**
     * Determines whether a registered runtime block should be exposed to the builder.
     *
     * @param string $block_name Block name.
     * @return bool
     */
    private static function is_ai_supported_runtime_block( string $block_name, ?\WP_Block_Type $block_type = null ): bool {
        if ( fooconvert_str_starts_with( $block_name, 'woocommerce/' ) ) {
            $parent = $block_type instanceof \WP_Block_Type && isset( $block_type->parent ) && is_array( $block_type->parent )
                ? array_values( $block_type->parent )
                : array();
            $ancestor = $block_type instanceof \WP_Block_Type && isset( $block_type->ancestor ) && is_array( $block_type->ancestor )
                ? array_values( $block_type->ancestor )
                : array();
            $supports = $block_type instanceof \WP_Block_Type && isset( $block_type->supports ) && is_array( $block_type->supports )
                ? $block_type->supports
                : array();
            $inserter = $block_type instanceof \WP_Block_Type && isset( $block_type->inserter )
                ? $block_type->inserter
                : null;

            return self::is_ai_supported_woocommerce_block(
                $block_name,
                $parent,
                $ancestor,
                self::is_block_inserter_disabled( $supports, $inserter )
            );
        }

        if ( ! fooconvert_str_starts_with( $block_name, 'fc/' ) ) {
            return false;
        }

        return ! in_array(
            $block_name,
            array(
                'fc/overlay',
                'fc/overlay-container',
                'fc/overlay-close-button',
                'fc/overlay-content',
                'fc/popup',
                'fc/popup-container',
                'fc/popup-close-button',
                'fc/popup-content',
                'fc/flyout',
                'fc/flyout-open-button',
                'fc/flyout-container',
                'fc/flyout-close-button',
                'fc/flyout-content',
                'fc/bar',
                'fc/bar-open-button',
                'fc/bar-container',
                'fc/bar-close-button',
                'fc/bar-content',
                'fc/example-block',
            ),
            true
        );
    }

    /**
     * Determines whether a WooCommerce block is directly usable in the builder.
     *
     * @param string            $block_name Block name.
     * @param array<int,string> $parent Parent block rules.
     * @param array<int,string> $ancestor Ancestor block rules.
     * @param bool              $inserter_disabled Whether the block is hidden from the inserter.
     * @return bool
     */
    private static function is_ai_supported_woocommerce_block(
        string $block_name,
        array $parent = array(),
        array $ancestor = array(),
        bool $inserter_disabled = false
    ): bool {
        return fooconvert_str_starts_with( $block_name, 'woocommerce/' )
            && empty( $parent )
            && empty( $ancestor )
            && ! $inserter_disabled;
    }

    /**
     * Determines whether a block explicitly disables the inserter.
     *
     * @param array<string,mixed> $supports Block supports configuration.
     * @param mixed               $inserter Optional top-level inserter flag.
     * @return bool
     */
    private static function is_block_inserter_disabled( array $supports = array(), $inserter = null ): bool {
        if ( array_key_exists( 'inserter', $supports ) ) {
            return false === $supports['inserter'];
        }

        return false === $inserter;
    }

    /**
     * Returns generated FooConvert block metadata for catalog completion.
     *
     * @return array<string,array<string,mixed>>
     */
    private static function get_fooconvert_metadata_catalog_map(): array {
        $metadata_map = self::get_generated_fooconvert_block_metadata_map();
        if ( empty( $metadata_map ) ) {
            return array();
        }

        $overrides = self::get_block_catalog_overrides();
        $child_map = self::get_metadata_block_child_map( $metadata_map );
        $catalog   = array();

        foreach ( $metadata_map as $block_name => $metadata ) {
            if ( ! self::is_ai_supported_runtime_block( $block_name ) ) {
                continue;
            }

            $override         = $overrides[ $block_name ] ?? array();
            $allowed_children = isset( $override['allowed_children'] ) && is_array( $override['allowed_children'] )
                ? array_values( array_unique( $override['allowed_children'] ) )
                : array_values( array_unique( $child_map[ $block_name ] ?? array() ) );
            $supports_children = array_key_exists( 'supports_children', $override )
                ? ! empty( $override['supports_children'] )
                : ! empty( $allowed_children );
            $attributes = isset( $metadata['attributes'] ) && is_array( $metadata['attributes'] )
                ? $metadata['attributes']
                : array();

            $catalog[ $block_name ] = array_merge(
                array(
                    'name'               => $block_name,
                    'label'              => isset( $metadata['title'] ) && is_string( $metadata['title'] ) && '' !== $metadata['title']
                        ? $metadata['title']
                        : $block_name,
                    'description'        => isset( $metadata['description'] ) && is_string( $metadata['description'] )
                        ? $metadata['description']
                        : '',
                    'supports_children'  => $supports_children,
                    'allowed_children'   => $allowed_children,
                    'attribute_examples' => self::build_attribute_examples_from_attributes( $block_name, $attributes ),
                    'attribute_schema'   => self::get_attribute_schema_from_attributes( $attributes ),
                    'parent'             => self::normalize_metadata_string_list( $metadata['parent'] ?? array() ),
                    'ancestor'           => self::normalize_metadata_string_list( $metadata['ancestor'] ?? array() ),
                ),
                $override
            );
        }

        return $catalog;
    }

    /**
     * Loads the generated FooConvert block metadata.
     *
     * @return array<string,array<string,mixed>>
     */
    private static function get_generated_fooconvert_block_metadata_map(): array {
        $file = __DIR__ . '/generated-fooconvert-blocks.php';
        if ( ! is_readable( $file ) ) {
            return array();
        }

        $metadata_map = require $file;

        return is_array( $metadata_map ) ? $metadata_map : array();
    }

    /**
     * Normalizes a block metadata string-list field.
     *
     * @param mixed $value Metadata value.
     * @return array<int,string>
     */
    private static function normalize_metadata_string_list( $value ): array {
        if ( is_string( $value ) && '' !== $value ) {
            return array( $value );
        }

        if ( ! is_array( $value ) ) {
            return array();
        }

        return array_values(
            array_filter(
                $value,
                static function( $item ): bool {
                    return is_string( $item ) && '' !== $item;
                }
            )
        );
    }

    /**
     * Builds a map of parent block names to their registered child blocks.
     *
     * @param array<string,\WP_Block_Type> $registered Registered block types.
     * @return array<string,array<int,string>>
     */
    private static function get_registered_block_child_map( array $registered ): array {
        $child_map = array();

        foreach ( $registered as $block_name => $block_type ) {
            if ( isset( $block_type->parent ) && is_array( $block_type->parent ) ) {
                foreach ( $block_type->parent as $parent ) {
                    $child_map[ $parent ]   = $child_map[ $parent ] ?? array();
                    $child_map[ $parent ][] = $block_name;
                }
            }
        }

        foreach ( $child_map as $parent => $children ) {
            $child_map[ $parent ] = array_values( array_unique( $children ) );
        }

        return $child_map;
    }

    /**
     * Builds a child map from block metadata keyed by parent block name.
     *
     * @param array<string,array<string,mixed>> $metadata_map Block metadata keyed by block name.
     * @return array<string,array<int,string>>
     */
    private static function get_metadata_block_child_map( array $metadata_map ): array {
        $child_map = array();

        foreach ( $metadata_map as $block_name => $metadata ) {
            $parents = array();
            if ( isset( $metadata['parent'] ) && is_array( $metadata['parent'] ) ) {
                $parents = array_values( $metadata['parent'] );
            } elseif ( isset( $metadata['parent'] ) && is_string( $metadata['parent'] ) && '' !== $metadata['parent'] ) {
                $parents = array( $metadata['parent'] );
            }

            foreach ( $parents as $parent ) {
                if ( ! is_string( $parent ) || '' === $parent ) {
                    continue;
                }

                $child_map[ $parent ]   = $child_map[ $parent ] ?? array();
                $child_map[ $parent ][] = $block_name;
            }
        }

        foreach ( $child_map as $parent => $children ) {
            $child_map[ $parent ] = array_values( array_unique( $children ) );
        }

        return $child_map;
    }

    /**
     * Returns WooCommerce block metadata when runtime registration is unavailable on the current screen.
     *
     * @return array<string,array<string,mixed>>
     */
    private static function get_woocommerce_metadata_catalog_map(): array {
        if ( ! fooconvert_is_woocommerce_active() || ! defined( 'WC_ABSPATH' ) ) {
            return array();
        }

        $base_dir = trailingslashit( WC_ABSPATH ) . 'assets/client/blocks';
        if ( ! is_dir( $base_dir ) ) {
            return array();
        }

        $metadata_map = array();
        $iterator     = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator( $base_dir, \FilesystemIterator::SKIP_DOTS )
        );

        foreach ( $iterator as $file ) {
            if ( ! $file instanceof \SplFileInfo || 'block.json' !== $file->getFilename() ) {
                continue;
            }

            $json = file_get_contents( $file->getPathname() );
            if ( false === $json ) {
                continue;
            }

            $metadata = json_decode( $json, true );
            if ( ! is_array( $metadata ) ) {
                continue;
            }

            $block_name = isset( $metadata['name'] ) && is_string( $metadata['name'] ) ? $metadata['name'] : '';
            if ( '' === $block_name || ! fooconvert_str_starts_with( $block_name, 'woocommerce/' ) ) {
                continue;
            }

            $metadata_map[ $block_name ] = $metadata;
        }

        if ( empty( $metadata_map ) ) {
            return array();
        }

        $overrides = self::get_block_catalog_overrides();
        $child_map = self::get_metadata_block_child_map( $metadata_map );
        $catalog   = array();

        foreach ( $metadata_map as $block_name => $metadata ) {
            $parent = isset( $metadata['parent'] ) && is_array( $metadata['parent'] )
                ? array_values( $metadata['parent'] )
                : array();
            $ancestor = isset( $metadata['ancestor'] ) && is_array( $metadata['ancestor'] )
                ? array_values( $metadata['ancestor'] )
                : array();
            $supports = isset( $metadata['supports'] ) && is_array( $metadata['supports'] )
                ? $metadata['supports']
                : array();
            $inserter = $metadata['inserter'] ?? null;

            if ( ! self::is_ai_supported_woocommerce_block(
                $block_name,
                $parent,
                $ancestor,
                self::is_block_inserter_disabled( $supports, $inserter )
            ) ) {
                continue;
            }

            $override         = $overrides[ $block_name ] ?? array();
            $allowed_children = isset( $override['allowed_children'] ) && is_array( $override['allowed_children'] )
                ? array_values( array_unique( $override['allowed_children'] ) )
                : array_values( array_unique( $child_map[ $block_name ] ?? array() ) );
            $supports_children = array_key_exists( 'supports_children', $override )
                ? ! empty( $override['supports_children'] )
                : ! empty( $allowed_children );
            $attributes = isset( $metadata['attributes'] ) && is_array( $metadata['attributes'] )
                ? $metadata['attributes']
                : array();

            $catalog[ $block_name ] = array_merge(
                array(
                    'name'               => $block_name,
                    'label'              => isset( $metadata['title'] ) && is_string( $metadata['title'] ) && '' !== $metadata['title']
                        ? $metadata['title']
                        : $block_name,
                    'description'        => isset( $metadata['description'] ) && is_string( $metadata['description'] )
                        ? $metadata['description']
                        : '',
                    'supports_children'  => $supports_children,
                    'allowed_children'   => $allowed_children,
                    'attribute_examples' => self::build_attribute_examples_from_attributes( $block_name, $attributes ),
                    'attribute_schema'   => self::get_attribute_schema_from_attributes( $attributes ),
                    'parent'             => $parent,
                    'ancestor'           => $ancestor,
                ),
                $override
            );
        }

        return $catalog;
    }

    /**
     * Builds lightweight attribute examples from a registered block type.
     *
     * @param string         $block_name Block name.
     * @param \WP_Block_Type $block_type Block type object.
     * @return array<string,mixed>
     */
    private static function build_attribute_examples_from_block_type( string $block_name, \WP_Block_Type $block_type ): array {
        return self::build_attribute_examples_from_attributes(
            $block_name,
            isset( $block_type->attributes ) && is_array( $block_type->attributes ) ? $block_type->attributes : array()
        );
    }

    /**
     * Builds lightweight attribute examples from a block attribute schema array.
     *
     * @param string              $block_name Block name.
     * @param array<string,mixed> $attributes Attribute schema.
     * @return array<string,mixed>
     */
    private static function build_attribute_examples_from_attributes( string $block_name, array $attributes ): array {
        $examples = array();

        foreach ( $attributes as $attribute_name => $schema ) {
            if ( ! is_array( $schema ) ) {
                continue;
            }

            if ( array_key_exists( 'default', $schema ) ) {
                $examples[ $attribute_name ] = $schema['default'];
                continue;
            }

            $type = isset( $schema['type'] ) ? $schema['type'] : null;

            switch ( $type ) {
                case 'string':
                    if ( in_array( $attribute_name, array( 'content', 'text', 'title', 'heading' ), true ) ) {
                        $examples[ $attribute_name ] = __( 'Sample text', 'fooconvert' );
                    } elseif ( in_array( $attribute_name, array( 'url', 'href', 'src' ), true ) ) {
                        $examples[ $attribute_name ] = 'https://example.com';
                    } elseif ( 'alt' === $attribute_name ) {
                        $examples[ $attribute_name ] = __( 'Image alt text', 'fooconvert' );
                    } else {
                        $examples[ $attribute_name ] = '';
                    }
                    break;
                case 'boolean':
                    $examples[ $attribute_name ] = false;
                    break;
                case 'number':
                case 'integer':
                    $examples[ $attribute_name ] = 0;
                    break;
                case 'array':
                    $examples[ $attribute_name ] = array();
                    break;
                case 'object':
                    $examples[ $attribute_name ] = array();
                    break;
            }
        }

        if ( 'woocommerce/mini-cart' === $block_name && empty( $examples ) ) {
            $examples['miniCartIcon'] = 'cart';
        }

        return $examples;
    }

    /**
     * Returns a JSON-safe attribute schema for a block type.
     *
     * @param \WP_Block_Type $block_type Block type.
     * @return array<string,mixed>
     */
    private static function get_block_attribute_schema( \WP_Block_Type $block_type ): array {
        return self::get_attribute_schema_from_attributes(
            isset( $block_type->attributes ) && is_array( $block_type->attributes ) ? $block_type->attributes : array()
        );
    }

    /**
     * Returns a JSON-safe attribute schema from a raw block attribute schema array.
     *
     * @param array<string,mixed> $attributes Attribute schema.
     * @return array<string,mixed>
     */
    private static function get_attribute_schema_from_attributes( array $attributes ): array {
        return ! empty( $attributes )
            ? self::sanitize_recursive( $attributes, 'attribute_schema' )
            : array();
    }

    /**
     * Returns the conversion playbook used as context for the AI.
     *
     * @return array<string,mixed>
     */
    public static function get_conversion_playbook(): array {
        $playbook = require __DIR__ . '/conversion-playbook.php';

        return is_array( $playbook ) ? $playbook : array();
    }

    /**
     * Returns the popup template library used by the AI.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function get_template_library(): array {
        if ( is_array( self::$template_library ) ) {
            return self::$template_library;
        }

        $templates = array();
        $examples  = self::get_demo_examples();

        foreach ( self::get_template_directories() as $popup_type => $directories ) {
            foreach ( $directories as $directory ) {
                $files = glob( trailingslashit( $directory ) . '*.php' );
                if ( ! is_array( $files ) ) {
                    continue;
                }

                foreach ( $files as $file ) {
                    $template = require $file;
                    if ( ! is_array( $template ) ) {
                        continue;
                    }

                    $slug           = self::normalize_template_slug( $template['name'] ?? '' );
                    if ( '' === $slug ) {
                        $slug = self::normalize_template_slug( $template['attributes']['template'] ?? '' );
                    }
                    if ( '' === $slug ) {
                        $slug = self::normalize_template_slug( basename( $file, '.php' ) );
                    }
                    $content_blocks = self::extract_content_blocks_from_variation(
                        $popup_type,
                        is_array( $template['innerBlocks'] ?? null ) ? $template['innerBlocks'] : array()
                    );
                    $block_names    = self::flatten_block_names( $content_blocks );

                    $templates[] = array(
                        'slug'               => $slug,
                        'popup_type'         => $popup_type,
                        'title'              => wp_strip_all_tags( (string) ( $template['title'] ?? $slug ) ),
                        'description'        => wp_strip_all_tags( (string) ( $template['description'] ?? '' ) ),
                        'attributes'         => self::sanitize_recursive( is_array( $template['attributes'] ?? null ) ? $template['attributes'] : array() ),
                        'sample_block_names' => array_values( array_unique( $block_names ) ),
                        'content_blocks'     => $content_blocks,
                        'example_markup'     => $examples[ $popup_type ][0]['markup'] ?? '',
                    );
                }
            }
        }

        self::$template_library = $templates;

        return self::$template_library;
    }

    /**
     * Returns demo markup examples keyed by popup type.
     *
     * @return array<string,array<int,array<string,string>>>
     */
    public static function get_demo_examples(): array {
        $examples = array(
            FOOCONVERT_POPUP_TYPE_BAR    => array(),
            FOOCONVERT_POPUP_TYPE_FLYOUT => array(),
            FOOCONVERT_POPUP_TYPE_POPUP  => array(),
        );

        $demo_content = new DemoContent();

        foreach ( $demo_content->get_demo_content() as $entry ) {
            if ( ! is_array( $entry ) ) {
                continue;
            }

            $popup_type = self::normalize_builder_popup_type( $entry['meta_input'][FOOCONVERT_META_KEY_POPUP_TYPE] ?? '' );
            if ( '' === $popup_type || ! isset( $examples[ $popup_type ] ) ) {
                continue;
            }

            $examples[ $popup_type ][] = array(
                'title'  => sanitize_text_field( (string) ( $entry['post_title'] ?? '' ) ),
                'markup' => is_string( $entry['post_content'] ?? null ) ? str_replace( '||POST_ID||', '0', $entry['post_content'] ) : '',
            );
        }

        return $examples;
    }

    /**
     * Returns a single template by slug.
     *
     * @param string $slug Template slug.
     * @return array<string,mixed>|null
     */
    public static function get_template_by_slug( string $slug ): ?array {
        $slug = self::normalize_template_slug( $slug );

        foreach ( self::get_template_library() as $template ) {
            if ( $slug === $template['slug'] ) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Sanitizes the AI response payload.
     *
     * @param mixed $payload AI response payload.
     * @return array<string,mixed>
     */
    public static function sanitize_ai_response( $payload, ?array $selected_block_names = null ): array {
        $payload = is_array( $payload ) ? $payload : array();

        $response = array(
            'assistant_message'  => self::sanitize_rich_text( $payload['assistant_message'] ?? '' ),
            'clarifying_question' => self::sanitize_rich_text( $payload['clarifying_question'] ?? '' ),
            'suggested_prompts'  => self::sanitize_string_list( $payload['suggested_prompts'] ?? array(), 4 ),
            'popup_draft'        => null,
            'validation'         => null,
            'media_items'        => PopupMedia::sanitize_media_items( $payload['media_items'] ?? array() ),
        );

        if ( is_array( $payload['popup_draft'] ?? null ) ) {
            $response['popup_draft'] = self::sanitize_popup_draft( $payload['popup_draft'], $selected_block_names );
            $response['validation']  = self::evaluate_popup_draft( $response['popup_draft'], $selected_block_names );
        }

        return $response;
    }

    /**
     * Sanitizes the AI builder metadata saved alongside a popup draft.
     *
     * @param mixed $metadata Builder metadata payload.
     * @return array<string,mixed>
     */
    public static function sanitize_builder_metadata( $metadata ): array {
        $metadata = is_array( $metadata ) ? $metadata : array();
        $messages = array();
        $response_payload = is_array( $metadata['response'] ?? null ) ? $metadata['response'] : $metadata;
        $options_payload  = is_array( $metadata['options'] ?? null ) ? $metadata['options'] : array();
        $source           = self::sanitize_plain_text( $metadata['source'] ?? '' );

        if ( is_array( $metadata['messages'] ?? null ) ) {
            foreach ( array_slice( array_values( $metadata['messages'] ), -20 ) as $message ) {
                if ( ! is_array( $message ) ) {
                    continue;
                }

                $content = self::sanitize_plain_text( $message['content'] ?? '' );
                if ( '' === $content ) {
                    continue;
                }

                $messages[] = array(
                    'role'    => 'assistant' === ( $message['role'] ?? '' ) ? 'assistant' : 'user',
                    'content' => $content,
                );
            }
        }

        $response = self::sanitize_ai_response(
            array(
                'assistant_message'   => $response_payload['assistant_message'] ?? '',
                'clarifying_question' => $response_payload['clarifying_question'] ?? '',
                'suggested_prompts'   => $response_payload['suggested_prompts'] ?? array(),
                'popup_draft'         => $response_payload['popup_draft'] ?? null,
                'media_items'         => $response_payload['media_items'] ?? array(),
            )
        );

        if ( is_array( $response_payload['validation'] ?? null ) ) {
            $response['validation'] = self::sanitize_validation( $response_payload['validation'] );
        }

        return array(
            'source'      => '' !== $source ? $source : 'ai-popup-builder',
            'saved_at'    => gmdate( 'c' ),
            'messages'    => $messages,
            'response'    => $response,
            'options'     => array(
                'generate_images'        => ! empty( $options_payload['generate_images'] ),
                'force_image_generation' => ! empty( $options_payload['force_image_generation'] ),
            ),
        );
    }

    /**
     * Returns the default saved AI metadata structure used by the popup editor.
     *
     * @return array<string,mixed>
     */
    public static function get_saved_ai_metadata_defaults(): array {
        return array(
            'source'   => '',
            'saved_at' => '',
            'messages' => array(),
            'response' => array(
                'assistant_message'   => '',
                'clarifying_question' => '',
                'suggested_prompts'   => array(),
                'media_items'         => array(),
                'popup_draft'         => null,
                'validation'          => null,
            ),
            'options'  => array(
                'generate_images'        => false,
                'force_image_generation' => false,
            ),
        );
    }

    /**
     * Sanitizes a popup draft payload.
     *
     * @param mixed $draft Popup draft.
     * @return array<string,mixed>
     */
    public static function sanitize_popup_draft( $draft, ?array $selected_block_names = null ): array {
        $draft = is_array( $draft ) ? $draft : array();

        $popup_type   = self::normalize_builder_popup_type( $draft['popup_type'] ?? '' );
        $template_slug = self::normalize_template_slug( $draft['template_slug'] ?? '' );
        $template      = '' !== $template_slug ? self::get_template_by_slug( $template_slug ) : null;

        if ( $popup_type === '' ) {
            $popup_type = $template['popup_type'] ?? FOOCONVERT_POPUP_TYPE_POPUP;
        }

        if ( $template && $template['popup_type'] !== $popup_type ) {
            $template_slug = '';
        }

        return array(
            'title'                => self::sanitize_plain_text( $draft['title'] ?? '' ),
            'popup_type'           => $popup_type,
            'goal'                 => self::sanitize_plain_text( $draft['goal'] ?? '' ),
            'audience'             => self::sanitize_plain_text( $draft['audience'] ?? '' ),
            'offer'                => self::sanitize_plain_text( $draft['offer'] ?? '' ),
            'template_slug'        => $template_slug,
            'trigger'              => self::sanitize_trigger( $draft['trigger'] ?? array(), $popup_type ),
            'root_attributes'      => self::sanitize_root_attributes( $draft['root_attributes'] ?? array(), $popup_type ),
            'content_blocks'       => self::sanitize_content_blocks( $draft['content_blocks'] ?? array(), 0, $selected_block_names ),
            'conversion_rationale' => self::sanitize_string_list( $draft['conversion_rationale'] ?? array(), 5 ),
            'notes'                => self::sanitize_string_list( $draft['notes'] ?? array(), 5 ),
        );
    }

    /**
     * Evaluates a popup draft for high-conversion fundamentals.
     *
     * @param mixed $draft Popup draft.
     * @return array<string,mixed>
     */
    public static function evaluate_popup_draft( $draft, ?array $selected_block_names = null ): array {
        $draft        = self::sanitize_popup_draft( $draft, $selected_block_names );
        $blocks       = self::flatten_blocks( $draft['content_blocks'] );
        $headline_cnt = self::count_blocks_by_name( $blocks, 'core/heading' );
        $button_cnt   = self::count_blocks_by_name( $blocks, 'core/button' );
        $signup_cnt   = self::count_blocks_by_name( $blocks, 'fc/sign-up' );
        $paragraph_cnt = self::count_blocks_by_name( $blocks, 'core/paragraph' );
        $score        = 78;
        $strengths    = array();
        $warnings     = array();
        $suggestions  = array();

        if ( $headline_cnt > 0 ) {
            $strengths[] = __( 'The draft includes a clear headline block.', 'fooconvert' );
            $score += 6;
        } else {
            $warnings[] = __( 'Add a strong headline so the offer lands immediately.', 'fooconvert' );
            $score -= 14;
        }

        if ( $button_cnt > 0 || $signup_cnt > 0 ) {
            $strengths[] = __( 'The popup includes a direct conversion action.', 'fooconvert' );
            $score += 6;
        } else {
            $warnings[] = __( 'There is no obvious CTA or form yet.', 'fooconvert' );
            $score -= 18;
        }

        if ( $button_cnt > 1 ) {
            $warnings[] = __( 'Multiple CTA buttons can reduce focus. Keep one primary action unless a second one is essential.', 'fooconvert' );
            $score -= 8;
        }

        if ( $paragraph_cnt > 4 ) {
            $warnings[] = __( 'The popup may be text-heavy. Tighten the copy to keep it scannable.', 'fooconvert' );
            $score -= 6;
        }

        if ( FOOCONVERT_POPUP_TYPE_BAR === $draft['popup_type'] && count( $blocks ) > 5 ) {
            $warnings[] = __( 'Bars convert best when they stay compact. Reduce the number of content blocks.', 'fooconvert' );
            $score -= 10;
        }

        if ( $signup_cnt > 0 && false === self::goal_mentions_email( $draft['goal'] ) ) {
            $suggestions[] = __( 'If the form is intentional, mention the reward for joining in the copy.', 'fooconvert' );
        }

        if ( '' === $draft['template_slug'] ) {
            $suggestions[] = __( 'Make sure the root attributes and supporting blocks reflect the extracted brand palette, typography, and spacing.', 'fooconvert' );
        } else {
            $strengths[] = __( 'A bundled template is being used as a structural reference.', 'fooconvert' );
            $score += 2;
        }

        if ( empty( $draft['trigger']['type'] ) ) {
            $warnings[] = __( 'Choose a trigger so the popup appears with intent.', 'fooconvert' );
            $score -= 6;
        }

        if ( empty( $warnings ) ) {
            $strengths[] = __( 'The draft is structurally ready for a first pass in the block editor.', 'fooconvert' );
        }

        $score = max( 0, min( 100, $score ) );

        return array(
            'score'       => $score,
            'strengths'   => array_values( array_unique( $strengths ) ),
            'warnings'    => array_values( array_unique( $warnings ) ),
            'suggestions' => array_values( array_unique( $suggestions ) ),
        );
    }

    /**
     * Returns the AI response schema.
     *
     * @return array<string,mixed>
     */
    public static function get_assistant_response_schema( ?array $selected_block_names = null ): array {
        $popup_draft_schema = self::get_popup_draft_schema( $selected_block_names );

        return array(
            'type'                 => 'object',
            'required'             => array( 'assistant_message', 'suggested_prompts', 'clarifying_question', 'media_items', 'popup_draft' ),
            'additionalProperties' => false,
            'properties'           => array(
                'assistant_message'  => array(
                    'type'        => 'string',
                    'description' => __( 'Concise assistant response describing the popup draft or asking the next question.', 'fooconvert' ),
                ),
                'clarifying_question' => array(
                    'type'        => 'string',
                    'description' => __( 'Leave empty when the popup draft is ready. Use only when more input is truly needed.', 'fooconvert' ),
                ),
                'suggested_prompts'  => array(
                    'type'        => 'array',
                    'description' => __( 'Up to four short follow-up prompts that refine the current generated popup. Phrase them as incremental edit commands, not alternate popup briefs.', 'fooconvert' ),
                    'items'       => array(
                        'type' => 'string',
                    ),
                ),
                'media_items'         => array(
                    'type'        => 'array',
                    'description' => __( 'Generated popup images available for the builder media panel.', 'fooconvert' ),
                    'items'       => PopupMedia::get_attachment_schema(),
                ),
                'popup_draft'        => array(
                    'type'        => array( 'object', 'null' ),
                    'description' => __( 'The structured popup blueprint. Omit or use null when asking a clarifying question.', 'fooconvert' ),
                    'required'    => $popup_draft_schema['required'],
                    'additionalProperties' => $popup_draft_schema['additionalProperties'],
                    'properties'  => $popup_draft_schema['properties'],
                    'anyOf'       => array(
                        array(
                            'type' => 'null',
                        ),
                        $popup_draft_schema,
                    ),
                ),
            ),
        );
    }

    /**
     * Returns a plain-text contract for the assistant JSON payload.
     *
     * This is used when the provider cannot reliably honor a nested JSON schema
     * while also using tool calls.
     *
     * @return string
     */
    public static function get_assistant_response_contract( ?array $selected_block_names = null ): string {
        $popup_types = implode( ', ', self::get_builder_popup_types() );
        $trigger_events = implode( ', ', self::get_supported_trigger_events() );
        $block_names = array_keys( self::get_block_catalog_map( $selected_block_names ) );
        $example_names = implode( ', ', array_slice( $block_names, 0, 24 ) );

        return implode(
            "\n",
            array(
                'Return only a single JSON object. Do not wrap it in Markdown fences.',
                'Use exactly these top-level keys: assistant_message, clarifying_question, suggested_prompts, media_items, popup_draft.',
                'assistant_message: string. Keep it concise and practical.',
                'clarifying_question: string. Use an empty string when you can already produce a draft.',
                'suggested_prompts: array of up to 4 short strings. Each prompt must be an incremental edit to the current popup draft, not a new popup brief. Preserve the current goal, audience, offer, popup type, and trigger unless the user explicitly asks to change them. Prefer prompts that tweak copy, CTA tone, urgency, proof, layout density, form friction, styling, or trigger timing.',
                'media_items: array. Use an empty array when no popup images are available yet.',
                'popup_draft: either null or an object with these keys:',
                '- title: string',
                '- popup_type: one of ' . $popup_types,
                '- goal: string',
                '- audience: string',
                '- offer: string',
                '- template_slug: string',
                '- trigger: object. Include type, lifetime, frequency, and only the event options needed for the chosen trigger.',
                '- trigger.type may use a shortcut (immediate, delay, exit_intent, scroll_percent) or a supported event identifier. For advanced triggers you may also set trigger.event. Supported events: ' . $trigger_events,
                '- trigger.where supplies advanced event options such as seconds, delaySeconds, percent, ids, selector, productIds, couponCodes, subtotal.operator, subtotal.amount, scrollPercent, timeSeconds, or viewCount.',
                '- trigger.lifetime must be one of page, session, visit. trigger.frequency must be one of once, repeat.',
                '- root_attributes: object. Only use these top-level keys when needed: template, settings, styles, openButton, closeButton, content',
                '- content_blocks: array of supported blocks',
                '- conversion_rationale: array of short strings',
                '- notes: array of short strings',
                'Use the block catalog ability to inspect the currently supported core, popup, and WooCommerce blocks before choosing advanced blocks.',
                'Example supported content block names: ' . $example_names,
                'Each content block should use this shape: {"name":"core/heading","attributes":{},"inner_blocks":[]}.',
                'Only blocks that support children may include non-empty inner_blocks.',
                'For core/list, prefer attributes.items as an array of strings.',
                'For fc/sign-up, use nested attributes like {"settings":{},"inputs":{"settings":{"emailOnly":true,"emailPlaceholder":"Enter your email"}},"button":{"settings":{"text":"Get My Offer"}}}. Do not use shorthand keys like buttonText.',
                'Use template_slug only when you want a structural reference. Brand context should drive styling choices.',
                'If you provide a popup_draft, make it complete enough to render immediately and suitable for popup validation.',
                'If you create or import an image, include that image in media_items and reference it from any core/image block using attributes.id, url, alt, and title when available.',
            )
        );
    }

    /**
     * Returns the saved AI builder metadata schema.
     *
     * @return array<string,mixed>
     */
    public static function get_saved_ai_metadata_schema(): array {
        $response_schema = self::get_assistant_response_schema();
        $validation_schema = array(
            'type'                 => 'object',
            'required'             => array( 'score', 'strengths', 'warnings', 'suggestions' ),
            'additionalProperties' => false,
            'properties'           => array(
                'score'       => array(
                    'type' => 'integer',
                ),
                'strengths'   => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
                'warnings'    => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
                'suggestions' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
            ),
        );
        $response_schema['properties']['validation'] = array(
            'type'       => array( 'object', 'null' ),
            'required'   => $validation_schema['required'],
            'additionalProperties' => $validation_schema['additionalProperties'],
            'properties' => $validation_schema['properties'],
            'anyOf'      => array(
                array(
                    'type' => 'null',
                ),
                $validation_schema,
            ),
        );
        $response_schema['properties']['popup_draft']['properties']['popup_type']['enum'] = array_values(
            array_unique(
                array_merge(
                    $response_schema['properties']['popup_draft']['properties']['popup_type']['enum'],
                    array( FOOCONVERT_POPUP_TYPE_OVERLAY )
                )
            )
        );
        $response_schema['properties']['popup_draft']['anyOf'][1]['properties']['popup_type']['enum'] = $response_schema['properties']['popup_draft']['properties']['popup_type']['enum'];

        return array(
            'type'       => 'object',
            'properties' => array(
                'source'   => array(
                    'type' => 'string',
                ),
                'saved_at' => array(
                    'type' => 'string',
                ),
                'messages' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'role'    => array(
                                'type' => 'string',
                            ),
                            'content' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'response' => $response_schema,
                'options'  => array(
                    'type'       => 'object',
                    'properties' => array(
                        'generate_images'       => array(
                            'type' => 'boolean',
                        ),
                        'force_image_generation' => array(
                            'type' => 'boolean',
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Returns the popup draft schema used for AI responses and validation.
     *
     * @return array<string,mixed>
     */
    public static function get_popup_draft_schema( ?array $selected_block_names = null ): array {
        return array(
            'type'                 => 'object',
            'required'             => array(
                'title',
                'popup_type',
                'goal',
                'audience',
                'offer',
                'template_slug',
                'trigger',
                'root_attributes',
                'content_blocks',
                'conversion_rationale',
                'notes',
            ),
            'additionalProperties' => false,
            'properties'           => array(
                'title'                => array(
                    'type' => 'string',
                ),
                'popup_type'           => array(
                    'type' => 'string',
                    'enum' => self::get_builder_popup_types(),
                ),
                'goal'                 => array(
                    'type' => 'string',
                ),
                'audience'             => array(
                    'type' => 'string',
                ),
                'offer'                => array(
                    'type' => 'string',
                ),
                'template_slug'        => array(
                    'type' => 'string',
                ),
                'trigger'              => array(
                    'type'                 => 'object',
                    'additionalProperties' => true,
                ),
                'root_attributes'      => array(
                    'type'                 => 'object',
                    'additionalProperties' => true,
                ),
                'content_blocks'       => array(
                    'type'  => 'array',
                    'items' => self::get_content_block_schema( $selected_block_names ),
                ),
                'conversion_rationale' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
                'notes'                => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
            ),
        );
    }

    /**
     * Returns a relaxed popup draft schema for helper abilities that only need
     * partial popup context.
     *
     * @return array<string,mixed>
     */
    public static function get_popup_draft_context_schema(): array {
        return array(
            'type'                 => 'object',
            'additionalProperties' => false,
            'properties'           => array(
                'title'                => array(
                    'type' => 'string',
                ),
                'popup_type'           => array(
                    'type' => 'string',
                    'enum' => self::get_builder_popup_types(),
                ),
                'goal'                 => array(
                    'type' => 'string',
                ),
                'audience'             => array(
                    'type' => 'string',
                ),
                'offer'                => array(
                    'type' => 'string',
                ),
                'template_slug'        => array(
                    'type' => 'string',
                ),
                'trigger'              => array(
                    'type'                 => 'object',
                    'additionalProperties' => true,
                ),
                'root_attributes'      => array(
                    'type'                 => 'object',
                    'additionalProperties' => true,
                ),
                'content_blocks'       => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'                 => 'object',
                        'additionalProperties' => true,
                    ),
                ),
                'conversion_rationale' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
                'notes'                => array(
                    'type'  => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
            ),
        );
    }

    /**
     * Returns the content block schema used for AI responses.
     *
     * @return array<string,mixed>
     */
    private static function get_content_block_schema( ?array $selected_block_names = null ): array {
        return array(
            'type'                 => 'object',
            'required'             => array( 'name', 'attributes', 'inner_blocks' ),
            'additionalProperties' => false,
            'properties'           => array(
                'name'        => array(
                    'type' => 'string',
                    'enum' => array_keys( self::get_block_catalog_map( $selected_block_names ) ),
                ),
                'attributes'  => array(
                    'type'                 => 'object',
                    'additionalProperties' => true,
                ),
                'inner_blocks' => array(
                    'type'  => 'array',
                    'items' => array(
                        'type'                 => 'object',
                        'additionalProperties' => true,
                    ),
                ),
            ),
        );
    }

    /**
     * Returns the template directories keyed by popup type.
     *
     * @return array<string,array<int,string>>
     */
    private static function get_template_directories(): array {
        $directories = array(
            FOOCONVERT_POPUP_TYPE_BAR    => array(
                FOOCONVERT_INCLUDES_PATH . 'Admin/Templates/bars',
            ),
            FOOCONVERT_POPUP_TYPE_FLYOUT => array(
                FOOCONVERT_INCLUDES_PATH . 'Admin/Templates/flyouts',
            ),
            FOOCONVERT_POPUP_TYPE_POPUP  => array(
                FOOCONVERT_INCLUDES_PATH . 'Admin/Templates/overlays',
            ),
        );

        if ( defined( 'FOOCONVERT_PRO_INCLUDES_PATH' ) ) {
            $directories[ FOOCONVERT_POPUP_TYPE_BAR ][]    = FOOCONVERT_PRO_INCLUDES_PATH . 'Admin/Templates/bars';
            $directories[ FOOCONVERT_POPUP_TYPE_FLYOUT ][] = FOOCONVERT_PRO_INCLUDES_PATH . 'Admin/Templates/flyouts';
            $directories[ FOOCONVERT_POPUP_TYPE_POPUP ][]  = FOOCONVERT_PRO_INCLUDES_PATH . 'Admin/Templates/overlays';
        }

        foreach ( $directories as $popup_type => $popup_type_directories ) {
            $directories[ $popup_type ] = array_values(
                array_filter(
                    array_unique( $popup_type_directories ),
                    static function( string $directory ): bool {
                        return is_dir( $directory );
                    }
                )
            );
        }

        return $directories;
    }

    /**
     * Extracts content blocks from a template variation structure.
     *
     * @param string                   $popup_type Popup type.
     * @param array<int,array<mixed>>  $blocks Template blocks.
     * @return array<int,array<string,mixed>>
     */
    private static function extract_content_blocks_from_variation( string $popup_type, array $blocks ): array {
        $content_block_name = self::get_content_container_block_name( $popup_type );

        foreach ( $blocks as $block ) {
            if ( ! is_array( $block ) || empty( $block[0] ) ) {
                continue;
            }

            $name       = (string) $block[0];
            $inner      = is_array( $block[2] ?? null ) ? $block[2] : array();

            if ( $name === $content_block_name ) {
                return self::normalize_variation_blocks( $inner );
            }

            $found = self::extract_content_blocks_from_variation( $popup_type, $inner );
            if ( ! empty( $found ) ) {
                return $found;
            }
        }

        return array();
    }

    /**
     * Normalizes a variation inner block array into popup draft blocks.
     *
     * @param array<int,array<mixed>> $blocks Variation blocks.
     * @return array<int,array<string,mixed>>
     */
    private static function normalize_variation_blocks( array $blocks ): array {
        $normalized = array();

        foreach ( $blocks as $block ) {
            if ( ! is_array( $block ) || empty( $block[0] ) ) {
                continue;
            }

            $item = array(
                'name' => (string) $block[0],
            );

            if ( ! empty( $block[1] ) && is_array( $block[1] ) ) {
                $item['attributes'] = self::sanitize_recursive( $block[1] );
            }

            if ( ! empty( $block[2] ) && is_array( $block[2] ) ) {
                $children = self::normalize_variation_blocks( $block[2] );
                if ( ! empty( $children ) ) {
                    $item['inner_blocks'] = $children;
                }
            }

            $normalized[] = $item;
        }

        return self::sanitize_content_blocks( $normalized );
    }

    /**
     * Returns the content container block name for a popup type.
     *
     * @param string $popup_type Popup type.
     * @return string
     */
    private static function get_content_container_block_name( string $popup_type ): string {
        switch ( self::normalize_builder_popup_type( $popup_type ) ) {
            case FOOCONVERT_POPUP_TYPE_BAR:
                return 'fc/bar-content';
            case FOOCONVERT_POPUP_TYPE_FLYOUT:
                return 'fc/flyout-content';
            case FOOCONVERT_POPUP_TYPE_POPUP:
            default:
                return 'fc/overlay-content';
        }
    }

    /**
     * Sanitizes a popup trigger structure.
     *
     * @param mixed  $trigger Trigger payload.
     * @param string $popup_type Popup type.
     * @return array<string,mixed>
     */
    private static function sanitize_trigger( $trigger, string $popup_type ): array {
        $trigger      = is_array( $trigger ) ? $trigger : array();
        $type_value   = is_string( $trigger['type'] ?? null ) ? trim( $trigger['type'] ) : '';
        $event_value  = is_string( $trigger['event'] ?? null ) ? trim( $trigger['event'] ) : '';
        $event        = self::normalize_trigger_event( $event_value );
        $event        = '' !== $event ? $event : self::normalize_trigger_event( $type_value );
        $type         = in_array( $type_value, self::get_supported_trigger_type_values(), true ) ? $type_value : '';

        if ( '' === $event ) {
            $type  = self::get_default_trigger_type( $popup_type );
            $event = self::normalize_trigger_event( $type );
        }

        if ( '' === $type ) {
            $type = self::get_trigger_type_for_event( $event );
        }

        return array(
            'type'           => $type,
            'event'          => $event,
            'where'          => self::sanitize_trigger_where( $trigger['where'] ?? array() ),
            'delay_seconds'  => max( 0, min( 60, absint( $trigger['delay_seconds'] ?? self::get_default_delay( $type ) ) ) ),
            'scroll_percent' => max( 1, min( 100, absint( $trigger['scroll_percent'] ?? 20 ) ) ),
            'lifetime'       => in_array( $trigger['lifetime'] ?? '', array( 'page', 'session', 'visit' ), true ) ? $trigger['lifetime'] : 'page',
            'frequency'      => self::sanitize_trigger_frequency( $trigger['frequency'] ?? '', $event ),
        );
    }

    /**
     * Returns the supported open-trigger event identifiers.
     *
     * @return string[]
     */
    public static function get_supported_trigger_events(): array {
        return array(
            'fc.immediate',
            'fc.anchor.click',
            'fc.element.visible',
            'fc.element.click',
            'fc.exit_intent',
            'fc.scroll.percent',
            'fc.timer.elapsed',
            'cart.add',
            'cart.updated',
            'cart.remove',
            'coupon.applied',
            'coupon.invalid',
            'checkout.error',
            'checkout.payment_failed',
            'cart.idle',
            'checkout.enter',
            'checkout.exit',
            'product.view',
            'product.high_intent',
        );
    }

    /**
     * Returns aliases accepted by the AI builder trigger shortcut shape.
     *
     * @return array<string,string>
     */
    private static function get_trigger_event_aliases(): array {
        return array(
            'immediate'       => 'fc.immediate',
            'delay'           => 'fc.timer.elapsed',
            'timer'           => 'fc.timer.elapsed',
            'exit_intent'     => 'fc.exit_intent',
            'exit-intent'     => 'fc.exit_intent',
            'scroll_percent'  => 'fc.scroll.percent',
            'scroll'          => 'fc.scroll.percent',
            'anchor'          => 'fc.anchor.click',
            'anchor_click'    => 'fc.anchor.click',
            'element'         => 'fc.element.click',
            'element_click'   => 'fc.element.click',
            'element_visible' => 'fc.element.visible',
        );
    }

    /**
     * Returns the supported values for popup_draft.trigger.type.
     *
     * @return string[]
     */
    private static function get_supported_trigger_type_values(): array {
        return array_values(
            array_unique(
                array_merge(
                    array_keys( self::get_trigger_event_aliases() ),
                    self::get_supported_trigger_events()
                )
            )
        );
    }

    /**
     * Normalizes a trigger event or alias.
     *
     * @param mixed $value Trigger event or alias.
     * @return string
     */
    private static function normalize_trigger_event( $value ): string {
        $value = is_string( $value ) ? trim( $value ) : '';

        if ( '' === $value ) {
            return '';
        }

        if ( in_array( $value, self::get_supported_trigger_events(), true ) ) {
            return $value;
        }

        $aliases = self::get_trigger_event_aliases();

        return $aliases[ $value ] ?? '';
    }

    /**
     * Returns the preferred shortcut type for a trigger event.
     *
     * @param string $event Trigger event.
     * @return string
     */
    private static function get_trigger_type_for_event( string $event ): string {
        switch ( $event ) {
            case 'fc.immediate':
                return 'immediate';
            case 'fc.timer.elapsed':
                return 'delay';
            case 'fc.exit_intent':
                return 'exit_intent';
            case 'fc.scroll.percent':
                return 'scroll_percent';
            default:
                return $event;
        }
    }

    /**
     * Sanitizes trigger event options.
     *
     * @param mixed $where Trigger where payload.
     * @return array<string,mixed>
     */
    private static function sanitize_trigger_where( $where ): array {
        if ( ! is_array( $where ) ) {
            return array();
        }

        $sanitized = self::sanitize_recursive( $where, 'trigger.where' );

        return is_array( $sanitized ) ? $sanitized : array();
    }

    /**
     * Sanitizes trigger frequency.
     *
     * @param mixed  $frequency Trigger frequency payload.
     * @param string $event Trigger event.
     * @return string
     */
    private static function sanitize_trigger_frequency( $frequency, string $event ): string {
        if ( is_array( $frequency ) ) {
            $frequency = $frequency['mode'] ?? '';
        }

        if ( in_array( $frequency, array( 'once', 'repeat' ), true ) ) {
            return $frequency;
        }

        return in_array(
            $event,
            array(
                'fc.anchor.click',
                'fc.element.click',
                'cart.add',
                'cart.updated',
                'cart.remove',
                'coupon.applied',
                'coupon.invalid',
                'checkout.error',
                'checkout.payment_failed',
            ),
            true
        ) ? 'repeat' : 'once';
    }

    /**
     * Returns a default trigger type for the popup type.
     *
     * @param string $popup_type Popup type.
     * @return string
     */
    private static function get_default_trigger_type( string $popup_type ): string {
        switch ( self::normalize_builder_popup_type( $popup_type ) ) {
            case FOOCONVERT_POPUP_TYPE_BAR:
                return 'delay';
            case FOOCONVERT_POPUP_TYPE_FLYOUT:
                return 'scroll_percent';
            case FOOCONVERT_POPUP_TYPE_POPUP:
            default:
                return 'exit_intent';
        }
    }

    /**
     * Returns the default delay for a trigger type.
     *
     * @param string $type Trigger type.
     * @return int
     */
    private static function get_default_delay( string $type ): int {
        $event = self::normalize_trigger_event( $type );

        if ( 'exit_intent' === $type || 'fc.exit_intent' === $event ) {
            return 5;
        }

        if ( 'delay' === $type || 'fc.timer.elapsed' === $event ) {
            return 4;
        }

        return 0;
    }

    /**
     * Sanitizes root attributes.
     *
     * @param mixed  $attributes Root attributes.
     * @param string $popup_type Popup type.
     * @return array<string,mixed>
     */
    private static function sanitize_root_attributes( $attributes, string $popup_type ): array {
        $attributes = is_array( $attributes ) ? $attributes : array();
        $allowed    = array( 'settings', 'styles', 'openButton', 'closeButton', 'content', 'template' );
        $sanitized  = array();

        foreach ( $allowed as $key ) {
            if ( ! array_key_exists( $key, $attributes ) ) {
                continue;
            }

            if ( 'template' === $key ) {
                $sanitized[ $key ] = self::normalize_template_slug( $attributes[ $key ] );
                continue;
            }

            if ( FOOCONVERT_POPUP_TYPE_POPUP === $popup_type && 'openButton' === $key ) {
                continue;
            }

            if ( is_array( $attributes[ $key ] ) ) {
                $sanitized[ $key ] = self::sanitize_recursive( $attributes[ $key ] );
            }
        }

        return $sanitized;
    }

    /**
     * Sanitizes content blocks.
     *
     * @param mixed $blocks Content blocks.
     * @param int   $depth Current recursion depth.
     * @return array<int,array<string,mixed>>
     */
    private static function sanitize_content_blocks( $blocks, int $depth = 0, ?array $selected_block_names = null ): array {
        if ( ! is_array( $blocks ) || $depth > 4 ) {
            return array();
        }

        $catalog   = self::get_block_catalog_map( $selected_block_names );
        $sanitized = array();

        foreach ( array_values( $blocks ) as $block ) {
            if ( ! is_array( $block ) ) {
                continue;
            }

            $name = isset( $block['name'] ) && is_string( $block['name'] ) ? trim( $block['name'] ) : '';
            if ( '' === $name || ! isset( $catalog[ $name ] ) ) {
                continue;
            }

            $item = array(
                'name' => $name,
            );

            if ( is_array( $block['attributes'] ?? null ) ) {
                $attributes = self::sanitize_recursive( $block['attributes'], 'attributes.' . $name );
                if ( is_array( $attributes ) ) {
                    $attributes = self::normalize_content_block_attributes( $name, $attributes );
                }

                if ( ! empty( $attributes ) ) {
                    $item['attributes'] = $attributes;
                }
            }

            if ( ! empty( $catalog[ $name ]['supports_children'] ) && is_array( $block['inner_blocks'] ?? null ) ) {
                $children = self::sanitize_content_blocks( $block['inner_blocks'], $depth + 1, $selected_block_names );

                if ( ! empty( $catalog[ $name ]['allowed_children'] ) ) {
                    $allowed_children = $catalog[ $name ]['allowed_children'];
                    $children         = array_values(
                        array_filter(
                            $children,
                            static function( array $child ) use ( $allowed_children ): bool {
                                return in_array( $child['name'], $allowed_children, true );
                            }
                        )
                    );
                }

                if ( ! empty( $children ) ) {
                    $item['inner_blocks'] = $children;
                }
            }

            $sanitized[] = $item;
        }

        return array_slice( $sanitized, 0, 16 );
    }

    /**
     * Normalizes supported block attributes into the shape expected by the UI and serializer.
     *
     * @param string               $name Block name.
     * @param array<string,mixed>  $attributes Sanitized attributes.
     * @return array<string,mixed>
     */
    private static function normalize_content_block_attributes( string $name, array $attributes ): array {
        switch ( $name ) {
            case 'core/heading':
                return self::normalize_text_block_alignment( $attributes, 'textAlign' );

            case 'core/paragraph':
                return self::normalize_text_block_alignment( $attributes, 'align' );

            case 'core/list':
                $attributes['items'] = self::extract_list_items_from_attributes( $attributes );
                unset( $attributes['values'] );
                return $attributes;

            case 'core/button':
                if ( empty( $attributes['text'] ) && ! empty( $attributes['content'] ) && is_string( $attributes['content'] ) ) {
                    $attributes['text'] = self::sanitize_plain_text( $attributes['content'] );
                }
                return $attributes;

            case 'core/image':
                if ( empty( $attributes['url'] ) && ! empty( $attributes['src'] ) && is_string( $attributes['src'] ) ) {
                    $attributes['url'] = esc_url_raw( $attributes['src'] );
                }

                if ( empty( $attributes['id'] ) && ! empty( $attributes['mediaId'] ) ) {
                    $attributes['id'] = absint( $attributes['mediaId'] );
                }

                if ( empty( $attributes['id'] ) && ! empty( $attributes['attachmentId'] ) ) {
                    $attributes['id'] = absint( $attributes['attachmentId'] );
                }

                if ( empty( $attributes['alt'] ) && ! empty( $attributes['altText'] ) && is_string( $attributes['altText'] ) ) {
                    $attributes['alt'] = self::sanitize_plain_text( $attributes['altText'] );
                }

                unset( $attributes['src'], $attributes['mediaId'], $attributes['attachmentId'], $attributes['altText'] );
                return $attributes;

            case 'fc/sign-up':
                $attributes['settings'] = is_array( $attributes['settings'] ?? null ) ? $attributes['settings'] : array();
                $attributes['inputs']   = is_array( $attributes['inputs'] ?? null ) ? $attributes['inputs'] : array();
                $attributes['button']   = is_array( $attributes['button'] ?? null ) ? $attributes['button'] : array();

                $attributes['inputs']['settings'] = is_array( $attributes['inputs']['settings'] ?? null ) ? $attributes['inputs']['settings'] : array();
                $attributes['button']['settings'] = is_array( $attributes['button']['settings'] ?? null ) ? $attributes['button']['settings'] : array();

                if ( empty( $attributes['button']['settings']['text'] ) && ! empty( $attributes['buttonText'] ) && is_string( $attributes['buttonText'] ) ) {
                    $attributes['button']['settings']['text'] = self::sanitize_plain_text( $attributes['buttonText'] );
                }

                if ( empty( $attributes['settings']['successMessage'] ) && ! empty( $attributes['successMessage'] ) && is_string( $attributes['successMessage'] ) ) {
                    $attributes['settings']['successMessage'] = self::sanitize_plain_text( $attributes['successMessage'] );
                }

                if ( ! isset( $attributes['settings']['closeOnSuccess'] ) && isset( $attributes['closeOnSuccess'] ) && is_bool( $attributes['closeOnSuccess'] ) ) {
                    $attributes['settings']['closeOnSuccess'] = $attributes['closeOnSuccess'];
                }

                if ( ! isset( $attributes['inputs']['settings']['emailOnly'] ) && isset( $attributes['emailOnly'] ) && is_bool( $attributes['emailOnly'] ) ) {
                    $attributes['inputs']['settings']['emailOnly'] = $attributes['emailOnly'];
                }

                if ( empty( $attributes['inputs']['settings']['emailPlaceholder'] ) && ! empty( $attributes['emailPlaceholder'] ) && is_string( $attributes['emailPlaceholder'] ) ) {
                    $attributes['inputs']['settings']['emailPlaceholder'] = self::sanitize_plain_text( $attributes['emailPlaceholder'] );
                }

                if ( empty( $attributes['inputs']['settings']['namePlaceholder'] ) && ! empty( $attributes['namePlaceholder'] ) && is_string( $attributes['namePlaceholder'] ) ) {
                    $attributes['inputs']['settings']['namePlaceholder'] = self::sanitize_plain_text( $attributes['namePlaceholder'] );
                }

                unset(
                    $attributes['buttonText'],
                    $attributes['successMessage'],
                    $attributes['closeOnSuccess'],
                    $attributes['emailOnly'],
                    $attributes['emailPlaceholder'],
                    $attributes['namePlaceholder']
                );

                return $attributes;

            default:
                return $attributes;
        }
    }

    /**
     * Normalizes core text-block alignment into the current block attribute shape.
     *
     * @param array<string,mixed> $attributes Block attributes.
     * @param string              $primary_attribute Canonical top-level alignment attribute.
     * @return array<string,mixed>
     */
    private static function normalize_text_block_alignment( array $attributes, string $primary_attribute ): array {
        $secondary_attribute = 'align' === $primary_attribute ? 'textAlign' : 'align';
        $style               = is_array( $attributes['style'] ?? null ) ? $attributes['style'] : array();
        $typography          = is_array( $style['typography'] ?? null ) ? $style['typography'] : array();
        $alignment           = self::normalize_text_alignment_value( $attributes[ $primary_attribute ] ?? '' );

        if ( '' === $alignment ) {
            $alignment = self::normalize_text_alignment_value( $attributes[ $secondary_attribute ] ?? '' );
        }

        if ( '' === $alignment ) {
            $alignment = self::normalize_text_alignment_value( $typography['textAlign'] ?? '' );
        }

        if ( '' === $alignment ) {
            return $attributes;
        }

        $attributes[ $primary_attribute ] = $alignment;
        unset( $attributes[ $secondary_attribute ] );

        $typography['textAlign'] = $alignment;
        $style['typography']     = $typography;
        $attributes['style']     = $style;

        return $attributes;
    }

    /**
     * Normalizes a text alignment value.
     *
     * @param mixed $value Alignment value.
     * @return string
     */
    private static function normalize_text_alignment_value( $value ): string {
        if ( ! is_string( $value ) ) {
            return '';
        }

        $alignment = strtolower( trim( $value ) );
        return in_array( $alignment, array( 'left', 'center', 'right', 'justify' ), true ) ? $alignment : '';
    }

    /**
     * Extracts plain-text list items from supported AI list payload shapes.
     *
     * @param array<string,mixed> $attributes List block attributes.
     * @return array<int,string>
     */
    private static function extract_list_items_from_attributes( array $attributes ): array {
        if ( is_array( $attributes['items'] ?? null ) ) {
            return self::sanitize_plain_text_list( $attributes['items'] );
        }

        if ( is_array( $attributes['values'] ?? null ) ) {
            return self::sanitize_plain_text_list( $attributes['values'] );
        }

        if ( ! is_string( $attributes['values'] ?? null ) ) {
            return array();
        }

        $value = trim( $attributes['values'] );
        if ( '' === $value ) {
            return array();
        }

        if ( preg_match_all( '/<li\b[^>]*>(.*?)<\/li>/si', $value, $matches ) && ! empty( $matches[1] ) ) {
            return self::sanitize_plain_text_list( $matches[1] );
        }

        return self::sanitize_plain_text_list( array( $value ) );
    }

    /**
     * Sanitizes nested values while preserving useful formatting strings.
     *
     * @param mixed  $value Source value.
     * @param string $path Key path.
     * @param int    $depth Recursion depth.
     * @return mixed
     */
    private static function sanitize_recursive( $value, string $path = '', int $depth = 0 ) {
        if ( $depth > 6 ) {
            return null;
        }

        if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
            return $value;
        }

        if ( is_string( $value ) ) {
            $last_segment = $path;
            $path_parts   = explode( '.', $path );
            if ( ! empty( $path_parts ) ) {
                $last_segment = end( $path_parts );
            }

            if ( in_array( $last_segment, array( 'content', 'text', 'values', 'description' ), true ) ) {
                return self::sanitize_rich_text( $value );
            }

            if ( in_array( $last_segment, array( 'url', 'href', 'src' ), true ) ) {
                return esc_url_raw( $value );
            }

            return self::sanitize_plain_text( $value );
        }

        if ( is_array( $value ) ) {
            $sanitized = array();
            foreach ( $value as $key => $item ) {
                if ( ! is_string( $key ) && ! is_int( $key ) ) {
                    continue;
                }

                $next_path = '' !== $path ? $path . '.' . (string) $key : (string) $key;
                $clean     = self::sanitize_recursive( $item, $next_path, $depth + 1 );
                if ( null !== $clean ) {
                    $sanitized[ $key ] = $clean;
                }
            }
            return $sanitized;
        }

        return null;
    }

    /**
     * Sanitizes a plain-text string.
     *
     * @param mixed $value Source string.
     * @return string
     */
    private static function sanitize_plain_text( $value ): string {
        return is_string( $value ) ? sanitize_text_field( $value ) : '';
    }

    /**
     * Sanitizes rich text content.
     *
     * @param mixed $value Source string.
     * @return string
     */
    private static function sanitize_rich_text( $value ): string {
        return is_string( $value ) ? wp_kses_post( $value ) : '';
    }

    /**
     * Sanitizes a string list.
     *
     * @param mixed $items Source list.
     * @param int   $limit Maximum entries.
     * @return array<int,string>
     */
    private static function sanitize_string_list( $items, int $limit = 6 ): array {
        if ( ! is_array( $items ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( $items as $item ) {
            $clean = self::sanitize_plain_text( $item );
            if ( '' !== $clean ) {
                $sanitized[] = $clean;
            }
        }

        return array_slice( array_values( array_unique( $sanitized ) ), 0, $limit );
    }

    /**
     * Sanitizes a generic list of plain-text strings.
     *
     * @param array<int,mixed> $items Source values.
     * @return array<int,string>
     */
    private static function sanitize_plain_text_list( array $items ): array {
        $sanitized = array();

        foreach ( $items as $item ) {
            $clean = self::sanitize_plain_text( wp_strip_all_tags( (string) $item ) );
            if ( '' !== $clean ) {
                $sanitized[] = $clean;
            }
        }

        return array_values( array_unique( $sanitized ) );
    }

    /**
     * Sanitizes a popup validation payload.
     *
     * @param mixed $validation Validation payload.
     * @return array<string,mixed>
     */
    private static function sanitize_validation( $validation ): array {
        $validation = is_array( $validation ) ? $validation : array();

        return array(
            'score'       => max( 0, min( 100, absint( $validation['score'] ?? 0 ) ) ),
            'strengths'   => self::sanitize_string_list( $validation['strengths'] ?? array(), 6 ),
            'warnings'    => self::sanitize_string_list( $validation['warnings'] ?? array(), 6 ),
            'suggestions' => self::sanitize_string_list( $validation['suggestions'] ?? array(), 6 ),
        );
    }

    /**
     * Normalizes a template slug.
     *
     * @param mixed $slug Template slug.
     * @return string
     */
    private static function normalize_template_slug( $slug ): string {
        if ( ! is_string( $slug ) ) {
            return '';
        }

        return preg_replace( '/[^a-z0-9_\\-]/', '', strtolower( $slug ) ) ?: '';
    }

    /**
     * Flattens a content block tree into block names.
     *
     * @param array<int,array<string,mixed>> $blocks Blocks.
     * @return array<int,string>
     */
    private static function flatten_block_names( array $blocks ): array {
        $names = array();

        foreach ( $blocks as $block ) {
            if ( ! empty( $block['name'] ) && is_string( $block['name'] ) ) {
                $names[] = $block['name'];
            }

            if ( ! empty( $block['inner_blocks'] ) && is_array( $block['inner_blocks'] ) ) {
                $names = array_merge( $names, self::flatten_block_names( $block['inner_blocks'] ) );
            }
        }

        return $names;
    }

    /**
     * Flattens a content block tree into full block entries.
     *
     * @param array<int,array<string,mixed>> $blocks Blocks.
     * @return array<int,array<string,mixed>>
     */
    private static function flatten_blocks( array $blocks ): array {
        $flattened = array();

        foreach ( $blocks as $block ) {
            $flattened[] = $block;
            if ( ! empty( $block['inner_blocks'] ) && is_array( $block['inner_blocks'] ) ) {
                $flattened = array_merge( $flattened, self::flatten_blocks( $block['inner_blocks'] ) );
            }
        }

        return $flattened;
    }

    /**
     * Counts blocks by block name.
     *
     * @param array<int,array<string,mixed>> $blocks Flattened blocks.
     * @param string                         $name Block name.
     * @return int
     */
    private static function count_blocks_by_name( array $blocks, string $name ): int {
        $count = 0;

        foreach ( $blocks as $block ) {
            if ( isset( $block['name'] ) && $block['name'] === $name ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Checks whether the goal likely implies email capture intent.
     *
     * @param string $goal Goal text.
     * @return bool
     */
    private static function goal_mentions_email( string $goal ): bool {
        $goal = strtolower( $goal );

        return false !== strpos( $goal, 'email' ) ||
            false !== strpos( $goal, 'newsletter' ) ||
            false !== strpos( $goal, 'lead' ) ||
            false !== strpos( $goal, 'signup' ) ||
            false !== strpos( $goal, 'sign up' );
    }
}
