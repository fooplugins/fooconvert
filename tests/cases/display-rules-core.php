<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Components\Base {
    /**
     * Minimal stub for the component base class used by DisplayRules.
     */
    class BaseComponent {
        /**
         * Stub constructor for component compatibility in tests.
         */
        public function __construct() {}
    }
}

namespace FooPlugins\FooConvert {
    /**
     * Minimal utility stub providing the helpers used by the display-rules tests.
     */
    class Utils {
        /**
         * @param array $items
         * @param callable $callback
         * @param mixed $default
         * @return mixed
         */
        public static function array_find( array $items, callable $callback, $default = false ) {
            foreach ( $items as $item ) {
                if ( $callback( $item ) ) {
                    return $item;
                }
            }

            return $default;
        }

        /**
         * @param mixed $value
         * @param string $key
         * @return string
         */
        public static function get_string( $value, string $key ): string {
            return is_array( $value ) && isset( $value[ $key ] ) ? (string) $value[ $key ] : '';
        }

        /**
         * @param mixed $value
         * @param string $key
         * @return array
         */
        public static function get_array( $value, string $key ): array {
            return is_array( $value ) && isset( $value[ $key ] ) && is_array( $value[ $key ] ) ? $value[ $key ] : array();
        }

        /**
         * @param mixed $value
         * @param string $key
         * @return int
         */
        public static function get_int( $value, string $key ): int {
            return is_array( $value ) && isset( $value[ $key ] ) ? intval( $value[ $key ] ) : 0;
        }
    }
}

namespace {
    use FooPlugins\FooConvert\DisplayRules;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    /**
     * Stub the WordPress hooks API used by the display-rules component constructor.
     *
     * @return void
     */
    function add_action( ...$args ): void {}

    /**
     * Stub the filter API so the custom matcher path can be exercised deterministically.
     *
     * @param string $tag The filter tag.
     * @param mixed $value The initial value.
     * @param mixed ...$args Additional filter arguments.
     * @return mixed
     */
    function apply_filters( string $tag, $value, ...$args ) {
        if ( $tag === 'fooconvert_display_rules_match_locations' ) {
            $compiled_locations = $args[0] ?? array();
            return !empty( $GLOBALS['fc_test_custom_location_match'] ) && array_key_exists( 'woocommerce:is_product', $compiled_locations );
        }

        return $value;
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/DisplayRules.php';

    $display_rules = new DisplayRules();
    $reflection = new \ReflectionClass( $display_rules );
    $compile_locations = $reflection->getMethod( 'compile_locations' );
    $compile_locations->setAccessible( true );

    $compiled = $compile_locations->invoke(
        $display_rules,
        array(
            array(
                'type' => 'general:front_page',
                'data' => array(),
            ),
            array(
                'type' => 'woocommerce:is_product',
                'data' => array(),
            ),
            array(
                'type' => 'specific:product',
                'data' => array(
                    array( 'id' => 99 ),
                    array( 'id' => 105 ),
                    array( 'id' => 0 ),
                ),
            ),
        )
    );

    Assertions::same(
        array(
            'general:front_page' => true,
            'woocommerce:is_product' => true,
            'specific:product' => array( 99, 105 ),
        ),
        $compiled,
        'compile_locations() should store static non-specific rules and flatten specific IDs.'
    );

    Assertions::true(
        $display_rules->match_compiled_locations(
            array( 'specific:product' => array( 105 ) ),
            array( 'type' => 'specific:product', 'data' => 105 )
        ),
        'match_compiled_locations() should preserve exact matching for specific rules.'
    );

    $GLOBALS['fc_test_custom_location_match'] = false;

    Assertions::false(
        $display_rules->match_compiled_locations(
            array( 'woocommerce:is_product' => true ),
            array( 'type' => 'general:front_page', 'data' => null )
        ),
        'Custom rules should not match when the extension filter returns false.'
    );

    $GLOBALS['fc_test_custom_location_match'] = true;

    Assertions::true(
        $display_rules->match_compiled_locations(
            array( 'woocommerce:is_product' => true ),
            array( 'type' => 'general:front_page', 'data' => null )
        ),
        'Custom rules should match when the extension filter returns true.'
    );

    echo "display-rules-core: ok\n";
}
