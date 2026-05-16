<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint;

defined( 'ABSPATH' ) || exit;

class Schema {

    public static function get_assistant_response_schema( ?array $selected_block_names = null ): array {
        return DraftNormalizer::get_assistant_response_schema( $selected_block_names );
    }

    public static function get_assistant_response_contract( ?array $selected_block_names = null ): string {
        return DraftNormalizer::get_assistant_response_contract( $selected_block_names );
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
