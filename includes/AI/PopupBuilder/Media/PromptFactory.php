<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Media;

defined( 'ABSPATH' ) || exit;

class PromptFactory {

    public static function generate_prompt_for_popup( array $popup_draft, string $instructions = '' ) {
        return Attachments::generate_prompt_for_popup( $popup_draft, $instructions );
    }

    public static function generate_prompt_for_background( array $popup_draft, array $brand = array(), string $instructions = '' ) {
        return Attachments::generate_prompt_for_background( $popup_draft, $brand, $instructions );
    }
}
