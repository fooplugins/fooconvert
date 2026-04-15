<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Data {
    class Query {
        /** @var array<int,array<string,mixed>> */
        public static $sales_rows = array();

        /**
         * @param int $limit
         * @return array<int,array<string,mixed>>
         */
        public static function get_sales_totals_by_popup( int $limit = 10 ): array {
            $rows = self::$sales_rows;

            usort(
                $rows,
                static function( array $left, array $right ): int {
                    $left_sales = isset( $left['total_sales'] ) ? (float) $left['total_sales'] : 0.0;
                    $right_sales = isset( $right['total_sales'] ) ? (float) $right['total_sales'] : 0.0;

                    if ( $left_sales === $right_sales ) {
                        $left_count = isset( $left['sale_count'] ) ? intval( $left['sale_count'] ) : 0;
                        $right_count = isset( $right['sale_count'] ) ? intval( $right['sale_count'] ) : 0;

                        return $right_count <=> $left_count;
                    }

                    return $right_sales <=> $left_sales;
                }
            );

            return array_slice( $rows, 0, $limit );
        }
    }
}

namespace FooPlugins\FooConvert {
    class Event {
        /**
         * @return array<int,array<string,mixed>>
         */
        public function get_all_popup_metrics(): array {
            $metrics = $GLOBALS['fc_test_all_popup_metrics'] ?? array();

            if ( empty( $metrics ) ) {
                return array();
            }

            $enriched = array();

            foreach ( $metrics as $metric ) {
                $enriched[] = apply_filters(
                    'fooconvert_popup_metrics',
                    $metric,
                    $metric['post_id']
                );
            }

            return $enriched;
        }
    }
}

namespace {
    use FooPlugins\FooConvert\Data\Query;
    use FooPlugins\FooConvert\Pro\Analytics\Metrics;
    use FooPlugins\FooConvert\Stats;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( !class_exists( 'WP_Post', false ) ) {
        class WP_Post {
            /** @var int */
            public $ID = 0;

            /** @var string */
            public $post_type = '';

            /** @var string */
            public $post_title = '';

            /** @var string */
            public $post_content = '';
        }
    }

    if ( !class_exists( 'WP_Query', false ) ) {
        class WP_Query {
            /** @var array<int,WP_Post> */
            public $posts = array();

            /**
             * @param array<string,mixed> $args
             */
            public function __construct( array $args = array() ) {
                $meta_key = isset( $args['meta_key'] ) ? (string) $args['meta_key'] : '';
                $post_type = isset( $args['post_type'] ) ? (string) $args['post_type'] : '';

                foreach ( $GLOBALS['fc_test_posts'] as $post ) {
                    if ( !$post instanceof WP_Post || $post->post_type !== $post_type ) {
                        continue;
                    }

                    $score = $GLOBALS['fc_test_post_meta'][ $post->ID ][ $meta_key ] ?? null;
                    if ( $score === null || $score === '' ) {
                        continue;
                    }

                    $this->posts[] = $post;
                }
            }
        }
    }

    /** @var array<string,array<int,array{callback:mixed,accepted_args:int}>> */
    $GLOBALS['fc_test_filters'] = array();

    /** @var array<int,WP_Post> */
    $GLOBALS['fc_test_posts'] = array();

    /** @var array<int,array<string,mixed>> */
    $GLOBALS['fc_test_post_meta'] = array();

    /** @var array<int,array<string,mixed>> */
    $GLOBALS['fc_test_all_popup_metrics'] = array();

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
    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_test_filters'][ $hook ][] = array(
            'callback'      => $callback,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * @param string $hook
     * @param mixed $callback
     * @param int $priority
     * @param int $accepted_args
     * @return void
     */
    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}

    /**
     * @param string $hook
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    function apply_filters( string $hook, $value, ...$args ) {
        if ( empty( $GLOBALS['fc_test_filters'][ $hook ] ) ) {
            return $value;
        }

        foreach ( $GLOBALS['fc_test_filters'][ $hook ] as $filter ) {
            $accepted_args = max( 1, (int) $filter['accepted_args'] );
            $callback_args = array_slice( array_merge( array( $value ), $args ), 0, $accepted_args );
            $value = call_user_func_array( $filter['callback'], $callback_args );
        }

        return $value;
    }

    /**
     * @return bool
     */
    function is_admin(): bool {
        return false;
    }

    /**
     * @param int $post_id
     * @return WP_Post|null
     */
    function get_post( int $post_id ) {
        return $GLOBALS['fc_test_posts'][ $post_id ] ?? null;
    }

    /**
     * @param int $post_id
     * @param string $meta_key
     * @param bool $single
     * @return mixed
     */
    function get_post_meta( int $post_id, string $meta_key, bool $single = false ) {
        $meta = $GLOBALS['fc_test_post_meta'][ $post_id ][ $meta_key ] ?? '';

        return $single ? $meta : array( $meta );
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
     * @param string $post_type
     * @return object|null
     */
    function get_post_type_object( string $post_type ) {
        $labels = (object) array(
            'singular_name' => ucfirst( $post_type ),
        );

        return (object) array(
            'labels' => $labels,
        );
    }

    /**
     * @param string $content
     * @return array<int,array<string,mixed>>
     */
    function parse_blocks( string $content ): array {
        return array();
    }

    if ( !defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Stats.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Analytics/Metrics.php';

    $metrics = new Metrics();
    $options = fooconvert_popup_metric_options();

    $expected_options = array(
        'engagement-rate'  => 'fooconvert_percentage_to_float',
        'clicks'           => 'intval',
        'click-rate'       => 'fooconvert_percentage_to_float',
        'conversions'      => 'intval',
        'conversion-rate'  => 'fooconvert_percentage_to_float',
        'sales'            => 'floatval',
        'change-views'     => 'fooconvert_metric_calculate_change_in_views',
        'change-views-perc'=> 'fooconvert_metric_calculate_change_in_views_percentage',
        'change-engagements' => 'fooconvert_metric_calculate_change_in_engagements',
        'change-clicks'    => 'fooconvert_metric_calculate_change_in_clicks',
        'change-conversions' => 'fooconvert_metric_calculate_change_in_conversions',
    );

    foreach ( $expected_options as $option_key => $callable_name ) {
        Assertions::true(
            isset( $options[ $option_key ] ),
            'Expected PRO top performer option "' . $option_key . '" to be registered.'
        );

        Assertions::same(
            $callable_name,
            $options[ $option_key ]['function'] ?? '',
            'Expected PRO top performer option "' . $option_key . '" to use the correct callback.'
        );

        Assertions::true(
            is_callable( $callable_name ),
            'Expected callback "' . $callable_name . '" to be callable for "' . $option_key . '".'
        );
    }

    $query = $metrics->adjust_get_all_popup_metrics_query( '', 'wp_fooconvert_events' );
    foreach (
        array(
            'total_clicks',
            'total_conversions',
            'total_sales',
            'last_7_days_views',
            'previous_7_days_views',
            'last_7_days_clicks',
            'previous_7_days_clicks',
            'last_7_days_conversions',
            'previous_7_days_conversions',
            'last_7_days_engagements',
            'previous_7_days_engagements',
        ) as $required_fragment
    ) {
        Assertions::true(
            false !== strpos( $query, $required_fragment ),
            'Expected the all-popup metrics query to expose "' . $required_fragment . '".'
        );
    }

    foreach ( array( 101, 102, 103 ) as $post_id ) {
        $post = new WP_Post();
        $post->ID = $post_id;
        $post->post_type = FOOCONVERT_CPT_POPUP;
        $post->post_title = 'Demo popup ' . $post_id;
        $GLOBALS['fc_test_posts'][ $post_id ] = $post;
        $GLOBALS['fc_test_post_meta'][ $post_id ] = array();
    }

    $GLOBALS['fc_test_all_popup_metrics'] = array(
        array(
            'post_id'                  => 101,
            'total_views'                => 100,
            'total_dismiss'              => 10,
            'total_engagements'          => 40,
            'total_clicks'               => 20,
            'total_conversions'          => 10,
            'total_positive_sentiment'   => 20,
            'total_negative_sentiment'   => 10,
            'last_7_days_views'          => 20,
            'previous_7_days_views'      => 10,
            'last_7_days_engagements'    => 15,
            'previous_7_days_engagements'=> 10,
            'last_7_days_clicks'         => 8,
            'previous_7_days_clicks'     => 3,
            'last_7_days_conversions'    => 5,
            'previous_7_days_conversions'=> 2,
        ),
        array(
            'post_id'                  => 102,
            'total_views'                => 80,
            'total_dismiss'              => 12,
            'total_engagements'          => 50,
            'total_clicks'               => 10,
            'total_conversions'          => 20,
            'total_positive_sentiment'   => 18,
            'total_negative_sentiment'   => 6,
            'last_7_days_views'          => 5,
            'previous_7_days_views'      => 10,
            'last_7_days_engagements'    => 3,
            'previous_7_days_engagements'=> 9,
            'last_7_days_clicks'         => 2,
            'previous_7_days_clicks'     => 4,
            'last_7_days_conversions'    => 1,
            'previous_7_days_conversions'=> 6,
        ),
        array(
            'post_id'                  => 103,
            'total_views'                => 50,
            'total_dismiss'              => 8,
            'total_engagements'          => 15,
            'total_clicks'               => 5,
            'total_conversions'          => 3,
            'total_positive_sentiment'   => 9,
            'total_negative_sentiment'   => 4,
            'last_7_days_views'          => 25,
            'previous_7_days_views'      => 5,
            'last_7_days_engagements'    => 12,
            'previous_7_days_engagements'=> 2,
            'last_7_days_clicks'         => 7,
            'previous_7_days_clicks'     => 1,
            'last_7_days_conversions'    => 5,
            'previous_7_days_conversions'=> 1,
        ),
    );

    Query::$sales_rows = array(
        array(
            'post_id'   => 101,
            'sale_count'  => 2,
            'total_sales' => '75.25',
        ),
        array(
            'post_id'   => 102,
            'sale_count'  => 3,
            'total_sales' => '125.00',
        ),
        array(
            'post_id'   => 103,
            'sale_count'  => 1,
            'total_sales' => '95.00',
        ),
    );

    $stats = new Stats();
    $expected_winners = array(
        'engagement-rate'   => 102,
        'clicks'            => 101,
        'click-rate'        => 101,
        'conversions'       => 102,
        'conversion-rate'   => 102,
        'sales'             => 102,
        'change-views'      => 103,
        'change-views-perc' => 103,
        'change-engagements'=> 103,
        'change-clicks'     => 103,
        'change-conversions'=> 103,
    );

    foreach ( $expected_winners as $sort => $expected_post_id ) {
        $top_performers = $stats->get_top_performers( $sort );

        Assertions::same(
            $expected_post_id,
            $top_performers[1]['id'] ?? 0,
            'Expected "' . $sort . '" to rank the correct popup first.'
        );
    }

    echo "top-performers-pro-options: ok\n";
}
