<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    define( 'ABSPATH', __DIR__ );

    if ( !defined( 'MINUTE_IN_SECONDS' ) ) {
        define( 'MINUTE_IN_SECONDS', 60 );
    }

    if ( !defined( 'HOUR_IN_SECONDS' ) ) {
        define( 'HOUR_IN_SECONDS', 3600 );
    }

    $GLOBALS['fc_current_time']  = 1700000000;
    $GLOBALS['fc_modified_time'] = $GLOBALS['fc_current_time'] - ( 35 * MINUTE_IN_SECONDS );

    /**
     * @param string $text
     * @param string|null $domain
     * @return string
     */
    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @param string $single
     * @param string $plural
     * @param int $number
     * @param string|null $domain
     * @return string
     */
    function _n( string $single, string $plural, int $number, ?string $domain = null ): string {
        return $number === 1 ? $single : $plural;
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
     * @return string
     */
    function esc_html( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
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
     * @param mixed $key
     * @return string
     */
    function sanitize_key( $key ): string {
        return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', (string) $key ) ?? '' );
    }

    /**
     * @param int $number
     * @return string
     */
    function number_format_i18n( int $number ): string {
        return (string) $number;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function fooconvert_get_setting( string $key, $default = false ) {
        return $default;
    }

    /**
     * @param string $panel Dashboard panel key.
     * @return bool
     */
    function fooconvert_is_dashboard_panel_hidden( string $panel ): bool {
        return false;
    }

    /**
     * @return array<int,string>
     */
    function fooconvert_get_dashboard_popup_statuses(): array {
        return array( 'publish' );
    }

    /**
     * @param array<string,mixed> $args
     * @return array<int,object>
     */
    function get_posts( array $args ): array {
        return array(
            (object) array(
                'ID'           => 123,
                'post_title'   => 'Spring Promo With A Very Long Campaign Title That Should Be Visually Truncated',
                'post_type'    => FOOCONVERT_CPT_POPUP,
                'post_content' => '',
            ),
        );
    }

    /**
     * @param object $post
     * @return string
     */
    function fooconvert_get_popup_title( object $post ): string {
        return $post->post_title;
    }

    /**
     * @param int $post_id
     * @return string
     */
    function fooconvert_admin_url_popup_edit( int $post_id ): string {
        return 'https://example.com/wp-admin/post.php?post=' . $post_id . '&action=edit';
    }

    /**
     * @param int $post_id
     * @return string
     */
    function fooconvert_admin_url_popup_stats( int $post_id ): string {
        return 'https://example.com/wp-admin/admin.php?page=fooconvert-popup-stats&post_id=' . $post_id;
    }

    /**
     * @param object $popup
     * @return string
     */
    function fooconvert_get_popup_type_label( object $popup ): string {
        return 'Overlay';
    }

    /**
     * @param string $popup_type
     * @return string
     */
    function fooconvert_admin_url_popup_new( string $popup_type ): string {
        return 'https://example.com/wp-admin/admin.php?page=fooconvert-popup-chooser&type=' . $popup_type;
    }

    /**
     * @param string $format
     * @param bool $gmt
     * @param object|null $post
     * @return int
     */
    function get_post_modified_time( string $format = 'U', bool $gmt = false, ?object $post = null ): int {
        return (int) $GLOBALS['fc_modified_time'];
    }

    /**
     * @param string $type
     * @param bool $gmt
     * @return int
     */
    function current_time( string $type, bool $gmt = false ): int {
        return (int) $GLOBALS['fc_current_time'];
    }

    /**
     * @param int $from
     * @param int $to
     * @return string
     */
    function human_time_diff( int $from, int $to = 0 ): string {
        $diff = abs( $to - $from );

        if ( $diff >= MINUTE_IN_SECONDS && $diff < HOUR_IN_SECONDS ) {
            $minutes = max( 1, (int) round( $diff / MINUTE_IN_SECONDS ) );
            return sprintf( $minutes === 1 ? '%s minute' : '%s minutes', $minutes );
        }

        return '1 hour';
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';

    ob_start();
    require dirname( __DIR__, 2 ) . '/includes/Admin/Views/dashboard-panel-recent.php';
    $output = (string) ob_get_clean();

    Assertions::true(
        strpos( $output, '35 mins ago' ) !== false,
        'The recent dashboard panel should abbreviate minute-based relative times.'
    );

    Assertions::false(
        strpos( $output, '35 minutes ago' ) !== false,
        'The recent dashboard panel should not render the long-form minutes label.'
    );

    Assertions::true(
        strpos( $output, 'class="fooconvert-dashboard-title"' ) !== false,
        'The recent dashboard panel should wrap popup titles in the truncating title element.'
    );

    echo "dashboard-panel-recent-minute-abbreviation: ok\n";
}
