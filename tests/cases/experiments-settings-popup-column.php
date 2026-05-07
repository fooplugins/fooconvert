<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\Pro\Experiments {
    class Experiment {
        /**
         * @return self
         */
        public static function instance(): self {
            return new self();
        }
    }

    class Results {}
}

namespace FooPlugins\FooConvert\Pro\Experiments\Admin {
    class ConfigMetabox {}
}

namespace {
    use FooPlugins\FooConvert\Pro\Experiments\Admin\Init as AdminInit;
    use FooPlugins\FooConvert\Pro\Experiments\Automation;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( !class_exists( 'WP_Post', false ) ) {
        class WP_Post {}
    }

    if ( !class_exists( 'WP_Query', false ) ) {
        class WP_Query {}
    }

    /** @var array<string,mixed> */
    $GLOBALS['fc_test_settings'] = array();

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
        return true;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function fooconvert_get_setting( string $key, $default = false ) {
        return $GLOBALS['fc_test_settings'][ $key ] ?? $default;
    }

    define( 'ABSPATH', __DIR__ );

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Experiments/Automation.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Experiments/Admin/Init.php';

    $automation_reflection = new \ReflectionClass( Automation::class );
    /** @var Automation $automation */
    $automation = $automation_reflection->newInstanceWithoutConstructor();

    $settings = array(
        'general' => array(
            'id'     => 'general',
            'fields' => array(
                FOOCONVERT_SETTING_EXPERIMENT_AUTO_WINNER_EMAIL_ADMIN => array(
                    'id'    => FOOCONVERT_SETTING_EXPERIMENT_AUTO_WINNER_EMAIL_ADMIN,
                    'label' => 'Old experiment field',
                ),
                'debug' => array(
                    'id'    => 'debug',
                    'label' => 'Debug',
                ),
            ),
        ),
    );

    $updated_settings = $automation->change_settings( $settings );

    Assertions::false(
        isset( $updated_settings['general']['fields'][ FOOCONVERT_SETTING_EXPERIMENT_AUTO_WINNER_EMAIL_ADMIN ] ),
        'Experiment settings should no longer live on the General tab.'
    );

    Assertions::true(
        isset( $updated_settings['experiments'] ),
        'change_settings() should register an Experiments settings tab.'
    );

    Assertions::same(
        'Experiments',
        $updated_settings['experiments']['label'],
        'The new settings tab should use the Experiments label.'
    );

    Assertions::same(
        false,
        $updated_settings['experiments']['fields'][ FOOCONVERT_SETTING_EXPERIMENT_AUTO_WINNER_EMAIL_ADMIN ]['default'],
        'Email Admin On Auto Winner should remain unchecked by default.'
    );

    Assertions::same(
        'Show Experiments Column',
        $updated_settings['experiments']['fields'][ FOOCONVERT_SETTING_EXPERIMENT_SHOW_POPUP_COLUMN ]['label'],
        'The popup list-table toggle should be added to the Experiments tab.'
    );

    Assertions::same(
        false,
        $updated_settings['experiments']['fields'][ FOOCONVERT_SETTING_EXPERIMENT_SHOW_POPUP_COLUMN ]['default'],
        'Show Experiments Column should be unchecked by default.'
    );

    $admin_init_reflection = new \ReflectionClass( AdminInit::class );
    /** @var AdminInit $admin_init */
    $admin_init = $admin_init_reflection->newInstanceWithoutConstructor();

    $popup_columns = array(
        'cb'    => '<input type="checkbox" />',
        'title' => 'Title',
        'date'  => 'Date',
    );

    $GLOBALS['fc_test_settings'] = array();

    Assertions::same(
        $popup_columns,
        $admin_init->popup_columns( $popup_columns, 'fc-popup' ),
        'The popup experiments column should stay hidden by default.'
    );

    $GLOBALS['fc_test_settings'][ FOOCONVERT_SETTING_EXPERIMENT_SHOW_POPUP_COLUMN ] = true;

    Assertions::same(
        array(
            'cb'                 => '<input type="checkbox" />',
            'title'              => 'Title',
            'fc-popup_experiment' => 'Experiment',
            'date'               => 'Date',
        ),
        $admin_init->popup_columns( $popup_columns, 'fc-popup' ),
        'The popup experiments column should be inserted after the title when enabled.'
    );
}
