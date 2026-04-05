<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert {
    class Utils {
        /**
         * @param mixed $value
         * @param string $key
         * @return string
         */
        public static function get_string( $value, string $key ): string {
            return is_array( $value ) && isset( $value[ $key ] ) ? (string) $value[ $key ] : '';
        }
    }
}

namespace {
    use FooPlugins\FooConvert\Pro\WooCommerce\CouponSearch;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    class WP_REST_Request {
        /** @var array<string,mixed> */
        private $params;

        /**
         * @param array<string,mixed> $params
         */
        public function __construct( array $params = array() ) {
            $this->params = $params;
        }

        /**
         * @return array<string,mixed>
         */
        public function get_params(): array {
            return $this->params;
        }
    }

    class WP_REST_Response {
        /** @var mixed */
        private $data;

        /**
         * @param mixed $data
         */
        public function __construct( $data ) {
            $this->data = $data;
        }

        /**
         * @return mixed
         */
        public function get_data() {
            return $this->data;
        }
    }

    class DummyWpdb {
        /** @var string */
        public $posts = 'wp_posts';

        /** @var string */
        public $last_prepare = '';

        /**
         * @param string $text
         * @return string
         */
        public function esc_like( string $text ): string {
            return addslashes( $text );
        }

        /**
         * @param string $query
         * @param mixed ...$args
         * @return string
         */
        public function prepare( string $query, ...$args ): string {
            $this->last_prepare = vsprintf(
                str_replace( array( '%s', '%d' ), array( "'%s'", '%d' ), $query ),
                $args
            );
            return $this->last_prepare;
        }

        /**
         * @param string $query
         * @return array<int,object>
         */
        public function get_results( string $query ): array {
            return array(
                (object) array( 'ID' => 11, 'post_title' => 'SAVE10' ),
                (object) array( 'ID' => 17, 'post_title' => 'SPRING15' ),
            );
        }
    }

    /** @var array<string,array<int,array{callback:mixed,priority:int,args:int}>> */
    $GLOBALS['fc_test_actions'] = array();
    /** @var array<string,mixed> */
    $GLOBALS['fc_test_routes'] = array();
    /** @var bool */
    $GLOBALS['fc_test_woo_active'] = true;
    /** @var bool */
    $GLOBALS['fc_test_can_edit_coupons'] = true;
    /** @var DummyWpdb */
    $GLOBALS['wpdb'] = new DummyWpdb();

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
     * @param string $capability Capability.
     * @return bool
     */
    function current_user_can( string $capability ): bool {
        return $capability === 'edit_shop_coupons' && $GLOBALS['fc_test_can_edit_coupons'];
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
     * @param string $text
     * @return string
     */
    function sanitize_text_field( string $text ): string {
        return trim( $text );
    }

    /**
     * @param int|string $value
     * @return int
     */
    function absint( $value ): int {
        return abs( intval( $value ) );
    }

    /**
     * @param mixed $response
     * @return WP_REST_Response
     */
    function rest_ensure_response( $response ): WP_REST_Response {
        return new WP_REST_Response( $response );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/WooCommerce/CouponSearch.php';

    $service = new CouponSearch();

    $callbacks = $GLOBALS['fc_test_actions']['rest_api_init'] ?? array();
    foreach ( $callbacks as $entry ) {
        call_user_func( $entry['callback'] );
    }

    Assertions::true(
        isset( $GLOBALS['fc_test_routes']['fooconvert/v1/woocommerce/coupons'] ),
        'CouponSearch should register the WooCommerce coupon search REST route.'
    );

    Assertions::true(
        $service->can_search_coupons(),
        'CouponSearch should allow access when WooCommerce is active and the user can edit coupons.'
    );

    $GLOBALS['fc_test_can_edit_coupons'] = false;

    Assertions::false(
        $service->can_search_coupons(),
        'CouponSearch should reject users who cannot edit coupons.'
    );

    $GLOBALS['fc_test_can_edit_coupons'] = true;
    $response = $service->search_coupons( new WP_REST_Request( array( 'search' => 'SAVE' ) ) );

    Assertions::same(
        array(
            array(
                'id' => 11,
                'code' => 'SAVE10',
                'label' => 'SAVE10',
            ),
            array(
                'id' => 17,
                'code' => 'SPRING15',
                'label' => 'SPRING15',
            ),
        ),
        $response->get_data(),
        'CouponSearch should return normalized coupon search results.'
    );

    Assertions::true(
        strpos( $GLOBALS['wpdb']->last_prepare, "post_title LIKE '%SAVE%'" ) !== false,
        'CouponSearch should search published coupons by matching their code.'
    );

    $GLOBALS['fc_test_woo_active'] = false;
    $empty = $service->search_coupons( new WP_REST_Request( array( 'search' => 'SAVE' ) ) );

    Assertions::same(
        array(),
        $empty->get_data(),
        'CouponSearch should return an empty result set when WooCommerce is inactive.'
    );

    echo "woocommerce-coupon-search: ok\n";
}
