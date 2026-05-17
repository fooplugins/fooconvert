<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder;

use FooPlugins\FooConvert\AI\Abilities;
use FooPlugins\FooConvert\AI\PopupBuilder\Media\Attachments;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the AI popup builder subsystem.
 */
class Plugin {

    /**
     * Registers builder services.
     */
    public function __construct() {
        new SettingsPage();
        new RestController();

        if ( Config::supports_ai_popup_builder() ) {
            new Abilities();
            new Attachments();
        }
    }
}
