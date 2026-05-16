<?php

namespace FooPlugins\FooConvert\AI\PopupBuilder\Blueprint;

defined( 'ABSPATH' ) || exit;

class Catalog {

    public static function get_block_catalog( ?array $selected_block_names = null ): array {
        return DraftNormalizer::get_block_catalog( $selected_block_names );
    }

    public static function get_default_woocommerce_context_block_names(): array {
        return DraftNormalizer::get_default_woocommerce_context_block_names();
    }

    public static function get_default_selected_block_names(): array {
        return DraftNormalizer::get_default_selected_block_names();
    }

    public static function sanitize_selected_block_names( $selected_block_names ): array {
        return DraftNormalizer::sanitize_selected_block_names( $selected_block_names );
    }

    public static function set_request_selected_block_names( $selected_block_names ): void {
        DraftNormalizer::set_request_selected_block_names( $selected_block_names );
    }

    public static function clear_request_selected_block_names(): void {
        DraftNormalizer::clear_request_selected_block_names();
    }

    public static function get_conversion_playbook(): array {
        return DraftNormalizer::get_conversion_playbook();
    }

    public static function get_template_library(): array {
        return DraftNormalizer::get_template_library();
    }

    public static function get_demo_examples(): array {
        return DraftNormalizer::get_demo_examples();
    }

    public static function get_template_by_slug( string $slug ): ?array {
        return DraftNormalizer::get_template_by_slug( $slug );
    }
}
