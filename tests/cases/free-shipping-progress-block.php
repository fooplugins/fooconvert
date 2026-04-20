<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Blocks\Base {
    use WP_Block;

    abstract class BaseBlock {
        public function render( array $attributes, string $content, WP_Block $block ) {
            return $content;
        }

        public function render_empty(): string {
            return '';
        }

        public function enqueue_frontend_styles( string $instance_id, array $styles ): bool {
            return true;
        }

        public function kses( array $attributes, string $content, WP_Block $block, string $context = '' ): string {
            return $content;
        }

        public function get_settings( array $attributes, string $child = '' ): array {
            if ( $child !== '' && isset( $attributes[ $child ] ) && is_array( $attributes[ $child ] ) ) {
                return is_array( $attributes[ $child ]['settings'] ?? null ) ? $attributes[ $child ]['settings'] : array();
            }

            return is_array( $attributes['settings'] ?? null ) ? $attributes['settings'] : array();
        }

        public function get_styles( array $attributes, string $child = '' ): array {
            if ( $child !== '' && isset( $attributes[ $child ] ) && is_array( $attributes[ $child ] ) ) {
                return is_array( $attributes[ $child ]['styles'] ?? null ) ? $attributes[ $child ]['styles'] : array();
            }

            return is_array( $attributes['styles'] ?? null ) ? $attributes['styles'] : array();
        }
    }
}

namespace FooPlugins\FooConvert {
    class Utils {
        public static function get_key( $array_or_object, $key, $default = null ) {
            if ( is_array( $array_or_object ) && array_key_exists( $key, $array_or_object ) ) {
                return $array_or_object[ $key ];
            }

            return $default;
        }

        public static function get_string( $array_or_object, $key, string $default = '' ): string {
            $value = self::get_key( $array_or_object, $key, $default );
            return is_string( $value ) ? $value : $default;
        }

        public static function get_int( $array_or_object, $key, int $default = 0 ): int {
            $value = self::get_key( $array_or_object, $key, $default );
            return is_int( $value ) ? $value : ( is_numeric( $value ) ? (int) $value : $default );
        }

        public static function get_bool( $array_or_object, $key, bool $default = false ): bool {
            $value = self::get_key( $array_or_object, $key, $default );
            return is_bool( $value ) ? $value : $default;
        }

        public static function get_key_path( $array_or_object, $key_path, $default = null ) {
            $keys = is_array( $key_path ) ? $key_path : explode( '.', (string) $key_path );
            $target = $array_or_object;

            foreach ( $keys as $key ) {
                if ( is_array( $target ) && array_key_exists( $key, $target ) ) {
                    $target = $target[ $key ];
                    continue;
                }

                return $default;
            }

            return $target;
        }

        public static function register_popup_blocks( array $blocks ) {
            return $blocks;
        }
    }

    class Components {
        public function get_font_family_classnames( array $attributes, array $paths ): array {
            return array();
        }

        public function get_styles( array $styles_attribute, array $color_map = array() ): array {
            return $styles_attribute;
        }
    }

    class Popups {
        public function get_registered_post_type(): string {
            return 'fc-popup';
        }
    }

    class FooConvert {
        /** @var ?FooConvert */
        private static $instance = null;

        /** @var Components */
        public $components;

        /** @var Popups */
        public $popups;

        public function __construct() {
            $this->components = new Components();
            $this->popups = new Popups();
        }

        public static function plugin(): FooConvert {
            if ( self::$instance === null ) {
                self::$instance = new FooConvert();
            }

            return self::$instance;
        }
    }
}

namespace {
    use FooPlugins\FooConvert\Pro\Blocks\FreeShippingProgress;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    class WP_Block {
        /** @var array<string,mixed> */
        public $context = array();
    }

    function __( string $text, string $domain = '' ): string {
        return $text;
    }

    function get_option( string $key, $default = null ) {
        $options = $GLOBALS['fc_test_options'] ?? array(
            'woocommerce_currency'     => 'USD',
            'woocommerce_currency_pos' => 'left',
        );

        return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
    }

    function get_woocommerce_currency_symbol( string $currency ): string {
        if ( $currency === 'USD' ) {
            return '$';
        }
        if ( $currency === 'GBP' ) {
            return '&pound;';
        }

        return $currency;
    }

    function wc_get_price_decimals(): int {
        return 2;
    }

    function wc_get_price_decimal_separator(): string {
        return '.';
    }

    function wc_get_price_thousand_separator(): string {
        return ',';
    }

    function absint( $value ): int {
        return abs( (int) $value );
    }

    function esc_attr( string $value ): string {
        return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
    }

    function do_blocks( string $content ): string {
        return $content;
    }

    if ( !defined( 'FOOCONVERT_CPT_POPUP' ) ) {
        define( 'FOOCONVERT_CPT_POPUP', 'fc-popup' );
    }
    if ( !defined( 'FOOCONVERT_PRO_ASSETS_PATH' ) ) {
        define( 'FOOCONVERT_PRO_ASSETS_PATH', 'pro/assets/' );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Blocks/FreeShippingProgress.php';

    $GLOBALS['fc_test_options'] = array(
        'woocommerce_currency'     => 'USD',
        'woocommerce_currency_pos' => 'left',
    );

    $parent_block = new FreeShippingProgress();
    Assertions::same(
        'fc/free-shipping-progress',
        $parent_block->get_block_name(),
        'FreeShippingProgress should register the expected block name.'
    );

    Assertions::same(
        'fc-free-shipping-progress',
        $parent_block->get_tag_name(),
        'FreeShippingProgress should render the expected custom element tag.'
    );

    $registered_blocks = $parent_block->register_blocks();

    Assertions::same(
        3,
        count( $registered_blocks ),
        'FreeShippingProgress should register the parent block and all internal child blocks.'
    );

    Assertions::same(
        FOOCONVERT_PRO_ASSETS_PATH . 'blocks/free-shipping-progress/editor/blocks/content/block.json',
        $registered_blocks[1]['file_or_folder'],
        'FreeShippingProgress should register the nested content block from within the parent block directory.'
    );

    Assertions::same(
        FOOCONVERT_PRO_ASSETS_PATH . 'blocks/free-shipping-progress/editor/blocks/bar/block.json',
        $registered_blocks[2]['file_or_folder'],
        'FreeShippingProgress should register the nested shared bar block from within the parent block directory.'
    );

    $editor_data = $parent_block->get_editor_data();

    Assertions::same(
        '$',
        $editor_data['currencyDefaults']['symbol'],
        'FreeShippingProgress should expose server-side currency defaults to the editor.'
    );

    $GLOBALS['fc_test_options'] = array(
        'woocommerce_currency'     => 'GBP',
        'woocommerce_currency_pos' => 'left',
    );

    $gbp_editor_data = $parent_block->get_editor_data();

    Assertions::same(
        '£',
        $gbp_editor_data['currencyDefaults']['symbol'],
        'FreeShippingProgress should decode HTML currency entities before exposing currency defaults.'
    );

    Assertions::same(
        '£',
        $gbp_editor_data['currencyDefaults']['prefix'],
        'FreeShippingProgress should decode HTML currency entities before building currency prefixes.'
    );

    $GLOBALS['fc_test_options'] = array(
        'woocommerce_currency'     => 'USD',
        'woocommerce_currency_pos' => 'left',
    );

    $configured_attributes = array(
        'settings' => array(
            'thresholdAmount'    => '49.80',
            'almostTherePercent' => 85,
            'roundTotals'        => false,
            'showBarInLocked'    => false,
        ),
        'bar' => array(
            'settings' => array(
                'showPercent' => true,
            ),
        ),
    );

    $frontend_data = $parent_block->get_frontend_data( 'fc-free-shipping-progress-test', $configured_attributes, new WP_Block() );

    Assertions::same(
        '49.80',
        $frontend_data['thresholdAmount'],
        'FreeShippingProgress should expose the configured threshold amount to the frontend.'
    );

    Assertions::same(
        85,
        $frontend_data['almostTherePercent'],
        'FreeShippingProgress should expose the configured almost-there percentage to the frontend.'
    );

    Assertions::false(
        $frontend_data['roundTotals'],
        'FreeShippingProgress should expose the configured round-totals toggle to the frontend.'
    );

    Assertions::false(
        $frontend_data['showBarInLocked'],
        'FreeShippingProgress should expose per-state shared bar visibility settings to the frontend.'
    );

    Assertions::true(
        $frontend_data['showPercent'],
        'FreeShippingProgress should expose the shared bar show-percent setting to the frontend.'
    );

    Assertions::same(
        'unavailable',
        $parent_block->get_frontend_attributes( 'fc-free-shipping-progress-test', array(), new WP_Block() )['data-active-state'],
        'FreeShippingProgress should default to the unavailable state before frontend runtime initializes.'
    );

    $content_render = $parent_block->render_content_slot(
        array(
            'state' => 'locked',
        ),
        '<p>You are {remaining} away.</p>',
        new WP_Block()
    );

    Assertions::true(
        strpos( $content_render, 'slot="locked"' ) !== false,
        'FreeShippingProgress should render state content blocks into named slots.'
    );

    Assertions::true(
        strpos( $content_render, '<p>You are {remaining} away.</p>' ) !== false,
        'FreeShippingProgress should preserve authored inner block content when rendering state slots.'
    );

    Assertions::same(
        '',
        $parent_block->render_empty(),
        'FreeShippingProgress should use the shared empty renderer for the editor-only bar child block on the frontend.'
    );

    $frontend_styles = $parent_block->get_frontend_styles(
        'fc-free-shipping-progress-test',
        array(
            'styles' => array(
                'display' => 'grid',
            ),
            'bar' => array(
                'styles' => array(
                    'background-color' => '#111827',
                ),
                'track' => array(
                    'styles' => array(
                        'background-color' => '#e5e7eb',
                    ),
                ),
                'fill' => array(
                    'styles' => array(
                        'background-color' => '#111827',
                    ),
                ),
                'percent' => array(
                    'styles' => array(
                        'color' => '#ffffff',
                    ),
                ),
            ),
        ),
        new WP_Block()
    );

    Assertions::true(
        isset( $frontend_styles['#fc-free-shipping-progress-test::part(container)'] ),
        'FreeShippingProgress should emit root container styles against the container shadow part.'
    );

    Assertions::true(
        isset( $frontend_styles['#fc-free-shipping-progress-test::part(bar-track)'] ),
        'FreeShippingProgress should emit shared bar track styles against the matching shadow part.'
    );

    Assertions::true(
        isset( $frontend_styles['#fc-free-shipping-progress-test::part(bar-percent)'] ),
        'FreeShippingProgress should emit shared bar percent styles against the matching shadow part.'
    );
}
