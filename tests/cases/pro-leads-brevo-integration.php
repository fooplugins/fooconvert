<?php
declare(strict_types=1);

namespace {
    use FooPlugins\FooConvert\Pro\Leads\Integrations\BrevoIntegration;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( !defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ );
    }

    if ( !class_exists( 'WP_Error', false ) ) {
        class WP_Error {
            /** @var string */
            private $message;

            public function __construct( string $code = '', string $message = '' ) {
                $this->message = $message;
            }

            public function get_error_message(): string {
                return $this->message;
            }
        }
    }

    class FC_Brevo_Json_Response extends Exception {
        /** @var bool */
        public $success;

        /** @var mixed */
        public $data;

        /**
         * @param bool  $success Whether this is a success response.
         * @param mixed $data Response data.
         */
        public function __construct( bool $success, $data ) {
            parent::__construct( 'JSON response sent.' );

            $this->success = $success;
            $this->data = $data;
        }
    }

    $GLOBALS['fc_brevo_settings'] = array();
    $GLOBALS['fc_brevo_post_requests'] = array();
    $GLOBALS['fc_brevo_get_requests'] = array();
    $GLOBALS['fc_brevo_get_responses'] = array();
    $GLOBALS['fc_brevo_post_response'] = array(
        'response' => array( 'code' => 201 ),
        'body'     => '{"id":123}',
    );
    $GLOBALS['fc_brevo_get_response'] = array(
        'response' => array( 'code' => 200 ),
        'body'     => '{}',
    );
    $GLOBALS['fc_brevo_saved_errors'] = array();
    $GLOBALS['fc_brevo_updated_options'] = array();
    $GLOBALS['fc_brevo_current_user_can'] = true;

    /**
     * @param string $key Setting key.
     * @param mixed  $default Default value.
     * @return mixed
     */
    function fooconvert_get_setting( string $key, $default = false ) {
        return array_key_exists( $key, $GLOBALS['fc_brevo_settings'] )
            ? $GLOBALS['fc_brevo_settings'][ $key ]
            : $default;
    }

    /**
     * @param string $integration Integration slug.
     * @param string $error_message Error message.
     * @return void
     */
    function fooconvert_lead_integration_save_error( string $integration, string $error_message ): void {
        $GLOBALS['fc_brevo_saved_errors'][] = array(
            'integration' => $integration,
            'message'     => $error_message,
        );
    }

    /**
     * @param mixed $value Value to inspect.
     * @return bool
     */
    function is_wp_error( $value ): bool {
        return $value instanceof WP_Error;
    }

    /**
     * @param string $url URL.
     * @param array  $args HTTP args.
     * @return array|WP_Error
     */
    function wp_remote_post( string $url, array $args ) {
        $GLOBALS['fc_brevo_post_requests'][] = array(
            'url'  => $url,
            'args' => $args,
        );

        return $GLOBALS['fc_brevo_post_response'];
    }

    /**
     * @param string $url URL.
     * @param array  $args HTTP args.
     * @return array|WP_Error
     */
    function wp_remote_get( string $url, array $args ) {
        $GLOBALS['fc_brevo_get_requests'][] = array(
            'url'  => $url,
            'args' => $args,
        );

        if ( !empty( $GLOBALS['fc_brevo_get_responses'] ) ) {
            return array_shift( $GLOBALS['fc_brevo_get_responses'] );
        }

        return $GLOBALS['fc_brevo_get_response'];
    }

    /**
     * @param array|WP_Error $response HTTP response.
     * @return int
     */
    function wp_remote_retrieve_response_code( $response ): int {
        return isset( $response['response']['code'] ) ? (int) $response['response']['code'] : 0;
    }

    /**
     * @param array|WP_Error $response HTTP response.
     * @return string
     */
    function wp_remote_retrieve_body( $response ): string {
        return isset( $response['body'] ) ? (string) $response['body'] : '';
    }

    /**
     * @param mixed $data Data to encode.
     * @return string
     */
    function wp_json_encode( $data ): string {
        return (string) json_encode( $data );
    }

    /**
     * @param string $email Email address.
     * @return string
     */
    function sanitize_email( string $email ): string {
        return trim( $email );
    }

    /**
     * @param string $email Email address.
     * @return bool
     */
    function is_email( string $email ): bool {
        return false !== filter_var( $email, FILTER_VALIDATE_EMAIL );
    }

    /**
     * @param string $text Text.
     * @return string
     */
    function sanitize_text_field( string $text ): string {
        return trim( strip_tags( $text ) );
    }

    /**
     * @param string $text Text.
     * @return string
     */
    function __( string $text ): string {
        return $text;
    }

    /**
     * @param string $hook Hook name.
     * @param mixed  $value Filter value.
     * @return mixed
     */
    function apply_filters( string $hook, $value ) {
        return $value;
    }

    /**
     * @param string $capability Capability.
     * @return bool
     */
    function current_user_can( string $capability ): bool {
        return $GLOBALS['fc_brevo_current_user_can'];
    }

    /**
     * @param string $option Option name.
     * @param mixed  $value Option value.
     * @return bool
     */
    function update_option( string $option, $value ): bool {
        $GLOBALS['fc_brevo_updated_options'][ $option ] = $value;
        return true;
    }

    /**
     * @param mixed $data Response data.
     * @return void
     * @throws FC_Brevo_Json_Response Always throws to halt the Ajax callback.
     */
    function wp_send_json_error( $data ): void {
        throw new FC_Brevo_Json_Response( false, $data );
    }

    /**
     * @param mixed $data Response data.
     * @return void
     * @throws FC_Brevo_Json_Response Always throws to halt the Ajax callback.
     */
    function wp_send_json_success( $data ): void {
        throw new FC_Brevo_Json_Response( true, $data );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Leads/Integrations/BaseIntegration.php';
    require_once dirname( __DIR__, 2 ) . '/pro/includes/Leads/Integrations/BrevoIntegration.php';

    $integration = new BrevoIntegration();

    Assertions::false(
        $integration->send_lead( array( 'email' => 'jane@example.com', 'name' => 'Jane Doe' ) ),
        'Disabled Brevo integration should not send leads.'
    );

    Assertions::same(
        array(),
        $GLOBALS['fc_brevo_post_requests'],
        'Disabled Brevo integration should not make HTTP requests.'
    );

    $GLOBALS['fc_brevo_settings'] = array(
        'brevo_enabled' => true,
        'brevo_api_key' => 'brevo-api-key',
        'brevo_list_id' => '42',
    );

    Assertions::true(
        $integration->send_lead( array( 'email' => ' jane@example.com ', 'name' => 'Jane Doe' ) ),
        'Enabled Brevo integration should accept a successful contact response.'
    );

    Assertions::same(
        'https://api.brevo.com/v3/contacts',
        $GLOBALS['fc_brevo_post_requests'][0]['url'],
        'Brevo leads should be sent to the contacts endpoint.'
    );

    Assertions::same(
        'brevo-api-key',
        $GLOBALS['fc_brevo_post_requests'][0]['args']['headers']['api-key'],
        'Brevo requests should authenticate with the api-key header.'
    );

    $payload = json_decode( $GLOBALS['fc_brevo_post_requests'][0]['args']['body'], true );

    Assertions::same(
        array(
            'email'         => 'jane@example.com',
            'listIds'       => array( 42 ),
            'updateEnabled' => true,
            'attributes'    => array(
                'FNAME' => 'Jane',
                'LNAME' => 'Doe',
            ),
        ),
        $payload,
        'Brevo contact payload should include the selected list, upsert flag, and supported name attributes.'
    );

    $GLOBALS['fc_brevo_post_response'] = array(
        'response' => array( 'code' => 204 ),
        'body'     => '',
    );

    Assertions::true(
        $integration->send_lead( array( 'email' => 'jane@example.com', 'name' => 'Jane Doe' ) ),
        'Brevo integration should accept 204 responses for existing contacts updated by updateEnabled.'
    );

    $GLOBALS['fc_brevo_saved_errors'] = array();
    $GLOBALS['fc_brevo_post_response'] = array(
        'response' => array( 'code' => 400 ),
        'body'     => '{"message":"Invalid API key"}',
    );

    Assertions::false(
        $integration->send_lead( array( 'email' => 'jane@example.com', 'name' => 'Jane Doe' ) ),
        'Brevo integration should fail on non-success responses.'
    );

    Assertions::same(
        array(
            array(
                'integration' => 'brevo',
                'message'     => 'Invalid API key',
            ),
        ),
        $GLOBALS['fc_brevo_saved_errors'],
        'Brevo integration should persist readable API error messages.'
    );

    Assertions::true(
        $integration->test_connection(),
        'Brevo connection test should pass on a 200 account response.'
    );

    Assertions::same(
        'https://api.brevo.com/v3/account',
        $GLOBALS['fc_brevo_get_requests'][0]['url'],
        'Brevo connection tests should call the account endpoint.'
    );

    $GLOBALS['fc_brevo_get_requests'] = array();
    $GLOBALS['fc_brevo_get_responses'] = array(
        array(
            'response' => array( 'code' => 200 ),
            'body'     => '{"count":51,"lists":[{"id":7,"name":"Newsletter"}]}',
        ),
        array(
            'response' => array( 'code' => 200 ),
            'body'     => '{"count":51,"lists":[{"id":8,"name":"Customers"}]}',
        ),
    );

    $reflection = new ReflectionMethod( $integration, 'get_lists_from_api' );
    $reflection->setAccessible( true );

    Assertions::same(
        array(
            array(
                'id'   => 7,
                'name' => 'Newsletter',
            ),
            array(
                'id'   => 8,
                'name' => 'Customers',
            ),
        ),
        $reflection->invoke( $integration, 'brevo-api-key' ),
        'Brevo list retrieval should simplify paginated API list objects for settings choices.'
    );

    Assertions::same(
        'https://api.brevo.com/v3/contacts/lists?limit=50&offset=0&sort=asc',
        $GLOBALS['fc_brevo_get_requests'][0]['url'],
        'Brevo list retrieval should request the first list page.'
    );

    Assertions::same(
        'https://api.brevo.com/v3/contacts/lists?limit=50&offset=50&sort=asc',
        $GLOBALS['fc_brevo_get_requests'][1]['url'],
        'Brevo list retrieval should request subsequent list pages while count has not been satisfied.'
    );

    $GLOBALS['fc_brevo_get_requests'] = array();
    $GLOBALS['fc_brevo_current_user_can'] = false;

    try {
        $integration->get_brevo_lists();
        Assertions::false( true, 'Brevo list Ajax callback should stop when the user lacks permission.' );
    } catch ( FC_Brevo_Json_Response $response ) {
        Assertions::false(
            $response->success,
            'Brevo list Ajax callback should send an error when the user lacks permission.'
        );
    }

    Assertions::same(
        array(),
        $GLOBALS['fc_brevo_get_requests'],
        'Brevo list Ajax callback should not call Brevo when the user lacks permission.'
    );

    echo "pro-leads-brevo-integration: ok\n";
}
