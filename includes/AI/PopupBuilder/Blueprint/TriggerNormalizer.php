<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint;

defined( 'ABSPATH' ) || exit;

class TriggerNormalizer {

    public static function get_supported_trigger_events(): array {
        return DraftNormalizer::get_supported_trigger_events();
    }
}
