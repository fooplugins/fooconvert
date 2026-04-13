<?php

namespace FooPlugins\FooConvert\AI;

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
     * Registers the popup builder REST routes when available.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
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
                    'popup_type'  => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                    'post_content' => array(
                        'type'     => 'string',
                        'required' => true,
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

        if ( empty( $messages ) ) {
            return new WP_Error(
                'fooconvert_ai_popup_builder_missing_messages',
                __( 'At least one user message is required.', 'fooconvert' ),
                array( 'status' => 400 )
            );
        }

        return $this->generate_ai_response( $messages, $popup_draft );
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

        if ( '' === $title ) {
            $title = sprintf(
                /* translators: %s: popup type label */
                __( 'AI %s Draft', 'fooconvert' ),
                fooconvert_get_popup_type_label( $popup_type )
            );
        }

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

        if ( ! isset( $blocks[0]['attrs'] ) || ! is_array( $blocks[0]['attrs'] ) ) {
            $blocks[0]['attrs'] = array();
        }

        $blocks[0]['attrs']['postId']   = (int) $post_id;
        $blocks[0]['attrs']['postType'] = FOOCONVERT_CPT_POPUP;

        $updated = wp_update_post(
            array(
                'ID'           => $post_id,
                'post_content' => serialize_blocks( $blocks ),
            ),
            true
        );

        if ( is_wp_error( $updated ) ) {
            wp_delete_post( $post_id, true );
            return $updated;
        }

        update_post_meta( $post_id, FOOCONVERT_META_KEY_POPUP_TYPE, $popup_type );

        return new WP_REST_Response(
            array(
                'postId'   => $post_id,
                'title'    => get_the_title( $post_id ),
                'editUrl'  => fooconvert_admin_url_widget_edit( $post_id ),
                'popupType' => $popup_type,
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
     * @return array<string,mixed>|WP_Error
     */
    private function generate_ai_response( array $messages, array $popup_draft ) {
        $history   = $this->build_history( $messages, $popup_draft );
        $abilities = Abilities::get_allowed_abilities();
        $resolver  = new WP_AI_Client_Ability_Function_Resolver( ...$abilities );

        for ( $iteration = 0; $iteration < self::MAX_ITERATIONS; $iteration++ ) {
            $prompt = wp_ai_client_prompt();
            $prompt
                ->with_history( ...$history )
                ->using_temperature( 0.35 )
                ->using_system_instruction( $this->build_system_instruction() )
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
                $history[] = $resolver->execute_abilities( $message );
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
     * @return array<int,Message>
     */
    private function build_history( array $messages, array $popup_draft ): array {
        $history        = array();
        $message_count  = count( $messages );

        foreach ( $messages as $index => $message ) {
            $role    = $message['role'];
            $content = $message['content'];

            if ( 'user' === $role && $index === $message_count - 1 && ! empty( $popup_draft ) ) {
                $content .= "\n\nCurrent popup draft JSON:\n" . wp_json_encode( $popup_draft, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
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
     * @return string
     */
    private function build_system_instruction(): string {
        return implode(
            "\n",
            array(
                'You are an experimental FooConvert popup strategist and builder.',
                'Your job is to turn natural-language requests into high-converting Fooconvert popup drafts.',
                'Always reason in terms of one clear conversion goal, one dominant CTA, and a popup type that fits the user intent.',
                'Use the available abilities when you need template references, supported block rules, best practices, or blueprint validation.',
                'If you return a popup_draft, run the popup blueprint validator ability before the final response.',
                'Keep the assistant_message concise and practical.',
                'Use supported Fooconvert content blocks only. Do not invent unsupported block names.',
                'Favor scannable popup structures: headline, support copy, proof or benefit stack, and CTA.',
                'Bars should stay compact. Flyouts should stay narrow. Popups can carry more detail, but still keep them focused.',
                'Only ask a clarifying question when absolutely necessary. Otherwise make a reasonable conversion-focused assumption and produce a complete draft.',
                'When a template_slug is helpful, pick one of the bundled Fooconvert templates instead of inventing a fake template.',
                PopupBlueprint::get_assistant_response_contract(),
            )
        );
    }
}
