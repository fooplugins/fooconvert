<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Data {
    /**
     * Minimal query stub for WooCommerce sale attribution smoke tests.
     */
    class Query {
        /** @var array<int,array<string,mixed>> */
        public static $latest_calls = array();

        /** @var array<int,array<string,mixed>> */
        public static $sale_scope_calls = array();

        /** @var array<string,array<string,mixed>|null> */
        public static $latest_responses = array();

        /** @var bool */
        public static $sale_exists = false;

        /**
         * Reset all recorded query state.
         *
         * @return void
         */
        public static function reset(): void {
            self::$latest_calls = array();
            self::$sale_scope_calls = array();
            self::$latest_responses = array();
            self::$sale_exists = false;
        }

        /**
         * Queue a lookup response for a specific criteria set.
         *
         * @param array<string,mixed> $criteria Lookup criteria.
         * @param array<string,mixed>|null $response Result to return.
         * @return void
         */
        public static function queue_latest_response( array $criteria, ?array $response ): void {
            self::$latest_responses[ self::criteria_key( $criteria ) ] = $response;
        }

        /**
         * Simulate resolving the latest qualifying event.
         *
         * @param array<string,mixed> $criteria Lookup criteria.
         * @return array<string,mixed>|null
         */
        public static function get_latest_qualifying_event( $criteria = array() ) {
            self::$latest_calls[] = $criteria;
            $key = self::criteria_key( $criteria );

            return self::$latest_responses[ $key ] ?? null;
        }

        /**
         * Simulate sale dedupe lookup.
         *
         * @param string $dedupe_mode Dedupe mode.
         * @param int $post_id Popup ID.
         * @param string|null $session_id Session ID.
         * @return bool
         */
        public static function sale_exists_for_scope( $dedupe_mode, $post_id, $session_id = null ) {
            self::$sale_scope_calls[] = array(
                'dedupe_mode' => $dedupe_mode,
                'post_id'   => $post_id,
                'session_id'  => $session_id,
            );

            return self::$sale_exists;
        }

        /**
         * Build a stable lookup key for queued criteria.
         *
         * @param array<string,mixed> $criteria Lookup criteria.
         * @return string
         */
        private static function criteria_key( array $criteria ): string {
            ksort( $criteria );

            return md5( json_encode( $criteria ) ?: '' );
        }
    }
}

namespace FooPlugins\FooConvert {
    /**
     * Minimal event stub that records created sale rows.
     */
    class Event {
        /** @var array<int,array<string,mixed>> */
        public static $created = array();

        /**
         * Reset recorded events.
         *
         * @return void
         */
        public static function reset(): void {
            self::$created = array();
        }

        /**
         * Capture created event payloads.
         *
         * @param array<string,mixed> $data Event payload.
         * @return int
         */
        public function create( $data ) {
            self::$created[] = $data;

            return count( self::$created );
        }
    }
}

namespace {
    use FooPlugins\FooConvert\Data\Query;
    use FooPlugins\FooConvert\Event;
    use FooPlugins\FooConvert\Pro\WooCommerce\Sales;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    /**
     * Minimal marker classes matching the production type checks.
     */
    class WP_Error {}
    class WooCommerce {}

    /**
     * Minimal Woo date wrapper for instanceof checks.
     */
    class WC_DateTime extends DateTime {}

    /**
     * Lightweight WC_Order stub covering the sales attribution paths.
     */
    class WC_Order {
        /** @var int */
        private $id;

        /** @var float */
        private $total;

        /** @var string */
        private $status;

        /** @var string */
        private $currency;

        /** @var int */
        private $customer_id;

        /** @var string */
        private $order_number;

        /** @var WC_DateTime */
        private $date_created;

        /** @var array<string,mixed> */
        private $meta = array();

        /** @var int */
        private $save_count = 0;

        /**
         * @param int $id Order ID.
         * @param float $total Order total.
         * @param string $status Order status.
         * @param string $currency Currency code.
         * @param int $customer_id Customer ID.
         * @param WC_DateTime|null $date_created Created timestamp.
         * @param string|null $order_number Public order number.
         */
        public function __construct( int $id, float $total, string $status = 'pending', string $currency = 'GBP', int $customer_id = 0, ?WC_DateTime $date_created = null, ?string $order_number = null ) {
            $this->id = $id;
            $this->total = $total;
            $this->status = $status;
            $this->currency = $currency;
            $this->customer_id = $customer_id;
            $this->date_created = $date_created ?: new WC_DateTime( '2026-04-03 09:30:00', new DateTimeZone( 'UTC' ) );
            $this->order_number = $order_number ?: (string) $id;
        }

        /**
         * @param string $key Meta key.
         * @param bool $single Ignored, included for signature compatibility.
         * @return mixed
         */
        public function get_meta( $key, $single = true ) {
            return $this->meta[ $key ] ?? '';
        }

        /**
         * @param string $key Meta key.
         * @param mixed $value Meta value.
         * @return void
         */
        public function update_meta_data( $key, $value ): void {
            $this->meta[ $key ] = $value;
        }

        /**
         * @return void
         */
        public function save(): void {
            $this->save_count++;
        }

        /**
         * @return int
         */
        public function get_save_count(): int {
            return $this->save_count;
        }

        /**
         * @return float
         */
        public function get_total(): float {
            return $this->total;
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
            return $this->order_number;
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
         * @return int
         */
        public function get_customer_id(): int {
            return $this->customer_id;
        }

        /**
         * @return WC_DateTime
         */
        public function get_date_created(): WC_DateTime {
            return $this->date_created;
        }
    }

    /**
     * Minimal action stub used by the constructor.
     *
     * @return void
     */
    function add_action( ...$args ): void {}

    /**
     * Minimal filter stub used by the constructor.
     *
     * @return void
     */
    function add_filter( ...$args ): void {}

    /**
     * @return bool
     */
    function is_admin(): bool {
        return false;
    }

    /**
     * @param string $text Source string.
     * @param string|null $domain Text domain.
     * @return string
     */
    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @param mixed $value Raw value.
     * @return string
     */
    function sanitize_text_field( $value ): string {
        if ( is_scalar( $value ) ) {
            return trim( (string) $value );
        }

        return '';
    }

    /**
     * @param mixed $value Value to encode.
     * @return string
     */
    function wp_json_encode( $value ): string {
        return json_encode( $value ) ?: '';
    }

    /**
     * @param string $type Time format type.
     * @param bool $gmt Whether GMT is requested.
     * @return string
     */
    function current_time( string $type, bool $gmt = false ): string {
        return '2026-04-03 10:00:00';
    }

    /**
     * @param mixed $value Value to normalize.
     * @return int
     */
    function absint( $value ): int {
        return abs( (int) $value );
    }

    /**
     * @param mixed $value Candidate error object.
     * @return bool
     */
    function is_wp_error( $value ): bool {
        return $value instanceof WP_Error;
    }

    /**
     * @return string|null
     */
    function fooconvert_get_request_session_id() {
        return $GLOBALS['fc_request_session_id'] ?? null;
    }

    /**
     * @return string|null
     */
    function fooconvert_get_request_anonymous_user_guid() {
        return $GLOBALS['fc_request_anonymous_user_guid'] ?? null;
    }

    /**
     * @return string
     */
    function fooconvert_get_sale_attribution_timing(): string {
        return $GLOBALS['fc_sale_attribution_timing'] ?? FOOCONVERT_SALE_ATTRIBUTION_TIMING_ORDER_CREATED;
    }

    /**
     * @return int
     */
    function fooconvert_get_sale_attribution_lookback_days(): int {
        return (int) ( $GLOBALS['fc_sale_attribution_lookback_days'] ?? 7 );
    }

    /**
     * @return string
     */
    function fooconvert_get_sale_dedupe_mode(): string {
        return $GLOBALS['fc_sale_dedupe_mode'] ?? FOOCONVERT_SALE_DEDUPE_MODE_POPUP_SESSION;
    }

    /**
     * @return bool
     */
    function fooconvert_get_sale_allow_multiple_orders_per_session(): bool {
        return $GLOBALS['fc_sale_allow_multiple_orders_per_session'] ?? true;
    }

    define( 'FOOCONVERT_EVENT_TYPE_SALE', 'sale' );
    define( 'FOOCONVERT_SALE_ATTRIBUTION_TIMING_PAYMENT_MADE', 'payment_made' );
    define( 'FOOCONVERT_SALE_ATTRIBUTION_TIMING_ORDER_CREATED', 'order_created' );
    define( 'FOOCONVERT_SALE_DEDUPE_MODE_POPUP_SESSION', 'popup_session' );
    define( 'FOOCONVERT_SALE_DEDUPE_MODE_SESSION_ONLY', 'session_only' );
    define( 'FOOCONVERT_SETTING_SALE_ALLOW_MULTIPLE_ORDERS_PER_SESSION', 'sale_allow_multiple_orders_per_session' );
    define( 'FOOCONVERT_WC_ORDER_META_SESSION_ID', '_fooconvert_session_id' );
    define( 'FOOCONVERT_WC_ORDER_META_ANONYMOUS_USER_GUID', '_fooconvert_anonymous_user_guid' );
    define( 'FOOCONVERT_WC_ORDER_META_ORDER_CREATED_AT_GMT', '_fooconvert_order_created_at_gmt' );
    define( 'FOOCONVERT_WC_ORDER_META_ATTRIBUTION_EVENT_ID', '_fooconvert_attribution_event_id' );
    define( 'FOOCONVERT_WC_ORDER_META_ATTRIBUTION_POST_ID', '_fooconvert_attribution_post_id' );
    define( 'FOOCONVERT_WC_ORDER_META_ATTRIBUTION_SESSION_ID', '_fooconvert_attribution_session_id' );
    define( 'FOOCONVERT_WC_ORDER_META_SALE_EVENT_ID', '_fooconvert_sale_event_id' );

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/WooCommerce/Sales.php';

    /**
     * Reset all global state between scenarios.
     *
     * @return void
     */
    function reset_sales_test_state(): void {
        Query::reset();
        Event::reset();
        $GLOBALS['fc_request_session_id'] = null;
        $GLOBALS['fc_request_anonymous_user_guid'] = null;
        unset( $GLOBALS['fc_sale_attribution_timing'] );
        $GLOBALS['fc_sale_attribution_lookback_days'] = 7;
        $GLOBALS['fc_sale_dedupe_mode'] = FOOCONVERT_SALE_DEDUPE_MODE_POPUP_SESSION;
        unset( $GLOBALS['fc_sale_allow_multiple_orders_per_session'] );
    }

    reset_sales_test_state();

    $service = new Sales();

    $guest_order = new WC_Order(
        101,
        49.99,
        'pending',
        'GBP',
        0,
        new WC_DateTime( '2026-04-03 09:30:00', new DateTimeZone( 'UTC' ) )
    );

    $GLOBALS['fc_request_session_id'] = 'sess-guest';
    $GLOBALS['fc_request_anonymous_user_guid'] = 'anon-guest';
    $GLOBALS['fc_sale_attribution_timing'] = FOOCONVERT_SALE_ATTRIBUTION_TIMING_PAYMENT_MADE;
    $GLOBALS['fc_sale_allow_multiple_orders_per_session'] = false;

    $service->handle_order_created( $guest_order );

    Assertions::same(
        'sess-guest',
        $guest_order->get_meta( FOOCONVERT_WC_ORDER_META_SESSION_ID, true ),
        'Order creation should snapshot the FooConvert session ID onto the order.'
    );

    Assertions::same(
        'anon-guest',
        $guest_order->get_meta( FOOCONVERT_WC_ORDER_META_ANONYMOUS_USER_GUID, true ),
        'Order creation should snapshot the FooConvert anonymous visitor ID onto the order.'
    );

    Assertions::same(
        '2026-04-03 09:30:00',
        $guest_order->get_meta( FOOCONVERT_WC_ORDER_META_ORDER_CREATED_AT_GMT, true ),
        'Order creation should persist the order-created GMT timestamp.'
    );

    Assertions::same(
        0,
        count( Event::$created ),
        'Payment-made timing should not create a sale event during order creation.'
    );

    Query::queue_latest_response(
        array(
            'anonymous_user_guid' => 'anon-guest',
            'session_id'          => 'sess-guest',
            'cutoff_gmt'          => '2026-04-03 09:30:00',
            'lookback_days'       => 7,
        ),
        array(
            'id'                  => 55,
            'post_id'           => 12,
            'session_id'          => 'sess-guest',
            'anonymous_user_guid' => 'anon-guest',
            'event_type'          => 'click',
            'timestamp'           => '2026-04-03 09:00:00',
            'page_url'            => 'https://example.com/offer',
        )
    );

    $service->handle_payment_complete( $guest_order );

    Assertions::same(
        1,
        count( Event::$created ),
        'Payment-made attribution should create a sale event once a qualifying source event is found.'
    );

    Assertions::same(
        FOOCONVERT_EVENT_TYPE_SALE,
        Event::$created[0]['event_type'],
        'Attributed sales should be recorded as sale events.'
    );

    Assertions::same(
        null,
        Event::$created[0]['conversion'],
        'Attributed sale events must not increment conversion counters.'
    );

    Assertions::same(
        12,
        Event::$created[0]['post_id'],
        'The sale event should be attributed to the popup from the qualifying source event.'
    );

    Assertions::same(
        'sess-guest',
        Event::$created[0]['session_id'],
        'The sale event should carry the attributed session ID.'
    );

    Assertions::same(
        49.99,
        Event::$created[0]['event_value'],
        'The sale event should record the WooCommerce order total as event_value.'
    );

    Assertions::same(
        55,
        Event::$created[0]['extra_data']['source_event_id'],
        'The sale event metadata should reference the source event ID.'
    );

    Assertions::same(
        FOOCONVERT_SALE_ATTRIBUTION_TIMING_PAYMENT_MADE,
        Event::$created[0]['extra_data']['attribution_timing'],
        'The sale event metadata should store the attribution timing mode.'
    );

    Assertions::same(
        55,
        $guest_order->get_meta( FOOCONVERT_WC_ORDER_META_ATTRIBUTION_EVENT_ID, true ),
        'The order should store the resolved source event ID.'
    );

    Assertions::same(
        12,
        $guest_order->get_meta( FOOCONVERT_WC_ORDER_META_ATTRIBUTION_POST_ID, true ),
        'The order should store the attributed popup ID.'
    );

    Assertions::same(
        1,
        $guest_order->get_meta( FOOCONVERT_WC_ORDER_META_SALE_EVENT_ID, true ),
        'The order should store the created sale event ID as an idempotency guard.'
    );

    Assertions::same(
        array(
            'dedupe_mode' => FOOCONVERT_SALE_DEDUPE_MODE_POPUP_SESSION,
            'post_id'   => 12,
            'session_id'  => 'sess-guest',
        ),
        Query::$sale_scope_calls[0],
        'Payment-made attribution should run dedupe using the default popup/session scope.'
    );

    reset_sales_test_state();

    $GLOBALS['fc_request_session_id'] = 'sess-created';
    $GLOBALS['fc_request_anonymous_user_guid'] = 'anon-created';

    $order_created_order = new WC_Order(
        202,
        19.95,
        'pending',
        'GBP',
        0,
        new WC_DateTime( '2026-04-03 11:00:00', new DateTimeZone( 'UTC' ) )
    );

    Query::queue_latest_response(
        array(
            'anonymous_user_guid' => 'anon-created',
            'session_id'          => 'sess-created',
            'cutoff_gmt'          => '2026-04-03 11:00:00',
            'lookback_days'       => 7,
        ),
        array(
            'id'                  => 88,
            'post_id'           => 44,
            'session_id'          => 'sess-created',
            'anonymous_user_guid' => 'anon-created',
            'event_type'          => 'conversion',
            'timestamp'           => '2026-04-03 10:45:00',
            'page_url'            => 'https://example.com/checkout',
        )
    );

    $service->handle_order_created( $order_created_order );
    $service->handle_payment_complete( $order_created_order );

    Assertions::same(
        1,
        count( Event::$created ),
        'Default order-created timing should write the sale once and remain idempotent on later payment hooks.'
    );

    Assertions::same(
        FOOCONVERT_SALE_ATTRIBUTION_TIMING_ORDER_CREATED,
        Event::$created[0]['extra_data']['attribution_timing'],
        'Default attribution should mark the resulting sale event with the order_created timing.'
    );

    Assertions::same(
        1,
        $order_created_order->get_meta( FOOCONVERT_WC_ORDER_META_SALE_EVENT_ID, true ),
        'Default order-created timing should still persist the sale event ID on the order.'
    );

    reset_sales_test_state();

    $GLOBALS['fc_request_session_id'] = 'sess-multi';
    $GLOBALS['fc_request_anonymous_user_guid'] = 'anon-multi';

    $first_multi_order = new WC_Order(
        250,
        10.00,
        'pending',
        'GBP',
        0,
        new WC_DateTime( '2026-04-03 11:15:00', new DateTimeZone( 'UTC' ) )
    );

    Query::queue_latest_response(
        array(
            'anonymous_user_guid' => 'anon-multi',
            'session_id'          => 'sess-multi',
            'cutoff_gmt'          => '2026-04-03 11:15:00',
            'lookback_days'       => 7,
        ),
        array(
            'id'                  => 140,
            'post_id'           => 91,
            'session_id'          => 'sess-multi',
            'anonymous_user_guid' => 'anon-multi',
            'event_type'          => 'conversion',
            'timestamp'           => '2026-04-03 11:10:00',
            'page_url'            => 'https://example.com/offer-a',
        )
    );

    $service->handle_order_created( $first_multi_order );

    $second_multi_order = new WC_Order(
        251,
        15.00,
        'pending',
        'GBP',
        0,
        new WC_DateTime( '2026-04-03 11:20:00', new DateTimeZone( 'UTC' ) )
    );

    Query::queue_latest_response(
        array(
            'anonymous_user_guid' => 'anon-multi',
            'session_id'          => 'sess-multi',
            'cutoff_gmt'          => '2026-04-03 11:20:00',
            'lookback_days'       => 7,
        ),
        array(
            'id'                  => 141,
            'post_id'           => 91,
            'session_id'          => 'sess-multi',
            'anonymous_user_guid' => 'anon-multi',
            'event_type'          => 'conversion',
            'timestamp'           => '2026-04-03 11:18:00',
            'page_url'            => 'https://example.com/offer-b',
        )
    );

    $service->handle_order_created( $second_multi_order );

    Assertions::same(
        2,
        count( Event::$created ),
        'Allow multiple orders per session should attribute more than one order in the same session by default.'
    );

    Assertions::same(
        0,
        count( Query::$sale_scope_calls ),
        'When multiple orders per session are allowed, the legacy session dedupe query should be skipped.'
    );

    reset_sales_test_state();

    $GLOBALS['fc_sale_attribution_timing'] = FOOCONVERT_SALE_ATTRIBUTION_TIMING_PAYMENT_MADE;
    $GLOBALS['fc_sale_dedupe_mode'] = FOOCONVERT_SALE_DEDUPE_MODE_SESSION_ONLY;
    $GLOBALS['fc_sale_allow_multiple_orders_per_session'] = false;
    $GLOBALS['fc_request_session_id'] = 'sess-dedupe';
    $GLOBALS['fc_request_anonymous_user_guid'] = 'anon-dedupe';
    Query::$sale_exists = true;

    $deduped_order = new WC_Order(
        303,
        99.00,
        'processing',
        'GBP',
        0,
        new WC_DateTime( '2026-04-03 12:15:00', new DateTimeZone( 'UTC' ) )
    );

    $service->handle_order_created( $deduped_order );

    Query::queue_latest_response(
        array(
            'anonymous_user_guid' => 'anon-dedupe',
            'session_id'          => 'sess-dedupe',
            'cutoff_gmt'          => '2026-04-03 12:15:00',
            'lookback_days'       => 7,
        ),
        array(
            'id'                  => 99,
            'post_id'           => 51,
            'session_id'          => 'sess-dedupe',
            'anonymous_user_guid' => 'anon-dedupe',
            'event_type'          => 'click',
            'timestamp'           => '2026-04-03 12:00:00',
            'page_url'            => 'https://example.com/campaign',
        )
    );

    $service->handle_payment_complete( $deduped_order );

    Assertions::same(
        0,
        count( Event::$created ),
        'Dedupe matches should prevent a second sale event from being created.'
    );

    Assertions::same(
        array(
            'dedupe_mode' => FOOCONVERT_SALE_DEDUPE_MODE_SESSION_ONLY,
            'post_id'   => 51,
            'session_id'  => 'sess-dedupe',
        ),
        Query::$sale_scope_calls[0],
        'Session-only dedupe should check for existing sales across the full session.'
    );

    Assertions::same(
        '',
        $deduped_order->get_meta( FOOCONVERT_WC_ORDER_META_SALE_EVENT_ID, true ),
        'Orders skipped by dedupe should not receive a sale event ID.'
    );

    reset_sales_test_state();

    $GLOBALS['fc_sale_attribution_timing'] = FOOCONVERT_SALE_ATTRIBUTION_TIMING_PAYMENT_MADE;
    $GLOBALS['fc_request_session_id'] = 'sess-user';
    $GLOBALS['fc_request_anonymous_user_guid'] = 'anon-user';

    $logged_in_order = new WC_Order(
        404,
        75.50,
        'completed',
        'GBP',
        77,
        new WC_DateTime( '2026-04-03 14:00:00', new DateTimeZone( 'UTC' ) )
    );

    $service->handle_order_created( $logged_in_order );

    Query::queue_latest_response(
        array(
            'anonymous_user_guid' => 'anon-user',
            'session_id'          => 'sess-user',
            'cutoff_gmt'          => '2026-04-03 14:00:00',
            'lookback_days'       => 7,
        ),
        array(
            'id'                  => 120,
            'post_id'           => 73,
            'session_id'          => 'sess-user',
            'anonymous_user_guid' => 'anon-user',
            'event_type'          => 'click',
            'timestamp'           => '2026-04-03 13:55:00',
            'page_url'            => 'https://example.com/fallback',
        )
    );

    $service->handle_payment_complete( $logged_in_order );

    Assertions::same(
        2,
        count( Query::$latest_calls ),
        'Logged-in orders should prefer user/session attribution and then fall back to the anonymous session when needed.'
    );

    Assertions::same(
        array(
            'user_id'       => 77,
            'session_id'    => 'sess-user',
            'cutoff_gmt'    => '2026-04-03 14:00:00',
            'lookback_days' => 7,
        ),
        Query::$latest_calls[0],
        'The first attribution attempt for logged-in orders should target the Woo user ID and session.'
    );

    Assertions::same(
        1,
        count( Event::$created ),
        'Anonymous fallback should still attribute the sale when no qualifying user-bound event exists.'
    );

    Assertions::same(
        73,
        Event::$created[0]['post_id'],
        'Anonymous fallback attribution should still credit the resolved popup.'
    );

    echo "sales-attribution-woocommerce: ok\n";
}
