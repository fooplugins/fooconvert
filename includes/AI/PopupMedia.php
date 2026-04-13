<?php

namespace FooPlugins\FooConvert\AI;

use Throwable;
use WP_Error;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

class PopupMedia {

    public const META_GENERATED = '_fooconvert_ai_popup_generated';

    public const META_PROMPT = '_fooconvert_ai_popup_prompt';

    public const META_POPUP_TYPE = '_fooconvert_ai_popup_type';

    public const META_SOURCE = '_fooconvert_ai_popup_source';

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
            'required'   => array( 'data', 'provider_metadata', 'model_metadata' ),
            'properties' => array(
                'data'              => array(
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

        $prompt_builder = wp_ai_client_prompt( $content )
            ->using_system_instruction( self::get_image_prompt_system_instruction() )
            ->using_temperature( 0.9 );

        if ( function_exists( '\WordPress\AI\get_preferred_models_for_text_generation' ) ) {
            $models = \WordPress\AI\get_preferred_models_for_text_generation();
            if ( is_array( $models ) && ! empty( $models ) ) {
                $prompt_builder = $prompt_builder->using_model_preference( ...$models );
            }
        }

        $result = $prompt_builder->generate_text();
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

        if ( function_exists( '\WordPress\AI\get_preferred_image_models' ) ) {
            $models = \WordPress\AI\get_preferred_image_models();
            if ( is_array( $models ) && ! empty( $models ) ) {
                $prompt_builder = $prompt_builder->using_model_preference( ...$models );
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
            update_post_meta( $attachment_id, self::META_SOURCE, 'ai-popup-builder' );
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
                'prompt'      => $prompt,
                'popup_type'  => $draft['popup_type'] ?? '',
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
     * Builds the textual context used to generate popup image prompts.
     *
     * @param array<string,mixed> $draft Popup draft.
     * @return string
     */
    private static function build_popup_image_context( array $draft ): string {
        $copy_fragments = self::extract_copy_fragments( $draft['content_blocks'] ?? array() );
        $template       = PopupBlueprint::get_template_by_slug( (string) ( $draft['template_slug'] ?? '' ) );
        $popup_type     = fooconvert_get_popup_type_label( $draft['popup_type'] ?? '' );
        $playbook       = PopupBlueprint::get_conversion_playbook();
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
                $provider ? sprintf( __( 'Generated by %s', 'fooconvert' ), $provider ) : '',
                $model ? sprintf( __( 'using %s', 'fooconvert' ), $model ) : '',
                $prompt ? sprintf( __( 'Prompt: %s', 'fooconvert' ), $prompt ) : '',
            )
        );

        return sanitize_text_field( implode( '. ', $parts ) );
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
