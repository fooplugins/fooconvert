<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Pro\Admin {
    class Init {}
}

namespace FooPlugins\FooConvert\Pro {
    class Retention {}
    class EventsFilter {}
}

namespace FooPlugins\FooConvert\Pro\Blocks {
    class ApplyCoupon {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }

    class FreeShippingProgress {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }

    class FreeShippingText {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }

    class FreeShippingBar {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }

    class Confetti {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }
}

namespace FooPlugins\FooConvert\Pro\Experiments {
    class Init {}
}

namespace FooPlugins\FooConvert\Pro\Leads {
    class Init {}
}

namespace FooPlugins\FooConvert\Pro\Analytics {
    class Metrics {}
    class Sales {}
    class RecentActivity {}
}

namespace FooPlugins\FooConvert\Pro\WooCommerce {
    class CartState {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }

    class CouponSearch {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }

    class Triggers {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }

    class Sales {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }
}

namespace FooPlugins\FooConvert\Pro\DisplayRules {
    class WooCommerce {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }
}

namespace FooPlugins\FooConvert {
    class Blocks {
        /** @var array<int,mixed> */
        public $registered = array();

        /**
         * @param mixed $block Block instance.
         * @return void
         */
        public function register( $block ): void {
            $this->registered[] = $block;
        }
    }

    class FooConvert {
        /** @var ?FooConvert */
        private static $instance = null;

        /** @var Blocks */
        public $blocks;

        public function __construct() {
            $this->blocks = new Blocks();
        }

        /**
         * @return FooConvert
         */
        public static function plugin(): FooConvert {
            if ( self::$instance === null ) {
                self::$instance = new FooConvert();
            }

            return self::$instance;
        }

        /**
         * @return void
         */
        public static function reset(): void {
            self::$instance = null;
        }
    }
}

namespace {
    use FooPlugins\FooConvert\FooConvert;
    use FooPlugins\FooConvert\Pro\Blocks\ApplyCoupon;
    use FooPlugins\FooConvert\Pro\Blocks\Confetti;
    use FooPlugins\FooConvert\Pro\Blocks\FreeShippingBar;
    use FooPlugins\FooConvert\Pro\Blocks\FreeShippingProgress;
    use FooPlugins\FooConvert\Pro\Blocks\FreeShippingText;
    use FooPlugins\FooConvert\Pro\DisplayRules\WooCommerce as DisplayRulesWooCommerce;
    use FooPlugins\FooConvert\Pro\Init;
    use FooPlugins\FooConvert\Pro\WooCommerce\CartState;
    use FooPlugins\FooConvert\Pro\WooCommerce\CouponSearch;
    use FooPlugins\FooConvert\Pro\WooCommerce\Sales as WooCommerceSales;
    use FooPlugins\FooConvert\Pro\WooCommerce\Triggers as WooCommerceTriggers;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    /** @var array<string,array<int,array{callback:mixed,priority:int,args:int}>> */
    $GLOBALS['fc_test_actions'] = array();
    $GLOBALS['fc_test_wp_scripts'] = null;

    if ( !class_exists( 'WP_Dependencies', false ) ) {
        class WP_Dependencies {
            /** @var array<string,mixed> */
            public $registered = array();
        }
    }

    if ( !class_exists( 'WP_Scripts', false ) ) {
        class WP_Scripts extends WP_Dependencies {}
    }

    if ( !class_exists( ' _WP_Dependency', false ) ) {
        class _WP_Dependency {
            /** @var array<int,string> */
            public $deps = array();

            /**
             * @param array<int,string> $deps Script dependencies.
             */
            public function __construct( array $deps = array() ) {
                $this->deps = $deps;
            }
        }
    }

    /**
     * @param string $hook Hook name.
     * @param mixed $callback Hook callback.
     * @param int $priority Hook priority.
     * @param int $accepted_args Accepted args count.
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
     * @return bool
     */
    function is_admin(): bool {
        return false;
    }

    /**
     * @return WP_Scripts
     */
    function wp_scripts(): WP_Scripts {
        if ( !( $GLOBALS['fc_test_wp_scripts'] instanceof WP_Scripts ) ) {
            $GLOBALS['fc_test_wp_scripts'] = new WP_Scripts();
        }

        return $GLOBALS['fc_test_wp_scripts'];
    }

    /**
     * @return bool
     */
    function fooconvert_is_woocommerce_active(): bool {
        return class_exists( 'WooCommerce', false );
    }

    /**
     * Reset hook and instance state between scenarios.
     *
     * @return void
     */
    function reset_pro_init_bootstrap_state(): void {
        $GLOBALS['fc_test_actions'] = array();
        $GLOBALS['fc_test_wp_scripts'] = null;
        FooConvert::reset();
        ApplyCoupon::$instances = 0;
        Confetti::$instances = 0;
        FreeShippingProgress::$instances = 0;
        FreeShippingText::$instances = 0;
        FreeShippingBar::$instances = 0;
        CartState::$instances = 0;
        CouponSearch::$instances = 0;
        DisplayRulesWooCommerce::$instances = 0;
        WooCommerceTriggers::$instances = 0;
        WooCommerceSales::$instances = 0;
    }

    /**
     * Execute recorded callbacks for a hook.
     *
     * @param string $hook Hook name.
     * @return void
     */
    function run_hook( string $hook ): void {
        $callbacks = $GLOBALS['fc_test_actions'][ $hook ] ?? array();
        usort(
            $callbacks,
            static function ( array $left, array $right ): int {
                return $left['priority'] <=> $right['priority'];
            }
        );

        foreach ( $callbacks as $entry ) {
            call_user_func( $entry['callback'] );
        }
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Init.php';

    reset_pro_init_bootstrap_state();

    $deferred = new Init();

    Assertions::same(
        0,
        DisplayRulesWooCommerce::$instances + WooCommerceTriggers::$instances + WooCommerceSales::$instances,
        'WooCommerce runtime services should not boot before WooCommerce is loaded.'
    );

    Assertions::true(
        isset( $GLOBALS['fc_test_actions']['plugins_loaded'] ),
        'Init should register a deferred plugins_loaded callback when WooCommerce is unavailable at construction time.'
    );

    Assertions::same(
        1,
        ApplyCoupon::$instances,
        'Init should always instantiate and register the PRO apply coupon block.'
    );

    Assertions::same(
        1,
        Confetti::$instances,
        'Init should always instantiate and register the PRO confetti block.'
    );

    Assertions::same(
        1,
        FreeShippingProgress::$instances,
        'Init should always instantiate and register the PRO free shipping progress block.'
    );

    Assertions::same(
        1,
        FreeShippingText::$instances,
        'Init should always instantiate and register the PRO free shipping text block.'
    );

    Assertions::same(
        1,
        FreeShippingBar::$instances,
        'Init should always instantiate and register the PRO free shipping bar block.'
    );

    Assertions::same(
        1,
        CartState::$instances,
        'Init should always bootstrap the public cart state REST service.'
    );

    Assertions::same(
        1,
        CouponSearch::$instances,
        'Init should always bootstrap the coupon search REST service.'
    );

    class_alias( \stdClass::class, 'WooCommerce' );

    run_hook( 'plugins_loaded' );

    Assertions::same(
        1,
        DisplayRulesWooCommerce::$instances,
        'Display rules should boot once WooCommerce becomes available on plugins_loaded.'
    );

    Assertions::same(
        1,
        WooCommerceTriggers::$instances,
        'WooCommerce triggers should boot once WooCommerce becomes available on plugins_loaded.'
    );

    Assertions::same(
        1,
        WooCommerceSales::$instances,
        'WooCommerce sales attribution should boot once WooCommerce becomes available on plugins_loaded.'
    );

    run_hook( 'plugins_loaded' );

    Assertions::same(
        1,
        DisplayRulesWooCommerce::$instances,
        'Deferred WooCommerce initialization should be idempotent.'
    );

    Assertions::same(
        1,
        WooCommerceTriggers::$instances,
        'Deferred WooCommerce trigger initialization should be idempotent.'
    );

    Assertions::same(
        1,
        WooCommerceSales::$instances,
        'Deferred WooCommerce sales initialization should be idempotent.'
    );

    reset_pro_init_bootstrap_state();

    $immediate = new Init();

    Assertions::same(
        1,
        DisplayRulesWooCommerce::$instances,
        'WooCommerce runtime services should boot immediately when WooCommerce is already loaded.'
    );

    Assertions::same(
        1,
        WooCommerceTriggers::$instances,
        'WooCommerce triggers should boot immediately when WooCommerce is already loaded.'
    );

    Assertions::same(
        1,
        WooCommerceSales::$instances,
        'WooCommerce sales attribution should boot immediately when WooCommerce is already loaded.'
    );

    Assertions::same(
        1,
        ApplyCoupon::$instances,
        'Each Init instance should register the apply coupon block exactly once.'
    );

    Assertions::same(
        1,
        Confetti::$instances,
        'Each Init instance should register the confetti block exactly once.'
    );

    Assertions::same(
        1,
        FreeShippingProgress::$instances,
        'Each Init instance should register the free shipping progress block exactly once.'
    );

    Assertions::same(
        1,
        FreeShippingText::$instances,
        'Each Init instance should register the free shipping text block exactly once.'
    );

    Assertions::same(
        1,
        FreeShippingBar::$instances,
        'Each Init instance should register the free shipping bar block exactly once.'
    );

    Assertions::same(
        1,
        CartState::$instances,
        'Each Init instance should bootstrap the public cart state REST service exactly once.'
    );

    Assertions::same(
        1,
        CouponSearch::$instances,
        'Each Init instance should bootstrap the coupon search REST service exactly once.'
    );

    Assertions::same(
        0,
        count( $GLOBALS['fc_test_actions']['plugins_loaded'] ?? array() ),
        'Init should not register a deferred plugins_loaded callback when WooCommerce is already available.'
    );

    Assertions::true(
        isset( $GLOBALS['fc_test_actions']['wp_enqueue_scripts'] ),
        'Init should register a wp_enqueue_scripts hook for WooCommerce script dependency compatibility.'
    );

    $scripts = wp_scripts();
    $scripts->registered['wc-order-attribution'] = new \_WP_Dependency( array( 'sourcebuster-js' ) );
    $scripts->registered['wc-blocks-data-store'] = new \_WP_Dependency();

    run_hook( 'wp_enqueue_scripts' );

    Assertions::true(
        in_array( 'wc-blocks-data-store', $scripts->registered['wc-order-attribution']->deps, true ),
        'Init should add wc-blocks-data-store as a dependency of wc-order-attribution when the blocks data store handle is registered.'
    );

    echo "pro-init-woocommerce-bootstrap: ok\n";
}
