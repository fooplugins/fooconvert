<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

use FooPlugins\FooConvert\AI\Abilities;
use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\Catalog;
use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\Schema;
use FooPlugins\FooConvert\AI\PopupBuilder\Media\Attachments as PopupMedia;
use FooPlugins\FooConvert\Admin\ScriptDependencies;
use FooPlugins\FooConvert\Brand\Manager as BrandManager;

defined( 'ABSPATH' ) || exit;

class Admin {

    /**
     * Admin hook suffix returned by add_submenu_page().
     *
     * @var string
     */
    private string $hook_suffix = '';

    /**
     * Registers the AI popup builder admin screen.
     */
    public function __construct() {
        add_action( 'fooconvert_admin_menu_after_post_types', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_filter( 'fooconvert_admin_menu_desired_slugs', array( $this, 'filter_desired_menu_slugs' ) );
        add_filter( 'fooconvert_should_register_popup_blocks_in_admin', array( $this, 'filter_should_register_popup_blocks_in_admin' ) );
    }

    /**
     * Registers the submenu page.
     *
     * @return void
     */
    public function register_menu(): void {
        $post_type_object = get_post_type_object( FOOCONVERT_CPT_POPUP );
        $capability       = $post_type_object && isset( $post_type_object->cap->create_posts )
            ? $post_type_object->cap->create_posts
            : 'manage_options';

        $hook_suffix = add_submenu_page(
            FOOCONVERT_MENU_SLUG,
            __( 'AI Popup Builder', 'fooconvert' ),
            __( 'AI Popup Builder', 'fooconvert' ),
            $capability,
            FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER,
            array( $this, 'render_page' )
        );

        $this->hook_suffix = is_string( $hook_suffix ) ? $hook_suffix : '';
    }

    /**
     * Enqueues assets for the AI popup builder screen.
     *
     * @param string $hook Current admin hook.
     * @return void
     */
    public function enqueue_assets( string $hook ): void {
        if ( ! $this->is_builder_screen( $hook ) ) {
            return;
        }

        $this->prime_runtime_block_registries();

        $asset_path = FOOCONVERT_ASSETS_PATH . 'admin/ai-popup-builder/index.asset.php';
        $asset      = file_exists( $asset_path )
            ? require $asset_path
            : array(
                'dependencies' => array( 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' ),
                'version'      => FOOCONVERT_VERSION,
            );
        $asset['dependencies'] = ScriptDependencies::prepare( $asset['dependencies'] );

        wp_enqueue_style(
            'fooconvert-ai-popup-builder',
            FOOCONVERT_ASSETS_URL . 'admin/ai-popup-builder/index.css',
            array( 'wp-components' ),
            $asset['version']
        );

        wp_enqueue_script(
            'fooconvert-ai-popup-builder',
            FOOCONVERT_ASSETS_URL . 'admin/ai-popup-builder/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_add_inline_script(
            'fooconvert-ai-popup-builder',
            'window.wpApiSettings = Object.assign( window.wpApiSettings || {}, ' . wp_json_encode(
                array(
                    'root'  => esc_url_raw( get_rest_url() ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                )
            ) . ' );',
            'before'
        );

        wp_add_inline_script(
            'fooconvert-ai-popup-builder',
            'window.FC_AI_POPUP_BUILDER = ' . wp_json_encode( $this->get_editor_config() ) . ';',
            'before'
        );
    }

    /**
     * Checks whether the current admin enqueue request targets the AI popup builder.
     *
     * @param string $hook Current admin hook.
     * @return bool
     */
    private function is_builder_screen( string $hook ): bool {
        if ( '' !== $this->hook_suffix && $hook === $this->hook_suffix ) {
            return true;
        }

        return Config::is_admin_page();
    }

    /**
     * Primes runtime block registries used by the AI builder catalog.
     *
     * @return void
     */
    private function prime_runtime_block_registries(): void {
        // Prime block assets so runtime block registries from FooConvert and other plugins are available to the builder.
        do_action( 'enqueue_block_assets' );

        if ( ! class_exists( '\WP_Block_Type_Registry' ) || ! fooconvert_is_woocommerce_active() ) {
            return;
        }

        $registered = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        foreach ( array_keys( $registered ) as $block_name ) {
            if ( 0 === strpos( $block_name, 'woocommerce/' ) ) {
                return;
            }
        }

        if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Package' ) || ! class_exists( '\Automattic\WooCommerce\Blocks\BlockTypesController' ) ) {
            return;
        }

        \Automattic\WooCommerce\Blocks\Package::init();

        $container = \Automattic\WooCommerce\Blocks\Package::container();
        if ( is_object( $container ) && method_exists( $container, 'get' ) ) {
            $controller = $container->get( \Automattic\WooCommerce\Blocks\BlockTypesController::class );
            if ( $controller instanceof \Automattic\WooCommerce\Blocks\BlockTypesController ) {
                $controller->register_blocks();
            }
        }
    }

    /**
     * Renders the AI popup builder container.
     *
     * @return void
     */
    public function render_page(): void {
        ?>
        <div class="wrap">
            <div id="fc-ai-popup-builder-root"></div>
        </div>
        <?php
    }

    /**
     * Builds the editor config for the AI popup builder UI.
     *
     * @return array<string,mixed>
     */
    private function get_editor_config(): array {
        $ai_client_available = Config::has_ai_client();
        $ai_connection_ready = Config::has_valid_ai_connection();
        $settings            = Settings::to_response();

        return array(
            'api'              => array(
                'chatPath'        => '/fooconvert/v1/ai-popup-builder/chat',
                'chatStreamPath'  => '/fooconvert/v1/ai-popup-builder/chat-stream',
                'savePath'        => '/fooconvert/v1/ai-popup-builder/save',
                'deleteMediaPath' => '/fooconvert/v1/ai-popup-builder/media',
                'brandPath'       => '/fooconvert/v1/brand-context',
                'settingsPath'    => '/fooconvert/v1/ai-popup-builder/settings',
                'debugResponsesPath' => '/fooconvert/v1/ai-popup-builder/debug-responses',
                'extractBrandPath' => '/fooconvert/v1/brand-context/extract',
                'loadPopupPath'    => '/fooconvert/v1/ai-popup-builder/popup',
            ),
            'restRoot'         => esc_url_raw( get_rest_url() ),
            'restNonce'        => wp_create_nonce( 'wp_rest' ),
            'wpVersion'        => Config::get_wp_version(),
            'supportsAiPopupBuilder' => Config::supports_ai_popup_builder(),
            'labels'           => array(
                FOOCONVERT_POPUP_TYPE_BAR    => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_BAR ),
                FOOCONVERT_POPUP_TYPE_FLYOUT => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_FLYOUT ),
                FOOCONVERT_POPUP_TYPE_POPUP  => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_POPUP ),
            ),
            'templates'        => Catalog::get_template_library(),
            'blockCatalog'     => Catalog::get_block_catalog(),
            'playbook'         => Catalog::get_conversion_playbook(),
            'systemPrompt'     => PromptFactory::get_default_system_instruction_preview(),
            'mediaItems'       => PopupMedia::list_generated_images( 12 ),
            'aiClientAvailable' => $ai_client_available,
            'aiClientUpgradeUrl' => admin_url( 'update-core.php' ),
            'aiClientMessage' => __( 'WP 7.0 is required for this feature to work', 'fooconvert' ),
            'aiConnectionReady' => $ai_connection_ready,
            'aiConnectionSetupUrl' => current_user_can( 'manage_options' ) ? admin_url( 'options-connectors.php' ) : '',
            'aiConnectionMessage' => __( 'AI Popup Builder chat needs a valid WordPress AI connector before it can generate popups. Go to Settings > Connectors to add or verify a connector, then reload this page.', 'fooconvert' ),
            'streamingAvailable' => $ai_connection_ready && Config::supports_streaming(),
            'imageGenerationAvailable' => $ai_connection_ready && PopupMedia::can_manage_media(),
            'canUploadMedia'   => PopupMedia::can_manage_media(),
            'models'           => array(
                'currentTextModel'  => $this->get_current_text_model( $settings ),
                'currentImageModel' => $this->get_current_image_model(),
            ),
            'abilitiesAvailable' => Abilities::wp_api_available(),
            'abilities'        => Abilities::get_allowed_abilities(),
            'brand'            => array(
                'savedBrand'    => BrandManager::get_saved_brand(),
                'hasSavedBrand' => BrandManager::has_saved_brand(),
                'defaultBrand'  => BrandManager::get_default_brand(),
            ),
            'settings'         => $settings,
            'initialPostId'    => $this->get_initial_post_id(),
            'debug'            => array(
                'enabled'        => function_exists( 'fooconvert_is_debug' ) && (bool) fooconvert_is_debug(),
                'canManage'      => function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ),
                'responseSchema' => function_exists( 'fooconvert_is_debug' ) && (bool) fooconvert_is_debug()
                    ? Schema::get_assistant_response_schema()
                    : null,
            ),
        );
    }

    /**
     * Returns the popup post ID requested for AI editing.
     *
     * @return int
     */
    private function get_initial_post_id(): int {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page context.
        return isset( $_GET['post_id'] ) ? absint( wp_unslash( $_GET['post_id'] ) ) : 0;
    }

    /**
     * Returns the model currently used for text generation when known.
     *
     * @param array<string,mixed> $settings Current AI builder settings response.
     * @return string
     */
    private function get_current_text_model( array $settings ): string {
        $override_model = $this->sanitize_model_label( $settings['overrideModel'] ?? '' );
        if ( '' !== $override_model ) {
            return $override_model;
        }

        $models = $this->get_preferred_ai_models( 'WordPress\\AI\\get_preferred_models_for_text_generation' );

        return $models[0] ?? '';
    }

    /**
     * Returns the model currently used for image generation when available.
     *
     * @return string
     */
    private function get_current_image_model(): string {
        $models = $this->get_preferred_ai_models( 'WordPress\\AI\\get_preferred_image_models' );

        return $models[0] ?? '';
    }

    /**
     * Returns sanitized preferred model names from the WordPress AI client.
     *
     * @param string $function Fully-qualified function name.
     * @return array<int,string>
     */
    private function get_preferred_ai_models( string $function ): array {
        if ( ! function_exists( $function ) ) {
            return array();
        }

        $models = call_user_func( $function );
        if ( ! is_array( $models ) ) {
            return array();
        }

        $model_names = array();
        foreach ( $models as $model ) {
            $model = $this->normalize_model_preference_label( $model );
            if ( '' !== $model ) {
                $model_names[] = $model;
            }
        }

        return array_values( array_unique( $model_names ) );
    }

    /**
     * Normalizes a WordPress AI model preference into a displayable label.
     *
     * @param mixed $model Raw model preference.
     * @return string
     */
    private function normalize_model_preference_label( $model ): string {
        if ( is_array( $model ) ) {
            $provider   = $this->sanitize_model_label( $model[0] ?? '' );
            $model_name = $this->sanitize_model_label( $model[1] ?? '' );

            if ( '' !== $provider && '' !== $model_name ) {
                return $provider . '/' . $model_name;
            }

            return '' !== $model_name ? $model_name : $provider;
        }

        return $this->sanitize_model_label( $model );
    }

    /**
     * Sanitizes a model label for display.
     *
     * @param mixed $model Raw model label.
     * @return string
     */
    private function sanitize_model_label( $model ): string {
        $model = is_scalar( $model ) ? trim( (string) $model ) : '';

        return function_exists( 'sanitize_text_field' )
            ? sanitize_text_field( $model )
            : $model;
    }

    /**
     * Adds the AI popup builder menu into the shared menu ordering.
     *
     * @param array<int,string> $desired_slugs Ordered slugs.
     * @return array<int,string>
     */
    public function filter_desired_menu_slugs( array $desired_slugs ): array {
        $desired_slugs[] = FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER;

        return array_values( array_unique( $desired_slugs ) );
    }

    /**
     * Allows popup block registration on AI builder-specific admin pages.
     *
     * @param bool $allow Whether popup block registration is already allowed.
     * @return bool
     */
    public function filter_should_register_popup_blocks_in_admin( bool $allow ): bool {
        return $allow || Config::is_admin_page();
    }
}
