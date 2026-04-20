<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Components\Base {
    /**
     * Minimal stub for the component base class used by DisplayRules.
     */
    class BaseComponent {
        /**
         * Stub constructor for component compatibility in tests.
         */
        public function __construct() {}
    }
}

namespace FooPlugins\FooConvert {
    /**
     * Minimal utility stub providing the helpers used by the display-rules tests.
     */
    class Utils {
        /**
         * @param array $items
         * @param callable $callback
         * @param mixed $default
         * @return mixed
         */
        public static function array_find( array $items, callable $callback, $default = false ) {
            foreach ( $items as $item ) {
                if ( $callback( $item ) ) {
                    return $item;
                }
            }

            return $default;
        }

        /**
         * @param mixed $value
         * @param string $key
         * @return string
         */
        public static function get_string( $value, string $key ): string {
            return is_array( $value ) && isset( $value[ $key ] ) ? (string) $value[ $key ] : '';
        }

        /**
         * @param mixed $value
         * @param string $key
         * @return array
         */
        public static function get_array( $value, string $key ): array {
            return is_array( $value ) && isset( $value[ $key ] ) && is_array( $value[ $key ] ) ? $value[ $key ] : array();
        }

        /**
         * @param mixed $value
         * @param string $key
         * @param bool $default
         * @return bool
         */
        public static function get_bool( $value, string $key, bool $default = false ): bool {
            return is_array( $value ) && isset( $value[ $key ] ) ? (bool) $value[ $key ] : $default;
        }

        /**
         * @param mixed $value
         * @param string $key
         * @return int
         */
        public static function get_int( $value, string $key ): int {
            return is_array( $value ) && isset( $value[ $key ] ) ? intval( $value[ $key ] ) : 0;
        }
    }
}

namespace {
    use FooPlugins\FooConvert\DisplayRules;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    /**
     * Stub the WordPress hooks API used by the display-rules component constructor.
     *
     * @return void
     */
    function add_action( ...$args ): void {}

    /**
     * Stub translation helper.
     *
     * @param string $text The source text.
     * @return string
     */
    function __( string $text ): string {
        return $text;
    }

    /**
     * Stub translation helper with escaping.
     *
     * @param string $text The source text.
     * @return void
     */
    function esc_html_e( string $text ): void {
        echo $text;
    }

    /**
     * Stub HTML escaping helper.
     *
     * @param string $text The source text.
     * @return string
     */
    function esc_html( string $text ): string {
        return $text;
    }

    /**
     * Stub attribute escaping helper.
     *
     * @param string $text The source text.
     * @return string
     */
    function esc_attr( string $text ): string {
        return $text;
    }

    /**
     * Minimal absolute integer helper.
     *
     * @param mixed $value Value to normalize.
     * @return int
     */
    function absint( $value ): int {
        return abs( (int) $value );
    }

    /**
     * Stub capability checks used by display rules.
     *
     * @param string $capability Capability name.
     * @return bool
     */
    function current_user_can( string $capability ): bool {
        if ( $capability === 'edit_posts' ) {
            return $GLOBALS['fc_test_can_edit_posts'] ?? true;
        }

        if ( $capability === 'edit_post' ) {
            return $GLOBALS['fc_test_can_edit_post'] ?? true;
        }

        return true;
    }

    /**
     * Stub the filter API so the custom matcher path can be exercised deterministically.
     *
     * @param string $tag The filter tag.
     * @param mixed $value The initial value.
     * @param mixed ...$args Additional filter arguments.
     * @return mixed
     */
    function apply_filters( string $tag, $value, ...$args ) {
        if ( $tag === 'fooconvert_display_rules_match_locations' ) {
            $compiled_locations = $args[0] ?? array();
            return !empty( $GLOBALS['fc_test_custom_location_match'] ) && array_key_exists( 'woocommerce:is_product', $compiled_locations );
        }

        if ( $tag === 'fooconvert_display_rules_can_edit' && array_key_exists( 'fc_test_display_rules_can_edit', $GLOBALS ) ) {
            return (bool) $GLOBALS['fc_test_display_rules_can_edit'];
        }

        if ( $tag === 'fooconvert_display_rules_admin_state' && isset( $GLOBALS['fc_test_display_rules_admin_state'] ) && is_array( $GLOBALS['fc_test_display_rules_admin_state'] ) ) {
            return array_merge( $value, $GLOBALS['fc_test_display_rules_admin_state'] );
        }

        return $value;
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/DisplayRules.php';

    $display_rules = new DisplayRules();
    $reflection = new \ReflectionClass( $display_rules );
    $compile_locations = $reflection->getMethod( 'compile_locations' );
    $compile_locations->setAccessible( true );

    $compiled = $compile_locations->invoke(
        $display_rules,
        array(
            array(
                'type' => 'general:front_page',
                'data' => array(),
            ),
            array(
                'type' => 'woocommerce:is_product',
                'data' => array(),
            ),
            array(
                'type' => 'specific:product',
                'data' => array(
                    array( 'id' => 99 ),
                    array( 'id' => 105 ),
                    array( 'id' => 0 ),
                ),
            ),
        )
    );

    Assertions::same(
        array(
            'general:front_page' => true,
            'woocommerce:is_product' => true,
            'specific:product' => array( 99, 105 ),
        ),
        $compiled,
        'compile_locations() should store static non-specific rules and flatten specific IDs.'
    );

    Assertions::true(
        $display_rules->match_compiled_locations(
            array( 'specific:product' => array( 105 ) ),
            array( 'type' => 'specific:product', 'data' => 105 )
        ),
        'match_compiled_locations() should preserve exact matching for specific rules.'
    );

    $GLOBALS['fc_test_custom_location_match'] = false;

    Assertions::false(
        $display_rules->match_compiled_locations(
            array( 'woocommerce:is_product' => true ),
            array( 'type' => 'general:front_page', 'data' => null )
        ),
        'Custom rules should not match when the extension filter returns false.'
    );

    $GLOBALS['fc_test_custom_location_match'] = true;

    Assertions::true(
        $display_rules->match_compiled_locations(
            array( 'woocommerce:is_product' => true ),
            array( 'type' => 'general:front_page', 'data' => null )
        ),
        'Custom rules should match when the extension filter returns true.'
    );

    $GLOBALS['fc_test_can_edit_posts'] = true;
    $GLOBALS['fc_test_display_rules_can_edit'] = true;

    Assertions::true(
        $display_rules->auth_callback( null, null, 123 ),
        'auth_callback() should allow edits when the extension filter leaves editability enabled.'
    );

    $GLOBALS['fc_test_display_rules_can_edit'] = false;

    Assertions::false(
        $display_rules->auth_callback( null, null, 123 ),
        'auth_callback() should delegate edit locking through the display-rules can-edit filter.'
    );

    $get_admin_state = $reflection->getMethod( 'get_admin_state' );
    $get_admin_state->setAccessible( true );

    $GLOBALS['fc_test_can_edit_post'] = true;
    unset( $GLOBALS['fc_test_display_rules_admin_state'] );

    Assertions::same(
        array(
            'canEdit' => true,
            'showSummary' => true,
            'lockedMessage' => '',
        ),
        $get_admin_state->invoke( $display_rules, 123, array() ),
        'get_admin_state() should default to showing the summary for editable rows.'
    );

    $GLOBALS['fc_test_display_rules_admin_state'] = array(
        'canEdit' => false,
        'showSummary' => false,
        'lockedMessage' => 'Managed by experiment',
    );

    Assertions::same(
        array(
            'canEdit' => false,
            'showSummary' => false,
            'lockedMessage' => 'Managed by experiment',
        ),
        $get_admin_state->invoke( $display_rules, 123, array() ),
        'get_admin_state() should expose extension-controlled lock state for admin rows.'
    );

    $get_column_summary = $reflection->getMethod( 'get_column_summary' );
    $get_column_summary->setAccessible( true );

    $render_column_summary_markup = $reflection->getMethod( 'render_column_summary_markup' );
    $render_column_summary_markup->setAccessible( true );

    $render_column_app_markup = $reflection->getMethod( 'render_column_app_markup' );
    $render_column_app_markup->setAccessible( true );

    $empty_summary = $get_column_summary->invoke(
        $display_rules,
        array(
            'location' => array(),
            'exclude'  => array(),
            'users'    => array( 'general:all_users' ),
        )
    );

    Assertions::true(
        $empty_summary['isNotSet'],
        'get_column_summary() should flag the default empty state as not set.'
    );

    $empty_markup = $render_column_summary_markup->invoke( $display_rules, $empty_summary );

    Assertions::true(
        strpos( $empty_markup, 'fc-display-rules-list__summary-empty' ) !== false,
        'The fallback summary markup should render the compact not-set state.'
    );

    Assertions::false(
        strpos( $empty_markup, 'Show on' ) !== false,
        'The compact not-set state should not include the Show on label.'
    );

    $configured_summary = $get_column_summary->invoke(
        $display_rules,
        array(
            'location' => array(
                array(
                    'type' => 'general:front_page',
                    'data' => array(),
                ),
            ),
            'exclude'  => array(),
            'users'    => array( 'general:all_users' ),
        )
    );

    Assertions::false(
        $configured_summary['isNotSet'],
        'Configured display rules should not use the compact not-set state.'
    );

    $configured_markup = $render_column_summary_markup->invoke( $display_rules, $configured_summary );

    Assertions::true(
        strpos( $configured_markup, 'Show on' ) !== false,
        'Configured display rules should keep the labeled summary rows.'
    );

    $editable_app_markup = $render_column_app_markup->invoke(
        $display_rules,
        $empty_summary,
        true,
        true,
        '',
        'Spring popup'
    );

    Assertions::true(
        strpos( $editable_app_markup, 'fc-display-rules-list__summary-button' ) !== false,
        'Editable rows should render the summary button wrapper in the server fallback.'
    );

    Assertions::true(
        strpos( $editable_app_markup, 'Edit display rules' ) !== false,
        'Editable rows should render the summary action text in the server fallback.'
    );

    $locked_app_markup = $render_column_app_markup->invoke(
        $display_rules,
        $configured_summary,
        false,
        true,
        'Managed by experiment',
        'Spring popup'
    );

    Assertions::true(
        strpos( $locked_app_markup, 'fc-display-rules-list__summary-card' ) !== false,
        'Non-editable rows should render the summary card wrapper in the server fallback.'
    );

    Assertions::true(
        strpos( $locked_app_markup, 'Managed by experiment' ) !== false,
        'Locked rows should keep their lock message in the server fallback.'
    );

    echo "display-rules-core: ok\n";
}
