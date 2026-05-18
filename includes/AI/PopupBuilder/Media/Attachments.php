<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Media;

use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\Catalog;
use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\DraftNormalizer as PopupBlueprint;
use FooPlugins\FooConvert\AI\PopupBuilder\Settings;
use FooPlugins\FooConvert\Brand\Manager as BrandManager;
use Throwable;
use WP_Error;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

defined( 'ABSPATH' ) || exit;

class Attachments {

    public const META_GENERATED = '_fooconvert_ai_popup_generated';

    public const META_PROMPT = '_fooconvert_ai_popup_prompt';

    public const META_POPUP_TYPE = '_fooconvert_ai_popup_type';

    public const META_SOURCE = '_fooconvert_ai_popup_source';

    public const SOURCE_POPUP_BUILDER = 'ai-popup-builder';

    public const SOURCE_POPUP_BACKGROUND = 'ai-popup-background';

    /**
     * AI request parameters disabled during the current request.
     *
     * @var array<int,string>
     */
    private static array $runtime_disabled_ai_params = array();

    /**
     * AI popup builder request settings for the current chat turn.
     *
     * @var array<string,mixed>|null
     */
    private static ?array $runtime_ai_settings = null;

    /**
     * Registers attachment meta used by the popup builder media workflow.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_attachment_meta' ) );
    }

    /**
     * Registers popup-builder attachment meta keys.
     *
     * @return void
     */
    public function register_attachment_meta(): void {
        register_post_meta(
            'attachment',
            self::META_GENERATED,
            array(
                'type'              => 'integer',
                'single'            => true,
                'show_in_rest'      => false,
                'sanitize_callback' => 'absint',
            )
        );

        register_post_meta(
            'attachment',
            self::META_PROMPT,
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => false,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        register_post_meta(
            'attachment',
            self::META_POPUP_TYPE,
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => false,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        register_post_meta(
            'attachment',
            self::META_SOURCE,
            array(
                'type'              => 'string',
                'single'            => true,
                'show_in_rest'      => false,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
    }

    /**
     * Returns whether the current user can generate and import media.
     *
     * @return bool
     */
    public static function can_manage_media(): bool {
        return current_user_can( 'upload_files' );
    }

    /**
     * Sets request-scoped AI settings for media generation.
     *
     * @param array<string,mixed> $settings AI popup builder settings.
     * @return void
     */
    public static function set_runtime_ai_settings( array $settings ): void {
        self::$runtime_ai_settings = Settings::sanitize_payload( $settings );
    }

    /**
     * Clears request-scoped AI settings for media generation.
     *
     * @return void
     */
    public static function clear_runtime_ai_settings(): void {
        self::$runtime_ai_settings = null;
    }

    /**
     * Returns the schema used for popup builder media items.
     *
     * @return array<string,mixed>
     */
    public static function get_attachment_schema(): array {
        return array(
            'type'       => 'object',
            'required'   => array(
                'id',
                'url',
                'previewUrl',
                'filename',
                'title',
                'description',
                'alt',
                'prompt',
                'popupType',
                'width',
                'height',
                'editUrl',
            ),
            'properties' => array(
                'id'         => array(
                    'type' => 'integer',
                ),
                'url'        => array(
                    'type' => 'string',
                ),
                'previewUrl' => array(
                    'type' => 'string',
                ),
                'filename'   => array(
                    'type' => 'string',
                ),
                'title'      => array(
                    'type' => 'string',
                ),
                'description' => array(
                    'type' => 'string',
                ),
                'alt'        => array(
                    'type' => 'string',
                ),
                'prompt'     => array(
                    'type' => 'string',
                ),
                'popupType'  => array(
                    'type' => 'string',
                ),
                'width'      => array(
                    'type' => 'integer',
                ),
                'height'     => array(
                    'type' => 'integer',
                ),
                'editUrl'    => array(
                    'type' => 'string',
                ),
            ),
        );
    }

    /**
     * Returns the schema used for raw generated image payloads.
     *
     * @return array<string,mixed>
     */
    public static function get_generated_image_schema(): array {
        return array(
            'type'       => 'object',
            'required'   => array( 'data', 'mime_type', 'provider_metadata', 'model_metadata' ),
            'properties' => array(
                'data'              => array(
                    'type' => 'string',
                ),
                'mime_type'         => array(
                    'type' => 'string',
                ),
                'provider_metadata' => array(
                    'type'       => 'object',
                    'required'   => array( 'id', 'name', 'type' ),
                    'properties' => array(
                        'id'   => array(
                            'type' => 'string',
                        ),
                        'name' => array(
                            'type' => 'string',
                        ),
                        'type' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
                'model_metadata'    => array(
                    'type'       => 'object',
                    'required'   => array( 'id', 'name' ),
                    'properties' => array(
                        'id'   => array(
                            'type' => 'string',
                        ),
                        'name' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Sanitizes a media item list into the shape expected by the builder.
     *
     * @param mixed $media_items Media payload.
     * @return array<int,array<string,mixed>>
     */
    public static function sanitize_media_items( $media_items ): array {
        if ( ! is_array( $media_items ) ) {
            return array();
        }

        $sanitized = array();

        foreach ( array_values( $media_items ) as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }

            $url = esc_url_raw( $item['url'] ?? '' );
            if ( '' === $url ) {
                continue;
            }

            $sanitized[] = array(
                'id'          => absint( $item['id'] ?? 0 ),
                'url'         => $url,
                'previewUrl'  => esc_url_raw( $item['previewUrl'] ?? $url ),
                'filename'    => self::sanitize_text( $item['filename'] ?? '' ),
                'title'       => self::sanitize_text( $item['title'] ?? '' ),
                'description' => self::sanitize_text( $item['description'] ?? '' ),
                'alt'         => self::sanitize_text( $item['alt'] ?? $item['alt_text'] ?? '' ),
                'prompt'      => self::sanitize_text( $item['prompt'] ?? '' ),
                'popupType'   => fooconvert_normalize_popup_type( $item['popupType'] ?? $item['popup_type'] ?? '' ),
                'width'       => absint( $item['width'] ?? 0 ),
                'height'      => absint( $item['height'] ?? 0 ),
                'editUrl'     => esc_url_raw( $item['editUrl'] ?? $item['edit_url'] ?? '' ),
            );
        }

        return array_slice( $sanitized, 0, 24 );
    }

    /**
     * Lists recent generated popup media for the current user.
     *
     * @param int $limit Maximum items to return.
     * @return array<int,array<string,mixed>>
     */
    public static function list_generated_images( int $limit = 12 ): array {
        $query = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => max( 1, min( 24, $limit ) ),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_key'       => self::META_GENERATED,
            'meta_value'     => '1',
            'fields'         => 'ids',
        );

        if ( function_exists( 'get_current_user_id' ) ) {
            $query['author'] = get_current_user_id();
        }

        $attachment_ids = get_posts( $query );
        if ( ! is_array( $attachment_ids ) ) {
            return array();
        }

        $items = array();

        foreach ( $attachment_ids as $attachment_id ) {
            $item = self::prepare_attachment( absint( $attachment_id ) );
            if ( is_array( $item ) ) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Returns a normalized attachment payload for the builder.
     *
     * @param int $attachment_id Attachment ID.
     * @return array<string,mixed>|null
     */
    public static function prepare_attachment( int $attachment_id ): ?array {
        $attachment = get_post( $attachment_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return null;
        }

        $url = wp_get_attachment_url( $attachment_id );
        if ( ! is_string( $url ) || '' === $url ) {
            return null;
        }

        $preview_url = function_exists( 'wp_get_attachment_image_url' )
            ? wp_get_attachment_image_url( $attachment_id, 'medium_large' )
            : '';
        $preview_url = is_string( $preview_url ) && '' !== $preview_url ? $preview_url : $url;

        $metadata = wp_get_attachment_metadata( $attachment_id );
        $file     = get_attached_file( $attachment_id );

        return array(
            'id'          => $attachment_id,
            'url'         => esc_url_raw( $url ),
            'previewUrl'  => esc_url_raw( $preview_url ),
            'filename'    => $file ? basename( $file ) : '',
            'title'       => sanitize_text_field( $attachment->post_title ),
            'description' => sanitize_text_field( $attachment->post_content ),
            'alt'         => sanitize_text_field( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ),
            'prompt'      => sanitize_text_field( (string) get_post_meta( $attachment_id, self::META_PROMPT, true ) ),
            'popupType'   => fooconvert_normalize_popup_type( get_post_meta( $attachment_id, self::META_POPUP_TYPE, true ) ),
            'width'       => is_array( $metadata ) ? absint( $metadata['width'] ?? 0 ) : 0,
            'height'      => is_array( $metadata ) ? absint( $metadata['height'] ?? 0 ) : 0,
            'editUrl'     => esc_url_raw( admin_url( 'post.php?post=' . $attachment_id . '&action=edit' ) ),
        );
    }

    /**
     * Generates a popup-specific image prompt from a popup draft.
     *
     * @param array<string,mixed> $popup_draft Popup draft.
     * @param string              $instructions Optional additional direction.
     * @return string|WP_Error
     */
    public static function generate_prompt_for_popup( array $popup_draft, string $instructions = '' ) {
        if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_unavailable',
                __( 'The WordPress AI client is not available for popup image prompts.', 'fooconvert' )
            );
        }

        $draft   = PopupBlueprint::sanitize_popup_draft( $popup_draft );
        $content = self::build_popup_image_context( $draft );

        if ( '' !== trim( $instructions ) ) {
            $content .= "\n\n<additional-direction>" . sanitize_text_field( $instructions ) . '</additional-direction>';
        }

        $result = self::generate_text_prompt(
            $content,
            self::get_image_prompt_system_instruction(),
            0.9
        );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $prompt = sanitize_text_field( trim( (string) $result ) );
        if ( '' === $prompt ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_empty_prompt',
                __( 'The AI client did not return an image prompt.', 'fooconvert' )
            );
        }

        return $prompt;
    }

    /**
     * Generates a background-specific image prompt from popup and brand context.
     *
     * @param array<string,mixed> $popup_draft Popup draft.
     * @param array<string,mixed> $brand Brand payload.
     * @param string              $instructions Optional additional direction.
     * @return string|WP_Error
     */
    public static function generate_prompt_for_background( array $popup_draft, array $brand = array(), string $instructions = '' ) {
        if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_unavailable',
                __( 'The WordPress AI client is not available for popup background prompts.', 'fooconvert' )
            );
        }

        $draft   = PopupBlueprint::sanitize_popup_draft( $popup_draft );
        $brand   = self::get_effective_brand_context( $brand );
        $content = self::build_popup_background_context( $draft, $brand );

        if ( '' !== trim( $instructions ) ) {
            $content .= "\n\n<additional-direction>" . sanitize_text_field( $instructions ) . '</additional-direction>';
        }

        $result = self::generate_text_prompt(
            $content,
            self::get_background_prompt_system_instruction(),
            0.85
        );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $prompt = sanitize_text_field( trim( (string) $result ) );
        if ( '' === $prompt ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_empty_prompt',
                __( 'The AI client did not return a popup background prompt.', 'fooconvert' )
            );
        }

        return $prompt;
    }

    /**
     * Generates text from the AI client while respecting disabled optional params.
     *
     * @param string $content Prompt content.
     * @param string $system_instruction System instruction.
     * @param float  $temperature Optional temperature value.
     * @return mixed
     */
    private static function generate_text_prompt( string $content, string $system_instruction, float $temperature ) {
        $retry_count = 0;

        do {
            $prompt_builder = self::build_text_prompt_builder( $content, $system_instruction, $temperature );
            $result         = $prompt_builder->generate_text();

            if ( ! is_wp_error( $result ) ) {
                return $result;
            }

            $unsupported_param = self::extract_unsupported_parameter_from_error( $result );
            if ( '' === $unsupported_param || self::is_ai_param_disabled( $unsupported_param ) || $retry_count >= 4 ) {
                return $result;
            }

            self::add_disabled_ai_param( $unsupported_param );
            $retry_count++;
        } while ( true );
    }

    /**
     * Builds a text prompt builder with optional params only when enabled.
     *
     * @param string $content Prompt content.
     * @param string $system_instruction System instruction.
     * @param float  $temperature Optional temperature value.
     * @return mixed
     */
    private static function build_text_prompt_builder( string $content, string $system_instruction, float $temperature ) {
        $prompt_builder = wp_ai_client_prompt( $content );
        $settings       = self::get_ai_settings();

        if ( '' !== $system_instruction && ! self::is_ai_param_disabled( 'system_instruction' ) && method_exists( $prompt_builder, 'using_system_instruction' ) ) {
            $prompt_builder = $prompt_builder->using_system_instruction( $system_instruction );
        }

        if ( ! self::is_ai_param_disabled( 'temperature' ) && method_exists( $prompt_builder, 'using_temperature' ) ) {
            $prompt_builder = $prompt_builder->using_temperature( $temperature );
        }

        if ( ! self::is_ai_param_disabled( 'model' ) && method_exists( $prompt_builder, 'using_model_preference' ) ) {
            $override_model = Settings::sanitize_model( $settings['override_model'] ?? '' );
            if ( '' !== $override_model ) {
                return $prompt_builder->using_model_preference( $override_model );
            }

            if ( function_exists( '\WordPress\AI\get_preferred_models_for_text_generation' ) ) {
                $models = \WordPress\AI\get_preferred_models_for_text_generation();
                if ( is_array( $models ) && ! empty( $models ) ) {
                    $prompt_builder = $prompt_builder->using_model_preference( ...$models );
                }
            }
        }

        return $prompt_builder;
    }

    /**
     * Adds a provider-rejected optional parameter to disabled AI settings.
     *
     * @param string $param Parameter name.
     * @return void
     */
    private static function add_disabled_ai_param( string $param ): void {
        $param = self::normalize_ai_param_name( $param );
        if ( '' === $param ) {
            return;
        }

        self::$runtime_disabled_ai_params[] = $param;
        self::$runtime_disabled_ai_params = self::sanitize_disabled_ai_params( self::$runtime_disabled_ai_params );

        Settings::add_disabled_param( $param );
    }

    /**
     * Returns whether an optional AI request parameter is disabled.
     *
     * @param string $param Parameter name.
     * @return bool
     */
    private static function is_ai_param_disabled( string $param ): bool {
        $settings = self::get_ai_settings();
        $params   = is_array( $settings['disabled_params'] ?? null ) ? $settings['disabled_params'] : array();
        $params   = array_merge( $params, self::$runtime_disabled_ai_params );
        $lookup   = self::get_disabled_param_lookup( $params );

        foreach ( self::get_ai_param_aliases( $param ) as $alias ) {
            if ( isset( $lookup[ $alias ] ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns request-scoped settings when available, otherwise saved settings.
     *
     * @return array<string,mixed>
     */
    private static function get_ai_settings(): array {
        return null !== self::$runtime_ai_settings
            ? self::$runtime_ai_settings
            : Settings::get();
    }

    /**
     * Builds a disabled parameter lookup with known provider aliases.
     *
     * @param mixed $params Disabled params payload.
     * @return array<string,bool>
     */
    private static function get_disabled_param_lookup( $params ): array {
        $lookup = array();
        $params = self::sanitize_disabled_ai_params( $params );

        foreach ( $params as $param ) {
            foreach ( self::get_ai_param_aliases( $param ) as $alias ) {
                $lookup[ $alias ] = true;
            }
        }

        return $lookup;
    }

    /**
     * Sanitizes a disabled parameter payload.
     *
     * @param mixed $value Raw disabled params payload.
     * @return array<int,string>
     */
    private static function sanitize_disabled_ai_params( $value ): array {
        return Settings::sanitize_disabled_params( $value );
    }

    /**
     * Extracts an unsupported request parameter from a model error.
     *
     * @param WP_Error $error Error response.
     * @return string
     */
    private static function extract_unsupported_parameter_from_error( WP_Error $error ): string {
        $message = $error->get_error_message();
        $patterns = array(
            '/Unsupported parameter:\s*[\'"]([^\'"]+)[\'"]/i',
            '/Unsupported parameter\s+([a-z0-9_.-]+)/i',
            '/unsupported[^.]*parameter[^\'"]*[\'"]([^\'"]+)[\'"]/i',
            '/[\'"]([^\'"]+)[\'"]\s+is not supported with this model/i',
        );

        foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern, $message, $matches ) ) {
                return self::normalize_ai_param_name( $matches[1] ?? '' );
            }
        }

        return '';
    }

    /**
     * Returns comparable names for one AI request parameter.
     *
     * @param string $param Parameter name.
     * @return array<int,string>
     */
    private static function get_ai_param_aliases( string $param ): array {
        return Settings::get_param_aliases( $param );
    }

    /**
     * Normalizes a request parameter name.
     *
     * @param mixed $param Raw parameter name.
     * @return string
     */
    private static function normalize_ai_param_name( $param ): string {
        return Settings::normalize_param_name( $param );
    }

    /**
     * Generates raw image data from an image prompt.
     *
     * @param string      $prompt Image prompt.
     * @param string|null $reference_image Optional base64 reference image.
     * @return array<string,mixed>|WP_Error
     */
    public static function generate_image_from_prompt( string $prompt, ?string $reference_image = null ) {
        if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_unavailable',
                __( 'The WordPress AI client is not available for popup image generation.', 'fooconvert' )
            );
        }

        $request_options = new RequestOptions();
        $request_options->setTimeout( 90 );

        $prompt_builder = wp_ai_client_prompt( $prompt )
            ->using_request_options( $request_options )
            ->as_output_file_type( FileTypeEnum::inline() );

        if ( ! self::is_ai_param_disabled( 'model' ) && method_exists( $prompt_builder, 'using_model_preference' ) ) {
            $settings       = self::get_ai_settings();
            $override_model = Settings::sanitize_model( $settings['override_image_model'] ?? '' );

            if ( '' !== $override_model ) {
                $prompt_builder = $prompt_builder->using_model_preference( $override_model );
            } elseif ( function_exists( '\WordPress\AI\get_preferred_image_models' ) ) {
                $models = \WordPress\AI\get_preferred_image_models();
                if ( is_array( $models ) && ! empty( $models ) ) {
                    $prompt_builder = $prompt_builder->using_model_preference( ...$models );
                }
            }
        }

        if ( null !== $reference_image ) {
            try {
                $prompt_builder = $prompt_builder->with_file( new File( $reference_image ) );
            } catch ( Throwable $error ) {
                return new WP_Error(
                    'fooconvert_ai_popup_media_invalid_reference',
                    __( 'The popup reference image is not valid base64 image data.', 'fooconvert' )
                );
            }
        }

        $result = $prompt_builder->generate_image_result();
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        try {
            $image_file = $result->toImageFile();
            $data       = sanitize_text_field( trim( (string) $image_file->getBase64Data() ) );

            if ( '' === $data ) {
                return new WP_Error(
                    'fooconvert_ai_popup_media_empty_data',
                    __( 'No image data was generated for the popup.', 'fooconvert' )
                );
            }

            $provider_metadata = $result->getProviderMetadata()->toArray();
            $model_metadata    = $result->getModelMetadata()->toArray();

            unset( $provider_metadata[ ProviderMetadata::KEY_CREDENTIALS_URL ] );
            unset( $model_metadata[ ModelMetadata::KEY_SUPPORTED_OPTIONS ] );
            unset( $model_metadata[ ModelMetadata::KEY_SUPPORTED_CAPABILITIES ] );

            return array(
                'data'              => $data,
                'mime_type'         => $image_file->getMimeType(),
                'provider_metadata' => $provider_metadata,
                'model_metadata'    => $model_metadata,
            );
        } catch ( Throwable $error ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_empty_data',
                __( 'No image data was generated for the popup.', 'fooconvert' ),
                $error
            );
        }
    }

    /**
     * Imports generated base64 image data into the media library.
     *
     * @param string               $data Base64 image data.
     * @param array<string,mixed>  $args Attachment arguments.
     * @return array<string,mixed>|WP_Error
     */
    public static function import_base64_image( string $data, array $args = array() ) {
        if ( ! self::can_manage_media() ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_forbidden',
                __( 'You do not have permission to import popup images.', 'fooconvert' )
            );
        }

        $args = wp_parse_args(
            $args,
            array(
                'filename'    => 'fooconvert-popup-image-' . time(),
                'title'       => '',
                'description' => '',
                'alt_text'    => '',
                'mime_type'   => null,
                'prompt'      => '',
                'popup_type'  => '',
                'source'      => self::SOURCE_POPUP_BUILDER,
            )
        );

        try {
            $file = new File( $data, $args['mime_type'] );
        } catch ( Throwable $error ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_invalid_data',
                __( 'The popup image data is not valid base64.', 'fooconvert' )
            );
        }

        if ( ! $file->isImage() ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_invalid_image',
                __( 'The popup image data is not a valid image.', 'fooconvert' )
            );
        }

        $base64_data = $file->getBase64Data();
        if ( empty( $base64_data ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_empty_base64',
                __( 'No popup image data was found to import.', 'fooconvert' )
            );
        }

        $imported = self::sideload_base64_image(
            $base64_data,
            array(
                'mime_type'   => $file->getMimeType(),
                'title'       => $args['title'],
                'description' => $args['description'],
                'filename'    => $args['filename'],
                'alt_text'    => $args['alt_text'],
            )
        );

        if ( is_wp_error( $imported ) ) {
            return $imported;
        }

        $attachment_id = absint( $imported['id'] ?? 0 );
        if ( $attachment_id > 0 ) {
            update_post_meta( $attachment_id, self::META_GENERATED, 1 );
            update_post_meta( $attachment_id, self::META_PROMPT, sanitize_text_field( (string) $args['prompt'] ) );
            update_post_meta( $attachment_id, self::META_POPUP_TYPE, fooconvert_normalize_popup_type( $args['popup_type'] ) );
            update_post_meta( $attachment_id, self::META_SOURCE, sanitize_text_field( (string) $args['source'] ) );
        }

        $item = self::prepare_attachment( $attachment_id );
        return is_array( $item ) ? $item : $imported;
    }

    /**
     * Generates and imports a popup image in one step.
     *
     * @param array<string,mixed> $popup_draft Popup draft.
     * @param string              $instructions Optional additional direction.
     * @return array<string,mixed>|WP_Error
     */
    public static function generate_popup_media( array $popup_draft, string $instructions = '' ) {
        $draft  = PopupBlueprint::sanitize_popup_draft( $popup_draft );
        $prompt = self::generate_prompt_for_popup( $draft, $instructions );

        if ( is_wp_error( $prompt ) ) {
            return $prompt;
        }

        $generated_image = self::generate_image_from_prompt( $prompt );
        if ( is_wp_error( $generated_image ) ) {
            return $generated_image;
        }

        $title = self::build_attachment_title( $draft );
        $alt   = self::build_attachment_alt_text( $draft );

        $image = self::import_base64_image(
            (string) $generated_image['data'],
            array(
                'filename'    => sanitize_title( $title ),
                'title'       => $title,
                'description' => self::build_attachment_description( $prompt, $generated_image ),
                'alt_text'    => $alt,
                'mime_type'   => isset( $generated_image['mime_type'] ) ? (string) $generated_image['mime_type'] : null,
                'prompt'      => $prompt,
                'popup_type'  => $draft['popup_type'] ?? '',
                'source'      => self::SOURCE_POPUP_BUILDER,
            )
        );

        if ( is_wp_error( $image ) ) {
            return $image;
        }

        return array(
            'prompt' => $prompt,
            'image'  => $image,
        );
    }

    /**
     * Generates and imports a popup background image in one step.
     *
     * @param array<string,mixed> $popup_draft Popup draft.
     * @param array<string,mixed> $brand Brand payload.
     * @param string              $instructions Optional additional direction.
     * @return array<string,mixed>|WP_Error
     */
    public static function generate_popup_background( array $popup_draft, array $brand = array(), string $instructions = '' ) {
        $draft  = PopupBlueprint::sanitize_popup_draft( $popup_draft );
        $brand  = self::get_effective_brand_context( $brand );
        $prompt = self::generate_prompt_for_background( $draft, $brand, $instructions );

        if ( is_wp_error( $prompt ) ) {
            return $prompt;
        }

        $generated_image = self::generate_image_from_prompt( $prompt );
        if ( is_wp_error( $generated_image ) ) {
            return $generated_image;
        }

        $title = self::build_background_attachment_title( $draft );
        $alt   = self::build_background_attachment_alt_text( $draft );

        $image = self::import_base64_image(
            (string) $generated_image['data'],
            array(
                'filename'    => sanitize_title( $title ),
                'title'       => $title,
                'description' => self::build_attachment_description( $prompt, $generated_image ),
                'alt_text'    => $alt,
                'mime_type'   => isset( $generated_image['mime_type'] ) ? (string) $generated_image['mime_type'] : null,
                'prompt'      => $prompt,
                'popup_type'  => $draft['popup_type'] ?? '',
                'source'      => self::SOURCE_POPUP_BACKGROUND,
            )
        );

        if ( is_wp_error( $image ) ) {
            return $image;
        }

        return array(
            'prompt' => $prompt,
            'image'  => $image,
        );
    }

    /**
     * Returns whether the popup draft already has a background image configured.
     *
     * @param array<string,mixed> $popup_draft Popup draft.
     * @return bool
     */
    public static function popup_draft_has_background( array $popup_draft ): bool {
        $draft = PopupBlueprint::sanitize_popup_draft( $popup_draft );

        if ( self::root_attributes_have_background( $draft['root_attributes'] ?? array() ) ) {
            return true;
        }

        $template_slug = isset( $draft['template_slug'] ) ? (string) $draft['template_slug'] : '';
        if ( '' === $template_slug ) {
            return false;
        }

        $template = Catalog::get_template_by_slug( $template_slug );

        return is_array( $template ) && self::root_attributes_have_background( $template['attributes'] ?? array() );
    }

    /**
     * Deletes a generated popup image from the media library.
     *
     * @param int $attachment_id Attachment ID.
     * @return true|WP_Error
     */
    public static function delete_generated_image( int $attachment_id ) {
        $attachment = get_post( $attachment_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_not_found',
                __( 'The generated popup image could not be found.', 'fooconvert' ),
                array( 'status' => 404 )
            );
        }

        if ( ! current_user_can( 'delete_post', $attachment_id ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_forbidden',
                __( 'You do not have permission to delete this popup image.', 'fooconvert' ),
                array( 'status' => 403 )
            );
        }

        if ( '1' !== (string) get_post_meta( $attachment_id, self::META_GENERATED, true ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_invalid_attachment',
                __( 'Only popup-builder generated images can be deleted from this panel.', 'fooconvert' ),
                array( 'status' => 400 )
            );
        }

        $deleted = wp_delete_attachment( $attachment_id, true );
        if ( ! $deleted ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_delete_failed',
                __( 'The generated popup image could not be deleted.', 'fooconvert' ),
                array( 'status' => 500 )
            );
        }

        return true;
    }

    /**
     * Inserts or updates an image block in a popup draft.
     *
     * @param array<string,mixed> $popup_draft Popup draft.
     * @param array<string,mixed> $media_item Media item.
     * @return array<string,mixed>
     */
    public static function inject_media_into_popup_draft( array $popup_draft, array $media_item ): array {
        $draft = PopupBlueprint::sanitize_popup_draft( $popup_draft );
        $image = self::sanitize_media_items( array( $media_item ) );

        if ( empty( $image ) ) {
            return $draft;
        }

        $image_block = array(
            'name'       => 'core/image',
            'attributes' => array(
                'id'    => $image[0]['id'],
                'url'   => $image[0]['url'],
                'alt'   => $image[0]['alt'],
                'title' => $image[0]['title'],
            ),
        );

        $blocks = is_array( $draft['content_blocks'] ?? null ) ? $draft['content_blocks'] : array();
        if ( self::replace_first_image_block( $blocks, $image_block ) ) {
            $draft['content_blocks'] = $blocks;
            return PopupBlueprint::sanitize_popup_draft( $draft );
        }

        $insert_index = self::get_image_insert_index( $blocks );
        array_splice( $blocks, $insert_index, 0, array( $image_block ) );
        $draft['content_blocks'] = $blocks;

        return PopupBlueprint::sanitize_popup_draft( $draft );
    }

    /**
     * Applies a generated background image to popup root attributes.
     *
     * @param array<string,mixed> $popup_draft Popup draft.
     * @param array<string,mixed> $media_item Media item.
     * @return array<string,mixed>
     */
    public static function inject_background_into_popup_draft( array $popup_draft, array $media_item ): array {
        $draft = PopupBlueprint::sanitize_popup_draft( $popup_draft );
        $image = self::sanitize_media_items( array( $media_item ) );

        if ( empty( $image ) ) {
            return $draft;
        }

        $background_image = array_filter(
            array(
                'url'    => $image[0]['url'],
                'id'     => $image[0]['id'] > 0 ? $image[0]['id'] : null,
                'source' => 'file',
                'title'  => '' !== $image[0]['title'] ? $image[0]['title'] : null,
            ),
            static function( $value ): bool {
                return null !== $value && '' !== $value;
            }
        );

        if ( empty( $background_image['url'] ) ) {
            return $draft;
        }

        $root_attributes = is_array( $draft['root_attributes'] ?? null ) ? $draft['root_attributes'] : array();
        $content         = is_array( $root_attributes['content'] ?? null ) ? $root_attributes['content'] : array();
        $styles          = is_array( $content['styles'] ?? null ) ? $content['styles'] : array();
        $background      = is_array( $styles['background'] ?? null ) ? $styles['background'] : array();

        $background['backgroundImage'] = $background_image;

        if ( empty( $background['backgroundSize'] ) || ! is_string( $background['backgroundSize'] ) ) {
            $background['backgroundSize'] = 'cover';
        }

        $styles['background']        = $background;
        $content['styles']           = $styles;
        $root_attributes['content']  = $content;
        $draft['root_attributes']    = $root_attributes;

        return PopupBlueprint::sanitize_popup_draft( $draft );
    }

    /**
     * Returns the prompt-generation system instruction for popup imagery.
     *
     * @return string
     */
    private static function get_image_prompt_system_instruction(): string {
        return implode(
            "\n",
            array(
                'You generate a single image-generation prompt for a marketing popup.',
                'Output only the final prompt text with no commentary.',
                'Use the popup goal, audience, offer, and conversion strategy as factual grounding.',
                'Describe a high-quality image that supports the popup and improves conversion intent.',
                'Do not include typography, UI chrome, watermarks, or logos in the image unless explicitly requested.',
                'Favor clean editorial composition, realistic lighting, and space for nearby popup copy.',
                'If the popup promotes a product or offer, make the subject feel specific and commercially useful rather than abstract.',
                'Keep the prompt concise but specific enough to generate a polished, production-ready marketing image.',
            )
        );
    }

    /**
     * Returns the prompt-generation system instruction for popup backgrounds.
     *
     * @return string
     */
    private static function get_background_prompt_system_instruction(): string {
        return implode(
            "\n",
            array(
                'You generate a single image-generation prompt for a marketing popup background.',
                'Output only the final prompt text with no commentary.',
                'This asset must function as a background behind popup copy and CTA controls, not as a standalone poster.',
                'Use the popup goal, audience, offer, popup format, and brand context as factual grounding.',
                'Favor brand-aligned color, restrained contrast, large calm areas, and composition that preserves legibility for nearby text.',
                'Do not include typography, UI chrome, mockups, watermarks, logos, or embedded text in the image.',
                'Keep any subject matter secondary and supportive so the CTA remains dominant.',
                'Respect the requested aspect ratio guidance implied by the popup format.',
                'Keep the prompt concise but specific enough to generate a polished, production-ready popup background.',
            )
        );
    }

    /**
     * Builds the textual context used to generate popup image prompts.
     *
     * @param array<string,mixed> $draft Popup draft.
     * @return string
     */
    private static function build_popup_image_context( array $draft ): string {
        $copy_fragments = self::extract_copy_fragments( $draft['content_blocks'] ?? array() );
        $template       = Catalog::get_template_by_slug( (string) ( $draft['template_slug'] ?? '' ) );
        $popup_type     = fooconvert_get_popup_type_label( $draft['popup_type'] ?? '' );
        $playbook       = Catalog::get_conversion_playbook();
        $popup_guidance = $playbook['popup_types'][ $draft['popup_type'] ?? '' ] ?? array();

        $lines = array(
            '<popup>',
            'Title: ' . self::sanitize_text( $draft['title'] ?? '' ),
            'Popup Type: ' . self::sanitize_text( $popup_type ),
            'Goal: ' . self::sanitize_text( $draft['goal'] ?? '' ),
            'Audience: ' . self::sanitize_text( $draft['audience'] ?? '' ),
            'Offer: ' . self::sanitize_text( $draft['offer'] ?? '' ),
        );

        if ( is_array( $template ) ) {
            $lines[] = 'Template: ' . self::sanitize_text( $template['title'] ?? '' );
            $lines[] = 'Template Description: ' . self::sanitize_text( $template['description'] ?? '' );
        }

        if ( ! empty( $copy_fragments ) ) {
            $lines[] = 'Popup Copy: ' . implode( ' | ', array_slice( $copy_fragments, 0, 8 ) );
        }

        if ( ! empty( $draft['conversion_rationale'] ) && is_array( $draft['conversion_rationale'] ) ) {
            $lines[] = 'Conversion Rationale: ' . implode( ' | ', array_map( 'sanitize_text_field', array_slice( $draft['conversion_rationale'], 0, 4 ) ) );
        }

        if ( ! empty( $popup_guidance['best_for'] ) ) {
            $lines[] = 'Popup Format Guidance: ' . self::sanitize_text( $popup_guidance['best_for'] );
        }

        $lines[] = 'Image Requirements: Support the offer, feel commercially useful, leave room for adjacent copy, and avoid embedded text.';
        $lines[] = '</popup>';

        return implode( "\n", array_filter( $lines ) );
    }

    /**
     * Builds the textual context used to generate popup background prompts.
     *
     * @param array<string,mixed> $draft Popup draft.
     * @param array<string,mixed> $brand Brand payload.
     * @return string
     */
    private static function build_popup_background_context( array $draft, array $brand ): string {
        $copy_fragments      = self::extract_copy_fragments( $draft['content_blocks'] ?? array() );
        $template            = Catalog::get_template_by_slug( (string) ( $draft['template_slug'] ?? '' ) );
        $popup_type_key      = (string) ( $draft['popup_type'] ?? '' );
        $popup_type          = fooconvert_get_popup_type_label( $popup_type_key );
        $popup_ratio         = self::get_popup_background_aspect_ratio( $popup_type_key );
        $popup_composition   = self::get_popup_background_composition_guidance( $popup_type_key );
        $playbook            = Catalog::get_conversion_playbook();
        $popup_guidance      = $playbook['popup_types'][ $popup_type_key ] ?? array();
        $brand_context_lines = self::build_background_brand_context_lines( $brand );

        $lines = array(
            '<popup-background>',
            'Title: ' . self::sanitize_text( $draft['title'] ?? '' ),
            'Popup Type: ' . self::sanitize_text( $popup_type ),
            'Target Aspect Ratio: ' . self::sanitize_text( $popup_ratio ),
            'Goal: ' . self::sanitize_text( $draft['goal'] ?? '' ),
            'Audience: ' . self::sanitize_text( $draft['audience'] ?? '' ),
            'Offer: ' . self::sanitize_text( $draft['offer'] ?? '' ),
            'Composition Guidance: ' . self::sanitize_text( $popup_composition ),
        );

        if ( is_array( $template ) ) {
            $lines[] = 'Template: ' . self::sanitize_text( $template['title'] ?? '' );
            $lines[] = 'Template Description: ' . self::sanitize_text( $template['description'] ?? '' );
        }

        if ( ! empty( $copy_fragments ) ) {
            $lines[] = 'Popup Copy Signals: ' . implode( ' | ', array_slice( $copy_fragments, 0, 8 ) );
        }

        if ( ! empty( $popup_guidance['best_for'] ) ) {
            $lines[] = 'Popup Format Guidance: ' . self::sanitize_text( $popup_guidance['best_for'] );
        }

        if ( ! empty( $brand_context_lines ) ) {
            $lines[] = '<brand>';
            $lines   = array_merge( $lines, $brand_context_lines );
            $lines[] = '</brand>';
        }

        $lines[] = 'Background Requirements: Create a background-only asset that is stylish but restrained, preserves generous negative space for headline and CTA copy, avoids embedded text, avoids UI elements, and does not compete with the conversion goal.';
        $lines[] = '</popup-background>';

        return implode( "\n", array_filter( $lines ) );
    }

    /**
     * Extracts textual copy fragments from popup content blocks.
     *
     * @param mixed $blocks Popup blocks.
     * @return array<int,string>
     */
    private static function extract_copy_fragments( $blocks ): array {
        if ( ! is_array( $blocks ) ) {
            return array();
        }

        $fragments = array();

        foreach ( $blocks as $block ) {
            if ( ! is_array( $block ) ) {
                continue;
            }

            $name       = isset( $block['name'] ) ? (string) $block['name'] : '';
            $attributes = is_array( $block['attributes'] ?? null ) ? $block['attributes'] : array();

            if ( in_array( $name, array( 'core/heading', 'core/paragraph' ), true ) && ! empty( $attributes['content'] ) ) {
                $fragments[] = self::sanitize_text( $attributes['content'] );
            } elseif ( 'core/button' === $name && ! empty( $attributes['text'] ) ) {
                $fragments[] = self::sanitize_text( $attributes['text'] );
            } elseif ( 'core/list' === $name && is_array( $attributes['items'] ?? null ) ) {
                foreach ( array_slice( $attributes['items'], 0, 4 ) as $item ) {
                    $fragments[] = self::sanitize_text( $item );
                }
            } elseif ( 'fc/sign-up' === $name ) {
                $button_text = $attributes['button']['settings']['text'] ?? '';
                $fragments[] = self::sanitize_text( $button_text );
            }

            if ( ! empty( $block['inner_blocks'] ) && is_array( $block['inner_blocks'] ) ) {
                $fragments = array_merge( $fragments, self::extract_copy_fragments( $block['inner_blocks'] ) );
            }
        }

        return array_values( array_filter( array_unique( $fragments ) ) );
    }

    /**
     * Builds a media title from popup draft context.
     *
     * @param array<string,mixed> $draft Popup draft.
     * @return string
     */
    private static function build_attachment_title( array $draft ): string {
        $base = self::sanitize_text( $draft['title'] ?? '' );
        if ( '' === $base ) {
            $base = self::sanitize_text( $draft['offer'] ?? '' );
        }

        if ( '' === $base ) {
            $base = __( 'Popup Visual', 'fooconvert' );
        }

        return sprintf(
            /* translators: %s: popup title or offer */
            __( '%s Image', 'fooconvert' ),
            $base
        );
    }

    /**
     * Builds a background media title from popup draft context.
     *
     * @param array<string,mixed> $draft Popup draft.
     * @return string
     */
    private static function build_background_attachment_title( array $draft ): string {
        $base = self::sanitize_text( $draft['title'] ?? '' );
        if ( '' === $base ) {
            $base = self::sanitize_text( $draft['offer'] ?? '' );
        }

        if ( '' === $base ) {
            $base = __( 'Popup', 'fooconvert' );
        }

        return sprintf(
            /* translators: %s: popup title or offer */
            __( '%s Background', 'fooconvert' ),
            $base
        );
    }

    /**
     * Builds attachment alt text from popup draft context.
     *
     * @param array<string,mixed> $draft Popup draft.
     * @return string
     */
    private static function build_attachment_alt_text( array $draft ): string {
        $offer    = self::sanitize_text( $draft['offer'] ?? '' );
        $audience = self::sanitize_text( $draft['audience'] ?? '' );

        if ( '' !== $offer && '' !== $audience ) {
            return sprintf(
                /* translators: 1: offer text, 2: audience */
                __( '%1$s visual for %2$s', 'fooconvert' ),
                $offer,
                $audience
            );
        }

        if ( '' !== $offer ) {
            return sprintf(
                /* translators: %s: popup offer */
                __( '%s popup visual', 'fooconvert' ),
                $offer
            );
        }

        return self::build_attachment_title( $draft );
    }

    /**
     * Builds background attachment alt text from popup draft context.
     *
     * @param array<string,mixed> $draft Popup draft.
     * @return string
     */
    private static function build_background_attachment_alt_text( array $draft ): string {
        $offer    = self::sanitize_text( $draft['offer'] ?? '' );
        $audience = self::sanitize_text( $draft['audience'] ?? '' );

        if ( '' !== $offer && '' !== $audience ) {
            return sprintf(
                /* translators: 1: offer text, 2: audience */
                __( '%1$s popup background for %2$s', 'fooconvert' ),
                $offer,
                $audience
            );
        }

        if ( '' !== $offer ) {
            return sprintf(
                /* translators: %s: popup offer */
                __( '%s popup background', 'fooconvert' ),
                $offer
            );
        }

        return self::build_background_attachment_title( $draft );
    }

    /**
     * Builds a stored attachment description.
     *
     * @param string              $prompt Generated prompt.
     * @param array<string,mixed> $generated_image Raw generation response.
     * @return string
     */
    private static function build_attachment_description( string $prompt, array $generated_image ): string {
        $provider = sanitize_text_field( (string) ( $generated_image['provider_metadata']['name'] ?? '' ) );
        $model    = sanitize_text_field( (string) ( $generated_image['model_metadata']['name'] ?? '' ) );
        $parts    = array_filter(
            array(
                /* translators: %s: AI provider name. */
                $provider ? sprintf( __( 'Generated by %s', 'fooconvert' ), $provider ) : '',
                /* translators: %s: AI model name. */
                $model ? sprintf( __( 'using %s', 'fooconvert' ), $model ) : '',
                /* translators: %s: image generation prompt. */
                $prompt ? sprintf( __( 'Prompt: %s', 'fooconvert' ), $prompt ) : '',
            )
        );

        return sanitize_text_field( implode( '. ', $parts ) );
    }

    /**
     * Returns the popup-format aspect ratio guidance used for backgrounds.
     *
     * @param string $popup_type Popup type.
     * @return string
     */
    private static function get_popup_background_aspect_ratio( string $popup_type ): string {
        switch ( fooconvert_normalize_popup_type( $popup_type ) ) {
            case FOOCONVERT_POPUP_TYPE_BAR:
                return '3:1 panoramic banner';
            case FOOCONVERT_POPUP_TYPE_FLYOUT:
                return '3:4 vertical flyout';
            case FOOCONVERT_POPUP_TYPE_POPUP:
            case FOOCONVERT_POPUP_TYPE_OVERLAY:
            default:
                return '16:9 wide popup';
        }
    }

    /**
     * Returns popup-format composition guidance for backgrounds.
     *
     * @param string $popup_type Popup type.
     * @return string
     */
    private static function get_popup_background_composition_guidance( string $popup_type ): string {
        switch ( fooconvert_normalize_popup_type( $popup_type ) ) {
            case FOOCONVERT_POPUP_TYPE_BAR:
                return 'Keep the composition panoramic and restrained with edge detail only so a compact horizontal CTA remains readable.';
            case FOOCONVERT_POPUP_TYPE_FLYOUT:
                return 'Favor a vertical composition with soft focal weight near the top or bottom and a large calm zone for stacked copy and form controls.';
            case FOOCONVERT_POPUP_TYPE_POPUP:
            case FOOCONVERT_POPUP_TYPE_OVERLAY:
            default:
                return 'Favor a wide composition with a clear quiet region for headline, support copy, and CTA treatment.';
        }
    }

    /**
     * Returns whether root attributes include a content background image.
     *
     * @param mixed $root_attributes Popup root attributes.
     * @return bool
     */
    private static function root_attributes_have_background( $root_attributes ): bool {
        if ( ! is_array( $root_attributes ) ) {
            return false;
        }

        $background = $root_attributes['content']['styles']['background'] ?? null;
        if ( ! is_array( $background ) ) {
            return false;
        }

        $background_image = $background['backgroundImage'] ?? null;

        if ( is_array( $background_image ) ) {
            return '' !== trim( (string) ( $background_image['url'] ?? '' ) )
                || absint( $background_image['id'] ?? 0 ) > 0;
        }

        if ( is_string( $background_image ) ) {
            $value = trim( $background_image );

            return '' !== $value && 'none' !== strtolower( $value );
        }

        return false;
    }

    /**
     * Returns a sanitized brand payload, falling back to the saved brand when needed.
     *
     * @param array<string,mixed> $brand Brand payload.
     * @return array<string,mixed>
     */
    private static function get_effective_brand_context( array $brand ): array {
        $brand = BrandManager::sanitize_brand( $brand );

        if ( self::brand_has_prompt_context( $brand ) ) {
            return $brand;
        }

        $saved_brand = BrandManager::get_saved_brand();

        return is_array( $saved_brand ) ? BrandManager::sanitize_brand( $saved_brand ) : array();
    }

    /**
     * Returns whether the brand contains prompt-relevant context.
     *
     * @param array<string,mixed> $brand Brand payload.
     * @return bool
     */
    private static function brand_has_prompt_context( array $brand ): bool {
        if ( '' !== trim( (string) ( $brand['brandOverview'] ?? '' ) ) ) {
            return true;
        }

        foreach ( array( 'primary', 'secondary', 'accent', 'background', 'textPrimary', 'textSecondary' ) as $color_key ) {
            if ( '' !== trim( (string) ( $brand['colors'][ $color_key ] ?? '' ) ) ) {
                return true;
            }
        }

        foreach ( array( 'primary', 'heading' ) as $font_key ) {
            if ( '' !== trim( (string) ( $brand['typography']['fontFamilies'][ $font_key ] ?? '' ) ) ) {
                return true;
            }
        }

        foreach ( array( 'buttonPrimary', 'buttonSecondary' ) as $button_key ) {
            foreach ( array( 'background', 'textColor', 'borderColor', 'borderRadius' ) as $setting_key ) {
                if ( '' !== trim( (string) ( $brand['components'][ $button_key ][ $setting_key ] ?? '' ) ) ) {
                    return true;
                }
            }
        }

        return absint( $brand['spacing']['baseUnit'] ?? 0 ) > 0
            || '' !== trim( (string) ( $brand['spacing']['borderRadius'] ?? '' ) );
    }

    /**
     * Builds background-oriented brand context lines for prompt generation.
     *
     * @param array<string,mixed> $brand Brand payload.
     * @return array<int,string>
     */
    private static function build_background_brand_context_lines( array $brand ): array {
        $brand = BrandManager::sanitize_brand( $brand );

        if ( ! self::brand_has_prompt_context( $brand ) ) {
            return array();
        }

        $palette = array_filter(
            array(
                'primary ' . self::sanitize_text( $brand['colors']['primary'] ?? '' ),
                'secondary ' . self::sanitize_text( $brand['colors']['secondary'] ?? '' ),
                'accent ' . self::sanitize_text( $brand['colors']['accent'] ?? '' ),
                'background ' . self::sanitize_text( $brand['colors']['background'] ?? '' ),
                'text ' . self::sanitize_text( $brand['colors']['textPrimary'] ?? '' ),
                'textSecondary ' . self::sanitize_text( $brand['colors']['textSecondary'] ?? '' ),
            )
        );

        $lines = array(
            'Brand Overview: ' . self::sanitize_text( $brand['brandOverview'] ?? '' ),
            'Preferred Color Scheme: ' . self::sanitize_text( $brand['colorScheme'] ?? '' ),
        );

        if ( ! empty( $palette ) ) {
            $lines[] = 'Brand Palette: ' . implode( ' | ', $palette );
        }

        $typography = array_filter(
            array(
                'Primary font ' . self::sanitize_text( $brand['typography']['fontFamilies']['primary'] ?? '' ),
                'Heading font ' . self::sanitize_text( $brand['typography']['fontFamilies']['heading'] ?? '' ),
                'Body size ' . self::sanitize_text( $brand['typography']['fontSizes']['body']['value'] ?? '' ),
                'Heading size ' . self::sanitize_text( $brand['typography']['fontSizes']['h1']['value'] ?? '' ),
            )
        );

        if ( ! empty( $typography ) ) {
            $lines[] = 'Typography Direction: ' . implode( ' | ', $typography );
        }

        $shape_language = array_filter(
            array(
                absint( $brand['spacing']['baseUnit'] ?? 0 ) > 0 ? 'Base spacing unit ' . absint( $brand['spacing']['baseUnit'] ?? 0 ) . 'px' : '',
                '' !== trim( (string) ( $brand['spacing']['borderRadius'] ?? '' ) )
                    ? 'Default border radius ' . self::sanitize_text( $brand['spacing']['borderRadius'] ?? '' )
                    : '',
            )
        );

        if ( ! empty( $shape_language ) ) {
            $lines[] = 'Spacing and Shape: ' . implode( ' | ', $shape_language );
        }

        $button_primary = array_filter(
            array(
                'background ' . self::sanitize_text( $brand['components']['buttonPrimary']['background'] ?? '' ),
                'text ' . self::sanitize_text( $brand['components']['buttonPrimary']['textColor'] ?? '' ),
                'radius ' . self::sanitize_text( $brand['components']['buttonPrimary']['borderRadius'] ?? '' ),
            )
        );

        if ( ! empty( $button_primary ) ) {
            $lines[] = 'Primary CTA Style: ' . implode( ' | ', $button_primary );
        }

        $button_secondary = array_filter(
            array(
                'background ' . self::sanitize_text( $brand['components']['buttonSecondary']['background'] ?? '' ),
                'text ' . self::sanitize_text( $brand['components']['buttonSecondary']['textColor'] ?? '' ),
                'border ' . self::sanitize_text( $brand['components']['buttonSecondary']['borderColor'] ?? '' ),
                'radius ' . self::sanitize_text( $brand['components']['buttonSecondary']['borderRadius'] ?? '' ),
            )
        );

        if ( ! empty( $button_secondary ) ) {
            $lines[] = 'Secondary CTA Style: ' . implode( ' | ', $button_secondary );
        }

        $lines[] = 'Brand Requirement: The background should feel on-brand and must not reduce the contrast or prominence of the popup CTA treatment.';

        return array_values( array_filter( $lines ) );
    }

    /**
     * Imports a decoded base64 image into the media library.
     *
     * @param string              $data Base64 image data.
     * @param array<string,mixed> $args Attachment arguments.
     * @return array<string,mixed>|WP_Error
     */
    private static function sideload_base64_image( string $data, array $args = array() ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $estimated_size = (int) floor( strlen( rtrim( $data, '=' ) ) * 3 / 4 );
        $max_upload_size = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
        if ( $max_upload_size > 0 && $estimated_size > $max_upload_size ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_file_too_large',
                __( 'The popup image is larger than the maximum allowed upload size.', 'fooconvert' ),
                array( 'status' => 413 )
            );
        }

        $decoded_data = base64_decode( $data, true );
        if ( false === $decoded_data ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_invalid_base64',
                __( 'The popup image could not be decoded from base64.', 'fooconvert' )
            );
        }

        $temp_file = wp_tempnam( 'fooconvert-popup-image' );
        if ( ! is_string( $temp_file ) || '' === $temp_file ) {
            return new WP_Error(
                'fooconvert_ai_popup_media_temp_file_failed',
                __( 'A temporary file could not be created for the popup image.', 'fooconvert' )
            );
        }

        $bytes_written = file_put_contents( $temp_file, $decoded_data ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
        if ( false === $bytes_written ) {
            wp_delete_file( $temp_file );
            return new WP_Error(
                'fooconvert_ai_popup_media_write_failed',
                __( 'The popup image could not be written to a temporary file.', 'fooconvert' )
            );
        }

        $extension = wp_get_default_extension_for_mime_type( (string) $args['mime_type'] );
        $file_args = array(
            'name'     => sanitize_file_name( (string) $args['filename'] ) . '.' . $extension,
            'type'     => (string) $args['mime_type'],
            'tmp_name' => $temp_file,
        );

        $attachment_id = media_handle_sideload(
            $file_args,
            0,
            (string) $args['description'],
            array(
                'post_title'     => sanitize_text_field( (string) $args['title'] ),
                'post_content'   => sanitize_text_field( (string) $args['description'] ),
                'post_mime_type' => (string) $args['mime_type'],
                'meta_input'     => array(
                    '_wp_attachment_image_alt' => sanitize_text_field( (string) $args['alt_text'] ),
                ),
            )
        );

        if ( file_exists( $temp_file ) ) {
            wp_delete_file( $temp_file );
        }

        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        return array(
            'id' => absint( $attachment_id ),
        );
    }

    /**
     * Replaces the first existing image block inside a block array.
     *
     * @param array<int,array<string,mixed>> $blocks Blocks to search.
     * @param array<string,mixed>            $image_block Replacement image block.
     * @return bool
     */
    private static function replace_first_image_block( array &$blocks, array $image_block ): bool {
        foreach ( $blocks as $index => &$block ) {
            if ( ! is_array( $block ) ) {
                continue;
            }

            if ( 'core/image' === ( $block['name'] ?? '' ) ) {
                $blocks[ $index ] = $image_block;
                return true;
            }

            if ( ! empty( $block['inner_blocks'] ) && is_array( $block['inner_blocks'] ) ) {
                if ( self::replace_first_image_block( $block['inner_blocks'], $image_block ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Chooses a sensible insertion point for a top-level popup image block.
     *
     * @param array<int,array<string,mixed>> $blocks Popup blocks.
     * @return int
     */
    private static function get_image_insert_index( array $blocks ): int {
        if ( empty( $blocks ) ) {
            return 0;
        }

        foreach ( $blocks as $index => $block ) {
            $name = is_array( $block ) ? (string) ( $block['name'] ?? '' ) : '';
            if ( in_array( $name, array( 'fc/sign-up', 'core/buttons', 'core/button' ), true ) ) {
                return $index;
            }
        }

        return min( 2, count( $blocks ) );
    }

    /**
     * Sanitizes a plain text value.
     *
     * @param mixed $value Value to sanitize.
     * @return string
     */
    private static function sanitize_text( $value ): string {
        return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
    }
}
