<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert {
    /**
     * Minimal stub satisfying the type-hint used by the WooCommerce display-rules service.
     */
    class DisplayRules {}
}

namespace {
    use FooPlugins\FooConvert\DisplayRules;
    use FooPlugins\FooConvert\Pro\DisplayRules\WooCommerce as WooCommerceDisplayRules;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    /**
     * Minimal WooCommerce marker class so class_exists( 'WooCommerce' ) succeeds.
     */
    class WooCommerce {}

    /**
     * Stub the filter API used by the WooCommerce display-rules service constructor.
     *
     * @return void
     */
    function add_filter( ...$args ): void {}

    /**
     * Translation stub for option labels.
     *
     * @param string $text The source string.
     * @param string|null $domain The text domain.
     * @return string
     */
    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @return bool
     */
    function is_shop(): bool {
        return (bool) ( $GLOBALS['fc_woo_conditions']['is_shop'] ?? false );
    }

    /**
     * @return bool
     */
    function is_cart(): bool {
        return (bool) ( $GLOBALS['fc_woo_conditions']['is_cart'] ?? false );
    }

    /**
     * @return bool
     */
    function is_checkout(): bool {
        return (bool) ( $GLOBALS['fc_woo_conditions']['is_checkout'] ?? false );
    }

    /**
     * @return bool
     */
    function is_account_page(): bool {
        return (bool) ( $GLOBALS['fc_woo_conditions']['is_account_page'] ?? false );
    }

    /**
     * @return bool
     */
    function is_product(): bool {
        return (bool) ( $GLOBALS['fc_woo_conditions']['is_product'] ?? false );
    }

    /**
     * @return bool
     */
    function is_product_tag(): bool {
        return (bool) ( $GLOBALS['fc_woo_conditions']['is_product_tag'] ?? false );
    }

    /**
     * @return bool
     */
    function is_product_category(): bool {
        return (bool) ( $GLOBALS['fc_woo_conditions']['is_product_category'] ?? false );
    }

    /**
     * @return bool
     */
    function is_woocommerce(): bool {
        return (bool) ( $GLOBALS['fc_woo_conditions']['is_woocommerce'] ?? false );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/DisplayRules/WooCommerce.php';

    $service = new WooCommerceDisplayRules();

    $locations = array(
        array(
            'group' => 'general',
            'label' => 'General',
            'options' => array(),
        ),
        array(
            'group' => 'specific_posts',
            'label' => 'Specific Posts',
            'options' => array(),
        ),
    );

    $updated_locations = $service->add_locations( $locations, 'default' );

    Assertions::same(
        'woocommerce',
        $updated_locations[1]['group'],
        'The WooCommerce group should be inserted immediately after General.'
    );

    Assertions::same(
        array(
            'woocommerce:is_shop',
            'woocommerce:is_cart',
            'woocommerce:is_checkout',
            'woocommerce:is_account_page',
            'woocommerce:is_product',
            'woocommerce:is_product_tag',
            'woocommerce:is_product_category',
            'woocommerce:is_woocommerce',
            'woocommerce:is_woocommerce_any',
        ),
        array_column( $updated_locations[1]['options'], 'value' ),
        'The WooCommerce group should expose the expected rule identifiers.'
    );

    $GLOBALS['fc_woo_conditions'] = array(
        'is_shop' => false,
        'is_cart' => false,
        'is_checkout' => false,
        'is_account_page' => false,
        'is_product' => true,
        'is_product_tag' => false,
        'is_product_category' => false,
        'is_woocommerce' => false,
    );

    Assertions::true(
        $service->match_locations(
            false,
            array( 'woocommerce:is_product' => true ),
            array( 'type' => 'general:front_page', 'data' => null ),
            new DisplayRules()
        ),
        'The product rule should delegate to is_product().'
    );

    $GLOBALS['fc_woo_conditions'] = array(
        'is_shop' => false,
        'is_cart' => true,
        'is_checkout' => false,
        'is_account_page' => false,
        'is_product' => false,
        'is_product_tag' => false,
        'is_product_category' => false,
        'is_woocommerce' => false,
    );

    Assertions::true(
        $service->match_locations(
            false,
            array( 'woocommerce:is_woocommerce_any' => true ),
            array( 'type' => 'general:front_page', 'data' => null ),
            new DisplayRules()
        ),
        'The any-WooCommerce rule should match cart, checkout, shop, and account pages in addition to Woo templates.'
    );

    $GLOBALS['fc_woo_conditions'] = array(
        'is_shop' => false,
        'is_cart' => false,
        'is_checkout' => false,
        'is_account_page' => false,
        'is_product' => false,
        'is_product_tag' => false,
        'is_product_category' => false,
        'is_woocommerce' => false,
    );

    Assertions::false(
        $service->match_locations(
            false,
            array( 'woocommerce:is_shop' => true ),
            array( 'type' => 'general:front_page', 'data' => null ),
            new DisplayRules()
        ),
        'WooCommerce rules should not match when their conditional helper returns false.'
    );

    Assertions::true(
        $service->match_locations(
            true,
            array( 'woocommerce:is_shop' => true ),
            array( 'type' => 'general:front_page', 'data' => null ),
            new DisplayRules()
        ),
        'The custom matcher should preserve an existing positive match.'
    );

    echo "display-rules-woocommerce: ok\n";
}
