<?php
declare(strict_types=1);

/**
 * Minimal test runner for the PHP display-rules smoke tests.
 *
 * Each test case is executed in a separate PHP process so it can define its own
 * WordPress function stubs without colliding with other cases.
 */

$cases = array(
    __DIR__ . '/cases/display-rules-core.php',
    __DIR__ . '/cases/display-rules-woocommerce.php',
    __DIR__ . '/cases/admin-branding.php',
    __DIR__ . '/cases/experiments-admin-init.php',
    __DIR__ . '/cases/experiments-settings-popup-column.php',
    __DIR__ . '/cases/pro-init-woocommerce-bootstrap.php',
    __DIR__ . '/cases/woocommerce-cart-state.php',
    __DIR__ . '/cases/woocommerce-coupon-search.php',
    __DIR__ . '/cases/popup-type-core.php',
    __DIR__ . '/cases/ai-popup-support.php',
    __DIR__ . '/cases/ai-popup-connection-detection.php',
    __DIR__ . '/cases/ai-popup-admin-init.php',
    __DIR__ . '/cases/ai-popup-admin-page.php',
    __DIR__ . '/cases/ai-popup-settings-route.php',
    __DIR__ . '/cases/ai-popup-blueprint.php',
    __DIR__ . '/cases/ai-popup-abilities.php',
    __DIR__ . '/cases/ai-popup-activity-log.php',
    __DIR__ . '/cases/ai-popup-invalid-response.php',
    __DIR__ . '/cases/ai-popup-settings-retry.php',
    __DIR__ . '/cases/ai-popup-streaming-context.php',
    __DIR__ . '/cases/ai-popup-streaming-autoload.php',
    __DIR__ . '/cases/ai-popup-media.php',
    __DIR__ . '/cases/ai-popup-background.php',
    __DIR__ . '/cases/ai-popup-generated-image.php',
    __DIR__ . '/cases/ai-popup-submit-background.php',
    __DIR__ . '/cases/ai-popup-save.php',
    __DIR__ . '/cases/ai-popup-load-existing.php',
    __DIR__ . '/cases/top-performers-pro-options.php',
    __DIR__ . '/cases/top-performers-sales-fallback.php',
    __DIR__ . '/cases/top-performers-trash-filter.php',
    __DIR__ . '/cases/demo-content-pro-top-performers.php',
    __DIR__ . '/cases/dashboard-panel-recent-empty.php',
    __DIR__ . '/cases/dashboard-panel-recent-trash-filter.php',
    __DIR__ . '/cases/confetti-block.php',
    __DIR__ . '/cases/free-shipping-progress-block.php',
    __DIR__ . '/cases/pro-admin-templates.php',
    __DIR__ . '/cases/pro-dashboard-sales-trash-filter.php',
    __DIR__ . '/cases/sales-attribution-woocommerce.php',
    __DIR__ . '/cases/stats-row-actions.php',
    __DIR__ . '/cases/fonts-frontend-enqueue.php',
    __DIR__ . '/cases/pro-leads-brevo-integration.php',
);

$failures = 0;

foreach ( $cases as $case ) {
    $command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $case );

    echo 'Running ' . basename( $case ) . "...\n";
    passthru( $command, $exit_code );

    if ( $exit_code !== 0 ) {
        $failures++;
    }
}

if ( $failures > 0 ) {
    fwrite( STDERR, sprintf( "%d test case(s) failed.\n", $failures ) );
    exit( 1 );
}

echo "All PHP tests passed.\n";
