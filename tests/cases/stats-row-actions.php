<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Admin\Stats;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( !class_exists( 'WP_Post', false ) ) {
        class WP_Post {
            /** @var int */
            public $ID = 0;

            /** @var string */
            public $post_type = '';
        }
    }

    if ( !defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/wordpress/' );
    }

    if ( !defined( 'FOOCONVERT_CPT_POPUP' ) ) {
        define( 'FOOCONVERT_CPT_POPUP', 'fc-popup' );
    }

    /** @var array<string,array<int,array{callback:mixed,priority:int,args:int}>> */
    $GLOBALS['fc_test_actions'] = array();

    /** @var array<string,array<int,array{callback:mixed,priority:int,args:int}>> */
    $GLOBALS['fc_test_filters'] = array();

    /**
     * @param string $hook
     * @param mixed $callback
     * @param int $priority
     * @param int $accepted_args
     * @return void
     */
    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_test_actions'][ $hook ][] = array(
            'callback' => $callback,
            'priority' => $priority,
            'args'     => $accepted_args,
        );
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
            'callback' => $callback,
            'priority' => $priority,
            'args'     => $accepted_args,
        );
    }

    /**
     * @param string $text
     * @param string|null $domain
     * @return string
     */
    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @param string $text
     * @param string|null $domain
     * @return string
     */
    function esc_html__( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @param string $url
     * @return string
     */
    function esc_url( string $url ): string {
        return $url;
    }

    /**
     * @param int $post_id
     * @return string
     */
    function fooconvert_admin_url_popup_stats( int $post_id ): string {
        return 'admin.php?page=fooconvert-popup-stats&post_id=' . $post_id;
    }

    require_once dirname( __DIR__ ) . '/support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Admin/Stats.php';

    $stats = new Stats();

    Assertions::false(
        isset( $GLOBALS['fc_test_actions']['manage_fc-popup_posts_custom_column'] ),
        'Stats should no longer register a popup stats list-table column renderer.'
    );

    Assertions::false(
        isset( $GLOBALS['fc_test_filters']['manage_fc-popup_posts_columns'] ),
        'Stats should no longer register a popup stats list-table column.'
    );

    Assertions::true(
        isset( $GLOBALS['fc_test_filters']['post_row_actions'] ),
        'Stats should register a popup row-action filter.'
    );

    $popup = new WP_Post();
    $popup->ID = 42;
    $popup->post_type = FOOCONVERT_CPT_POPUP;

    $actions = array(
        'edit'               => '<a>Edit</a>',
        'inline hide-if-no-js' => '<a>Quick Edit</a>',
        'trash'              => '<a>Trash</a>',
        'view'               => '<a>View</a>',
    );

    $updated = $stats->add_stats_row_action( $actions, $popup );

    Assertions::same(
        array( 'edit', 'inline hide-if-no-js', 'fooconvert_stats', 'trash', 'view' ),
        array_keys( $updated ),
        'View Stats should be inserted after Quick Edit and before Trash.'
    );

    Assertions::same(
        '<a href="admin.php?page=fooconvert-popup-stats&post_id=42">View Stats</a>',
        $updated['fooconvert_stats'],
        'The popup row action should link to the popup stats page.'
    );

    $post = new WP_Post();
    $post->ID = 100;
    $post->post_type = 'post';

    Assertions::same(
        $actions,
        $stats->add_stats_row_action( $actions, $post ),
        'Non-popup row actions should remain unchanged.'
    );

    echo "stats-row-actions: ok\n";
}
