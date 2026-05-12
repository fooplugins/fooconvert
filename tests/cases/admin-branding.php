<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Admin\Dashboard;
    use FooPlugins\FooConvert\Admin\Init as AdminInit;
    use FooPlugins\FooConvert\FooConvert;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( !defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ );
    }

    /** @var array<int,array{0:string,1:string,2:string,3:string,4:string}> */
    $GLOBALS['menu'] = array();

    /** @var array<string,array<int,array{0:string,1:string,2:string,3:string}>> */
    $GLOBALS['submenu'] = array();

    /**
     * @param string $text
     * @param string|null $domain
     * @return string
     */
    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param mixed $callback
     * @param string $icon_url
     * @param int|null $position
     * @return string
     */
    function add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = null, string $icon_url = '', ?int $position = null ): string {
        $GLOBALS['menu'][] = array( $menu_title, $capability, $menu_slug, $page_title, $icon_url );

        return 'toplevel_page_' . $menu_slug;
    }

    /**
     * @param string|null $parent_slug
     * @param string $page_title
     * @param string $menu_title
     * @param string $capability
     * @param string $menu_slug
     * @param mixed $callback
     * @param int|null $position
     * @return string
     */
    function add_submenu_page( ?string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $callback = null, ?int $position = null ): string {
        if ( $parent_slug !== null ) {
            $GLOBALS['submenu'][ $parent_slug ][] = array( $menu_title, $capability, $menu_slug, $page_title );
        }

        return $parent_slug . '_page_' . $menu_slug;
    }

    /**
     * @param string $hook
     * @return void
     */
    function do_action( string $hook ): void {}

    /**
     * @param string $hook
     * @param mixed $value
     * @return mixed
     */
    function apply_filters( string $hook, $value ) {
        return $value;
    }

    /**
     * @param string $post_type
     * @return object|null
     */
    function get_post_type_object( string $post_type ) {
        if ( $post_type !== FOOCONVERT_CPT_POPUP ) {
            return null;
        }

        return (object) array(
            'labels' => (object) array(
                'add_new_item' => 'Add New Popup',
            ),
            'cap'    => (object) array(
                'create_posts' => 'edit_posts',
            ),
        );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    if ( !defined( 'FOOCONVERT_SLUG' ) ) {
        define( 'FOOCONVERT_SLUG', 'fooconvert' );
    }
    require_once dirname( __DIR__, 2 ) . '/includes/Admin/Init.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Admin/Dashboard.php';
    require_once dirname( __DIR__, 2 ) . '/includes/FooConvert.php';

    $admin = ( new ReflectionClass( AdminInit::class ) )->newInstanceWithoutConstructor();
    $admin->register_menu();

    Assertions::same(
        array( 'FooConvert', 'manage_options', FOOCONVERT_MENU_SLUG, 'FooConvert', 'dashicons-format-chat' ),
        $GLOBALS['menu'][0] ?? null,
        'Top-level admin menu should be labeled FooConvert and use a popup-friendly icon without changing its slug.'
    );

    $dashboard = ( new ReflectionClass( Dashboard::class ) )->newInstanceWithoutConstructor();
    $dashboard->register_menu();

    Assertions::same(
        array( 'Dashboard', 'manage_options', FOOCONVERT_MENU_SLUG, 'Popup Dashboard' ),
        $GLOBALS['submenu'][ FOOCONVERT_MENU_SLUG ][1] ?? null,
        'Dashboard submenu should keep a generic menu label and use the Popup Dashboard page title.'
    );

    Assertions::true(
        strpos(
            file_get_contents( dirname( __DIR__, 2 ) . '/includes/Admin/Views/dashboard.php' ),
            "esc_html_e( 'Popup Dashboard', 'fooconvert' )"
        ) !== false,
        'Dashboard view should render a visible Popup Dashboard heading.'
    );

    $GLOBALS['submenu'][ FOOCONVERT_MENU_SLUG ] = array(
        array( 'Dashboard', 'manage_options', FOOCONVERT_MENU_SLUG, 'Dashboard' ),
        array( 'Settings', 'manage_options', 'fooconvert-settings', 'FooConvert Settings' ),
        array( 'Popups', 'edit_posts', 'edit.php?post_type=' . FOOCONVERT_CPT_POPUP, 'Popups' ),
        array( 'Add New Popup', 'edit_posts', 'post-new.php?post_type=' . FOOCONVERT_CPT_POPUP, 'Add New Popup' ),
        array( 'Leads', 'manage_options', 'fooconvert-leads', 'Leads' ),
    );

    $admin->reorder_menu();

    Assertions::same(
        array(
            'edit.php?post_type=' . FOOCONVERT_CPT_POPUP,
            'post-new.php?post_type=' . FOOCONVERT_CPT_POPUP,
            FOOCONVERT_MENU_SLUG,
            'fooconvert-settings',
            'fooconvert-leads',
        ),
        array_column( $GLOBALS['submenu'][ FOOCONVERT_MENU_SLUG ], 2 ),
        'Submenu order should be Popups, Add New Popup, Dashboard, then existing items.'
    );

    Assertions::same(
        array( 'Popups', 'Add New Popup', 'Dashboard' ),
        array_slice( array_column( $GLOBALS['submenu'][ FOOCONVERT_MENU_SLUG ], 0 ), 0, 3 ),
        'First three submenu labels should be agency-friendly.'
    );

    $plugin = ( new ReflectionClass( FooConvert::class ) )->newInstanceWithoutConstructor();
    $categories = $plugin->register_block_category(
        array(
            array(
                'slug'  => 'widgets',
                'title' => 'Widgets',
            ),
        )
    );

    Assertions::same(
        'fooconvert',
        $categories[0]['slug'] ?? '',
        'Block category slug should remain unchanged for compatibility.'
    );

    Assertions::same(
        'Popup Blocks (FooConvert)',
        $categories[0]['title'] ?? '',
        'Block category title should include the FooConvert brand.'
    );

    Assertions::same(
        'widgets',
        $categories[1]['slug'] ?? '',
        'Existing block categories should be preserved after the popup category.'
    );
}
