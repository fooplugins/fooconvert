<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\DraftNormalizer as PopupBlueprint;
use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\Schema;
use FooPlugins\FooConvert\AI\PopupBuilder\Media\Attachments as PopupMedia;
use FooPlugins\FooConvert\Brand\Manager as BrandManager;
use FooPlugins\FooConvert\Utils;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

class RestController {

    /**
     * Maximum media items returned to the builder.
     */
    private const MEDIA_ITEMS_LIMIT = 12;

    /**
     * Chat response service.
     *
     * @var ChatService
     */
    private $chat_service;

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
                    'schema' => Schema::get_saved_ai_metadata_schema(),
                ),
                'sanitize_callback' => array( PopupBlueprint::class, 'sanitize_builder_metadata' ),
                'auth_callback'     => array( $this, 'can_manage_saved_meta' ),
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
        if ( ! Config::supports_ai_popup_builder() ) {
            return;
        }

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
                    'settings' => array(
                        'type' => 'object',
                    ),
                    'model' => array(
                        'type' => 'string',
                    ),
                    'timeout' => array(
                        'type' => 'integer',
                    ),
                    'max_tool_calls' => array(
                        'type' => 'integer',
                    ),
                    'disabled_params' => array(
                        'type' => 'array',
                    ),
                    'selected_block_names' => array(
                        'type' => 'array',
                    ),
                ),
            )
        );

        register_rest_route(
            'fooconvert/v1',
            '/ai-popup-builder/chat-stream',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_chat_stream' ),
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
                    'settings' => array(
                        'type' => 'object',
                    ),
                    'model' => array(
                        'type' => 'string',
                    ),
                    'timeout' => array(
                        'type' => 'integer',
                    ),
                    'max_tool_calls' => array(
                        'type' => 'integer',
                    ),
                    'disabled_params' => array(
                        'type' => 'array',
                    ),
                    'selected_block_names' => array(
                        'type' => 'array',
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
            '/ai-popup-builder/debug-responses',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'handle_get_debug_responses' ),
                    'permission_callback' => array( $this, 'can_manage_debug_responses' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'handle_clear_debug_responses' ),
                    'permission_callback' => array( $this, 'can_manage_debug_responses' ),
                ),
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
     * Returns stored debug responses from failed AI popup builder responses.
     *
     * @return WP_REST_Response
     */
    public function handle_get_debug_responses(): WP_REST_Response {
        return new WP_REST_Response(
            array(
                'enabled'   => $this->is_debug_logging_enabled(),
                'responses' => $this->get_debug_response_log(),
            )
        );
    }

    /**
     * Clears stored debug responses.
     *
     * @return WP_REST_Response
     */
    public function handle_clear_debug_responses(): WP_REST_Response {
        DebugResponseLog::clear();

        return new WP_REST_Response(
            array(
                'enabled'   => $this->is_debug_logging_enabled(),
                'responses' => array(),
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
        $chat_request = $this->prepare_chat_request( $request );

        if ( is_wp_error( $chat_request ) ) {
            return $chat_request;
        }

        return $this->get_chat_service()->build_chat_response( $chat_request );
    }

    /**
     * Handles streaming AI chat requests.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_Error|null
     */
    public function handle_chat_stream( WP_REST_Request $request ) {
        if ( ! Config::supports_streaming() ) {
            return $this->get_streaming_unavailable_error();
        }

        $chat_request = $this->prepare_chat_request( $request );

        if ( is_wp_error( $chat_request ) ) {
            return $chat_request;
        }

        $this->start_stream_response();
        $response = $this->get_chat_service()->build_chat_response(
            $chat_request,
            array(
                'on_status' => function ( array $item ): void {
                    $this->send_stream_event( 'activity', $item );
                },
                'on_activity' => function ( array $item ): void {
                    $this->send_stream_event( 'activity', $item );
                },
                'on_assistant_delta' => function ( string $delta ): void {
                    $this->send_stream_event(
                        'assistant_delta',
                        array(
                            'content' => $delta,
                        )
                    );
                },
                'on_reasoning_delta' => function ( string $delta ): void {
                    $this->send_stream_event(
                        'reasoning_delta',
                        array(
                            'content' => $delta,
                        )
                    );
                },
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->send_stream_event(
                'error',
                array(
                    'message' => $response->get_error_message(),
                    'code'    => $response->get_error_code(),
                )
            );
            $this->send_stream_event( 'done', array( 'ok' => false ) );
            exit;
        }

        $this->send_stream_event( 'result', $response );
        $this->send_stream_event( 'done', array( 'ok' => true ) );
        exit;
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
                'editUrl'  => fooconvert_admin_url_popup_edit( $post_id ),
                'previewUrl' => fooconvert_popup_preview_url( $post_id ),
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
            'builderUrl' => Config::supports_ai_popup_builder()
                ? admin_url( 'admin.php?page=' . FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER )
                : '',
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
     * Checks whether the current user can create popups.
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
     * Checks whether saved AI builder metadata can be edited for a popup.
     *
     * @param bool        $allowed Whether access was already allowed.
     * @param string      $meta_key Meta key.
     * @param int|string  $post_id Post ID.
     * @return bool
     */
    public function can_manage_saved_meta( $allowed = false, $meta_key = '', $post_id = 0 ): bool {
        $post_id = absint( $post_id );

        if ( $post_id > 0 ) {
            return current_user_can( 'edit_post', $post_id );
        }

        return (bool) $allowed || $this->can_manage_popups();
    }

    /**
     * Checks whether the current user can inspect popup builder debug data.
     *
     * @return bool
     */
    public function can_manage_debug_responses(): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Normalizes a chat request into the shared builder payload.
     *
     * @param WP_REST_Request $request REST request.
     * @return array<string,mixed>|WP_Error
     */
    private function prepare_chat_request( WP_REST_Request $request ) {
        if ( ! Config::supports_ai_popup_builder() ) {
            return $this->get_ai_popup_builder_version_error();
        }

        if ( ! Config::has_ai_client() ) {
            return $this->get_ai_popup_builder_unavailable_error();
        }

        if ( ! Config::has_valid_ai_connection() ) {
            return $this->get_ai_popup_builder_connection_error();
        }

        $messages               = $this->get_chat_service()->sanitize_messages( $request->get_param( 'messages' ) );
        $settings               = $this->prepare_ai_settings_from_request( $request );
        $popup_draft            = is_array( $request->get_param( 'popup_draft' ) ) ? PopupBlueprint::sanitize_popup_draft( $request->get_param( 'popup_draft' ), $settings['selected_block_names'] ?? null ) : array();
        $generate_images        = ! empty( $request->get_param( 'generate_images' ) );
        $force_image_generation = ! empty( $request->get_param( 'force_image_generation' ) );
        $brand                  = BrandManager::sanitize_brand( $request->get_param( 'brand' ) );
        $existing_media         = PopupMedia::list_generated_images( self::MEDIA_ITEMS_LIMIT );

        if ( empty( $messages ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_builder_missing_messages',
                __( 'At least one user message is required.', 'fooconvert' ),
                array( 'status' => 400 )
            );
        }

        return array(
            'messages'               => $messages,
            'popup_draft'            => $popup_draft,
            'generate_images'        => $generate_images,
            'force_image_generation' => $force_image_generation,
            'brand'                  => $brand,
            'existing_media'         => $existing_media,
            'settings'               => $settings,
        );
    }

    /**
     * Normalizes AI popup builder settings from saved options and request overrides.
     *
     * @param WP_REST_Request $request REST request.
     * @return array<string,mixed>
     */
    private function prepare_ai_settings_from_request( WP_REST_Request $request ): array {
        $settings = Settings::sanitize_payload( $request->get_param( 'settings' ) );

        $model = $request->get_param( 'model' );
        if ( null !== $model ) {
            $settings['override_model'] = Settings::sanitize_model( $model );
        }

        $timeout = $request->get_param( 'timeout' );
        if ( null !== $timeout ) {
            $settings['timeout'] = Settings::sanitize_timeout( $timeout );
        }

        $max_tool_calls = $request->get_param( 'max_tool_calls' );
        if ( null !== $max_tool_calls ) {
            $settings['max_tool_calls'] = Settings::sanitize_max_tool_calls( $max_tool_calls );
        }

        $disabled_params = $request->get_param( 'disabled_params' );
        if ( null !== $disabled_params ) {
            $settings['disabled_params'] = Settings::sanitize_disabled_params( $disabled_params );
            $settings['disabled_params_text'] = implode( "\n", $settings['disabled_params'] );
        }

        $selected_block_names = $request->get_param( 'selected_block_names' );
        if ( null !== $selected_block_names ) {
            $settings['selected_block_names'] = Settings::sanitize_selected_block_names( $selected_block_names );
        }

        return $settings;
    }

    /**
     * Resolves the final builder response from a normalized chat request.
     *
     * @param array<string,mixed> $chat_request Normalized chat request.
     * @param array<string,callable> $stream_callbacks Optional streaming callbacks.
     * @return array<string,mixed>|WP_Error
     */
    private function get_ai_popup_builder_version_error(): WP_Error {
        return new WP_Error(
            'fooconvert_ai_popup_builder_wp7_required',
            __( 'The AI popup builder requires WordPress 7 or newer.', 'fooconvert' ),
            array( 'status' => 501 )
        );
    }

    /**
     * Returns the AI-client availability error for the AI popup builder.
     *
     * @return WP_Error
     */
    private function get_ai_popup_builder_unavailable_error(): WP_Error {
        return new WP_Error(
            'fooconvert_ai_popup_builder_unavailable',
            __( 'The WordPress AI client is not available on this site.', 'fooconvert' ),
            array( 'status' => 501 )
        );
    }

    /**
     * Returns the AI connection setup error for the AI popup builder.
     *
     * @return WP_Error
     */
    private function get_ai_popup_builder_connection_error(): WP_Error {
        return new WP_Error(
            'fooconvert_ai_popup_builder_missing_ai_connection',
            __( 'A valid WordPress AI connector is required before chat-based popup generation can run.', 'fooconvert' ),
            array( 'status' => 501 )
        );
    }

    /**
     * Returns the streaming availability error for the AI popup builder.
     *
     * @return WP_Error
     */
    private function get_streaming_unavailable_error(): WP_Error {
        return new WP_Error(
            'fooconvert_ai_popup_builder_streaming_unavailable',
            __( 'Streaming chat is not available on this site.', 'fooconvert' ),
            array( 'status' => 501 )
        );
    }

    /**
     * Starts the streamed chat response.
     *
     * @return void
     */
    private function start_stream_response(): void {
        if ( function_exists( 'session_status' ) && PHP_SESSION_ACTIVE === session_status() && function_exists( 'session_write_close' ) ) {
            session_write_close();
        }

        if ( function_exists( 'ignore_user_abort' ) ) {
            ignore_user_abort( true );
        }

        if ( function_exists( 'set_time_limit' ) ) {
            @set_time_limit( 0 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Streaming responses need to remain open while the AI provider sends events.
        }

        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        status_header( 200 );
        nocache_headers();
        header( 'Content-Type: text/event-stream; charset=utf-8' );
        header( 'Cache-Control: no-cache, no-transform' );
        header( 'X-Accel-Buffering: no' );
    }

    /**
     * Emits one server-sent event payload.
     *
     * @param string              $event Event name.
     * @param array<string,mixed> $payload Event payload.
     * @return void
     */
    private function send_stream_event( string $event, array $payload ): void {
        echo 'event: ' . sanitize_key( $event ) . "\n";
        echo 'data: ' . wp_json_encode( $payload ) . "\n\n";

        if ( function_exists( 'flush' ) ) {
            flush();
        }
    }

    /**
     * Checks whether debug response logging is active.
     *
     * @return bool
     */
    private function is_debug_logging_enabled(): bool {
        return DebugResponseLog::is_enabled();
    }

    /**
     * Returns sanitized stored debug response entries.
     *
     * @return array<int,array<string,mixed>>
     */
    private function get_debug_response_log(): array {
        return DebugResponseLog::get_entries();
    }

    /**
     * Returns the chat service, creating it only for chat requests.
     *
     * @return ChatService
     */
    private function get_chat_service(): ChatService {
        if ( ! ( $this->chat_service instanceof ChatService ) ) {
            $this->chat_service = new ChatService();
        }

        return $this->chat_service;
    }
}
