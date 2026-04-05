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

        public static function register_post_type_blocks( $post_type, array $blocks ) {
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

    class Widgets {
        public function get_post_types(): array {
            return array( 'fc-popup', 'fc-bar', 'fc-flyout' );
        }
    }

    class FooConvert {
        /** @var ?FooConvert */
        private static $instance = null;

        /** @var Components */
        public $components;

        /** @var Widgets */
        public $widgets;

        public function __construct() {
            $this->components = new Components();
            $this->widgets = new Widgets();
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
        $options = array(
            'woocommerce_currency'     => 'USD',
            'woocommerce_currency_pos' => 'left',
        );

        return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
    }

    function get_woocommerce_currency_symbol( string $currency ): string {
        return $currency === 'USD' ? '$' : $currency;
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

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Blocks/FreeShippingProgress.php';

    $block = new FreeShippingProgress();

    Assertions::same(
        'fc/free-shipping-progress',
        $block->get_block_name(),
        'FreeShippingProgress should register the expected block name.'
    );

    Assertions::same(
        'fc-free-shipping-progress',
        $block->get_tag_name(),
        'FreeShippingProgress should render the expected custom element tag.'
    );

    $editor_data = $block->get_editor_data();

    Assertions::same(
        'Free shipping at {threshold}',
        $editor_data['thresholdLabelTemplate'],
        'FreeShippingProgress should expose the default threshold label template to the editor.'
    );

    Assertions::same(
        '$',
        $editor_data['currencyDefaults']['symbol'],
        'FreeShippingProgress should expose server-side currency defaults to the editor.'
    );

    $configured_attributes = array(
        'settings' => array(
            'thresholdAmount'    => '50',
            'almostTherePercent' => 85,
            'showProgressBar'    => false,
            'showThresholdLabel' => true,
            'lockedMessage'      => 'Locked at {remaining}',
            'almostMessage'      => 'Almost at {remaining}',
            'unlockedMessage'    => 'Unlocked',
            'unavailableMessage' => 'Spend {threshold} to unlock free shipping.',
        ),
    );

    $frontend_data = $block->get_frontend_data( 'fc-free-shipping-progress-test', $configured_attributes, new WP_Block() );

    Assertions::same(
        '50',
        $frontend_data['thresholdAmount'],
        'FreeShippingProgress should expose the configured threshold amount to the frontend.'
    );

    Assertions::same(
        85,
        $frontend_data['almostTherePercent'],
        'FreeShippingProgress should expose the configured almost-there percentage to the frontend.'
    );

    Assertions::false(
        $frontend_data['showProgressBar'],
        'FreeShippingProgress should expose the configured progress-bar toggle to the frontend.'
    );

    $configured_render = $block->render( $configured_attributes, '', new WP_Block() );

    Assertions::true(
        strpos( $configured_render, 'Spend $50.00 to unlock free shipping.' ) !== false,
        'FreeShippingProgress should render the unavailable fallback copy using the configured threshold token.'
    );

    Assertions::true(
        strpos( $configured_render, 'Free shipping at $50.00' ) !== false,
        'FreeShippingProgress should render the threshold label when it is enabled and a threshold exists.'
    );

    $missing_threshold_render = $block->render(
        array(
            'settings' => array(
                'unavailableMessage' => 'Free shipping is unavailable right now.',
                'showThresholdLabel' => true,
            ),
        ),
        '',
        new WP_Block()
    );

    Assertions::same(
        '<span slot="message__text">Free shipping is unavailable right now.</span>',
        $missing_threshold_render,
        'FreeShippingProgress should omit the threshold label when no valid threshold is configured.'
    );

    echo "free-shipping-progress-block: ok\n";
}
