<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\Catalog;
use FooPlugins\FooConvert\AI\PopupBuilder\Blueprint\Schema;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;

defined( 'ABSPATH' ) || exit;

class PromptFactory {

    /**
     * Builds the prompt history from the UI message payload.
     *
     * @param array<int,array<string,string>> $messages Conversation messages.
     * @param array<string,mixed>             $popup_draft Current popup draft.
     * @param array<int,array<string,mixed>>  $media_items Existing generated popup media.
     * @param array<string,mixed>             $brand Brand context.
     * @param bool                            $generate_images Whether image generation is available for this turn.
     * @param bool                            $force_image_generation Whether this turn should explicitly generate a new image.
     * @param string                          $format_requirement Final response format reminder.
     * @return array<int,Message>
     */
    public static function build_history( array $messages, array $popup_draft, array $media_items, array $brand, bool $generate_images, bool $force_image_generation, string $format_requirement ): array {
        $history       = array();
        $message_count = count( $messages );

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

                $content .= "\n\n" . $format_requirement;
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
     * Builds the system instruction for the popup builder.
     *
     * @param bool              $generate_images Whether image generation is available for this turn.
     * @param bool              $force_image_generation Whether this turn should explicitly generate a new image.
     * @param array<int,string> $selected_block_names Selected block names.
     * @return string
     */
    public static function build_system_instruction( bool $generate_images, bool $force_image_generation, array $selected_block_names = array() ): string {
        return self::compose_system_instruction( $generate_images, $force_image_generation, $selected_block_names );
    }

    /**
     * Returns the default system instruction preview shown in the builder UI.
     *
     * @return string
     */
    public static function get_default_system_instruction_preview(): string {
        return self::compose_system_instruction( false, false );
    }

    /**
     * Composes the popup builder system instruction.
     *
     * @param bool              $generate_images Whether image generation is available for this turn.
     * @param bool              $force_image_generation Whether this turn should explicitly generate a new image.
     * @param array<int,string> $selected_block_names Selected block names.
     * @return string
     */
    private static function compose_system_instruction( bool $generate_images, bool $force_image_generation, array $selected_block_names = array() ): string {
        $instructions = array(
            'You are a popup strategist and builder.',
            'Your job is to turn natural-language requests into high-converting popup drafts.',
            'Always reason in terms of one clear conversion goal, one dominant CTA, and a popup type that fits the user intent.',
            'Use the available tools when you need structural template references, supported block rules, best practices, media context, or blueprint validation.',
            'Use the conversion playbook JSON below as baseline guidance before drafting or revising a popup.',
            'If you need popup imagery, prefer the create popup image tool because it returns an imported media item ready for core/image blocks.',
            'If you return a popup_draft, run the popup blueprint validator tool before the final response.',
            'Keep the assistant_message concise and practical.',
            'Suggested follow-up prompts must be small edits to the generated popup, not alternate popup strategies or fresh popup briefs.',
            'Use the extracted brand context as the main source of truth for colors, typography, spacing, and visual tone.',
            'Templates are optional structural references only. Do not let a template override the brand styling direction.',
            'Use supported core, popup, and WooCommerce content blocks only. Do not invent unsupported block names.',
            'Favor scannable popup structures: headline, support copy, proof or benefit stack, and CTA.',
            'Bars should stay compact. Flyouts should stay narrow. Popups can carry more detail, but still keep them focused.',
            'Use real FooConvert trigger events from the response contract for popup_draft.trigger. Do not use display locations such as cart page, checkout page, or product page as trigger names.',
            'For simple requests you may use trigger.type shortcuts: immediate, delay, exit_intent, scroll_percent. For other triggers, set trigger.type or trigger.event to the event identifier and put event-specific settings in trigger.where.',
            'Only ask a clarifying question when absolutely necessary. Otherwise make a reasonable conversion-focused assumption and produce a complete draft.',
            'When a template_slug is helpful, pick one of the bundled templates as a structural guide instead of inventing a fake template.',
            'If image generation is enabled and your final draft still has no content background image, prefer giving the popup a supportive branded background rather than leaving the backdrop blank.',
        );

        if ( $force_image_generation ) {
            $instructions[] = 'This turn explicitly requires a new popup image. Create one with the popup image tools and incorporate it into the draft when appropriate.';
            $instructions[] = 'If the user specifically needs a popup background, prefer the create popup background tool because it is optimized for brand-aligned, text-safe backdrops.';
        } elseif ( $generate_images ) {
            $instructions[] = 'Image generation is enabled for this turn. Use popup image tools when a visual will materially improve the popup or when the user asks for imagery.';
            $instructions[] = 'If the user specifically needs a popup background, prefer the create popup background tool because it is optimized for brand-aligned, text-safe backdrops.';
        } else {
            $instructions[] = 'Do not generate new popup images unless the user explicitly asks for imagery later.';
        }

        $conversion_playbook = self::get_conversion_playbook_system_context();
        if ( '' !== $conversion_playbook ) {
            $instructions[] = $conversion_playbook;
        }

        $instructions[] = Schema::get_assistant_response_contract( $selected_block_names );

        return implode( "\n", $instructions );
    }

    /**
     * Returns the conversion playbook as compact system-prompt context.
     *
     * @return string
     */
    private static function get_conversion_playbook_system_context(): string {
        $playbook = wp_json_encode( Catalog::get_conversion_playbook(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        if ( ! is_string( $playbook ) || '' === $playbook ) {
            return '';
        }

        return "Conversion playbook JSON:\n" . $playbook;
    }
}
