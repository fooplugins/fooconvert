<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Pro\Experiments\Admin {
    class Init {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }
}

namespace FooPlugins\FooConvert\Pro\Experiments {
    class Experiment {
        /** @var int */
        public static $instances = 0;

        /**
         * @return self
         */
        public static function instance(): self {
            self::$instances++;

            return new self();
        }
    }

    class Resolver {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }

    class Automation {
        /** @var int */
        public static $instances = 0;

        public function __construct() {
            self::$instances++;
        }
    }
}

namespace {
    use FooPlugins\FooConvert\Pro\Experiments\Admin\Init as AdminInit;
    use FooPlugins\FooConvert\Pro\Experiments\Automation;
    use FooPlugins\FooConvert\Pro\Experiments\Experiment;
    use FooPlugins\FooConvert\Pro\Experiments\Init;
    use FooPlugins\FooConvert\Pro\Experiments\Resolver;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    /** @var array<string,array<int,array{callback:mixed,priority:int,args:int}>> */
    $GLOBALS['fc_test_actions'] = array();

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
        return true;
    }

    require_once dirname( __DIR__ ) . '/support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Experiments/Init.php';

    new Init();

    Assertions::same( 1, Experiment::$instances, 'Experiment::instance() should still run immediately.' );
    Assertions::same( 1, Resolver::$instances, 'Resolver should still be initialized immediately.' );
    Assertions::same( 1, Automation::$instances, 'Automation should still be initialized immediately.' );
    Assertions::same( 0, AdminInit::$instances, 'Admin init should be deferred until the init hook runs.' );
    Assertions::true( isset( $GLOBALS['fc_test_actions']['init'] ), 'Experiments admin bootstrap should register an init hook.' );

    $callbacks = array_values(
        array_filter(
            $GLOBALS['fc_test_actions']['init'],
            static function ( array $registration ): bool {
                return $registration['priority'] === 20;
            }
        )
    );

    Assertions::same( 1, count( $callbacks ), 'Experiments admin bootstrap should register exactly one init callback at priority 20.' );

    $callback = $callbacks[0]['callback'];
    $callback();

    Assertions::same( 1, AdminInit::$instances, 'Admin init should run when the deferred init callback executes.' );
}
