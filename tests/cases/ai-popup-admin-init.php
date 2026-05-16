<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Admin {
    class Stats {
        public function __construct() {}
    }

    class Dashboard {
        public function __construct() {}
    }

    class ContainerManager {
        public function __construct() {}
    }

    class Settings {
        public function __construct() {}
    }

    class LeadsMenu {
        public function __construct() {}
    }

    class BrandContext {
        public function __construct() {
            $GLOBALS['fc_ai_admin_init_brand_context'] = ( $GLOBALS['fc_ai_admin_init_brand_context'] ?? 0 ) + 1;
        }
    }
}

namespace FooPlugins\FooConvert\AI\PopupBuilder {
    class Admin {
        public function __construct() {
            $GLOBALS['fc_ai_admin_init_builder'] = ( $GLOBALS['fc_ai_admin_init_builder'] ?? 0 ) + 1;
        }
    }
}

namespace {
    use FooPlugins\FooConvert\Admin\Init;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
    }

    require_once dirname( __DIR__ ) . '/support/Assertions.php';

    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_ai_admin_init_actions'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
    }

    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_ai_admin_init_filters'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
    }

    function fooconvert_fs() {
        return new class {
            public function is_not_paying(): bool {
                return false;
            }
        };
    }

    require_once dirname( __DIR__, 2 ) . '/includes/Admin/Init.php';

    new Init();

    Assertions::same(
        1,
        $GLOBALS['fc_ai_admin_init_builder'] ?? 0,
        'The free admin init should initialize the AI popup builder admin menu.'
    );

    Assertions::same(
        1,
        $GLOBALS['fc_ai_admin_init_brand_context'] ?? 0,
        'The free admin init should initialize the Brand Context admin tab.'
    );

    fwrite( STDOUT, "ai-popup-admin-init: ok\n" );
}
