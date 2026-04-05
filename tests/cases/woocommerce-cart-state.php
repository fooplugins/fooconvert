<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Pro\WooCommerce\CartState;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    class DummyProduct {
        /** @var string */
        private $name;

        public function __construct( string $name ) {
            $this->name = $name;
        }

        public function get_name(): string {
            return $this->name;
        }
    }

    class DummyCart {
        /** @var int */
        public $calculate_totals_calls = 0;

        /** @return array<string,array<string,mixed>> */
        public function get_cart(): array {
            return array(
                'abc123' => array(
                    'product_id'   => 44,
                    'quantity'     => 2,
                    'variation_id' => 9,
                    'data'         => new DummyProduct( 'Beanie' ),
                ),
            );
        }

        /** @return bool */
        public function has_calculated_shipping(): bool {
            return true;
        }

        /** @return float */
        public function get_subtotal(): float {
            return 50.00;
        }

        /** @return float */
        public function get_subtotal_tax(): float {
            return 10.00;
        }

        /** @return float */
        public function get_fee_total(): float {
            return 1.50;
        }

        /** @return float */
        public function get_fee_tax(): float {
            return 0.30;
        }

        /** @return float */
        public function get_discount_total(): float {
            return 5.00;
        }

        /** @return float */
        public function get_discount_tax(): float {
            return 1.00;
        }

        /** @return float */
        public function get_shipping_total(): float {
            return 4.00;
        }

        /** @return float */
        public function get_shipping_tax(): float {
            return 0.80;
        }

        /**
         * @param string $context
         * @return float
         */
        public function get_total( string $context = '' ): float {
            return $context === 'edit' ? 61.60 : 0.0;
        }

        /** @return float */
        public function get_total_tax(): float {
            return 11.10;
        }

        /** @return int */
        public function get_cart_contents_count(): int {
            return 2;
        }

        /** @return array<int,string> */
        public function get_applied_coupons(): array {
            return array( 'save10' );
        }

        /** @return string */
        public function get_cart_hash(): string {
            return 'cart-hash-123';
        }

        /** @return void */
        public function calculate_totals(): void {
            $this->calculate_totals_calls++;
            $GLOBALS['fc_test_did_actions']['woocommerce_after_calculate_totals'] = 1;
        }
    }

    class DummyWooCommerce {
        /** @var DummyCart */
        public $cart;

        public function __construct() {
            $this->cart = new DummyCart();
        }
    }

    class WP_REST_Response {
        /** @var mixed */
        private $data;

        /** @var array<string,string> */
        private $headers = array();

        /**
         * @param mixed $data
         */
        public function __construct( $data ) {
            $this->data = $data;
        }

        /**
         * @param string $name
         * @param string $value
         * @return void
         */
        public function header( string $name, string $value ): void {
            $this->headers[ $name ] = $value;
        }

        /**
         * @return mixed
         */
        public function get_data() {
            return $this->data;
        }

        /**
         * @return array<string,string>
         */
        public function get_headers(): array {
            return $this->headers;
        }
    }

    /** @var array<string,array<int,array{callback:mixed,priority:int,args:int}>> */
    $GLOBALS['fc_test_actions'] = array();
    /** @var array<string,mixed> */
    $GLOBALS['fc_test_routes'] = array();
    /** @var bool */
    $GLOBALS['fc_test_woo_active'] = true;
    /** @var int */
    $GLOBALS['fc_test_wc_load_cart_calls'] = 0;
    /** @var array<string,int> */
    $GLOBALS['fc_test_did_actions'] = array();
    /** @var DummyWooCommerce */
    $GLOBALS['fc_test_woocommerce'] = new DummyWooCommerce();

    /**
     * @param string $hook Hook name.
     * @param mixed $callback Callback.
     * @param int $priority Priority.
     * @param int $accepted_args Accepted args.
     * @return void
     */
    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_test_actions'][ $hook ][] = array(
            'callback' => $callback,
            'priority' => $priority,
            'args' => $accepted_args,
        );
    }

    /**
     * @return bool
     */
    function fooconvert_is_woocommerce_active(): bool {
        return $GLOBALS['fc_test_woo_active'];
    }

    /**
     * @return DummyWooCommerce
     */
    function WC(): DummyWooCommerce {
        return $GLOBALS['fc_test_woocommerce'];
    }

    /**
     * @return void
     */
    function wc_load_cart(): void {
        $GLOBALS['fc_test_wc_load_cart_calls']++;
    }

    /**
     * @param string $hook
     * @return int
     */
    function did_action( string $hook ): int {
        return $GLOBALS['fc_test_did_actions'][ $hook ] ?? 0;
    }

    /**
     * @param string $namespace REST namespace.
     * @param string $route REST route.
     * @param array<string,mixed> $args Route args.
     * @return void
     */
    function register_rest_route( string $namespace, string $route, array $args ): void {
        $GLOBALS['fc_test_routes'][ $namespace . $route ] = $args;
    }

    /**
     * @return bool
     */
    function __return_true(): bool {
        return true;
    }

    /**
     * @param mixed $response
     * @return WP_REST_Response
     */
    function rest_ensure_response( $response ): WP_REST_Response {
        return new WP_REST_Response( $response );
    }

    /**
     * @param int|string $value
     * @return int
     */
    function absint( $value ): int {
        return abs( intval( $value ) );
    }

    /**
     * @param string $value
     * @return string
     */
    function sanitize_text_field( string $value ): string {
        return trim( $value );
    }

    /**
     * @param string $name
     * @return string
     */
    function get_option( string $name ): string {
        return $name === 'woocommerce_currency_pos' ? 'left_space' : '';
    }

    /**
     * @return string
     */
    function get_woocommerce_currency_symbol(): string {
        return '$';
    }

    /**
     * @return string
     */
    function get_woocommerce_currency(): string {
        return 'USD';
    }

    /**
     * @return int
     */
    function wc_get_price_decimals(): int {
        return 2;
    }

    /**
     * @return string
     */
    function wc_get_price_decimal_separator(): string {
        return '.';
    }

    /**
     * @return string
     */
    function wc_get_price_thousand_separator(): string {
        return ',';
    }

    /**
     * @param float $value
     * @param int $decimals
     * @param bool $trim_zeros
     * @return string
     */
    function wc_format_decimal( float $value, int $decimals = 0, bool $trim_zeros = false ): string {
        return number_format( $value, $decimals, '.', '' );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/WooCommerce/CartState.php';

    $service = new CartState();

    $callbacks = $GLOBALS['fc_test_actions']['rest_api_init'] ?? array();
    foreach ( $callbacks as $entry ) {
        call_user_func( $entry['callback'] );
    }

    Assertions::true(
        isset( $GLOBALS['fc_test_routes']['fooconvert/v1/woocommerce/cart'] ),
        'CartState should register the WooCommerce cart REST route.'
    );

    $response = $service->get_cart_state();
    $data = $response->get_data();

    Assertions::same(
        1,
        $GLOBALS['fc_test_wc_load_cart_calls'],
        'CartState should load the WooCommerce cart before reading the session snapshot.'
    );

    Assertions::same(
        'no-store, private',
        $response->get_headers()['Cache-Control'] ?? '',
        'CartState should disable caching for cart-state responses.'
    );

    Assertions::same(
        'cart-hash-123',
        $response->get_headers()['Cart-Hash'] ?? '',
        'CartState should expose the Woo cart hash header for cache-aware clients.'
    );

    Assertions::same(
        array(
            'key' => 'abc123',
            'id' => 44,
            'quantity' => 2,
            'variationId' => 9,
            'name' => 'Beanie',
        ),
        $data['items'][0],
        'CartState should normalize cart items into the frontend cart snapshot shape.'
    );

    Assertions::same(
        5000,
        $data['totals']['subtotalMinor'],
        'CartState should return subtotal amounts in minor units.'
    );

    Assertions::same(
        '$ ',
        $data['totals']['currency']['prefix'],
        'CartState should expose Store API-style currency prefix metadata.'
    );

    Assertions::same(
        '61.60',
        $data['totals']['totalDisplay'],
        'CartState should provide decimal display amounts alongside minor-unit totals.'
    );

    Assertions::same(
        'rest-api',
        $data['meta']['source'],
        'CartState should identify REST-derived cart snapshots.'
    );

    $GLOBALS['fc_test_woo_active'] = false;
    $inactive = $service->get_cart_state();

    Assertions::same(
        null,
        $inactive->get_data(),
        'CartState should return null when WooCommerce is inactive.'
    );

    echo "woocommerce-cart-state: ok\n";
}
