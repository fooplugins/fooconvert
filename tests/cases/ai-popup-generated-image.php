<?php
declare(strict_types=1);

namespace WordPress\AI {
    function get_preferred_image_models(): array {
        return array( 'stub-image-model' );
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
            return $this;
        }

        public function using_model_preference( string ...$models ): self {
            $GLOBALS['fc_generated_image_models'] = $models;

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

    if ( ! defined( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL' ) ) {
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_MODEL', 'ai_popup_builder_override_model' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL', 'ai_popup_builder_override_image_model' );
        define( 'FOOCONVERT_SETTING_AI_POPUP_BUILDER_DISABLED_PARAMS', 'ai_popup_builder_disabled_params' );
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

    $GLOBALS['fc_generated_image_settings'] = array(
        FOOCONVERT_SETTING_AI_POPUP_BUILDER_OVERRIDE_IMAGE_MODEL => 'custom-image-model',
    );
    unset( $GLOBALS['fc_generated_image_models'] );

    PopupMedia::generate_image_from_prompt( 'Create another calm branded popup background.' );

    Assertions::same(
        array( 'custom-image-model' ),
        $GLOBALS['fc_generated_image_models'] ?? array(),
        'Generating popup image data should prefer the configured image model override when it is set.'
    );

    echo "ai-popup-generated-image: ok\n";
}
