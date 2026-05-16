<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Media;

defined( 'ABSPATH' ) || exit;

class ImageGenerator {

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
