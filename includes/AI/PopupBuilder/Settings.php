<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\Catalog;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes and persists AI popup builder request settings.
 */
class Settings {

    /**
     * Highest saved/request timeout allowed for popup-builder AI calls.
     */
    private const MAX_TIMEOUT = 120;

    /**
     * Highest saved/request tool-call loop count allowed.
     */
    private const MAX_TOOL_CALLS = 20;

    /**
     * Returns the default AI request timeout.
     *
     * @return int
     */
    public static function get_default_timeout(): int {
        return defined( 'FOOCONVERT_AI_POPUP_BUILDER_TIMEOUT_DEFAULT' )
            ? (int) FOOCONVERT_AI_POPUP_BUILDER_TIMEOUT_DEFAULT
            : 45;
    }

    /**
     * Returns the default maximum tool-call rounds.
     *
     * @return int
     */
    public static function get_default_max_tool_calls(): int {
        return defined( 'FOOCONVERT_AI_POPUP_BUILDER_MAX_TOOL_CALLS_DEFAULT' )
            ? (int) FOOCONVERT_AI_POPUP_BUILDER_MAX_TOOL_CALLS_DEFAULT
            : 10;
    }

    /**
     * Normalizes an AI request parameter name for comparison.
     *
     * @param mixed $param Raw parameter name.
     * @return string
     */
    public static function normalize_param_name( $param ): string {
        $param = is_string( $param ) ? trim( $param ) : '';
        if ( '' === $param ) {
            return '';
        }

        $param = preg_replace( '/([a-z0-9])([A-Z])/', '$1_$2', $param );
        $param = is_string( $param ) ? strtolower( $param ) : '';
        $param = preg_replace( '/[^a-z0-9_.-]+/', '_', $param );
        $param = is_string( $param ) ? trim( $param, '_-.' ) : '';

        return str_replace( '-', '_', $param );
    }

    /**
     * Sanitizes a disabled parameter payload into a unique list.
     *
     * @param mixed $value Raw disabled params.
     * @return array<int,string>
     */
    public static function sanitize_disabled_params( $value ): array {
        if ( is_string( $value ) ) {
            $items = preg_split( '/[\r\n,]+/', $value );
        } elseif ( is_array( $value ) ) {
            $items = $value;
        } else {
            $items = array();
        }

        $params = array();
        foreach ( $items as $item ) {
            $param = self::normalize_param_name( is_scalar( $item ) ? (string) $item : '' );
            if ( '' !== $param ) {
                $params[ $param ] = $param;
            }
        }

        return array_values( $params );
    }

    /**
     * Sanitizes an override model name.
     *
     * @param mixed $value Raw model.
     * @return string
     */
    public static function sanitize_model( $value ): string {
        return is_string( $value ) ? sanitize_text_field( $value ) : '';
    }

    /**
     * Sanitizes a timeout value.
     *
     * @param mixed $value Raw timeout.
     * @return int
     */
    public static function sanitize_timeout( $value ): int {
        $timeout = absint( $value );

        return $timeout > 0 ? min( self::MAX_TIMEOUT, $timeout ) : self::get_default_timeout();
    }

    /**
     * Sanitizes the max tool-call setting.
     *
     * @param mixed $value Raw max tool calls.
     * @return int
     */
    public static function sanitize_max_tool_calls( $value ): int {
        $max_tool_calls = absint( $value );

        return $max_tool_calls > 0 ? min( self::MAX_TOOL_CALLS, $max_tool_calls ) : self::get_default_max_tool_calls();
    }

    /**
     * Sanitizes selected block names.
     *
     * @param mixed $value Raw selected block names.
     * @return array<int,string>
     */
    public static function sanitize_selected_block_names( $value ): array {
        return Catalog::sanitize_selected_block_names( $value );
    }

    /**
     * Returns normalized saved settings.
     *
     * @return array<string,mixed>
     */
    public static function get(): array {
        $disabled_params = self::sanitize_disabled_params(
            fooconvert_get_setting( FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS, '' )
        );

        return array(
            'override_model'       => self::sanitize_model(
                fooconvert_get_setting( FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL, '' )
            ),
            'disabled_params'      => $disabled_params,
            'disabled_params_text' => implode( "\n", $disabled_params ),
            'selected_block_names' => self::sanitize_selected_block_names(
                fooconvert_get_setting( FOOCONVERT_SETTING_AI_POPUP_BUILDER_SELECTED_BLOCKS, array() )
            ),
            'timeout'              => self::sanitize_timeout(
                fooconvert_get_setting( FOOCONVERT_SETTING_AI_POPUP_BUILDER_TIMEOUT, self::get_default_timeout() )
            ),
            'max_tool_calls'       => self::sanitize_max_tool_calls(
                fooconvert_get_setting( FOOCONVERT_SETTING_AI_POPUP_BUILDER_MAX_TOOL_CALLS, self::get_default_max_tool_calls() )
            ),
        );
    }

    /**
     * Formats settings for JavaScript responses.
     *
     * @param array<string,mixed>|null $settings Optional normalized settings.
     * @return array<string,mixed>
     */
    public static function to_response( ?array $settings = null ): array {
        $settings = self::sanitize_payload( $settings ?? self::get() );

        return array(
            'overrideModel'       => $settings['override_model'],
            'disabledParams'      => $settings['disabled_params'],
            'disabledParamsText'  => $settings['disabled_params_text'],
            'timeout'             => $settings['timeout'],
            'timeoutDefault'      => self::get_default_timeout(),
            'maxToolCalls'        => $settings['max_tool_calls'],
            'maxToolCallsDefault' => self::get_default_max_tool_calls(),
            'selectedBlockNames'  => $settings['selected_block_names'],
            'defaultSelectedBlockNames' => Catalog::get_default_selected_block_names(),
            'canManage'           => self::can_manage_settings(),
        );
    }

    /**
     * Sanitizes a mixed settings payload.
     *
     * @param mixed $payload Raw payload.
     * @return array<string,mixed>
     */
    public static function sanitize_payload( $payload ): array {
        $current = self::get();
        if ( ! is_array( $payload ) ) {
            return $current;
        }

        $override_model = $payload['overrideModel'] ?? $payload['override_model'] ?? $current['override_model'];
        $disabled_params = $payload['disabledParams'] ?? $payload['disabled_params'] ?? null;
        if ( null === $disabled_params ) {
            $disabled_params = $payload['disabledParamsText'] ?? $payload['disabled_params_text'] ?? $current['disabled_params'];
        }
        $timeout              = $payload['timeout'] ?? $current['timeout'];
        $max_tool_calls       = $payload['maxToolCalls'] ?? $payload['max_tool_calls'] ?? $current['max_tool_calls'];
        $selected_block_names = $payload['selectedBlockNames'] ?? $payload['selected_block_names'] ?? ( $current['selected_block_names'] ?? array() );
        $disabled_params      = self::sanitize_disabled_params( $disabled_params );

        return array(
            'override_model'       => self::sanitize_model( $override_model ),
            'disabled_params'      => $disabled_params,
            'disabled_params_text' => implode( "\n", $disabled_params ),
            'selected_block_names' => self::sanitize_selected_block_names( $selected_block_names ),
            'timeout'              => self::sanitize_timeout( $timeout ),
            'max_tool_calls'       => self::sanitize_max_tool_calls( $max_tool_calls ),
        );
    }

    /**
     * Saves normalized settings.
     *
     * @param array<string,mixed> $settings Settings to save.
     * @return array<string,mixed>
     */
    public static function save( array $settings ): array {
        $settings = self::sanitize_payload( $settings );
        $option   = fooconvert_get_settings();
        if ( ! is_array( $option ) ) {
            $option = array();
        }

        $option[ FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL ] = $settings['override_model'];
        $option[ FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS ] = $settings['disabled_params_text'];
        $option[ FOOCONVERT_SETTING_AI_POPUP_BUILDER_TIMEOUT ] = $settings['timeout'];
        $option[ FOOCONVERT_SETTING_AI_POPUP_BUILDER_MAX_TOOL_CALLS ] = $settings['max_tool_calls'];
        $option[ FOOCONVERT_SETTING_AI_POPUP_BUILDER_SELECTED_BLOCKS ] = $settings['selected_block_names'];

        update_option( FOOCONVERT_OPTION_DATA, $option );

        return $settings;
    }

    /**
     * Checks whether the current user can manage persisted AI popup builder settings.
     *
     * @return bool
     */
    public static function can_manage_settings(): bool {
        return function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );
    }

    /**
     * Adds a disabled parameter to saved settings.
     *
     * @param mixed $param Raw parameter.
     * @return array<string,mixed>
     */
    public static function add_disabled_param( $param ): array {
        $settings = self::get();
        $param    = self::normalize_param_name( $param );
        if ( '' === $param ) {
            return $settings;
        }

        if ( ! self::can_manage_settings() ) {
            return $settings;
        }

        $settings['disabled_params'][]      = $param;
        $settings['disabled_params']        = self::sanitize_disabled_params( $settings['disabled_params'] );
        $settings['disabled_params_text']   = implode( "\n", $settings['disabled_params'] );

        return self::save( $settings );
    }

    /**
     * Checks whether an AI request parameter is disabled.
     *
     * @param array<string,mixed> $settings AI settings.
     * @param string              $param Parameter name.
     * @return bool
     */
    public static function is_param_disabled( array $settings, string $param ): bool {
        $lookup = self::get_disabled_param_lookup( $settings );

        foreach ( self::get_param_aliases( $param ) as $alias ) {
            if ( isset( $lookup[ $alias ] ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a disabled-parameter lookup.
     *
     * @param array<string,mixed> $settings AI settings.
     * @return array<string,bool>
     */
    public static function get_disabled_param_lookup( array $settings ): array {
        $lookup = array();
        $params = self::sanitize_disabled_params( $settings['disabled_params'] ?? array() );

        foreach ( $params as $param ) {
            foreach ( self::get_param_aliases( $param ) as $alias ) {
                $lookup[ $alias ] = true;
            }
        }

        return $lookup;
    }

    /**
     * Returns known aliases for one request parameter.
     *
     * @param string $param Parameter name.
     * @return array<int,string>
     */
    public static function get_param_aliases( string $param ): array {
        $param  = self::normalize_param_name( $param );
        $groups = array(
            array( 'temperature' ),
            array( 'response_format', 'response_mime_type', 'response_schema', 'json', 'json_schema', 'output_mime_type' ),
            array( 'tools', 'tool', 'tool_choice', 'functions', 'function_declarations', 'abilities' ),
            array( 'model', 'models' ),
            array( 'timeout', 'request_timeout', 'connect_timeout' ),
            array( 'system_instruction', 'system', 'instructions' ),
        );

        foreach ( $groups as $group ) {
            $normalized_group = array_map( array( self::class, 'normalize_param_name' ), $group );
            if ( in_array( $param, $normalized_group, true ) ) {
                return $normalized_group;
            }
        }

        return array( $param );
    }

    /**
     * Restores empty JSON schema object containers after associative decoding.
     *
     * @param mixed  $value Payload fragment.
     * @param string $current_key Current key.
     * @return mixed
     */
    public static function restore_streaming_schema_objects( $value, string $current_key = '' ) {
        if ( ! is_array( $value ) ) {
            return $value;
        }

        if ( array() === $value && in_array( $current_key, array( 'properties', '$defs', 'definitions', 'patternProperties' ), true ) ) {
            return new \stdClass();
        }

        foreach ( $value as $key => $child_value ) {
            $value[ $key ] = self::restore_streaming_schema_objects(
                $child_value,
                is_string( $key ) ? $key : ''
            );
        }

        return $value;
    }
}
