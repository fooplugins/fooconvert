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
                'currentImageModel' => $this->get_current_image_model( $settings ),
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

        $resolved_model = $this->get_resolved_ai_model_label(
            'text',
            'WordPress\\AI\\get_preferred_models_for_text_generation'
        );
        if ( '' !== $resolved_model ) {
            return $resolved_model;
        }

        $models = $this->get_preferred_ai_models( 'WordPress\\AI\\get_preferred_models_for_text_generation' );

        return $models[0] ?? '';
    }

    /**
     * Returns the model currently used for image generation when available.
     *
     * @return string
     */
    private function get_current_image_model( array $settings ): string {
        $override_model = $this->sanitize_model_label( $settings['overrideImageModel'] ?? '' );
        if ( '' !== $override_model ) {
            return $override_model;
        }

        $resolved_model = $this->get_resolved_ai_model_label(
            'image',
            'WordPress\\AI\\get_preferred_image_models'
        );
        if ( '' !== $resolved_model ) {
            return $resolved_model;
        }

        $models = $this->get_preferred_ai_models( 'WordPress\\AI\\get_preferred_image_models' );

        return $models[0] ?? '';
    }

    /**
     * Returns the model the WP AI client would select from configured providers.
     *
     * @param string $capability Capability to resolve. Supports "text" and "image".
     * @param string $preference_function Fully-qualified preferred-model function name.
     * @return string
     */
    private function get_resolved_ai_model_label( string $capability, string $preference_function ): string {
        $required_classes = array(
            'WordPress\\AiClient\\AiClient',
            'WordPress\\AiClient\\Messages\\DTO\\MessagePart',
            'WordPress\\AiClient\\Messages\\DTO\\UserMessage',
            'WordPress\\AiClient\\Providers\\Models\\DTO\\ModelConfig',
            'WordPress\\AiClient\\Providers\\Models\\DTO\\ModelRequirements',
            'WordPress\\AiClient\\Providers\\Models\\Enums\\CapabilityEnum',
        );

        if ( 'image' === $capability ) {
            $required_classes[] = 'WordPress\\AiClient\\Files\\Enums\\FileTypeEnum';
        }

        foreach ( $required_classes as $class_name ) {
            if ( ! class_exists( $class_name ) ) {
                return '';
            }
        }

        try {
            $ai_client_class = 'WordPress\\AiClient\\AiClient';
            if ( ! method_exists( $ai_client_class, 'defaultRegistry' ) ) {
                return '';
            }

            $registry = call_user_func( array( $ai_client_class, 'defaultRegistry' ) );
            if ( ! is_object( $registry ) || ! method_exists( $registry, 'findModelsMetadataForSupport' ) ) {
                return '';
            }

            $requirements = $this->create_ai_model_requirements( $capability );
            if ( null === $requirements ) {
                return '';
            }

            $provider_models = $registry->findModelsMetadataForSupport( $requirements );
            if ( ! is_array( $provider_models ) ) {
                return '';
            }

            $candidates = $this->get_ai_model_candidate_labels( $provider_models );
            if ( empty( $candidates ) ) {
                return '';
            }

            foreach ( $this->get_ai_model_preference_keys( $preference_function ) as $preference_key ) {
                if ( isset( $candidates[ $preference_key ] ) ) {
                    return $candidates[ $preference_key ];
                }
            }

            $first_candidate = reset( $candidates );

            return is_string( $first_candidate ) ? $first_candidate : '';
        } catch ( \Throwable $error ) {
            return '';
        }
    }

    /**
     * Creates model requirements matching the popup builder request type.
     *
     * @param string $capability Capability to resolve. Supports "text" and "image".
     * @return object|null
     */
    private function create_ai_model_requirements( string $capability ) {
        try {
            $message_part_class   = 'WordPress\\AiClient\\Messages\\DTO\\MessagePart';
            $user_message_class   = 'WordPress\\AiClient\\Messages\\DTO\\UserMessage';
            $model_config_class   = 'WordPress\\AiClient\\Providers\\Models\\DTO\\ModelConfig';
            $requirements_class   = 'WordPress\\AiClient\\Providers\\Models\\DTO\\ModelRequirements';
            $capability_enum_class = 'WordPress\\AiClient\\Providers\\Models\\Enums\\CapabilityEnum';

            $model_config = 'image' === $capability
                ? $this->create_image_model_config( $model_config_class )
                : new $model_config_class();
            $message      = new $user_message_class( array( new $message_part_class( 'FooConvert popup builder' ) ) );
            $method       = 'image' === $capability ? 'imageGeneration' : 'textGeneration';

            if ( ! is_callable( array( $capability_enum_class, $method ) ) || ! method_exists( $requirements_class, 'fromPromptData' ) ) {
                return null;
            }

            $ai_capability = call_user_func( array( $capability_enum_class, $method ) );

            return call_user_func(
                array( $requirements_class, 'fromPromptData' ),
                $ai_capability,
                array( $message ),
                $model_config
            );
        } catch ( \Throwable $error ) {
            return null;
        }
    }

    /**
     * Creates the image request model config used by popup image generation.
     *
     * @param string $model_config_class Fully-qualified ModelConfig class name.
     * @return object
     */
    private function create_image_model_config( string $model_config_class ) {
        if ( method_exists( $model_config_class, 'fromArray' ) ) {
            return call_user_func(
                array( $model_config_class, 'fromArray' ),
                array( 'outputFileType' => 'inline' )
            );
        }

        return new $model_config_class();
    }

    /**
     * Returns candidate model labels keyed like WP AI client's preference map.
     *
     * @param array<int,mixed> $provider_models Provider model groups from the AI registry.
     * @return array<string,string>
     */
    private function get_ai_model_candidate_labels( array $provider_models ): array {
        $candidates = array();

        foreach ( $provider_models as $provider_model_group ) {
            if (
                ! is_object( $provider_model_group )
                || ! method_exists( $provider_model_group, 'getProvider' )
                || ! method_exists( $provider_model_group, 'getModels' )
            ) {
                continue;
            }

            $provider = $provider_model_group->getProvider();
            $models   = $provider_model_group->getModels();
            if ( ! is_object( $provider ) || ! method_exists( $provider, 'getId' ) || ! is_array( $models ) ) {
                continue;
            }

            $provider_id = $this->sanitize_model_label( $provider->getId() );
            if ( '' === $provider_id ) {
                continue;
            }

            foreach ( $models as $model ) {
                if ( ! is_object( $model ) || ! method_exists( $model, 'getId' ) ) {
                    continue;
                }

                $model_id = $this->sanitize_model_label( $model->getId() );
                if ( '' === $model_id ) {
                    continue;
                }

                $label              = $this->format_ai_model_label( $provider_id, $model_id );
                $provider_model_key = $this->create_ai_provider_model_preference_key( $provider_id, $model_id );
                $model_key          = $this->create_ai_model_preference_key( $model_id );

                if ( ! isset( $candidates[ $provider_model_key ] ) ) {
                    $candidates[ $provider_model_key ] = $label;
                }

                if ( ! isset( $candidates[ $model_key ] ) ) {
                    $candidates[ $model_key ] = $label;
                }
            }
        }

        return $candidates;
    }

    /**
     * Returns sanitized preferred model names from the WordPress AI client.
     *
     * @param string $function Fully-qualified function name.
     * @return array<int,string>
     */
    private function get_preferred_ai_models( string $function ): array {
        $model_names = array();
        foreach ( $this->get_raw_preferred_ai_models( $function ) as $model ) {
            $model = $this->normalize_model_preference_label( $model );
            if ( '' !== $model ) {
                $model_names[] = $model;
            }
        }

        return array_values( array_unique( $model_names ) );
    }

    /**
     * Returns raw preferred model values from a WordPress AI helper function.
     *
     * @param string $function Fully-qualified function name.
     * @return array<int,mixed>
     */
    private function get_raw_preferred_ai_models( string $function ): array {
        if ( ! function_exists( $function ) ) {
            return array();
        }

        try {
            $models = call_user_func( $function );
        } catch ( \Throwable $error ) {
            return array();
        }

        return is_array( $models ) ? $models : array();
    }

    /**
     * Returns normalized preferred model keys matching WP AI client discovery.
     *
     * @param string $function Fully-qualified preferred-model function name.
     * @return array<int,string>
     */
    private function get_ai_model_preference_keys( string $function ): array {
        $preference_keys = array();

        foreach ( $this->get_raw_preferred_ai_models( $function ) as $model ) {
            $preference_key = $this->normalize_ai_model_preference_key( $model );
            if ( '' !== $preference_key ) {
                $preference_keys[] = $preference_key;
            }
        }

        return array_values( array_unique( $preference_keys ) );
    }

    /**
     * Normalizes a WordPress AI model preference into a discovery key.
     *
     * @param mixed $model Raw model preference.
     * @return string
     */
    private function normalize_ai_model_preference_key( $model ): string {
        if ( is_array( $model ) ) {
            $provider   = $this->sanitize_model_label( $model[0] ?? '' );
            $model_name = $this->sanitize_model_label( $model[1] ?? '' );

            return '' !== $provider && '' !== $model_name
                ? $this->create_ai_provider_model_preference_key( $provider, $model_name )
                : '';
        }

        if ( is_object( $model ) ) {
            return $this->normalize_ai_model_object_preference_key( $model );
        }

        $model_name = $this->sanitize_model_label( $model );

        return '' !== $model_name ? $this->create_ai_model_preference_key( $model_name ) : '';
    }

    /**
     * Normalizes a model object preference into a discovery key.
     *
     * @param object $model Model preference object.
     * @return string
     */
    private function normalize_ai_model_object_preference_key( object $model ): string {
        if ( ! method_exists( $model, 'metadata' ) || ! method_exists( $model, 'providerMetadata' ) ) {
            return '';
        }

        try {
            $metadata          = $model->metadata();
            $provider_metadata = $model->providerMetadata();
        } catch ( \Throwable $error ) {
            return '';
        }

        if (
            ! is_object( $metadata )
            || ! method_exists( $metadata, 'getId' )
            || ! is_object( $provider_metadata )
            || ! method_exists( $provider_metadata, 'getId' )
        ) {
            return '';
        }

        $provider_id = $this->sanitize_model_label( $provider_metadata->getId() );
        $model_id    = $this->sanitize_model_label( $metadata->getId() );

        return '' !== $provider_id && '' !== $model_id
            ? $this->create_ai_provider_model_preference_key( $provider_id, $model_id )
            : '';
    }

    /**
     * Creates a provider/model preference key compatible with WP AI client.
     *
     * @param string $provider_id Provider identifier.
     * @param string $model_id Model identifier.
     * @return string
     */
    private function create_ai_provider_model_preference_key( string $provider_id, string $model_id ): string {
        return 'providerModel::' . $provider_id . '::' . $model_id;
    }

    /**
     * Creates a model preference key compatible with WP AI client.
     *
     * @param string $model_id Model identifier.
     * @return string
     */
    private function create_ai_model_preference_key( string $model_id ): string {
        return 'model::' . $model_id;
    }

    /**
     * Formats a resolved AI model for display.
     *
     * @param string $provider_id Provider identifier.
     * @param string $model_id Model identifier.
     * @return string
     */
    private function format_ai_model_label( string $provider_id, string $model_id ): string {
        if ( '' !== $provider_id && '' !== $model_id ) {
            return $provider_id . '/' . $model_id;
        }

        return '' !== $model_id ? $model_id : $provider_id;
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
