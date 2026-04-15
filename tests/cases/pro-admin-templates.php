<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    $GLOBALS['fc_test_filters'] = array();

    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_test_filters'][ $hook ][] = array(
            'callback' => $callback,
            'priority' => $priority,
            'args'     => $accepted_args,
        );
    }

    function __( string $text, string $domain = '' ): string {
        return $text;
    }

    if ( !defined( 'FOOCONVERT_ASSETS_URL' ) ) {
        define( 'FOOCONVERT_ASSETS_URL', 'https://example.test/assets/' );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Admin/Templates/Init.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Admin/Init.php';

    new \FooPlugins\FooConvert\Pro\Admin\Init();

    Assertions::true(
        isset( $GLOBALS['fc_test_filters']['fooconvert_editor_variations-fc-bar'] ),
        'PRO admin init should register the bar template filter.'
    );

    Assertions::true(
        isset( $GLOBALS['fc_test_filters']['fooconvert_editor_variations-fc-flyout'] ),
        'PRO admin init should register the flyout template filter.'
    );

    Assertions::true(
        isset( $GLOBALS['fc_test_filters']['fooconvert_editor_variations-fc-popup'] ),
        'PRO admin init should register the popup template filter.'
    );

    Assertions::same(
        1,
        count( $GLOBALS['fc_test_filters']['fooconvert_editor_variations-fc-bar'] ),
        'The bar template filter should be registered once.'
    );

    Assertions::same(
        1,
        count( $GLOBALS['fc_test_filters']['fooconvert_editor_variations-fc-flyout'] ),
        'The flyout template filter should be registered once.'
    );

    Assertions::same(
        1,
        count( $GLOBALS['fc_test_filters']['fooconvert_editor_variations-fc-popup'] ),
        'The popup template filter should be registered once.'
    );

    $bar_template = require dirname( __DIR__, 2 ) . '/pro/includes/Admin/Templates/bars/almost_free_shipping_bar.php';
    $flyout_template = require dirname( __DIR__, 2 ) . '/pro/includes/Admin/Templates/flyouts/add_to_cart_unlock.php';
    $cart_idle_template = require dirname( __DIR__, 2 ) . '/pro/includes/Admin/Templates/overlays/cart_idle_rescue.php';
    $checkout_exit_template = require dirname( __DIR__, 2 ) . '/pro/includes/Admin/Templates/overlays/checkout_exit_save.php';
    $high_intent_template = require dirname( __DIR__, 2 ) . '/pro/includes/Admin/Templates/overlays/high_intent_offer.php';

    foreach ( array(
        $bar_template,
        $flyout_template,
        $cart_idle_template,
        $checkout_exit_template,
        $high_intent_template,
    ) as $template ) {
        Assertions::same(
            2,
            $template['attributes']['settings']['trigger']['version'] ?? 0,
            'Each PRO template should use the normalized trigger schema.'
        );

        Assertions::same(
            array( 'block' ),
            $template['scope'] ?? array(),
            'Each PRO template should remain a block-scoped variation.'
        );
    }

    Assertions::same(
        'bar__almost_free_shipping',
        $bar_template['name'],
        'The bar template should expose the expected variation name.'
    );

    Assertions::same(
        'flyout__add_to_cart_unlock',
        $flyout_template['attributes']['template'],
        'The flyout template should expose the expected template slug.'
    );

    Assertions::same(
        'popup__checkout_exit_save',
        $checkout_exit_template['name'],
        'The checkout exit template should expose the expected variation name.'
    );

    echo "pro-admin-templates: ok\n";
}
