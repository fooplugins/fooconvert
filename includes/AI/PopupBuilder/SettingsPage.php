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
                    'label'       => __( 'Override Model', 'fooconvert' ),
                    'placeholder' => __( 'Optional custom model name', 'fooconvert' ),
                    'desc'        => __( 'When set, chat requests ask the AI client to prefer this model.', 'fooconvert' ),
                ),
                FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS => array(
                    'id'          => FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS,
                    'order'       => 50,
                    'type'        => 'textarea',
                    'label'       => __( 'Disabled Params', 'fooconvert' ),
                    'placeholder' => "temperature\nresponse_format",
                    'desc'        => __( 'One parameter per line or comma-separated. Listed optional parameters are not sent with AI chat requests.', 'fooconvert' ),
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
     * Registers the AI popup builder settings REST route.
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
                        'disabledParams'     => array(
                            'type' => 'array',
                        ),
                        'disabledParamsText' => array(
                            'type' => 'string',
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
                'disabledParams'     => $request->get_param( 'disabledParams' ),
                'disabledParamsText' => $request->get_param( 'disabledParamsText' ),
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
     * Checks whether the current user can manage AI popup builder settings.
     *
     * @return bool
     */
    public function can_manage_settings(): bool {
        return current_user_can( 'manage_options' );
    }
}
