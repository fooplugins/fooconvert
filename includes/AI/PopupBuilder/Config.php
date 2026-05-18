<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * Runtime configuration checks for the AI popup builder.
 */
class Config {

    /**
     * Returns the current WordPress version as a comparable major.minor string.
     *
     * @return string
     */
    public static function get_wp_version(): string {
        global $wp_version;

        $version = is_string( $wp_version ) ? $wp_version : '';

        if ( preg_match( '/^\d+(?:\.\d+)?/', $version, $matches ) ) {
            return (string) $matches[0];
        }

        return '0';
    }

    /**
     * Returns whether the current site is running WordPress 7 or newer.
     *
     * @return bool
     */
    public static function is_wp7_or_newer(): bool {
        return version_compare( self::get_wp_version(), '7.0', '>=' );
    }

    /**
     * Returns whether the AI popup builder can be used.
     *
     * @return bool
     */
    public static function supports_ai_popup_builder(): bool {
        return self::is_wp7_or_newer();
    }

    /**
     * Returns whether the AI client function is available.
     *
     * @return bool
     */
    public static function has_ai_client(): bool {
        return self::supports_ai_popup_builder() && function_exists( 'wp_ai_client_prompt' );
    }

    /**
     * Returns whether a configured AI connection is ready.
     *
     * @return bool
     */
    public static function has_valid_ai_connection(): bool {
        if ( ! self::has_ai_client() ) {
            return false;
        }

        try {
            if ( function_exists( 'WordPress\\AI\\has_valid_ai_credentials' ) ) {
                return (bool) \WordPress\AI\has_valid_ai_credentials();
            }

            $has_configured_ai_connector = self::has_configured_ai_connector();
            if ( false === $has_configured_ai_connector ) {
                return false;
            }

            $prompt = wp_ai_client_prompt( 'Test' );
            if ( is_object( $prompt ) && is_callable( array( $prompt, 'is_supported_for_text_generation' ) ) ) {
                $supported = $prompt->is_supported_for_text_generation();

                if ( function_exists( 'is_wp_error' ) && is_wp_error( $supported ) ) {
                    return false;
                }

                return (bool) $supported;
            }
        } catch ( \Throwable $throwable ) {
            return false;
        }

        return false;
    }

    /**
     * Returns whether a WordPress AI provider connector has credentials.
     *
     * @return bool|null True/false when the Connectors API is available, null when unknown.
     */
    private static function has_configured_ai_connector(): ?bool {
        if ( ! function_exists( 'wp_get_connectors' ) ) {
            return null;
        }

        $connectors      = wp_get_connectors();
        $has_credentials = false;

        foreach ( $connectors as $connector_data ) {
            if ( ! is_array( $connector_data ) || 'ai_provider' !== ( $connector_data['type'] ?? '' ) ) {
                continue;
            }

            $auth = isset( $connector_data['authentication'] ) && is_array( $connector_data['authentication'] )
                ? $connector_data['authentication']
                : array();

            if ( 'api_key' !== ( $auth['method'] ?? '' ) || empty( $auth['setting_name'] ) || ! function_exists( 'get_option' ) ) {
                continue;
            }

            if ( '' !== get_option( $auth['setting_name'], '' ) ) {
                $has_credentials = true;
                break;
            }
        }

        if ( function_exists( 'apply_filters' ) ) {
            $has_credentials = (bool) apply_filters( 'wpai_has_ai_credentials', $has_credentials, $connectors );
        }

        return $has_credentials;
    }

    /**
     * Returns whether streaming chat can be used.
     *
     * @return bool
     */
    public static function supports_streaming(): bool {
        return self::has_ai_client() && function_exists( 'wp_ai_client_stream' );
    }

    /**
     * Checks whether the current admin request targets the AI builder page.
     *
     * @return bool
     */
    public static function is_admin_page(): bool {
        $page = '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
        if ( isset( $_GET['page'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page check.
            $page = sanitize_key( wp_unslash( $_GET['page'] ) );
        }

        return is_admin()
            && FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER === $page;
    }
}
