<?php
declare(strict_types=1);

namespace FooPlugins\FooConvert\AI {
    class Abilities {
        public static function wp_api_available(): bool {
            return true;
        }

        public static function get_allowed_abilities(): array {
            return array( 'fooconvert/list-popup-templates' );
        }
    }

}

namespace WordPress\AI {
    function get_preferred_models_for_text_generation(): array {
        return $GLOBALS['fc_ai_builder_text_models'] ?? array();
    }

    function get_preferred_image_models(): array {
        return $GLOBALS['fc_ai_builder_image_models'] ?? array();
    }
}

namespace WordPress\AiClient {
    class AiClient {
        public static function defaultRegistry(): object {
            return $GLOBALS['fc_ai_builder_ai_registry'] ?? new \FcAiBuilderRegistryStub( array() );
        }
    }
}

namespace WordPress\AiClient\Messages\DTO {
    class MessagePart {
        public function __construct( string $text ) {}
    }

    class UserMessage {
        public function __construct( array $parts ) {}
    }
}

namespace WordPress\AiClient\Providers\Models\Enums {
    class CapabilityEnum {
        public string $value;

        private function __construct( string $value ) {
            $this->value = $value;
        }

        public static function textGeneration(): self {
            return new self( 'text_generation' );
        }

        public static function imageGeneration(): self {
            return new self( 'image_generation' );
        }
    }
}

namespace WordPress\AiClient\Providers\Models\DTO {
    class ModelConfig {
        public array $data = array();

        public static function fromArray( array $data ): self {
            $config       = new self();
            $config->data = $data;

            return $config;
        }
    }

    class ModelRequirements {
        public object $capability;
        public array $messages;
        public object $model_config;

        public function __construct( object $capability, array $messages, object $model_config ) {
            $this->capability   = $capability;
            $this->messages     = $messages;
            $this->model_config = $model_config;
        }

        public static function fromPromptData( object $capability, array $messages, object $model_config ): self {
            return new self( $capability, $messages, $model_config );
        }
    }
}

namespace WordPress\AiClient\Files\Enums {
    class FileTypeEnum {}
}

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint {
    class DraftNormalizer {
        public static function get_template_library(): array {
            return array();
        }

        public static function get_block_catalog(): array {
            return array();
        }

        public static function get_conversion_playbook(): array {
            return array();
        }

        public static function get_assistant_response_schema(): array {
            return array();
        }

        public static function get_assistant_response_contract(): string {
            return 'Return popup JSON.';
        }
    }
}

namespace FooPlugins\FooConvert\AI\PopupBuilder {
    class RestController {
        public static function get_default_system_instruction_preview(): string {
            return 'Build useful popups.';
        }
    }

    class Settings {
        public static function to_response( ?array $settings = null ): array {
            return array(
                'overrideModel' => $GLOBALS['fc_ai_builder_override_model'] ?? '',
            );
        }
    }

    class Config {
        public static function is_admin_page(): bool {
            return isset( $_GET['page'] ) && $_GET['page'] === FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER;
        }

        public static function has_ai_client(): bool {
            return $GLOBALS['fc_ai_builder_has_ai_client'] ?? true;
        }

        public static function has_valid_ai_connection(): bool {
            return $GLOBALS['fc_ai_builder_has_valid_ai_connection'] ?? true;
        }

        public static function get_wp_version(): string {
            return '7.0';
        }

        public static function supports_ai_popup_builder(): bool {
            return true;
        }

        public static function supports_streaming(): bool {
            return true;
        }
    }
}

namespace FooPlugins\FooConvert\AI\PopupBuilder\Media {
    class Attachments {
        public static function list_generated_images( int $limit ): array {
            return array();
        }

        public static function can_manage_media(): bool {
            return true;
        }
    }
}

namespace FooPlugins\FooConvert\Brand {
    class Manager {
        public static function get_saved_brand(): array {
            return array();
        }

        public static function has_saved_brand(): bool {
            return false;
        }

        public static function get_default_brand(): array {
            return array();
        }
    }
}

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\Admin as AiPopupBuilder;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
    }

    if ( ! defined( 'FOOCONVERT_CPT_POPUP' ) ) {
        define( 'FOOCONVERT_CPT_POPUP', 'fc_popup' );
    }

    if ( ! defined( 'FOOCONVERT_MENU_SLUG' ) ) {
        define( 'FOOCONVERT_MENU_SLUG', 'fooconvert' );
    }

    if ( ! defined( 'FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER' ) ) {
        define( 'FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER', 'fooconvert-ai-popup-builder' );
    }

    if ( ! defined( 'FOOCONVERT_ASSETS_PATH' ) ) {
        define( 'FOOCONVERT_ASSETS_PATH', __DIR__ . '/missing-assets/' );
    }

    if ( ! defined( 'FOOCONVERT_ASSETS_URL' ) ) {
        define( 'FOOCONVERT_ASSETS_URL', 'https://example.test/wp-content/plugins/fooconvert/assets/' );
    }

    if ( ! defined( 'FOOCONVERT_VERSION' ) ) {
        define( 'FOOCONVERT_VERSION', '2.0.0' );
    }

    if ( ! defined( 'FOOCONVERT_POPUP_TYPE_BAR' ) ) {
        define( 'FOOCONVERT_POPUP_TYPE_BAR', 'bar' );
        define( 'FOOCONVERT_POPUP_TYPE_FLYOUT', 'flyout' );
        define( 'FOOCONVERT_POPUP_TYPE_POPUP', 'popup' );
    }

    require_once dirname( __DIR__ ) . '/support/Assertions.php';

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_ai_builder_actions'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
    }

    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['fc_ai_builder_filters'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
    }

    function do_action( string $hook, ...$args ): void {}

    function get_post_type_object( string $post_type ) {
        return (object) array(
            'cap' => (object) array(
                'create_posts' => 'edit_posts',
            ),
        );
    }

    function add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, $callback ) {
        $GLOBALS['fc_ai_builder_registered_submenu'] = compact( 'parent_slug', 'page_title', 'menu_title', 'capability', 'menu_slug' );

        return $GLOBALS['fc_ai_builder_next_hook_suffix'] ?? '';
    }

    function wp_enqueue_style( string $handle, string $src = '', array $deps = array(), $ver = false ): void {
        $GLOBALS['fc_ai_builder_enqueued_styles'][ $handle ] = compact( 'src', 'deps', 'ver' );
    }

    function wp_enqueue_script( string $handle, string $src = '', array $deps = array(), $ver = false, $args = false ): void {
        $GLOBALS['fc_ai_builder_enqueued_scripts'][ $handle ] = compact( 'src', 'deps', 'ver', 'args' );
    }

    function wp_add_inline_script( string $handle, string $data, string $position = 'after' ): void {
        $GLOBALS['fc_ai_builder_inline_scripts'][ $handle ][] = compact( 'data', 'position' );
    }

    function wp_json_encode( $value ): string {
        return json_encode( $value );
    }

    function esc_url_raw( $value ): string {
        return (string) $value;
    }

    function get_rest_url(): string {
        return 'https://example.test/wp-json/';
    }

    function wp_create_nonce( string $action ): string {
        return 'nonce-' . $action;
    }

    function current_user_can( string $capability ): bool {
        return true;
    }

    function admin_url( string $path = '' ): string {
        return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
    }

    function fooconvert_get_popup_type_label( string $popup_type ): string {
        return ucfirst( $popup_type );
    }

    function fooconvert_is_woocommerce_active(): bool {
        return false;
    }

    class FcAiBuilderRegistryStub {
        private array $groups_by_capability;

        public function __construct( array $groups_by_capability ) {
            $this->groups_by_capability = $groups_by_capability;
        }

        public function findModelsMetadataForSupport( $requirements ): array {
            $capability = is_object( $requirements ) && isset( $requirements->capability->value )
                ? $requirements->capability->value
                : '';

            $GLOBALS['fc_ai_builder_model_requirements'][ $capability ] = $requirements;

            return $this->groups_by_capability[ $capability ] ?? array();
        }
    }

    class FcAiBuilderProviderModelsStub {
        private FcAiBuilderProviderStub $provider;
        private array $models;

        public function __construct( string $provider_id, array $model_ids ) {
            $this->provider = new FcAiBuilderProviderStub( $provider_id );
            $this->models   = array_map(
                static fn( string $model_id ): FcAiBuilderModelStub => new FcAiBuilderModelStub( $model_id ),
                $model_ids
            );
        }

        public function getProvider(): FcAiBuilderProviderStub {
            return $this->provider;
        }

        public function getModels(): array {
            return $this->models;
        }
    }

    class FcAiBuilderProviderStub {
        private string $id;

        public function __construct( string $id ) {
            $this->id = $id;
        }

        public function getId(): string {
            return $this->id;
        }
    }

    class FcAiBuilderModelStub {
        private string $id;

        public function __construct( string $id ) {
            $this->id = $id;
        }

        public function getId(): string {
            return $this->id;
        }
    }

    function fc_ai_builder_decode_config_script( string $config_script ): array {
        $prefix = 'window.FC_AI_POPUP_BUILDER = ';
        if ( 0 !== strpos( $config_script, $prefix ) ) {
            return array();
        }

        $json = rtrim( substr( $config_script, strlen( $prefix ) ), ';' );
        $data = json_decode( $json, true );

        return is_array( $data ) ? $data : array();
    }

    require_once dirname( __DIR__, 2 ) . '/includes/Admin/ScriptDependencies.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Admin.php';

    $GLOBALS['fc_ai_builder_next_hook_suffix'] = 'popups_page_fooconvert-ai-popup-builder';
    $GLOBALS['fc_ai_builder_text_models']      = array( array( 'openai', 'stub-text-model' ) );
    $GLOBALS['fc_ai_builder_image_models']     = array( array( 'google', 'stub-image-model' ) );
    $builder = new AiPopupBuilder();
    $builder->register_menu();

    Assertions::true(
        isset( $GLOBALS['fc_ai_builder_actions']['fooconvert_admin_menu_after_post_types'] ),
        'The AI popup builder menu hook should register regardless of AI connection state.'
    );

    $builder->enqueue_assets( 'fooconvert_page_fooconvert-ai-popup-builder' );

    Assertions::false(
        isset( $GLOBALS['fc_ai_builder_enqueued_scripts']['fooconvert-ai-popup-builder'] ),
        'The old parent-menu hook suffix should not enqueue builder assets after the menu label changes.'
    );

    $builder->enqueue_assets( 'popups_page_fooconvert-ai-popup-builder' );

    Assertions::true(
        isset( $GLOBALS['fc_ai_builder_enqueued_scripts']['fooconvert-ai-popup-builder'] ),
        'The add_submenu_page() hook suffix should enqueue the AI popup builder app.'
    );

    unset( $GLOBALS['fc_ai_builder_enqueued_scripts'], $GLOBALS['fc_ai_builder_enqueued_styles'], $GLOBALS['fc_ai_builder_inline_scripts'] );
    $_GET['page'] = FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER;

    $builder_without_registered_hook = new AiPopupBuilder();
    $builder_without_registered_hook->enqueue_assets( 'admin_page_fooconvert-ai-popup-builder' );

    Assertions::true(
        isset( $GLOBALS['fc_ai_builder_enqueued_scripts']['fooconvert-ai-popup-builder'] ),
        'The AI popup builder page request should still enqueue assets when the stored hook suffix is unavailable.'
    );

    $config_script = $GLOBALS['fc_ai_builder_inline_scripts']['fooconvert-ai-popup-builder'][1]['data'] ?? '';
    $config        = fc_ai_builder_decode_config_script( $config_script );
    Assertions::true(
        false !== strpos( $config_script, '"aiConnectionReady":true' ),
        'The AI popup builder config should expose the valid AI connection status.'
    );

    Assertions::same(
        'openai/stub-text-model',
        $config['models']['currentTextModel'] ?? '',
        'The AI popup builder config should expose the current preferred text model.'
    );

    Assertions::same(
        'google/stub-image-model',
        $config['models']['currentImageModel'] ?? '',
        'The AI popup builder config should expose the current preferred image model.'
    );

    Assertions::false(
        array_key_exists( 'starterPrompts', $config ),
        'The AI popup builder config should not expose starter prompts after they move to the admin app.'
    );

    unset( $GLOBALS['fc_ai_builder_enqueued_scripts'], $GLOBALS['fc_ai_builder_enqueued_styles'], $GLOBALS['fc_ai_builder_inline_scripts'] );
    $GLOBALS['fc_ai_builder_override_model'] = 'custom-text-model';
    $GLOBALS['fc_ai_builder_image_models']   = array();
    $builder_without_image_models = new AiPopupBuilder();
    $builder_without_image_models->enqueue_assets( 'admin_page_fooconvert-ai-popup-builder' );

    $no_image_model_config = fc_ai_builder_decode_config_script( $GLOBALS['fc_ai_builder_inline_scripts']['fooconvert-ai-popup-builder'][1]['data'] ?? '' );
    Assertions::same(
        'custom-text-model',
        $no_image_model_config['models']['currentTextModel'] ?? '',
        'The AI popup builder config should expose a selected text model override.'
    );

    Assertions::same(
        '',
        $no_image_model_config['models']['currentImageModel'] ?? null,
        'The AI popup builder config should expose an empty image model when image model preferences are unavailable.'
    );

    unset( $GLOBALS['fc_ai_builder_override_model'] );

    unset( $GLOBALS['fc_ai_builder_enqueued_scripts'], $GLOBALS['fc_ai_builder_enqueued_styles'], $GLOBALS['fc_ai_builder_inline_scripts'] );
    $GLOBALS['fc_ai_builder_text_models'] = array(
        array( 'anthropic', 'missing-text-model' ),
        array( 'openai', 'resolved-text-model' ),
    );
    $GLOBALS['fc_ai_builder_image_models'] = array();
    $GLOBALS['fc_ai_builder_ai_registry']  = new FcAiBuilderRegistryStub(
        array(
            'text_generation'  => array(
                new FcAiBuilderProviderModelsStub( 'openai', array( 'resolved-text-model' ) ),
            ),
            'image_generation' => array(
                new FcAiBuilderProviderModelsStub( 'openai', array( 'resolved-image-model' ) ),
            ),
        )
    );
    $builder_with_resolved_models = new AiPopupBuilder();
    $builder_with_resolved_models->enqueue_assets( 'admin_page_fooconvert-ai-popup-builder' );

    $resolved_model_config = fc_ai_builder_decode_config_script( $GLOBALS['fc_ai_builder_inline_scripts']['fooconvert-ai-popup-builder'][1]['data'] ?? '' );
    Assertions::same(
        'openai/resolved-text-model',
        $resolved_model_config['models']['currentTextModel'] ?? '',
        'The AI popup builder config should expose the resolved configured text model instead of an unavailable preferred model.'
    );

    Assertions::same(
        'openai/resolved-image-model',
        $resolved_model_config['models']['currentImageModel'] ?? '',
        'The AI popup builder config should expose the resolved configured image model when image preferences are unavailable.'
    );

    Assertions::same(
        'inline',
        $GLOBALS['fc_ai_builder_model_requirements']['image_generation']->model_config->data['outputFileType'] ?? '',
        'Image model discovery should match the inline image output config used by popup media generation.'
    );

    unset( $GLOBALS['fc_ai_builder_ai_registry'], $GLOBALS['fc_ai_builder_model_requirements'] );

    unset( $GLOBALS['fc_ai_builder_enqueued_scripts'], $GLOBALS['fc_ai_builder_enqueued_styles'], $GLOBALS['fc_ai_builder_inline_scripts'] );
    $GLOBALS['fc_ai_builder_has_valid_ai_connection'] = false;
    $builder_without_connection = new AiPopupBuilder();
    $builder_without_connection->enqueue_assets( 'admin_page_fooconvert-ai-popup-builder' );

    $missing_connection_config = $GLOBALS['fc_ai_builder_inline_scripts']['fooconvert-ai-popup-builder'][1]['data'] ?? '';
    Assertions::true(
        false !== strpos( $missing_connection_config, '"aiConnectionReady":false' ),
        'The AI popup builder config should tell the app when no valid AI connection is configured.'
    );

    Assertions::true(
        false !== strpos( $missing_connection_config, 'options-connectors.php' ),
        'The AI popup builder config should include the WordPress AI connectors URL for setup.'
    );

    Assertions::true(
        false !== strpos( $missing_connection_config, 'AI Popup Builder chat needs a valid WordPress AI connector before it can generate popups. Go to Settings > Connectors to add or verify a connector, then reload this page.' ),
        'The AI popup builder warning should use the configured connector setup message.'
    );

    unset(
        $GLOBALS['fc_ai_builder_actions'],
        $GLOBALS['fc_ai_builder_filters'],
        $GLOBALS['fc_ai_builder_enqueued_scripts'],
        $GLOBALS['fc_ai_builder_enqueued_styles'],
        $GLOBALS['fc_ai_builder_inline_scripts']
    );
    $GLOBALS['fc_ai_builder_has_ai_client']             = false;
    $GLOBALS['fc_ai_builder_has_valid_ai_connection']   = false;
    $builder_without_ai_client = new AiPopupBuilder();
    $builder_without_ai_client->register_menu();
    $builder_without_ai_client->enqueue_assets( 'admin_page_fooconvert-ai-popup-builder' );

    Assertions::true(
        isset( $GLOBALS['fc_ai_builder_actions']['fooconvert_admin_menu_after_post_types'] ),
        'The AI popup builder menu hook should register even when no AI client connection is available.'
    );

    Assertions::same(
        FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER,
        $GLOBALS['fc_ai_builder_registered_submenu']['menu_slug'] ?? '',
        'The AI popup builder submenu should still be added when no AI client connection is available.'
    );

    $missing_ai_client_config = $GLOBALS['fc_ai_builder_inline_scripts']['fooconvert-ai-popup-builder'][1]['data'] ?? '';
    Assertions::true(
        false !== strpos( $missing_ai_client_config, '"aiClientAvailable":false' ),
        'The AI popup builder config should tell the app when the WordPress AI client is unavailable.'
    );

    Assertions::true(
        false !== strpos( $missing_ai_client_config, 'update-core.php' ),
        'The AI popup builder config should include the WordPress update URL when the AI client is unavailable.'
    );

    Assertions::true(
        false !== strpos( $missing_ai_client_config, 'WP 7.0 is required for this feature to work' ),
        'The AI popup builder warning should use the WordPress upgrade message when the AI client is unavailable.'
    );

    fwrite( STDOUT, "ai-popup-admin-page: ok\n" );
}
