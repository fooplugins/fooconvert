<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\AI\Abilities;
use FooPlugins\FooConvert\AI\PopupBlueprint;
use FooPlugins\FooConvert\AI\PopupMedia;

class AiPopupBuilder {

    /**
     * Registers the AI popup builder admin screen.
     */
    public function __construct() {
        add_action( 'fooconvert_admin_menu_after_post_types', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Registers the submenu page.
     *
     * @return void
     */
    public function register_menu(): void {
        $post_type_object = get_post_type_object( FOOCONVERT_CPT_POPUP );
        $capability       = $post_type_object && isset( $post_type_object->cap->create_posts )
            ? $post_type_object->cap->create_posts
            : 'manage_options';

        add_submenu_page(
            FOOCONVERT_MENU_SLUG,
            __( 'AI Popup Builder', 'fooconvert' ),
            __( 'AI Popup Builder', 'fooconvert' ),
            $capability,
            FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER,
            array( $this, 'render_page' )
        );
    }

    /**
     * Enqueues assets for the AI popup builder screen.
     *
     * @param string $hook Current admin hook.
     * @return void
     */
    public function enqueue_assets( string $hook ): void {
        if ( 'fooconvert_page_' . FOOCONVERT_MENU_SLUG_AI_POPUP_BUILDER !== $hook ) {
            return;
        }

        $asset_path = FOOCONVERT_ASSETS_PATH . 'admin/ai-popup-builder/index.asset.php';
        $asset      = file_exists( $asset_path )
            ? require $asset_path
            : array(
                'dependencies' => array( 'wp-api-fetch', 'wp-components', 'wp-element', 'wp-i18n' ),
                'version'      => FOOCONVERT_VERSION,
            );

        wp_enqueue_style(
            'fooconvert-ai-popup-builder',
            FOOCONVERT_ASSETS_URL . 'admin/ai-popup-builder/index.css',
            array( 'wp-components' ),
            $asset['version']
        );

        wp_enqueue_script(
            'fooconvert-ai-popup-builder',
            FOOCONVERT_ASSETS_URL . 'admin/ai-popup-builder/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_add_inline_script(
            'fooconvert-ai-popup-builder',
            'window.wpApiSettings = Object.assign( window.wpApiSettings || {}, ' . wp_json_encode(
                array(
                    'root'  => esc_url_raw( get_rest_url() ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                )
            ) . ' );',
            'before'
        );

        wp_add_inline_script(
            'fooconvert-ai-popup-builder',
            'window.FC_AI_POPUP_BUILDER = ' . wp_json_encode( $this->get_editor_config() ) . ';',
            'before'
        );
    }

    /**
     * Renders the AI popup builder container.
     *
     * @return void
     */
    public function render_page(): void {
        ?>
        <div class="wrap">
            <div id="fc-ai-popup-builder-root"></div>
        </div>
        <?php
    }

    /**
     * Builds the editor config for the AI popup builder UI.
     *
     * @return array<string,mixed>
     */
    private function get_editor_config(): array {
        return array(
            'api'              => array(
                'chatPath'       => '/fooconvert/v1/ai-popup-builder/chat',
                'savePath'       => '/fooconvert/v1/ai-popup-builder/save',
                'deleteMediaPath' => '/fooconvert/v1/ai-popup-builder/media',
            ),
            'restRoot'         => esc_url_raw( get_rest_url() ),
            'restNonce'        => wp_create_nonce( 'wp_rest' ),
            'labels'           => array(
                FOOCONVERT_POPUP_TYPE_BAR    => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_BAR ),
                FOOCONVERT_POPUP_TYPE_FLYOUT => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_FLYOUT ),
                FOOCONVERT_POPUP_TYPE_POPUP  => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_POPUP ),
            ),
            'templates'        => PopupBlueprint::get_template_library(),
            'blockCatalog'     => PopupBlueprint::get_block_catalog(),
            'playbook'         => PopupBlueprint::get_conversion_playbook(),
            'mediaItems'       => PopupMedia::list_generated_images( 12 ),
            'aiClientAvailable' => function_exists( 'wp_ai_client_prompt' ),
            'imageGenerationAvailable' => function_exists( 'wp_ai_client_prompt' ) && PopupMedia::can_manage_media(),
            'canUploadMedia'   => PopupMedia::can_manage_media(),
            'abilitiesAvailable' => Abilities::wp_api_available(),
            'starterPrompts'   => array(
                __( 'Build a Black Friday popup that offers 15% off the first order and captures email addresses.', 'fooconvert' ),
                __( 'Create a minimal flyout for a free shipping threshold campaign with one primary CTA.', 'fooconvert' ),
                __( 'Make a newsletter popup for a content brand that feels premium and low pressure.', 'fooconvert' ),
                __( 'Design a mobile-friendly announcement bar promoting a limited launch offer.', 'fooconvert' ),
            ),
        );
    }
}
