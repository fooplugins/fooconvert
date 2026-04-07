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

    /** @var array<string,array<int,array{callback:mixed,accepted_args:int}>> */
    $GLOBALS['fc_test_actions'] = array();

    /** @var array<int,int> */
    $GLOBALS['fc_test_wp_rand_values'] = array();

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
        42,
        15000,
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

    Assertions::same(
        2,
        count( $sale_events ),
        'Expected PRO demo analytics to add deterministic sale events for top performer sales.'
    );

    foreach ( $sale_events as $entry ) {
        Assertions::same(
            2313,
            $entry['data']['widget_id'],
            'Expected PRO demo sale events to target the same widget.'
        );
        Assertions::same(
            true,
            $entry['meta']['demo'] ?? false,
            'Expected PRO demo sale events to retain the demo marker meta.'
        );
        Assertions::true(
            isset( $entry['data']['event_value'] ) && $entry['data']['event_value'] > 0,
            'Expected PRO demo sale events to include a positive sale amount.'
        );
    }

    echo "demo-content-pro-top-performers: ok\n";
}
