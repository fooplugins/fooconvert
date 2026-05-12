<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert {
    class Event {
        /** @var array<int,array{data:array<string,mixed>,meta:array<string,mixed>}> */
        public static $created = array();

        /**
         * @return void
         */
        public static function reset(): void {
            self::$created = array();
        }

        /**
         * @param array<string,mixed> $data
         * @param array<string,mixed> $meta
         * @return int
         */
        public function create( $data, $meta = array() ) {
            self::$created[] = array(
                'data' => $data,
                'meta' => $meta,
            );

            return count( self::$created );
        }
    }
}

namespace {
    use FooPlugins\FooConvert\Admin\DemoContent;
    use FooPlugins\FooConvert\Event;
    use FooPlugins\FooConvert\Pro\Analytics\DemoContent as ProDemoContent;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    class WooCommerce {}

    class WC_Product {
        /** @var int */
        private $id;

        /** @var string */
        private $type;

        /** @var string */
        private $name;

        /** @var float */
        private $price;

        /**
         * @param int $id Product ID.
         * @param string $type Product type.
         * @param string $name Product name.
         * @param float $price Product price.
         */
        public function __construct( int $id, string $type, string $name, float $price ) {
            $this->id = $id;
            $this->type = $type;
            $this->name = $name;
            $this->price = $price;
        }

        /**
         * @return int
         */
        public function get_id(): int {
            return $this->id;
        }

        /**
         * @return string
         */
        public function get_type(): string {
            return $this->type;
        }

        /**
         * @param string $type Product type.
         * @return bool
         */
        public function is_type( string $type ): bool {
            return $this->type === $type;
        }

        /**
         * @return string
         */
        public function get_name(): string {
            return $this->name;
        }

        /**
         * @return string
         */
        public function get_price(): string {
            return (string) $this->price;
        }

        /**
         * @return string
         */
        public function get_permalink(): string {
            return 'https://example.test/product/' . $this->id;
        }
    }

    class FC_Test_Order_Item {
        /** @var WC_Product */
        private $product;

        /**
         * @param WC_Product $product Product.
         */
        public function __construct( WC_Product $product ) {
            $this->product = $product;
        }

        /**
         * @return WC_Product
         */
        public function get_product(): WC_Product {
            return $this->product;
        }

        /**
         * @return int
         */
        public function get_product_id(): int {
            return $this->product->get_id();
        }
    }

    class WC_Order {
        /** @var int */
        private $id;

        /** @var string */
        private $number;

        /** @var string */
        private $status;

        /** @var string */
        private $currency;

        /** @var array<int,FC_Test_Order_Item> */
        private $items;

        /**
         * @param int $id Order ID.
         * @param string $number Order number.
         * @param string $status Order status.
         * @param string $currency Currency code.
         * @param array<int,FC_Test_Order_Item> $items Order line items.
         */
        public function __construct( int $id, string $number, string $status, string $currency, array $items ) {
            $this->id = $id;
            $this->number = $number;
            $this->status = $status;
            $this->currency = $currency;
            $this->items = $items;
        }

        /**
         * @return int
         */
        public function get_id(): int {
            return $this->id;
        }

        /**
         * @return string
         */
        public function get_order_number(): string {
            return $this->number;
        }

        /**
         * @return string
         */
        public function get_status(): string {
            return $this->status;
        }

        /**
         * @return string
         */
        public function get_currency(): string {
            return $this->currency;
        }

        /**
         * @param string $type Item type.
         * @return array<int,FC_Test_Order_Item>
         */
        public function get_items( string $type = 'line_item' ): array {
            return $this->items;
        }
    }

    /** @var array<string,array<int,array{callback:mixed,accepted_args:int}>> */
    $GLOBALS['fc_test_actions'] = array();

    /** @var array<int,int> */
    $GLOBALS['fc_test_wp_rand_values'] = array();

    /** @var array<int,WC_Order> */
    $GLOBALS['fc_test_wc_orders'] = array();

    /** @var array<int,WC_Product> */
    $GLOBALS['fc_test_wc_products'] = array();

    /**
     * @param string $text
     * @param string|null $domain
     * @return string
     */
    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @param string $hook
     * @param mixed $callback
     * @param int $priority
     * @param int $accepted_args
     * @return void
     */
    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_test_actions'][ $hook ][] = array(
            'callback'      => $callback,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * @param string $hook
     * @param mixed ...$args
     * @return void
     */
    function do_action( string $hook, ...$args ): void {
        if ( empty( $GLOBALS['fc_test_actions'][ $hook ] ) ) {
            return;
        }

        foreach ( $GLOBALS['fc_test_actions'][ $hook ] as $action ) {
            $accepted_args = max( 0, (int) $action['accepted_args'] );
            call_user_func_array(
                $action['callback'],
                array_slice( $args, 0, $accepted_args )
            );
        }
    }

    /**
     * @param int|null $min
     * @param int|null $max
     * @return int
     */
    function wp_rand( $min = null, $max = null ) {
        if ( empty( $GLOBALS['fc_test_wp_rand_values'] ) ) {
            throw new RuntimeException( 'wp_rand queue exhausted' );
        }

        return array_shift( $GLOBALS['fc_test_wp_rand_values'] );
    }

    /**
     * @param string $path
     * @return string
     */
    function home_url( string $path = '' ): string {
        return 'https://example.test' . $path;
    }

    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    function get_option( string $option, $default = false ) {
        return $default;
    }

    /**
     * @param array<string,mixed> $args Query args.
     * @return array<int,WC_Order>
     */
    function wc_get_orders( array $args = array() ): array {
        return $GLOBALS['fc_test_wc_orders'];
    }

    /**
     * @param array<string,mixed> $args Query args.
     * @return array<int,WC_Product>
     */
    function wc_get_products( array $args = array() ): array {
        return $GLOBALS['fc_test_wc_products'];
    }

    /**
     * @param mixed $value Decimal candidate.
     * @return string
     */
    function wc_format_decimal( $value ): string {
        return number_format( (float) $value, 2, '.', '' );
    }

    if ( !defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Admin/DemoContent.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Analytics/DemoContent.php';

    Event::reset();
    new ProDemoContent();

    $max_rand = mt_getrandmax();
    $GLOBALS['fc_test_wp_rand_values'] = array(
        0,
        1,
        10,
        1,
        4,
        8,
        (int) floor( $max_rand * 0.8 ),
        1,
        2,
        20,
        1,
        5,
        6,
        (int) floor( $max_rand * 0.95 ),
        3,
        30,
        1,
        7,
        9,
    );

    $demo = new DemoContent();
    $demo->create_events( 2313, array( 'demo' => true ), 3 );

    $base_events = array_slice( Event::$created, 0, 3 );
    $seed_events = array_values(
        array_filter(
            Event::$created,
            static function( array $entry ): bool {
                return !empty( $entry['data']['extra_data']['pro_top_performer_seed'] );
            }
        )
    );
    $sale_events = array_values(
        array_filter(
            Event::$created,
            static function( array $entry ): bool {
                return $entry['data']['event_type'] === FOOCONVERT_EVENT_TYPE_SALE;
            }
        )
    );

    Assertions::same(
        3,
        count( $base_events ),
        'Expected the demo event generator to create the requested number of base events.'
    );
    Assertions::same(
        array(
            FOOCONVERT_EVENT_TYPE_OPEN,
            FOOCONVERT_EVENT_TYPE_CLICK,
            FOOCONVERT_EVENT_TYPE_CLOSE,
        ),
        array_map(
            static function( array $entry ): string {
                return (string) $entry['data']['event_type'];
            },
            $base_events
        ),
        'Expected the deterministic demo event queue to generate open, click, and close events in order.'
    );

    $click_event = null;
    $close_event = null;
    foreach ( $base_events as $entry ) {
        if ( $entry['data']['event_type'] === FOOCONVERT_EVENT_TYPE_CLICK ) {
            $click_event = $entry;
        } elseif ( $entry['data']['event_type'] === FOOCONVERT_EVENT_TYPE_CLOSE ) {
            $close_event = $entry;
        }
    }

    Assertions::same(
        FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT,
        $click_event['data']['event_subtype'] ?? null,
        'Expected demo click events to count as engagements for top performer metrics.'
    );
    Assertions::same(
        1,
        $click_event['data']['sentiment'] ?? null,
        'Expected demo click events to record positive sentiment.'
    );
    Assertions::same(
        FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT,
        $close_event['data']['event_subtype'] ?? null,
        'Expected demo close events to count as engagements for top performer metrics.'
    );
    Assertions::same(
        0,
        $close_event['data']['sentiment'] ?? null,
        'Expected demo close events to record negative sentiment.'
    );
    Assertions::same(
        1,
        $click_event['data']['conversion'] ?? null,
        'Expected the deterministic click event to create conversion data for conversion-based top performer metrics.'
    );
    Assertions::false(
        isset( $click_event['data']['extra_data']['order_id'] ),
        'Expected base demo conversion events to omit WooCommerce order metadata when real orders/products are unavailable.'
    );

    Assertions::same(
        array(
            'https://example.test/demo-seed-recent-view-1',
            'https://example.test/demo-seed-recent-view-2',
            'https://example.test/demo-seed-recent-click',
            'https://example.test/demo-seed-recent-close',
            'https://example.test/demo-seed-previous-view',
            'https://example.test/demo-seed-previous-close',
        ),
        array_map(
            static function( array $entry ): string {
                return (string) $entry['data']['page_url'];
            },
            $seed_events
        ),
        'Expected PRO demo analytics to add deterministic recent and previous-period events for all top performer sort windows.'
    );

    $seed_click = null;
    foreach ( $seed_events as $entry ) {
        if ( $entry['data']['event_type'] === FOOCONVERT_EVENT_TYPE_CLICK ) {
            $seed_click = $entry;
            break;
        }
    }

    Assertions::same(
        1,
        $seed_click['data']['conversion'] ?? null,
        'Expected the seeded PRO click event to count as a conversion.'
    );
    Assertions::same(
        FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT,
        $seed_click['data']['event_subtype'] ?? null,
        'Expected the seeded PRO click event to count as an engagement.'
    );
    Assertions::same(
        true,
        $seed_click['data']['extra_data']['pro_top_performer_seed'] ?? false,
        'Expected the seeded PRO click event to be marked for top performer demo analytics.'
    );
    Assertions::false(
        isset( $seed_click['data']['extra_data']['order_id'] ),
        'Expected the seeded PRO click event to omit WooCommerce order metadata when real orders/products are unavailable.'
    );

    Assertions::same(
        0,
        count( $sale_events ),
        'Expected PRO demo analytics to skip sale events when real WooCommerce orders/products are unavailable.'
    );

    Event::reset();

    $simple_product_a = new WC_Product( 701, 'simple', 'Simple Mug', 24.5 );
    $simple_product_b = new WC_Product( 702, 'simple', 'Simple Shirt', 39.95 );
    $variable_product = new WC_Product( 703, 'variable', 'Variable Hoodie', 89.0 );

    $GLOBALS['fc_test_wc_products'] = array(
        $variable_product,
        $simple_product_a,
        $simple_product_b,
    );
    $GLOBALS['fc_test_wc_orders'] = array(
        new WC_Order(
            501,
            'FC-501',
            'processing',
            'GBP',
            array(
                new FC_Test_Order_Item( $variable_product ),
                new FC_Test_Order_Item( $simple_product_a ),
            )
        ),
        new WC_Order(
            502,
            'FC-502',
            'completed',
            'GBP',
            array(
                new FC_Test_Order_Item( $simple_product_b ),
            )
        ),
    );

    Event::reset();
    $GLOBALS['fc_test_wp_rand_values'] = array(
        (int) floor( $max_rand * 0.8 ),
        1,
        0,
        0,
        1,
        1,
        1,
    );
    $demo->create_events( 2313, array( 'demo' => true ), 1 );

    $base_real_click = Event::$created[0];
    Assertions::same(
        501,
        $base_real_click['data']['extra_data']['order_id'] ?? null,
        'Expected base demo conversion metadata to point at a real WooCommerce order when available.'
    );
    Assertions::same(
        24.5,
        $base_real_click['data']['extra_data']['order_value'] ?? null,
        'Expected base demo conversion metadata to use a real simple product price.'
    );
    Assertions::same(
        'simple',
        $base_real_click['data']['extra_data']['product_type'] ?? '',
        'Expected base demo conversion metadata to use simple products only.'
    );

    Event::reset();
    $pro_demo = new ProDemoContent();
    $pro_demo->create_sales_events( 2313, array( 'demo' => true ), 500 );

    $real_sale_events = array_values(
        array_filter(
            Event::$created,
            static function( array $entry ): bool {
                return $entry['data']['event_type'] === FOOCONVERT_EVENT_TYPE_SALE;
            }
        )
    );

    Assertions::same(
        2,
        count( $real_sale_events ),
        'Expected PRO demo analytics to add sale events when real orders and simple products are available.'
    );

    Assertions::same(
        array( 24.5, 39.95 ),
        array_map(
            static function( array $entry ): float {
                return $entry['data']['event_value'];
            },
            $real_sale_events
        ),
        'Expected demo sale event values to use real simple product prices.'
    );

    Assertions::same(
        array( 501, 502 ),
        array_map(
            static function( array $entry ): int {
                return $entry['data']['extra_data']['order_id'];
            },
            $real_sale_events
        ),
        'Expected demo sale events to point at real WooCommerce orders.'
    );

    Assertions::same(
        array( 701, 702 ),
        array_map(
            static function( array $entry ): int {
                return $entry['data']['extra_data']['product_id'];
            },
            $real_sale_events
        ),
        'Expected demo sale events to use simple product IDs and ignore variable products.'
    );

    foreach ( $real_sale_events as $entry ) {
        Assertions::same(
            2313,
            $entry['data']['post_id'],
            'Expected PRO demo sale events to target the same popup.'
        );
        Assertions::same(
            true,
            $entry['meta']['demo'] ?? false,
            'Expected PRO demo sale events to retain the demo marker meta.'
        );
        Assertions::same(
            true,
            $entry['data']['extra_data']['demo'] ?? false,
            'Expected PRO demo sale event metadata to retain the demo marker.'
        );
        Assertions::same(
            'simple',
            $entry['data']['extra_data']['product_type'] ?? '',
            'Expected PRO demo sale events to only use simple product metadata.'
        );
        Assertions::same(
            'GBP',
            $entry['data']['extra_data']['order_currency'] ?? '',
            'Expected PRO demo sale events to retain the order currency.'
        );
    }

    Event::reset();
    $pro_demo->create_top_performer_seed_data( 2313, array( 'demo' => true ), 500 );

    $real_seed_click = null;
    foreach ( Event::$created as $entry ) {
        if (
            $entry['data']['event_type'] === FOOCONVERT_EVENT_TYPE_CLICK
            && !empty( $entry['data']['extra_data']['pro_top_performer_seed'] )
        ) {
            $real_seed_click = $entry;
            break;
        }
    }

    Assertions::same(
        501,
        $real_seed_click['data']['extra_data']['order_id'] ?? null,
        'Expected PRO seeded click conversion metadata to point at a real WooCommerce order when available.'
    );
    Assertions::same(
        24.5,
        $real_seed_click['data']['extra_data']['order_value'] ?? null,
        'Expected PRO seeded click conversion metadata to use a real simple product price.'
    );
    Assertions::same(
        'simple',
        $real_seed_click['data']['extra_data']['product_type'] ?? '',
        'Expected PRO seeded click conversion metadata to use simple products only.'
    );

    echo "demo-content-pro-top-performers: ok\n";
}
