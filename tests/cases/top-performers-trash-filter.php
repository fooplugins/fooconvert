<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert {
    class Event {
        /**
         * @return array<int,array<string,mixed>>
         */
        public function get_all_popup_metrics(): array {
            return $GLOBALS['fc_test_all_popup_metrics'] ?? array();
        }
    }
}

namespace FooPlugins\FooConvert\Data {
    class Query {
        /**
         * @param int $limit
         * @return array<int,array<string,mixed>>
         */
        public static function get_sales_totals_by_popup( int $limit = 10 ): array {
            return $GLOBALS['fc_test_sales_rows'] ?? array();
        }
    }
}

namespace {
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

            /** @var string */
            public $post_status = 'publish';
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

    /** @var array<int,array<string,mixed>> */
    $GLOBALS['fc_test_sales_rows'] = array();

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
     * @param array<string,mixed> $args
     * @param string $output
     * @return array<int,string>
     */
    function get_post_stati( array $args = array(), string $output = 'names' ): array {
        return array( 'publish', 'draft', 'future', 'pending', 'private', 'trash' );
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
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Stats.php';

    add_filter( 'fooconvert_popup_metric_options', static function( array $options ): array {
        $options['sales'] = array(
            'dropdown_option' => 'sales',
            'label'           => 'Sales',
            'description'     => 'Total attributed sales revenue',
            'metric'          => 'total_sales',
            'meta_key'        => '_fooconvert_metric_sales',
            'format'          => 'currency',
        );

        return $options;
    } );

    $active = new WP_Post();
    $active->ID = 4101;
    $active->post_type = FOOCONVERT_CPT_POPUP;
    $active->post_title = 'Published popup';
    $active->post_status = 'publish';

    $trashed = new WP_Post();
    $trashed->ID = 4102;
    $trashed->post_type = FOOCONVERT_CPT_POPUP;
    $trashed->post_title = 'Trashed popup';
    $trashed->post_status = 'trash';

    $GLOBALS['fc_test_posts'][4101] = $active;
    $GLOBALS['fc_test_posts'][4102] = $trashed;

    $GLOBALS['fc_test_post_meta'][4101] = array(
        FOOCONVERT_META_KEY_METRIC_VIEWS => 50,
    );
    $GLOBALS['fc_test_post_meta'][4102] = array(
        FOOCONVERT_META_KEY_METRIC_VIEWS => 500,
    );

    $GLOBALS['fc_test_all_popup_metrics'] = array(
        array(
            'post_id'            => 4101,
            'total_engagements'  => 20,
        ),
        array(
            'post_id'            => 4102,
            'total_engagements'  => 200,
        ),
    );

    $GLOBALS['fc_test_sales_rows'] = array(
        array(
            'post_id'     => 4101,
            'sale_count'  => 1,
            'total_sales' => '75.00',
        ),
        array(
            'post_id'     => 4102,
            'sale_count'  => 2,
            'total_sales' => '175.00',
        ),
    );

    $stats = new Stats();

    Assertions::same(
        4101,
        $stats->get_top_performers( 'views' )[1]['id'] ?? 0,
        'Cached top performer rankings should skip trashed popup posts.'
    );

    Assertions::same(
        4101,
        $stats->get_top_performers( 'engagements' )[1]['id'] ?? 0,
        'Metric fallback rankings should skip trashed popup posts.'
    );

    Assertions::same(
        4101,
        $stats->get_top_performers( 'sales' )[1]['id'] ?? 0,
        'Sales fallback rankings should skip trashed popup posts.'
    );

    echo "top-performers-trash-filter: ok\n";
}
