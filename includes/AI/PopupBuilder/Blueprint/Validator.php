<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint;

defined( 'ABSPATH' ) || exit;

class Validator {

    public static function evaluate_popup_draft( $draft, ?array $selected_block_names = null ): array {
        return DraftNormalizer::evaluate_popup_draft( $draft, $selected_block_names );
    }
}
