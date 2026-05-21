<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Admin\Dashboard;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    define( 'ABSPATH', __DIR__ );

    /**
     * Exception used to intercept JSON responses from Ajax callbacks.
     */
    class FC_Dashboard_Json_Response extends RuntimeException {
        /** @var bool */
        public $success;

        /** @var mixed */
        public $data;

        /**
         * @param bool $success Whether the response is successful.
         * @param mixed $data Response payload.
         */
        public function __construct( bool $success, $data ) {
            parent::__construct( 'JSON response sent.' );

            $this->success = $success;
            $this->data    = $data;
        }
    }

    /** @var array<string,mixed> */
    $GLOBALS['fc_options'] = array();
    $GLOBALS['fc_update_option_result'] = false;

    /**
     * @param string $text
     * @param string|null $domain
     * @return string
     */
    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    function wp_unslash( $value ) {
        return $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    function sanitize_text_field( $value ): string {
        return is_scalar( $value ) ? trim( (string) $value ) : '';
    }

    /**
     * @param mixed $key
     * @return string
     */
    function sanitize_key( $key ): string {
        return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', (string) $key ) ?? '' );
    }

    /**
     * @param string $option Option name.
     * @param mixed $default Default value.
     * @return mixed
     */
    function get_option( string $option, $default = false ) {
        return array_key_exists( $option, $GLOBALS['fc_options'] ) ? $GLOBALS['fc_options'][ $option ] : $default;
    }

    /**
     * @param string $option Option name.
     * @param mixed $value Option value.
     * @return bool
     */
    function update_option( string $option, $value ): bool {
        $GLOBALS['fc_options'][ $option ] = $value;

        return (bool) $GLOBALS['fc_update_option_result'];
    }

    /**
     * @param mixed $data Response data.
     * @return void
     * @throws FC_Dashboard_Json_Response Always throws to halt the Ajax callback.
     */
    function wp_send_json_success( $data ): void {
        throw new FC_Dashboard_Json_Response( true, $data );
    }

    /**
     * @param mixed $data Response data.
     * @return void
     * @throws FC_Dashboard_Json_Response Always throws to halt the Ajax callback.
     */
    function wp_send_json_error( $data ): void {
        throw new FC_Dashboard_Json_Response( false, $data );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/constants.php';
    require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/Admin/Dashboard.php';

    $_POST['panel'] = 'recent';

    $dashboard = ( new ReflectionClass( Dashboard::class ) )->newInstanceWithoutConstructor();

    try {
        $dashboard->hide_panel();
    } catch ( FC_Dashboard_Json_Response $response ) {
        Assertions::true(
            $response->success,
            'Hiding a dashboard panel should send a successful JSON response.'
        );

        Assertions::same(
            array( 'recent' => 'recent' ),
            $GLOBALS['fc_options'][ FOOCONVERT_OPTION_DATA ]['hide_dashboard_panels'] ?? null,
            'Hiding a dashboard panel should persist the panel key in the checkbox list settings shape.'
        );

        echo "dashboard-hide-panel-persistence: ok\n";
        return;
    }

    throw new RuntimeException( 'The hide panel callback did not send a JSON response.' );
}
