<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Media;

defined( 'ABSPATH' ) || exit;

class ImageGenerator {

    public static function set_runtime_ai_settings( array $settings ): void {
        Attachments::set_runtime_ai_settings( $settings );
    }

    public static function clear_runtime_ai_settings(): void {
        Attachments::clear_runtime_ai_settings();
    }

    public static function generate_image_from_prompt( string $prompt, ?string $reference_image = null ) {
        return Attachments::generate_image_from_prompt( $prompt, $reference_image );
    }

    public static function generate_popup_media( array $popup_draft, string $instructions = '' ) {
        return Attachments::generate_popup_media( $popup_draft, $instructions );
    }

    public static function generate_popup_background( array $popup_draft, array $brand = array(), string $instructions = '' ) {
        return Attachments::generate_popup_background( $popup_draft, $brand, $instructions );
    }
}
