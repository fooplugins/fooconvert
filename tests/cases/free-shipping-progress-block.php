<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Blocks\Base {
    use WP_Block;

    abstract class BaseBlock {
        public function render( array $attributes, string $content, WP_Block $block ) {
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
    use FooPlugins\FooConvert\Pro\Blocks\FreeShippingBar;
    use FooPlugins\FooConvert\Pro\Blocks\FreeShippingProgress;
    use FooPlugins\FooConvert\Pro\Blocks\FreeShippingText;
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

    if ( !defined( 'FOOCONVERT_CPT_POPUP' ) ) {
        define( 'FOOCONVERT_CPT_POPUP', 'fc-popup' );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Blocks/FreeShippingProgress.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Blocks/FreeShippingText.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Blocks/FreeShippingBar.php';

    $GLOBALS['fc_test_options'] = array(
        'woocommerce_currency'     => 'USD',
        'woocommerce_currency_pos' => 'left',
    );

    $parent_block = new FreeShippingProgress();
    $text_block = new FreeShippingText();
    $bar_block = new FreeShippingBar();

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

    Assertions::same(
        'fc/free-shipping-text',
        $text_block->get_block_name(),
        'FreeShippingText should register the expected block name.'
    );

    Assertions::same(
        'fc/free-shipping-bar',
        $bar_block->get_block_name(),
        'FreeShippingBar should register the expected block name.'
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

    Assertions::same(
        'unavailable',
        $parent_block->get_frontend_attributes( 'fc-free-shipping-progress-test', array(), new WP_Block() )['data-active-state'],
        'FreeShippingProgress should default to the unavailable state before frontend runtime initializes.'
    );

    $text_attributes = array(
        'content' => 'Spend <strong>{threshold}</strong> now. <a href="{threshold}">{subtotal}</a> {remaining} {progress_percent}',
        'settings' => array(
            'tagName' => 'h3',
        ),
    );
    $text_context_block = new WP_Block();
    $text_context_block->context['fc/free-shipping-progress/settings'] = array(
        'thresholdAmount' => '49.80',
    );

    $text_render = $text_block->render( $text_attributes, '', $text_context_block );

    Assertions::true(
        strpos( $text_render, '<h3 class="fc--free-shipping-text__content">Spend <strong>$50</strong> now.' ) !== false,
        'FreeShippingText should wrap resolved content in the configured semantic tag.'
    );

    Assertions::true(
        strpos( $text_render, '<a href="{threshold}"></a>' ) !== false,
        'FreeShippingText should replace tokens inside text nodes only and preserve HTML attributes.'
    );

    Assertions::true(
        strpos( $text_render, '{remaining}' ) === false && strpos( $text_render, '{progress_percent}' ) === false,
        'FreeShippingText should collapse subtotal-dependent tokens when no live cart subtotal is available.'
    );

    $unrounded_context_block = new WP_Block();
    $unrounded_context_block->context['fc/free-shipping-progress/settings'] = array(
        'thresholdAmount' => '49.80',
        'roundTotals'     => false,
    );

    $unrounded_render = $text_block->render(
        array(
            'content' => 'Free shipping at {threshold}',
            'settings' => array(
                'tagName' => 'p',
            ),
        ),
        '',
        $unrounded_context_block
    );

    Assertions::true(
        strpos( $unrounded_render, '<p class="fc--free-shipping-text__content">Free shipping at $49.80</p>' ) !== false,
        'FreeShippingText should preserve decimal amounts when round totals is disabled.'
    );

    $text_frontend_data = $text_block->get_frontend_data(
        'fc-free-shipping-text-test',
        $text_attributes,
        $text_context_block
    );

    Assertions::same(
        $text_attributes['content'],
        $text_frontend_data['content'],
        'FreeShippingText should expose the raw token template to the frontend.'
    );

    Assertions::same(
        'h3',
        $text_frontend_data['tagName'],
        'FreeShippingText should expose the configured semantic tag to the frontend.'
    );

    $bar_frontend_data = $bar_block->get_frontend_data(
        'fc-free-shipping-bar-test',
        array(
            'settings' => array(
                'showPercent' => true,
            ),
        ),
        new WP_Block()
    );

    Assertions::true(
        $bar_frontend_data['showPercent'],
        'FreeShippingBar should expose the show-percent toggle to the frontend.'
    );

    $bar_styles = $bar_block->get_frontend_styles(
        'fc-free-shipping-bar-test',
        array(
            'styles' => array(
                'color' => '#111111',
            ),
            'track'  => array(
                'styles' => array(
                    'background-color' => '#eeeeee',
                ),
            ),
            'fill'   => array(
                'styles' => array(
                    'background-color' => '#111111',
                ),
            ),
        ),
        new WP_Block()
    );

    Assertions::same(
        '#eeeeee',
        $bar_styles['#fc-free-shipping-bar-test .fc--free-shipping-bar__track']['background-color'],
        'FreeShippingBar should target track styles with the expected selector.'
    );

    Assertions::same(
        '#111111',
        $bar_styles['#fc-free-shipping-bar-test .fc--free-shipping-bar__fill']['background-color'],
        'FreeShippingBar should target fill styles with the expected selector.'
    );

    $bar_render = $bar_block->render( array(), '', new WP_Block() );

    Assertions::true(
        strpos( $bar_render, 'fc--free-shipping-bar__track' ) !== false,
        'FreeShippingBar should render the expected fallback track markup.'
    );

    Assertions::true(
        strpos( $bar_render, 'fc--free-shipping-bar__percent' ) !== false,
        'FreeShippingBar should render the percent span for runtime updates.'
    );
}
