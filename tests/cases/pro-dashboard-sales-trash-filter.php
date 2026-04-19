<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Data {
    class Query {
        /**
         * @param int $limit
         * @return array<int,array<string,mixed>>
         */
        public static function get_recent_sales( int $limit = 10 ): array {
            return $GLOBALS['fc_recent_sales_rows'] ?? array();
        }

        /**
         * @param int $limit
         * @return array<int,array<string,mixed>>
         */
        public static function get_sales_totals_by_popup( int $limit = 10 ): array {
            return $GLOBALS['fc_sales_totals_rows'] ?? array();
        }

        /**
         * @param int $post_id
         * @param int $days
         * @return array<int,array<string,mixed>>
         */
        public static function get_popup_sales( int $post_id, int $days = 7 ): array {
            return array();
        }
    }
}

namespace {
    use FooPlugins\FooConvert\Pro\Analytics\Sales;
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
            public $post_status = 'publish';
        }
    }

    /** @var array<int,WP_Post> */
    $GLOBALS['fc_test_posts'] = array();

    /** @var array<int,array<string,mixed>> */
    $GLOBALS['fc_recent_sales_rows'] = array();

    /** @var array<int,array<string,mixed>> */
    $GLOBALS['fc_sales_totals_rows'] = array();

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
    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}

    /**
     * @param string $hook
     * @param mixed $callback
     * @param int $priority
     * @param int $accepted_args
     * @return void
     */
    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {}

    /**
     * @return bool
     */
    function is_admin(): bool {
        return false;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    function maybe_unserialize( $value ) {
        return $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    function sanitize_text_field( $value ): string {
        return is_string( $value ) ? $value : '';
    }

    /**
     * @param string $type
     * @param bool $gmt
     * @return string
     */
    function current_time( string $type, bool $gmt = false ): string {
        return '2026-04-19 12:00:00';
    }

    /**
     * @param string $value
     * @param string $format
     * @return string
     */
    function get_date_from_gmt( string $value, string $format ): string {
        return $value;
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
     * @param int $post_id
     * @return WP_Post|null
     */
    function get_post( int $post_id ) {
        return $GLOBALS['fc_test_posts'][ $post_id ] ?? null;
    }

    /**
     * @param WP_Post|int $thing
     * @return bool
     */
    function fooconvert_is_dashboard_popup_post( $thing ): bool {
        $post = $thing instanceof WP_Post ? $thing : get_post( (int) $thing );

        return $post instanceof WP_Post
            && $post->post_type === FOOCONVERT_CPT_POPUP
            && $post->post_status !== 'trash';
    }

    /**
     * @param mixed $post
     * @return string
     */
    function fooconvert_get_popup_title( $post ): string {
        return $post instanceof WP_Post ? $post->post_title : '';
    }

    /**
     * @param mixed $post
     * @return string
     */
    function fooconvert_get_popup_type_label( $post ): string {
        return 'Popup';
    }

    /**
     * @param int $post_id
     * @return string
     */
    function fooconvert_admin_url_popup_stats( int $post_id ): string {
        return 'https://example.com/wp-admin/admin.php?page=fooconvert-popup-stats&post_id=' . $post_id;
    }

    /**
     * @param mixed $amount
     * @param string|null $currency
     * @return string
     */
    function fooconvert_format_revenue( $amount, $currency = null ): string {
        $currency = is_string( $currency ) && $currency !== '' ? $currency : 'USD';

        return sprintf( '%s %.2f', $currency, (float) $amount );
    }

    /**
     * @return bool
     */
    function fooconvert_is_woocommerce_active(): bool {
        return false;
    }

    /**
     * @param int $order_id
     * @return string
     */
    function fooconvert_get_order_admin_edit_url( int $order_id ): string {
        return '';
    }

    if ( !defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Analytics/Sales.php';

    $active = new WP_Post();
    $active->ID = 5101;
    $active->post_type = FOOCONVERT_CPT_POPUP;
    $active->post_title = 'Revenue popup';
    $active->post_status = 'publish';

    $trashed = new WP_Post();
    $trashed->ID = 5102;
    $trashed->post_type = FOOCONVERT_CPT_POPUP;
    $trashed->post_title = 'Trashed revenue popup';
    $trashed->post_status = 'trash';

    $GLOBALS['fc_test_posts'][5101] = $active;
    $GLOBALS['fc_test_posts'][5102] = $trashed;

    $GLOBALS['fc_recent_sales_rows'] = array(
        array(
            'post_id'     => 5101,
            'event_value' => 49.95,
            'extra_data'  => array(
                'order_currency' => 'USD',
                'order_status'   => 'processing',
            ),
            'timestamp'   => '2026-04-19 10:00:00',
        ),
        array(
            'post_id'     => 5102,
            'event_value' => 149.95,
            'extra_data'  => array(
                'order_currency' => 'USD',
                'order_status'   => 'processing',
            ),
            'timestamp'   => '2026-04-19 11:00:00',
        ),
    );

    $GLOBALS['fc_sales_totals_rows'] = array(
        array(
            'post_id'     => 5101,
            'sale_count'  => 1,
            'total_sales' => 49.95,
        ),
        array(
            'post_id'     => 5102,
            'sale_count'  => 3,
            'total_sales' => 149.95,
        ),
    );

    $sales = new Sales();
    $reflection = new \ReflectionClass( $sales );

    $recent_method = $reflection->getMethod( 'get_recent_sales_rows' );
    $recent_method->setAccessible( true );
    $recent_rows = $recent_method->invoke( $sales );

    Assertions::same(
        1,
        count( $recent_rows ),
        'The dashboard recent sales rows should skip trashed popup events.'
    );

    $totals_method = $reflection->getMethod( 'get_sales_totals_by_popup_rows' );
    $totals_method->setAccessible( true );
    $totals_rows = $totals_method->invoke( $sales );

    Assertions::same(
        1,
        count( $totals_rows ),
        'The dashboard popup sales totals should skip trashed popups.'
    );

    Assertions::same(
        5101,
        $totals_rows[0]['post_id'] ?? 0,
        'The dashboard popup sales totals should preserve the published popup row.'
    );

    echo "pro-dashboard-sales-trash-filter: ok\n";
}
