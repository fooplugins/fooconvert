<?php
declare(strict_types=1);

namespace WordPress\AI {
    function get_preferred_image_models(): array {
        return array( 'stub-image-model' );
    }
}

namespace WordPress\AiClient {
    class AiClient {
        public static function defaultRegistry(): object {
            return new \PopupGeneratedImageModelRegistryStub();
        }
    }
}

namespace WordPress\AiClient\Files\Enums {
    class FileTypeEnum {
        public static function inline(): self {
            return new self();
        }
    }
}

namespace WordPress\AiClient\Providers\Http\DTO {
    class RequestOptions {
        public function setTimeout( int $timeout ): void {}
    }
}

namespace WordPress\AiClient\Providers\DTO {
    class ProviderMetadata {
        public const KEY_CREDENTIALS_URL = 'credentialsUrl';
    }
}

namespace WordPress\AiClient\Providers\Models\DTO {
    class ModelConfig {
        private array $custom_options = array();

        public static function fromArray( array $data ): self {
            $config = new self();
            foreach ( $data as $key => $value ) {
                $config->setCustomOption( (string) $key, $value );
            }

            return $config;
        }

        public function setCustomOption( string $key, $value ): void {
            $this->custom_options[ $key ] = $value;
        }

        public function getCustomOptions(): array {
            return $this->custom_options;
        }
    }

    class ModelMetadata {
        public const KEY_SUPPORTED_OPTIONS = 'supportedOptions';
        public const KEY_SUPPORTED_CAPABILITIES = 'supportedCapabilities';
    }
}

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint {
    class Catalog {
        public static function sanitize_selected_block_names( $selected_block_names ): array {
            return is_array( $selected_block_names ) ? array_values( array_filter( $selected_block_names, 'is_string' ) ) : array();
        }

        public static function get_default_selected_block_names(): array {
            return array();
        }
    }
}

namespace {
    use FooPlugins\FooConvert\AI\PopupBuilder\Media\Attachments as PopupMedia;
    use FooPlugins\FooConvert\Tests\Support\Assertions;

    class WP_Error {
        private string $code;
        private string $message;

        public function __construct( string $code, string $message ) {
            $this->code = $code;
            $this->message = $message;
        }

        public function get_error_code(): string {
            return $this->code;
        }

        public function get_error_message(): string {
            return $this->message;
        }
    }

    class PopupGeneratedImageModelRegistryStub {
        public function getProviderModel( string $provider, string $model, \WordPress\AiClient\Providers\Models\DTO\ModelConfig $config ): object {
            unset( $config );

            if ( ! empty( $GLOBALS['fc_generated_image_model_registry_fail'] ) ) {
                throw new \RuntimeException( 'Model metadata is not registered.' );
            }

            $GLOBALS['fc_generated_image_forced_models'][] = compact( 'provider', 'model' );

            return (object) compact( 'provider', 'model' );
        }
    }

    class PopupGeneratedImageProviderMetaStub {
        public function toArray(): array {
            return array(
                'id'   => 'provider-id',
                'name' => 'Provider Name',
                'type' => 'image',
            );
        }
    }

    class PopupGeneratedImageModelMetaStub {
        public function toArray(): array {
            return array(
                'id'   => 'model-id',
                'name' => 'Model Name',
            );
        }
    }

    class PopupGeneratedImageFileStub {
        public function getBase64Data(): string {
            return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO7Z0Y4AAAAASUVORK5CYII=';
        }

        public function getMimeType(): string {
            return 'image/png';
        }
    }

    class PopupGeneratedImageResultStub {
        public function toImageFile(): PopupGeneratedImageFileStub {
            return new PopupGeneratedImageFileStub();
        }

        public function getProviderMetadata(): PopupGeneratedImageProviderMetaStub {
            return new PopupGeneratedImageProviderMetaStub();
        }

        public function getModelMetadata(): PopupGeneratedImageModelMetaStub {
            return new PopupGeneratedImageModelMetaStub();
        }
    }

    class PopupGeneratedImagePromptBuilderStub {
        public function using_request_options( $request_options ): self {
            return $this;
        }

        public function as_output_file_type( $file_type ): self {
            $GLOBALS['fc_generated_image_method_order'][] = 'output_file_type';

            return $this;
        }

        public function as_output_mime_type( string $mime_type ): self {
            $GLOBALS['fc_generated_image_method_order'][] = 'output_mime_type';
            $GLOBALS['fc_generated_image_output_mime_type'] = $mime_type;

            return $this;
        }

        public function using_model_config( $model_config ): self {
            $GLOBALS['fc_generated_image_method_order'][] = 'model_config';

            if ( method_exists( $model_config, 'getCustomOptions' ) ) {
                $GLOBALS['fc_generated_image_custom_options'] = $model_config->getCustomOptions();
            }

            return $this;
        }

        public function using_model_preference( string ...$models ): self {
            $GLOBALS['fc_generated_image_models'] = $models;

            return $this;
        }

        public function using_model( $model ): self {
            $GLOBALS['fc_generated_image_forced_model'] = $model;

            return $this;
        }

        public function generate_image_result(): PopupGeneratedImageResultStub {
            return new PopupGeneratedImageResultStub();
        }
    }

    function __( string $text, ?string $domain = null ): string {
        return $text;
    }

    function sanitize_text_field( $value ): string {
        return trim( strip_tags( (string) $value ) );
    }

    function absint( $value ): int {
        return abs( (int) $value );
    }

    function fooconvert_get_setting( string $key, $default = null ) {
        $settings = $GLOBALS['fc_generated_image_settings'] ?? array();

        return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
    }

    function fooconvert_get_settings(): array {
        return $GLOBALS['fc_generated_image_settings'] ?? array();
    }

    function wp_ai_client_prompt( string $content = '' ): PopupGeneratedImagePromptBuilderStub {
        $GLOBALS['fc_generated_image_prompt'] = $content;

        return new PopupGeneratedImagePromptBuilderStub();
    }

    function is_wp_error( $thing ): bool {
        return $thing instanceof WP_Error;
    }

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
    }

    if ( ! defined( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL' ) ) {
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL', 'ai_popup_builder_override_model' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL', 'ai_popup_builder_override_image_model' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS', 'ai_popup_builder_disabled_params' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OPTIMIZE_IMAGE_OUTPUT', 'ai_popup_builder_optimize_image_output' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_TIMEOUT', 'ai_popup_builder_timeout' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_MAX_TOOL_CALLS', 'ai_popup_builder_max_tool_calls' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_SELECTED_BLOCKS', 'ai_popup_builder_selected_blocks' );
    }

    require_once __DIR__ . '/../support/Assertions.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Settings.php';
    require_once dirname( __DIR__, 2 ) . '/includes/AI/PopupBuilder/Media/Attachments.php';

    $result = PopupMedia::generate_image_from_prompt( 'Create a calm branded popup background.' );

    Assertions::true(
        is_array( $result ),
        'Generating popup image data should return an image payload array.'
    );

    Assertions::same(
        'image/png',
        $result['mime_type'] ?? '',
        'Generated popup image payloads should preserve MIME type for later import.'
    );

    Assertions::same(
        'provider-id',
        $result['provider_metadata']['id'] ?? '',
        'Generated popup image payloads should retain provider metadata.'
    );

    Assertions::same(
        array( 'stub-image-model' ),
        $GLOBALS['fc_generated_image_models'] ?? array(),
        'Generating popup image data should honor the preferred image model list when available.'
    );

    Assertions::same(
        'image/webp',
        $GLOBALS['fc_generated_image_output_mime_type'] ?? '',
        'Generating popup image data should request WebP output by default.'
    );

    Assertions::same(
        array(
            'output_compression' => 80,
            'background'         => 'opaque',
        ),
        $GLOBALS['fc_generated_image_custom_options'] ?? array(),
        'Generating popup image data should request compressed opaque image output by default.'
    );

    Assertions::same(
        array( 'model_config', 'output_file_type', 'output_mime_type' ),
        $GLOBALS['fc_generated_image_method_order'] ?? array(),
        'Generating popup image data should apply compression before re-applying inline file output and WebP MIME settings.'
    );

    $GLOBALS['fc_generated_image_settings'] = array(
        FOOCONVERT_SETTING_AI_POPUP_BUILDER_OPTIMIZE_IMAGE_OUTPUT => false,
    );
    unset(
        $GLOBALS['fc_generated_image_method_order'],
        $GLOBALS['fc_generated_image_output_mime_type'],
        $GLOBALS['fc_generated_image_custom_options']
    );

    PopupMedia::generate_image_from_prompt( 'Create an unoptimized popup background.' );

    Assertions::same(
        '',
        $GLOBALS['fc_generated_image_output_mime_type'] ?? '',
        'Generated popup image optimization should be skipped when disabled.'
    );

    Assertions::same(
        array(),
        $GLOBALS['fc_generated_image_custom_options'] ?? array(),
        'Generated popup image compression should be skipped when optimization is disabled.'
    );

    $GLOBALS['fc_generated_image_settings'] = array(
        FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL => 'openrouter/black-forest-labs/flux-pro',
    );
    unset(
        $GLOBALS['fc_generated_image_forced_model'],
        $GLOBALS['fc_generated_image_forced_models'],
        $GLOBALS['fc_generated_image_models']
    );

    PopupMedia::generate_image_from_prompt( 'Create another calm branded popup background.' );

    Assertions::same(
        array(),
        $GLOBALS['fc_generated_image_models'] ?? array(),
        'Provider/model image overrides should not use the model preference path.'
    );

    Assertions::same(
        array(
            array(
                'provider' => 'openrouter',
                'model'    => 'black-forest-labs/flux-pro',
            ),
        ),
        $GLOBALS['fc_generated_image_forced_models'] ?? array(),
        'Generating popup image data should force the configured provider/model image override when it is set.'
    );

    $GLOBALS['fc_generated_image_model_registry_fail'] = true;
    unset( $GLOBALS['fc_generated_image_forced_model'], $GLOBALS['fc_generated_image_forced_models'] );

    $unresolved_image_override = PopupMedia::generate_image_from_prompt( 'Create an image with an unavailable override.' );
    unset( $GLOBALS['fc_generated_image_model_registry_fail'] );

    Assertions::true(
        $unresolved_image_override instanceof WP_Error,
        'An unresolved provider/model image override should return an error instead of falling back to model discovery.'
    );

    Assertions::same(
        'fooconvert_ai_popup_builder_model_override_unavailable',
        $unresolved_image_override->get_error_code(),
        'Unresolved provider/model image overrides should use the explicit model override error code.'
    );

    if ( function_exists( 'imagecreatetruecolor' ) && function_exists( 'imagewebp' ) && function_exists( 'imagecreatefromstring' ) ) {
        $source_image = imagecreatetruecolor( 2, 2 );
        imagealphablending( $source_image, false );
        imagesavealpha( $source_image, true );
        $hidden_color = imagecolorallocatealpha( $source_image, 112, 96, 144, 127 );
        imagefilledrectangle( $source_image, 0, 0, 1, 1, $hidden_color );

        ob_start();
        imagewebp( $source_image, null, 80 );
        $transparent_webp = ob_get_clean();
        imagedestroy( $source_image );

        $reflection = new \ReflectionClass( PopupMedia::class );
        $method     = $reflection->getMethod( 'normalize_generated_image_binary' );
        $method->setAccessible( true );

        $normalized = $method->invoke( null, $transparent_webp, 'image/webp' );
        $image      = imagecreatefromstring( $normalized );
        $pixel      = imagecolorsforindex( $image, imagecolorat( $image, 0, 0 ) );
        imagedestroy( $image );

        Assertions::same(
            0,
            (int) $pixel['alpha'],
            'Generated WebP import should repair fully transparent alpha when RGB image data exists underneath.'
        );
    }

    echo "ai-popup-generated-image: ok\n";
}
