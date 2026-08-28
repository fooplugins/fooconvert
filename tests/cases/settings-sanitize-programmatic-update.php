<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Admin\FooFields\SettingsPage;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    define( 'ABSPATH', __DIR__ );

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Admin/FooFields/Base.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Admin/FooFields/Container.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Admin/FooFields/SettingsPage.php';

    class FC_Test_Settings_Page extends SettingsPage {
        public function __construct() {
        }

        /**
         * @return bool
         */
        function is_settings_page() {
            return false;
        }
    }

    $settings_page = new FC_Test_Settings_Page();
    $input = array(
        'hide_dashboard_panels' => array( 'recent' ),
    );

    Assertions::same(
        $input,
        $settings_page->sanitize_callback( $input ),
        'Programmatic option updates should not be replaced with settings page posted data.'
    );

    echo "settings-sanitize-programmatic-update: ok\n";
}
