<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint;

defined( 'ABSPATH' ) || exit;

class Schema {

    public static function get_assistant_response_schema( ?array $selected_block_names = null ): array {
        $schema = class_exists( DraftNormalizer::class )
            ? DraftNormalizer::get_assistant_response_schema( $selected_block_names )
            : array();

        if ( function_exists( 'apply_filters' ) ) {
            /**
             * Filters the JSON schema passed to the AI client for popup builder responses.
             *
             * @param array<string,mixed> $schema Response schema.
             * @param array<int,string>|null $selected_block_names Selected block names.
             */
            $filtered_schema = apply_filters( 'fooconvert_ai_popup_builder_assistant_response_schema', $schema, $selected_block_names );
            if ( is_array( $filtered_schema ) ) {
                return $filtered_schema;
            }
        }

        return $schema;
    }

    public static function get_assistant_response_contract( ?array $selected_block_names = null ): string {
        return class_exists( DraftNormalizer::class )
            ? DraftNormalizer::get_assistant_response_contract( $selected_block_names )
            : '';
    }

    public static function get_saved_ai_metadata_schema(): array {
        return DraftNormalizer::get_saved_ai_metadata_schema();
    }

    public static function get_popup_draft_schema( ?array $selected_block_names = null ): array {
        return DraftNormalizer::get_popup_draft_schema( $selected_block_names );
    }

    public static function get_popup_draft_context_schema(): array {
        return DraftNormalizer::get_popup_draft_context_schema();
    }
}
