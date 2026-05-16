<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Media;

defined( 'ABSPATH' ) || exit;

class DraftImages {

    public static function popup_draft_has_background( array $popup_draft ): bool {
        return Attachments::popup_draft_has_background( $popup_draft );
    }

    public static function inject_media_into_popup_draft( array $popup_draft, array $media_item ): array {
        return Attachments::inject_media_into_popup_draft( $popup_draft, $media_item );
    }

    public static function inject_background_into_popup_draft( array $popup_draft, array $media_item ): array {
        return Attachments::inject_background_into_popup_draft( $popup_draft, $media_item );
    }
}
