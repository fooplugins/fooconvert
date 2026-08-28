<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds and exposes AI popup builder settings.
 */
class SettingsPage {

    /**
     * Whether hooks have already been registered for the current request.
     *
     * @var bool
     */
    private static bool $hooks_registered = false;

    /**
     * Registers hooks.
     */
    public function __construct() {
        if ( self::$hooks_registered ) {
            return;
        }

        self::$hooks_registered = true;

        if ( function_exists( 'is_admin' ) && is_admin() ) {
            add_filter( 'fooconvert_admin_settings', array( $this, 'add_settings_tab' ) );
        }

        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    /**
     * Adds the AI popup builder tab to the main FooConvert settings page.
     *
     * @param array<string,mixed> $settings Existing settings tabs.
     * @return array<string,mixed>
     */
    public function add_settings_tab( array $settings ): array {
        $settings['ai_popup_builder'] = array(
            'id'     => 'ai_popup_builder',
            'label'  => __( 'AI Popup Builder', 'fooconvert' ),
            'icon'   => 'dashicons-format-chat',
            'order'  => 40,
            'fields' => array(
                FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL => array(
                    'id'          => FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL,
                    'order'       => 10,
                    'type'        => 'text',
                    'label'       => __( 'Override Text Model', 'fooconvert' ),
                    'placeholder' => __( 'provider/model-name', 'fooconvert' ),
                    'desc'        => __( 'When set, text requests force this provider/model through the AI client.', 'fooconvert' ),
                ),
                FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL => array(
                    'id'          => FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL,
                    'order'       => 20,
                    'type'        => 'text',
                    'label'       => __( 'Override Image Model', 'fooconvert' ),
                    'placeholder' => __( 'provider/model-name', 'fooconvert' ),
                    'desc'        => __( 'When set, image generation requests force this provider/model through the AI client.', 'fooconvert' ),
                ),
                FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS => array(
                    'id'          => FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS,
                    'order'       => 50,
                    'type'        => 'textarea',
                    'label'       => __( 'Disabled Params', 'fooconvert' ),
                    'placeholder' => "temperature\noutput_compression",
                    'desc'        => __( 'One optional model/provider request parameter per line or comma-separated. Required popup builder capabilities such as tools and response_format are always kept enabled.', 'fooconvert' ),
                ),
                FOOCONVERT_SETTING_AI_POPUP_BUILDER_OPTIMIZE_IMAGE_OUTPUT => array(
                    'id'                  => FOOCONVERT_SETTING_AI_POPUP_BUILDER_OPTIMIZE_IMAGE_OUTPUT,
                    'order'               => 55,
                    'type'                => 'checkbox',
                    'label'               => __( 'Optimize Generated Images', 'fooconvert' ),
                    'default'             => 'on',
                    'desc'                => __( 'Request WebP output, compression, and opaque backgrounds for generated popup images. Disable this if your AI image provider rejects output_format, output_compression, or background.', 'fooconvert' ),
                    'before_input_render' => array( $this, 'render_checkbox_off_value' ),
                    'value_decoder'       => array( $this, 'decode_optimize_image_output_field' ),
                    'value_encoder'       => array( $this, 'encode_optimize_image_output_field' ),
                ),
                FOOCONVERT_SETTING_AI_POPUP_BUILDER_TIMEOUT => array(
                    'id'      => FOOCONVERT_SETTING_AI_POPUP_BUILDER_TIMEOUT,
                    'order'   => 30,
                    'type'    => 'number',
                    'label'   => __( 'Timeout', 'fooconvert' ),
                    'default' => FOOCONVERT_AI_POPUP_BUILDER_TIMEOUT_DEFAULT,
                    'min'     => 1,
                    'step'    => 1,
                    'desc'    => __( 'Maximum time in seconds to wait for an AI chat response.', 'fooconvert' ),
                ),
                FOOCONVERT_SETTING_AI_POPUP_BUILDER_MAX_TOOL_CALLS => array(
                    'id'      => FOOCONVERT_SETTING_AI_POPUP_BUILDER_MAX_TOOL_CALLS,
                    'order'   => 40,
                    'type'    => 'number',
                    'label'   => __( 'Max Tool Calls', 'fooconvert' ),
                    'default' => FOOCONVERT_AI_POPUP_BUILDER_MAX_TOOL_CALLS_DEFAULT,
                    'min'     => 1,
                    'step'    => 1,
                    'desc'    => __( 'Maximum number of AI tool-call rounds allowed while building one popup response. Increase this if complex prompts stop with the tool-call limit error.', 'fooconvert' ),
                ),
            ),
        );

        return $settings;
    }

    /**
     * Renders a hidden fallback so an unchecked default-on checkbox can save off.
     *
     * @param mixed $field FooFields field instance.
     * @return void
     */
    public function render_checkbox_off_value( $field ): void {
        if ( ! is_object( $field ) || ! isset( $field->name ) ) {
            return;
        }

        printf(
            '<input type="hidden" name="%s" value="off" />',
            esc_attr( $field->name )
        );
    }

    /**
     * Decodes the saved optimize-image setting for the FooFields checkbox.
     *
     * @param mixed $value Saved value.
     * @return string
     */
    public function decode_optimize_image_output_field( $value ): string {
        return Settings::sanitize_bool( $value, true ) ? 'on' : '';
    }

    /**
     * Encodes the posted optimize-image checkbox value for storage.
     *
     * @param mixed $value Posted value.
     * @return bool
     */
    public function encode_optimize_image_output_field( $value ): bool {
        return Settings::sanitize_bool( $value, true );
    }

    /**
     * Registers the AI popup builder settings REST routes.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route(
            'fooconvert/v1',
            '/ai-popup-builder/settings',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'handle_get_settings' ),
                    'permission_callback' => array( $this, 'can_manage_settings' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'handle_save_settings' ),
                    'permission_callback' => array( $this, 'can_manage_settings' ),
                    'args'                => array(
                        'overrideModel'      => array(
                            'type' => 'string',
                        ),
                        'overrideImageModel' => array(
                            'type' => 'string',
                        ),
                        'disabledParams'     => array(
                            'type' => 'array',
                        ),
                        'disabledParamsText' => array(
                            'type' => 'string',
                        ),
                        'optimizeImageOutput' => array(
                            'type' => 'boolean',
                        ),
                        'timeout'            => array(
                            'type' => 'integer',
                        ),
                        'maxToolCalls'       => array(
                            'type' => 'integer',
                        ),
                        'selectedBlockNames' => array(
                            'type' => 'array',
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            'fooconvert/v1',
            '/ai-popup-builder/settings/test-model',
            array(
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'handle_test_model' ),
                    'permission_callback' => array( $this, 'can_manage_settings' ),
                    'args'                => array(
                        'type'  => array(
                            'type'     => 'string',
                            'required' => true,
                            'enum'     => array( 'text', 'image' ),
                        ),
                        'model' => array(
                            'type'     => 'string',
                            'required' => true,
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Returns current AI popup builder settings.
     *
     * @return WP_REST_Response
     */
    public function handle_get_settings(): WP_REST_Response {
        return new WP_REST_Response(
            array(
                'settings' => Settings::to_response(),
            )
        );
    }

    /**
     * Saves AI popup builder settings.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response
     */
    public function handle_save_settings( WP_REST_Request $request ): WP_REST_Response {
        $settings = Settings::save(
            array(
                'overrideModel'      => $request->get_param( 'overrideModel' ),
                'overrideImageModel' => $request->get_param( 'overrideImageModel' ),
                'disabledParams'     => $request->get_param( 'disabledParams' ),
                'disabledParamsText' => $request->get_param( 'disabledParamsText' ),
                'optimizeImageOutput' => $request->get_param( 'optimizeImageOutput' ),
                'timeout'            => $request->get_param( 'timeout' ),
                'maxToolCalls'       => $request->get_param( 'maxToolCalls' ),
                'selectedBlockNames' => $request->get_param( 'selectedBlockNames' ),
            )
        );

        return new WP_REST_Response(
            array(
                'settings' => array_merge(
                    Settings::to_response( $settings ),
                    array(
                        'canManage' => true,
                    )
                ),
            )
        );
    }

    /**
     * Tests whether a configured current model can be forced with using_model().
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|\WP_Error
     */
    public function handle_test_model( WP_REST_Request $request ) {
        $type  = 'image' === $request->get_param( 'type' ) ? 'image' : 'text';
        $model = Settings::sanitize_model( $request->get_param( 'model' ) );
        $test  = Settings::test_model_override( $model );

        if ( is_wp_error( $test ) ) {
            return $this->format_model_test_error( $test, $model );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'type'    => $type,
                'model'   => $test['model'],
                'provider' => $test['provider'],
                'name'    => $test['name'],
                'message' => sprintf(
                    /* translators: %s: AI model override value. */
                    __( 'Model test passed for "%s".', 'fooconvert' ),
                    $test['model']
                ),
            )
        );
    }

    /**
     * Formats model test errors for the settings UI without hiding details.
     *
     * @param \WP_Error $error Original model test error.
     * @param string    $model Tested model.
     * @return \WP_Error
     */
    private function format_model_test_error( \WP_Error $error, string $model ): \WP_Error {
        $code = $error->get_error_code();
        $data = is_array( $error->get_error_data() ) ? $error->get_error_data() : array();
        $data['details'] = $error->get_error_message();

        if ( 'fooconvert_ai_popup_builder_missing_model_override' === $code ) {
            return new \WP_Error(
                $code,
                __( 'Enter a provider/model-name before testing.', 'fooconvert' ),
                $data
            );
        }

        if ( 'fooconvert_ai_popup_builder_invalid_model_override' === $code ) {
            return new \WP_Error(
                $code,
                __( 'Use provider/model-name format before testing this model.', 'fooconvert' ),
                $data
            );
        }

        return new \WP_Error(
            $code,
            sprintf(
                /* translators: %s: AI model override value. */
                __( 'Could not test "%s". Check that the connector is active and the model name is available.', 'fooconvert' ),
                $model
            ),
            $data
        );
    }

    /**
     * Checks whether the current user can manage AI popup builder settings.
     *
     * @return bool
     */
    public function can_manage_settings(): bool {
        return current_user_can( 'manage_options' );
    }
}
