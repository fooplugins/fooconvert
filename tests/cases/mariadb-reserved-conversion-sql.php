<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Pro\Experiments {
    class Experiment {
        public const GOAL_CLICK = 'click';
        public const GOAL_CONVERSION = 'conversion';
        public const GOAL_LEAD = 'lead';

        /** @return self */
        public static function instance(): self {
            return new self();
        }

        /** @return array<string,mixed> */
        public function get_state( int $experiment_id ): array {
            return array(
                'goal'            => self::GOAL_CONVERSION,
                'run_state'       => 'running',
                'run_windows'     => array(
                    array(
                        'start' => '2026-09-01 00:00:00',
                        'end'   => '2026-09-08 00:00:00',
                    ),
                ),
                'control_post_id' => 101,
                'winner_post_id'  => 0,
                'weights'         => array( 101 => 50, 102 => 50 ),
            );
        }

        /** @return array<int,int> */
        public function get_participant_ids( int $experiment_id ): array {
            return array( 101, 102 );
        }

        public function get_participant_label( int $experiment_id, int $post_id ): string {
            return 'Popup ' . $post_id;
        }

        public function get_participant_role( int $experiment_id, int $post_id ): string {
            return 101 === $post_id ? 'control' : 'variant';
        }
    }
}

namespace {
    use FooPlugins\FooConvert\Data\Query;
    use FooPlugins\FooConvert\Pro\Analytics\Metrics;
    use FooPlugins\FooConvert\Pro\Experiments\Results;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    final class ConversionSqlWpdb {
        /** @var string */
        public $prefix = 'wp_';

        /** @var string */
        public $posts = 'wp_posts';

        /** @var array<int,string> */
        public $queries = array();

        /**
         * @param string $query
         * @param mixed ...$args
         */
        public function prepare( string $query, ...$args ): string {
            return $query;
        }

        /**
         * @param string $query
         * @param mixed $output
         * @return array<string,mixed>
         */
        public function get_row( string $query, $output = null ): array {
            $this->queries[] = $query;
            return array();
        }

        /**
         * @param string $query
         * @param mixed $output
         * @return array<int,array<string,mixed>>
         */
        public function get_results( string $query, $output = null ): array {
            $this->queries[] = $query;
            return array();
        }
    }

    /** @var array<string,array<int,array{callback:mixed,accepted_args:int}>> */
    $GLOBALS['fc_test_filters'] = array();

    /**
     * @param string $hook
     * @param mixed $callback
     */
    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_test_filters'][ $hook ][] = array(
            'callback'      => $callback,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * @param string $hook
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    function apply_filters( string $hook, $value, ...$args ) {
        foreach ( $GLOBALS['fc_test_filters'][ $hook ] ?? array() as $filter ) {
            $callback_args = array_slice(
                array_merge( array( $value ), $args ),
                0,
                max( 1, (int) $filter['accepted_args'] )
            );
            $value = call_user_func_array( $filter['callback'], $callback_args );
        }

        return $value;
    }

    function is_admin(): bool {
        return false;
    }

    function get_the_title( int $post_id ): string {
        return 'Popup ' . $post_id;
    }

    if ( !defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ );
    }
    if ( !defined( 'ARRAY_A' ) ) {
        define( 'ARRAY_A', 'ARRAY_A' );
    }

    define( 'FOOCONVERT_DB_TABLE_EVENTS', 'fooconvert_events' );
    define( 'FOOCONVERT_CPT_POPUP', 'fc-popup' );
    define( 'FOOCONVERT_METRICS_DAYS_DEFAULT', 7 );

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Data/Base.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Data/Schema.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Data/Query.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Analytics/Metrics.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Experiments/Results.php';

    $wpdb = new ConversionSqlWpdb();
    $GLOBALS['wpdb'] = $wpdb;
    new Metrics();

    /**
     * @param string $label
     * @param string $query
     */
    $assert_conversion_is_quoted = static function( string $label, string $query, int $expected_count ): void {
        Assertions::same(
            0,
            preg_match( '/(?<![`A-Za-z0-9_])conversion(?![`A-Za-z0-9_])/i', $query ),
            $label . ' must not use the MariaDB 12.3 reserved word conversion as an unquoted identifier.'
        );
        Assertions::same(
            $expected_count,
            substr_count( $query, '`conversion` = 1' ),
            $label . ' must quote every conversion-column comparison.'
        );
    };

    Query::get_popup_metrics( 101 );
    $assert_conversion_is_quoted( 'Single-popup aggregate SQL', $wpdb->queries[0], 1 );

    Query::get_all_popup_metrics();
    $assert_conversion_is_quoted( 'All-popup aggregate SQL', $wpdb->queries[1], 3 );

    Query::get_popup_daily_activity( 101 );
    $assert_conversion_is_quoted( 'Daily popup activity SQL', $wpdb->queries[2], 1 );

    $results = new Results();
    $results->get_results( 501 );
    $experiment_event_query = '';
    foreach ( array_slice( $wpdb->queries, 3 ) as $query ) {
        if ( false !== strpos( $query, ' AS conversions' ) ) {
            $experiment_event_query = $query;
            break;
        }
    }

    Assertions::true(
        '' !== $experiment_event_query,
        'The experiment results path must execute its event metrics query.'
    );
    $assert_conversion_is_quoted( 'Experiment result SQL', $experiment_event_query, 1 );

    echo "mariadb-reserved-conversion-sql: ok\n";
}
