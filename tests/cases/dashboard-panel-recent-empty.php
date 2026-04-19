<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    define( 'ABSPATH', __DIR__ );

    /**
     * @param string $text
     * @param string|null $domain
     * @return string
     */
    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @param string $text
     * @param string|null $domain
     * @return void
     */
    function esc_html_e( string $text, ?string $domain = null ): void {
        echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }

    /**
     * @param string $text
     * @param string|null $domain
     * @return string
     */
    function esc_attr__( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @param string $url
     * @return string
     */
    function esc_url( string $url ): string {
        return $url;
    }

    /**
     * @param string $url
     * @return string
     */
    function admin_url( string $url = '' ): string {
        return 'https://example.com/wp-admin/' . ltrim( $url, '/' );
    }

    /**
     * @param array<string,mixed> $args
     * @param string $url
     * @return string
     */
    function add_query_arg( array $args, string $url ): string {
        return $url . '?' . http_build_query( $args );
    }

    /**
     * @param string $option
     * @param mixed $default
     * @return mixed
     */
    function get_option( string $option, $default = false ) {
        return array();
    }

    /**
     * @param array<string,mixed> $args
     * @return array<int,mixed>
     */
    function get_posts( array $args ): array {
        return array();
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';

    ob_start();
    require dirname( __DIR__, 2 ) . '/includes/Admin/Views/dashboard-panel-recent.php';
    $output = (string) ob_get_clean();

    Assertions::false(
        $output === '',
        'The recent dashboard panel should still render when there are no popups.'
    );

    Assertions::true(
        strpos( $output, 'No popups yet. Create a bar, flyout, or overlay to get started.' ) !== false,
        'The recent dashboard panel should show an empty state message when there are no popups.'
    );

    Assertions::true(
        strpos( $output, 'Add New Bar' ) !== false,
        'The recent dashboard panel should always show the Add New Bar action.'
    );

    Assertions::true(
        strpos( $output, 'Add New Flyout' ) !== false,
        'The recent dashboard panel should always show the Add New Flyout action.'
    );

    Assertions::true(
        strpos( $output, 'Add New Overlay' ) !== false,
        'The recent dashboard panel should always show the Add New Overlay action.'
    );
}
