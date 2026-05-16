<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Brand\Manager as BrandManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adds the reusable Brand Context admin surface.
 */
class BrandContext {

    /**
     * Registers hooks.
     */
    public function __construct() {
        add_filter( 'fooconvert_admin_settings', array( $this, 'add_settings_tab' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Adds the standalone Brand Context tab to the main FooConvert settings page.
     *
     * @param array<string,mixed> $settings Existing settings tabs.
     * @return array<string,mixed>
     */
    public function add_settings_tab( array $settings ): array {
        $settings['brand_context'] = array(
            'id'     => 'brand_context',
            'label'  => __( 'Brand Context', 'fooconvert' ),
            'icon'   => 'dashicons-admin-appearance',
            'order'  => 35,
            'fields' => array(
                'brand_context_editor' => array(
                    'id'    => 'brand_context_editor',
                    'order' => 10,
                    'type'  => 'html',
                    'label' => __( 'Brand Context', 'fooconvert' ),
                    'html'  => $this->get_open_button_html(),
                    'desc'  => __( 'Store reusable brand colors, typography, spacing, and button styling for popup tools.', 'fooconvert' ),
                ),
            ),
        );

        return $settings;
    }

    /**
     * Enqueues the standalone Brand Context modal launcher on the main settings page.
     *
     * @param string $hook Current admin hook.
     * @return void
     */
    public function enqueue_assets( string $hook ): void {
        if ( ! $this->is_settings_page( $hook ) ) {
            return;
        }

        $asset_path = FOOCONVERT_ASSETS_PATH . 'admin/brand-context/index.asset.php';
        $asset      = file_exists( $asset_path )
            ? require $asset_path
            : array(
                'dependencies' => array( 'wp-api-fetch', 'wp-components', 'wp-dom-ready', 'wp-element', 'wp-i18n' ),
                'version'      => FOOCONVERT_VERSION,
            );
        $asset['dependencies'] = ScriptDependencies::prepare( $asset['dependencies'] );

        wp_enqueue_style(
            'fooconvert-brand-context',
            FOOCONVERT_ASSETS_URL . 'admin/brand-context/index.css',
            array( 'wp-components' ),
            $asset['version']
        );

        wp_enqueue_script(
            'fooconvert-brand-context',
            FOOCONVERT_ASSETS_URL . 'admin/brand-context/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_add_inline_script(
            'fooconvert-brand-context',
            'window.wpApiSettings = Object.assign( window.wpApiSettings || {}, ' . wp_json_encode(
                array(
                    'root'  => esc_url_raw( get_rest_url() ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                )
            ) . ' );',
            'before'
        );

        wp_add_inline_script(
            'fooconvert-brand-context',
            'window.FC_BRAND_CONTEXT = ' . wp_json_encode( self::get_config() ) . ';',
            'before'
        );
    }

    /**
     * Returns Brand Context config for admin JavaScript consumers.
     *
     * @return array<string,mixed>
     */
    public static function get_config(): array {
        return array(
            'api'       => array(
                'brandPath'        => '/fooconvert/v1/brand-context',
                'extractBrandPath' => '/fooconvert/v1/brand-context/extract',
            ),
            'restRoot'  => esc_url_raw( get_rest_url() ),
            'restNonce' => wp_create_nonce( 'wp_rest' ),
            'brand'     => array(
                'savedBrand'    => BrandManager::get_saved_brand(),
                'hasSavedBrand' => BrandManager::has_saved_brand(),
                'defaultBrand'  => BrandManager::get_default_brand(),
            ),
        );
    }

    /**
     * Checks whether the current request is for the main FooConvert settings page.
     *
     * @param string $hook Current admin hook.
     * @return bool
     */
    private function is_settings_page( string $hook ): bool {
        if ( 'fooconvert_page_fooconvert-settings' === $hook ) {
            return true;
        }

        if ( function_exists( 'get_current_screen' ) ) {
            $screen = get_current_screen();
            if ( is_object( $screen ) && isset( $screen->id ) && false !== strpos( (string) $screen->id, 'fooconvert-settings' ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Builds the settings-page button that opens the Brand Context modal.
     *
     * @return string
     */
    private function get_open_button_html(): string {
        return sprintf(
            '<button type="button" class="button" data-fc-brand-context-open>%s</button>',
            esc_html__( 'Open Brand Context', 'fooconvert' )
        );
    }
}
