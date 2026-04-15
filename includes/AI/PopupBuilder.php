<?php

namespace FooPlugins\FooConvert\AI;

use FooPlugins\FooConvert\Brand\Manager as BrandManager;
use FooPlugins\FooConvert\Utils;
use WP_AI_Client_Ability_Function_Resolver;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;

class PopupBuilder {

    /**
     * Maximum number of turns to send through the AI function-call loop.
     */
    private const MAX_ITERATIONS = 6;

    /**
     * Maximum media items returned to the builder.
     */
    private const MEDIA_ITEMS_LIMIT = 12;

    /**
     * Registers the popup builder REST routes when available.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_saved_meta' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'fooconvert_enqueued_editor_assets', array( $this, 'enqueue_editor_data' ) );
    }

    /**
     * Registers the saved AI builder meta on popup posts.
     *
     * @return void
     */
    public function register_saved_meta(): void {
        register_post_meta(
            FOOCONVERT_CPT_POPUP,
            FOOCONVERT_META_KEY_AI_BUILDER_METADATA,
            array(
                'type'              => 'object',
                'single'            => true,
                'show_in_rest'      => array(
                    'schema' => PopupBlueprint::get_saved_ai_metadata_schema(),
                ),
                'sanitize_callback' => array( PopupBlueprint::class, 'sanitize_builder_metadata' ),
                'auth_callback'     => array( $this, 'can_manage_popups' ),
            )
        );
    }

    /**
     * Enqueues the AI builder editor data for popup post editing.
     *
     * @param string $handle Editor script handle.
     * @return void
     */
    public function enqueue_editor_data( string $handle ): void {
        $script = Utils::to_js_script( 'FC_AI_BUILDER', $this->get_editor_data() );
        if ( ! is_string( $script ) || '' === $script ) {
            return;
        }

        wp_add_inline_script( $handle, $script, 'before' );
    }

    /**
     * Registers the popup builder REST routes.
     *
     * @return void
     */
    public function register_rest_routes(): void {
        register_rest_route(
            'fooconvert/v1',
            '/ai-popup-builder/chat',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_chat' ),
                'permission_callback' => array( $this, 'can_manage_popups' ),
                'args'                => array(
                    'messages'     => array(
                        'type'     => 'array',
                        'required' => true,
                    ),
                    'popup_draft'  => array(
                        'type' => 'object',
                    ),
                    'generate_images' => array(
                        'type' => 'boolean',
                    ),
                    'force_image_generation' => array(
                        'type' => 'boolean',
                    ),
                    'brand' => array(
                        'type' => 'object',
                    ),
                ),
            )
        );

        register_rest_route(
            'fooconvert/v1',
            '/ai-popup-builder/media/(?P<id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'handle_delete_media' ),
                'permission_callback' => array( $this, 'can_manage_popups' ),
            )
        );

        register_rest_route(
            'fooconvert/v1',
            '/ai-popup-builder/save',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_save' ),
                'permission_callback' => array( $this, 'can_manage_popups' ),
                'args'                => array(
                    'title'       => array(
                        'type'     => 'string',
                        'required' => false,
                    ),
                    'post_id'     => array(
                        'type'     => 'integer',
                        'required' => false,
                    ),
                    'popup_type'  => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                    'post_content' => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                    'ai_metadata' => array(
                        'type'     => 'object',
                        'required' => false,
                    ),
                ),
            )
        );
    }

    /**
     * Handles AI chat requests.
     *
     * @param WP_REST_Request $request REST request.
     * @return array<string,mixed>|WP_Error
     */
    public function handle_chat( WP_REST_Request $request ) {
        if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_builder_unavailable',
                __( 'The WordPress AI client is not available on this site.', 'fooconvert' ),
                array( 'status' => 501 )
            );
        }

        $messages    = $this->sanitize_messages( $request->get_param( 'messages' ) );
        $popup_draft = is_array( $request->get_param( 'popup_draft' ) ) ? PopupBlueprint::sanitize_popup_draft( $request->get_param( 'popup_draft' ) ) : array();
        $generate_images = ! empty( $request->get_param( 'generate_images' ) );
        $force_image_generation = ! empty( $request->get_param( 'force_image_generation' ) );
        $brand       = BrandManager::sanitize_brand( $request->get_param( 'brand' ) );
        $existing_media = PopupMedia::list_generated_images( self::MEDIA_ITEMS_LIMIT );
        $activity_log = array();

        if ( empty( $messages ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_builder_missing_messages',
                __( 'At least one user message is required.', 'fooconvert' ),
                array( 'status' => 400 )
            );
        }

        $response = $this->generate_ai_response(
            $messages,
            $popup_draft,
            $existing_media,
            $brand,
            $generate_images,
            $force_image_generation,
            $activity_log
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response = $this->maybe_force_generate_popup_image(
            $response,
            $messages,
            array_column( $existing_media, 'id' ),
            $force_image_generation
        );

        $response['media_items'] = PopupMedia::list_generated_images( self::MEDIA_ITEMS_LIMIT );
        $response['activity_log'] = $activity_log;

        return $response;
    }

    /**
     * Saves a generated popup draft as a draft popup post.
     *
     * @param WP_REST_Request $request REST request.
     * @return array<string,mixed>|WP_Error|WP_REST_Response
     */
    public function handle_save( WP_REST_Request $request ) {
        $popup_type   = fooconvert_normalize_popup_type( $request->get_param( 'popup_type' ) );
        $post_content = (string) $request->get_param( 'post_content' );
        $title        = sanitize_text_field( (string) $request->get_param( 'title' ) );
        $requested_post_id = absint( $request->get_param( 'post_id' ) );
        $ai_metadata  = PopupBlueprint::sanitize_builder_metadata( $request->get_param( 'ai_metadata' ) );

        if ( '' === $popup_type ) {
            return new WP_Error(
                'fooconvert_ai_popup_builder_invalid_type',
                __( 'A valid popup type is required.', 'fooconvert' ),
                array( 'status' => 400 )
            );
        }

        if ( '' === trim( $post_content ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_builder_missing_content',
                __( 'Popup block HTML is required before saving.', 'fooconvert' ),
                array( 'status' => 400 )
            );
        }

        $root_block_name = fooconvert_get_popup_type_block_name( $popup_type );
        $blocks          = parse_blocks( $post_content );

        if ( empty( $blocks ) || ! is_array( $blocks[0] ) || ( $blocks[0]['blockName'] ?? '' ) !== $root_block_name ) {
            return new WP_Error(
                'fooconvert_ai_popup_builder_invalid_markup',
                __( 'The generated block HTML does not match the selected popup type.', 'fooconvert' ),
                array( 'status' => 400 )
            );
        }

        $existing_post = null;
        if ( $requested_post_id > 0 ) {
            $existing_post = get_post( $requested_post_id );

            if ( ! $existing_post || FOOCONVERT_CPT_POPUP !== $existing_post->post_type ) {
                return new WP_Error(
                    'fooconvert_ai_popup_builder_invalid_post',
                    __( 'The popup draft could not be found.', 'fooconvert' ),
                    array( 'status' => 404 )
                );
            }

            if ( ! current_user_can( 'edit_post', $requested_post_id ) ) {
                return new WP_Error(
                    'fooconvert_ai_popup_builder_cannot_edit',
                    __( 'You do not have permission to update this popup draft.', 'fooconvert' ),
                    array( 'status' => 403 )
                );
            }
        }

        if ( '' === $title && $existing_post instanceof \WP_Post ) {
            $title = $existing_post->post_title;
        }

        if ( '' === $title ) {
            $title = sprintf(
                /* translators: %s: popup type label */
                __( 'AI %s Draft', 'fooconvert' ),
                fooconvert_get_popup_type_label( $popup_type )
            );
        }

        $post_id = $requested_post_id;

        if ( $post_id <= 0 ) {
            $post_id = wp_insert_post(
                array(
                    'post_type'   => FOOCONVERT_CPT_POPUP,
                    'post_status' => 'draft',
                    'post_title'  => $title,
                ),
                true
            );

            if ( is_wp_error( $post_id ) ) {
                return $post_id;
            }
        }

        if ( ! isset( $blocks[0]['attrs'] ) || ! is_array( $blocks[0]['attrs'] ) ) {
            $blocks[0]['attrs'] = array();
        }

        $blocks[0]['attrs']['postId']   = (int) $post_id;
        $blocks[0]['attrs']['postType'] = FOOCONVERT_CPT_POPUP;

        $updated = wp_update_post(
            array(
                'ID'           => $post_id,
                'post_title'   => $title,
                'post_status'  => 'draft',
                'post_content' => serialize_blocks( $blocks ),
            ),
            true
        );

        if ( is_wp_error( $updated ) ) {
            if ( $requested_post_id <= 0 ) {
                wp_delete_post( $post_id, true );
            }
            return $updated;
        }

        update_post_meta( $post_id, FOOCONVERT_META_KEY_POPUP_TYPE, $popup_type );
        update_post_meta( $post_id, FOOCONVERT_META_KEY_AI_BUILDER_METADATA, $ai_metadata );

        return new WP_REST_Response(
            array(
                'postId'   => $post_id,
                'title'    => get_the_title( $post_id ),
                'editUrl'  => fooconvert_admin_url_widget_edit( $post_id ),
                'previewUrl' => fooconvert_admin_url_ai_popup_preview( $post_id ),
                'popupType' => $popup_type,
                'updatedExisting' => $requested_post_id > 0,
            )
        );
    }

    /**
     * Returns the editor data used to render saved AI builder context in the popup editor.
     *
     * @return array<string,mixed>
     */
    private function get_editor_data(): array {
        return array(
            'meta'       => array(
                'key'      => FOOCONVERT_META_KEY_AI_BUILDER_METADATA,
                'defaults' => PopupBlueprint::get_saved_ai_metadata_defaults(),
            ),
            'builderUrl' => admin_url( 'admin.php?page=' . FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER ),
            'labels'     => array(
                FOOCONVERT_POPUP_TYPE_BAR    => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_BAR ),
                FOOCONVERT_POPUP_TYPE_FLYOUT => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_FLYOUT ),
                FOOCONVERT_POPUP_TYPE_POPUP  => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_POPUP ),
            ),
        );
    }

    /**
     * Deletes a generated popup image.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_Error|WP_REST_Response
     */
    public function handle_delete_media( WP_REST_Request $request ) {
        $attachment_id = absint( $request->get_param( 'id' ) );
        $deleted       = PopupMedia::delete_generated_image( $attachment_id );

        if ( is_wp_error( $deleted ) ) {
            return $deleted;
        }

        return new WP_REST_Response(
            array(
                'deletedId'   => $attachment_id,
                'media_items' => PopupMedia::list_generated_images( self::MEDIA_ITEMS_LIMIT ),
            )
        );
    }

    /**
     * Checks whether the current user can create Fooconvert popups.
     *
     * @return bool
     */
    public function can_manage_popups(): bool {
        $post_type_object = get_post_type_object( FOOCONVERT_CPT_POPUP );
        $capability       = $post_type_object && isset( $post_type_object->cap->create_posts )
            ? $post_type_object->cap->create_posts
            : 'manage_options';

        return current_user_can( $capability );
    }

    /**
     * Generates a popup builder response from the AI client.
     *
     * @param array<int,array<string,string>> $messages Conversation messages.
     * @param array<string,mixed>             $popup_draft Current popup draft.
     * @param array<int,array<string,mixed>>  $media_items Existing generated popup media.
     * @param bool                            $generate_images Whether image generation is available for this turn.
     * @param bool                            $force_image_generation Whether this turn should explicitly generate a new image.
     * @return array<string,mixed>|WP_Error
     */
    private function generate_ai_response( array $messages, array $popup_draft, array $media_items, array $brand, bool $generate_images, bool $force_image_generation, array &$activity_log ) {
        $history   = $this->build_history( $messages, $popup_draft, $media_items, $brand, $generate_images, $force_image_generation );
        $abilities = Abilities::get_allowed_abilities();
        $resolver  = new WP_AI_Client_Ability_Function_Resolver( ...$abilities );

        $activity_log[] = array(
            'type'  => 'status',
            'label' => __( 'Calling AI model', 'fooconvert' ),
        );

        for ( $iteration = 0; $iteration < self::MAX_ITERATIONS; $iteration++ ) {
            $prompt = wp_ai_client_prompt();
            $prompt
                ->with_history( ...$history )
                ->using_temperature( 0.35 )
                ->using_system_instruction( $this->build_system_instruction( $generate_images, $force_image_generation ) )
                ->using_abilities( ...$abilities )
                ->as_json_response();

            $result = $prompt->generate_text_result();
            if ( is_wp_error( $result ) ) {
                return $result;
            }

            $candidates = $result->getCandidates();
            if ( empty( $candidates ) ) {
                return new WP_Error(
                    'fooconvert_ai_popup_builder_empty_result',
                    __( 'The AI client returned no candidates.', 'fooconvert' ),
                    array( 'status' => 500 )
                );
            }

            $message   = $candidates[0]->getMessage();
            $history[] = $message;

            if ( $resolver->has_ability_calls( $message ) ) {
                $activity_log = array_merge( $activity_log, $this->get_message_ability_calls( $message ) );
                $ability_response = $resolver->execute_abilities( $message );
                $activity_log = array_merge( $activity_log, $this->get_message_ability_results( $ability_response ) );
                $history[] = $ability_response;
                continue;
            }

            $response = $this->decode_json_response( $result->toText() );
            if ( ! is_array( $response ) ) {
                return new WP_Error(
                    'fooconvert_ai_popup_builder_invalid_json',
                    __( 'The AI returned an invalid popup response.', 'fooconvert' ),
                    array( 'status' => 500 )
                );
            }

            return PopupBlueprint::sanitize_ai_response( $response );
        }

        return new WP_Error(
            'fooconvert_ai_popup_builder_iteration_limit',
            __( 'The AI popup builder reached its tool-call limit before completing the popup.', 'fooconvert' ),
            array( 'status' => 500 )
        );
    }

    /**
     * Builds the prompt history from the UI message payload.
     *
     * @param array<int,array<string,string>> $messages Conversation messages.
     * @param array<string,mixed>             $popup_draft Current popup draft.
     * @param array<int,array<string,mixed>>  $media_items Existing generated popup media.
     * @param bool                            $generate_images Whether image generation is available for this turn.
     * @param bool                            $force_image_generation Whether this turn should explicitly generate a new image.
     * @return array<int,Message>
     */
    private function build_history( array $messages, array $popup_draft, array $media_items, array $brand, bool $generate_images, bool $force_image_generation ): array {
        $history        = array();
        $message_count  = count( $messages );

        foreach ( $messages as $index => $message ) {
            $role    = $message['role'];
            $content = $message['content'];

            if ( 'user' === $role && $index === $message_count - 1 ) {
                if ( ! empty( $popup_draft ) ) {
                    $content .= "\n\nCurrent popup draft JSON:\n" . wp_json_encode( $popup_draft, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
                }

                if ( ! empty( $media_items ) ) {
                    $content .= "\n\nCurrent generated popup media JSON:\n" . wp_json_encode( $media_items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
                }

                if ( ! empty( $brand ) ) {
                    $content .= "\n\nBrand context JSON (this should drive styling, tone, and component treatment):\n" . wp_json_encode( $brand, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
                }

                if ( $force_image_generation ) {
                    $content .= "\n\nImage instruction for this turn: generate a new supporting popup image and incorporate it when possible.";
                } elseif ( $generate_images ) {
                    $content .= "\n\nImage generation is available for this turn when it would materially improve the popup.";
                }
            }

            $part = new MessagePart( $content );

            if ( 'assistant' === $role ) {
                $history[] = new ModelMessage( array( $part ) );
            } else {
                $history[] = new UserMessage( array( $part ) );
            }
        }

        return $history;
    }

    /**
     * Sanitizes the UI message payload.
     *
     * @param mixed $messages Message payload.
     * @return array<int,array<string,string>>
     */
    private function sanitize_messages( $messages ): array {
        if ( ! is_array( $messages ) ) {
            return array();
        }

        $sanitized = array();
        $messages  = array_slice( array_values( $messages ), -12 );

        foreach ( $messages as $message ) {
            if ( ! is_array( $message ) ) {
                continue;
            }

            $role = isset( $message['role'] ) && 'assistant' === $message['role'] ? 'assistant' : 'user';
            $content = isset( $message['content'] ) && is_string( $message['content'] )
                ? trim( wp_strip_all_tags( $message['content'] ) )
                : '';

            if ( '' === $content ) {
                continue;
            }

            $sanitized[] = array(
                'role'    => $role,
                'content' => mb_substr( $content, 0, 2400 ),
            );
        }

        return $sanitized;
    }

    /**
     * Decodes a JSON object response from the AI model.
     *
     * @param string $payload Raw model text.
     * @return array<string,mixed>|null
     */
    private function decode_json_response( string $payload ): ?array {
        $payload = trim( $payload );

        if ( '' === $payload ) {
            return null;
        }

        $decoded = json_decode( $payload, true );
        if ( is_array( $decoded ) ) {
            return $decoded;
        }

        if ( 0 === strpos( $payload, '```' ) ) {
            $payload = preg_replace( '/^```(?:json)?\s*|\s*```$/', '', $payload );
            if ( is_string( $payload ) ) {
                $decoded = json_decode( trim( $payload ), true );
                if ( is_array( $decoded ) ) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * Builds the system instruction for the popup builder.
     *
     * @param bool $generate_images Whether image generation is available for this turn.
     * @param bool $force_image_generation Whether this turn should explicitly generate a new image.
     * @return string
     */
    private function build_system_instruction( bool $generate_images, bool $force_image_generation ): string {
        $instructions = array(
            'You are an experimental FooConvert popup strategist and builder.',
            'Your job is to turn natural-language requests into high-converting Fooconvert popup drafts.',
            'Always reason in terms of one clear conversion goal, one dominant CTA, and a popup type that fits the user intent.',
            'Use the available abilities when you need structural template references, supported block rules, best practices, media context, or blueprint validation.',
            'If you need popup imagery, prefer the create popup image ability because it returns an imported media item ready for core/image blocks.',
            'If you return a popup_draft, run the popup blueprint validator ability before the final response.',
            'Keep the assistant_message concise and practical.',
            'Use the extracted brand context as the main source of truth for colors, typography, spacing, and visual tone.',
            'Templates are optional structural references only. Do not let a template override the brand styling direction.',
            'Use supported core, FooConvert, and WooCommerce content blocks only. Do not invent unsupported block names.',
            'Favor scannable popup structures: headline, support copy, proof or benefit stack, and CTA.',
            'Bars should stay compact. Flyouts should stay narrow. Popups can carry more detail, but still keep them focused.',
            'Only ask a clarifying question when absolutely necessary. Otherwise make a reasonable conversion-focused assumption and produce a complete draft.',
            'When a template_slug is helpful, pick one of the bundled Fooconvert templates as a structural guide instead of inventing a fake template.',
        );

        if ( $force_image_generation ) {
            $instructions[] = 'This turn explicitly requires a new popup image. Create one with the popup image abilities and incorporate it into the draft when appropriate.';
        } elseif ( $generate_images ) {
            $instructions[] = 'Image generation is enabled for this turn. Use popup image abilities when a visual will materially improve the popup or when the user asks for imagery.';
        } else {
            $instructions[] = 'Do not generate new popup images unless the user explicitly asks for imagery later.';
        }

        $instructions[] = PopupBlueprint::get_assistant_response_contract();

        return implode( "\n", $instructions );
    }

    /**
     * Ensures a force-image request creates at least one new popup image.
     *
     * @param array<string,mixed>           $response Builder response.
     * @param array<int,array<string,string>> $messages Conversation messages.
     * @param array<int,int|string>         $existing_media_ids Media IDs present before the request.
     * @param bool                          $force_image_generation Whether a new image is required.
     * @return array<string,mixed>
     */
    private function maybe_force_generate_popup_image( array $response, array $messages, array $existing_media_ids, bool $force_image_generation ): array {
        if ( ! $force_image_generation || ! is_array( $response['popup_draft'] ?? null ) ) {
            return $response;
        }

        $latest_media_ids = array_column( PopupMedia::list_generated_images( self::MEDIA_ITEMS_LIMIT ), 'id' );
        $new_media_ids    = array_diff( array_map( 'intval', $latest_media_ids ), array_map( 'intval', $existing_media_ids ) );

        if ( ! empty( $new_media_ids ) ) {
            return $response;
        }

        $latest_user_message = '';
        foreach ( array_reverse( $messages ) as $message ) {
            if ( 'user' === $message['role'] ) {
                $latest_user_message = $message['content'];
                break;
            }
        }

        $generated_media = PopupMedia::generate_popup_media( $response['popup_draft'], $latest_user_message );
        if ( is_wp_error( $generated_media ) || empty( $generated_media['image'] ) || ! is_array( $generated_media['image'] ) ) {
            return $response;
        }

        $response['popup_draft'] = PopupMedia::inject_media_into_popup_draft( $response['popup_draft'], $generated_media['image'] );
        $response['validation']  = PopupBlueprint::evaluate_popup_draft( $response['popup_draft'] );
        $response['assistant_message'] = trim(
            implode(
                ' ',
                array_filter(
                    array(
                        $response['assistant_message'] ?? '',
                        __( 'I also generated a popup image and wired it into the draft.', 'fooconvert' ),
                    )
                )
            )
        );

        return $response;
    }

    /**
     * Collects ability call log entries from a model message.
     *
     * @param Message $message Model message.
     * @return array<int,array<string,string>>
     */
    private function get_message_ability_calls( Message $message ): array {
        $entries = array();

        foreach ( $message->getParts() as $part ) {
            if ( ! $part->getType()->isFunctionCall() ) {
                continue;
            }

            $function_call = $part->getFunctionCall();
            if ( ! $function_call ) {
                continue;
            }

            $function_name = (string) $function_call->getName();
            $ability_name  = '' !== $function_name
                ? WP_AI_Client_Ability_Function_Resolver::function_name_to_ability_name( $function_name )
                : __( 'unknown ability', 'fooconvert' );

            $entries[] = array(
                'type'    => 'tool_call',
                'label'   => $ability_name,
                'summary' => $this->summarize_activity_payload( $function_call->getArgs() ),
            );
        }

        return $entries;
    }

    /**
     * Collects ability result log entries from a tool response message.
     *
     * @param Message $message Tool response message.
     * @return array<int,array<string,string>>
     */
    private function get_message_ability_results( Message $message ): array {
        $entries = array();

        foreach ( $message->getParts() as $part ) {
            if ( ! $part->getType()->isFunctionResponse() ) {
                continue;
            }

            $function_response = $part->getFunctionResponse();
            if ( ! $function_response ) {
                continue;
            }

            $function_name = (string) $function_response->getName();
            $ability_name  = '' !== $function_name
                ? WP_AI_Client_Ability_Function_Resolver::function_name_to_ability_name( $function_name )
                : __( 'unknown ability', 'fooconvert' );

            $entries[] = array(
                'type'    => 'tool_result',
                'label'   => $ability_name,
                'summary' => $this->summarize_activity_payload( $function_response->getResponse() ),
            );
        }

        return $entries;
    }

    /**
     * Produces a short human-readable summary for tool call/result payloads.
     *
     * @param mixed $payload Tool payload.
     * @return string
     */
    private function summarize_activity_payload( $payload ): string {
        if ( is_array( $payload ) ) {
            if ( isset( $payload['error'] ) && is_string( $payload['error'] ) ) {
                return $payload['error'];
            }

            if ( isset( $payload['templates'] ) && is_array( $payload['templates'] ) ) {
                return sprintf( __( 'Returned %d templates', 'fooconvert' ), count( $payload['templates'] ) );
            }

            if ( isset( $payload['blocks'] ) && is_array( $payload['blocks'] ) ) {
                return sprintf( __( 'Returned %d blocks', 'fooconvert' ), count( $payload['blocks'] ) );
            }

            if ( isset( $payload['media_items'] ) && is_array( $payload['media_items'] ) ) {
                return sprintf( __( 'Returned %d media items', 'fooconvert' ), count( $payload['media_items'] ) );
            }

            if ( isset( $payload['validation']['score'] ) ) {
                return sprintf( __( 'Validation score %d/100', 'fooconvert' ), absint( $payload['validation']['score'] ) );
            }

            if ( isset( $payload['prompt'] ) && is_string( $payload['prompt'] ) ) {
                return __( 'Generated an image prompt', 'fooconvert' );
            }

            if ( isset( $payload['image'] ) && is_array( $payload['image'] ) ) {
                return __( 'Prepared a popup image', 'fooconvert' );
            }

            if ( isset( $payload['playbook'] ) && is_array( $payload['playbook'] ) ) {
                return __( 'Loaded the conversion playbook', 'fooconvert' );
            }

            $keys = array_slice( array_keys( $payload ), 0, 4 );
            if ( ! empty( $keys ) ) {
                return sprintf(
                    /* translators: %s: comma-separated payload keys */
                    __( 'Payload keys: %s', 'fooconvert' ),
                    implode( ', ', array_map( 'strval', $keys ) )
                );
            }
        }

        if ( is_string( $payload ) ) {
            $text = trim( wp_strip_all_tags( $payload ) );
            return mb_substr( $text, 0, 140 );
        }

        if ( is_bool( $payload ) ) {
            return $payload ? __( 'Completed successfully', 'fooconvert' ) : __( 'Returned false', 'fooconvert' );
        }

        if ( is_numeric( $payload ) ) {
            return (string) $payload;
        }

        return __( 'Completed', 'fooconvert' );
    }
}
