<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\AI\Abilities;
use FooPlugins\FooConvert\AI\PopupBlueprint;
use FooPlugins\FooConvert\AI\PopupBuilder as PopupBuilderAI;
use FooPlugins\FooConvert\AI\PopupMedia;
use FooPlugins\FooConvert\Brand\Manager as BrandManager;
use FooPlugins\FooConvert\FooConvert;

class AiPopupBuilder {

    /**
     * Registers the AI popup builder admin screen.
     */
    public function __construct() {
        add_action( 'fooconvert_admin_menu_after_post_types', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'enqueue_preview_widget' ) );
        add_action( 'admin_footer', array( $this, 'render_preview_widget' ) );
        add_filter( 'fooconvert-widget-frontend-attributes', array( $this, 'override_preview_attributes' ), 10, 4 );
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

        add_submenu_page(
            null,
            __( 'Popup Preview', 'fooconvert' ),
            __( 'Popup Preview', 'fooconvert' ),
            $capability,
            FOOCONVERT_MENU_SLUG_AI_POPUP_PREVIEW,
            array( $this, 'render_preview_page' )
        );
    }

    /**
     * Enqueues assets for the AI popup builder screen.
     *
     * @param string $hook Current admin hook.
     * @return void
     */
    public function enqueue_assets( string $hook ): void {
        if ( $this->is_preview_hook( $hook ) ) {
            $this->enqueue_preview_assets();
            return;
        }

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
     * Renders the dedicated popup preview page.
     *
     * @return void
     */
    public function render_preview_page(): void {
        $widget_id   = isset( $_GET['widget_id'] ) ? absint( $_GET['widget_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $is_embedded = isset( $_GET['fc_embed'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['fc_embed'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $widget      = $widget_id > 0 ? get_post( $widget_id ) : null;
        ?>
        <?php if ( $is_embedded ) : ?>
            <style>
                body.fooconvert-ai-preview-embed #wpadminbar,
                body.fooconvert-ai-preview-embed #adminmenumain,
                body.fooconvert-ai-preview-embed #screen-meta-links,
                body.fooconvert-ai-preview-embed #screen-meta,
                body.fooconvert-ai-preview-embed #wpfooter,
                body.fooconvert-ai-preview-embed .fooconvert-admin-header,
                body.fooconvert-ai-preview-embed #clawpress-floating-panel-root,
                body.fooconvert-ai-preview-embed .notice,
                body.fooconvert-ai-preview-embed .update-nag {
                    display: none !important;
                }

                html.wp-toolbar body.fooconvert-ai-preview-embed {
                    padding-top: 0 !important;
                }

                body.fooconvert-ai-preview-embed #wpcontent,
                body.fooconvert-ai-preview-embed #wpbody,
                body.fooconvert-ai-preview-embed #wpbody-content {
                    margin-left: 0 !important;
                }

                body.fooconvert-ai-preview-embed #wpbody-content {
                    padding: 0 24px 24px !important;
                }
            </style>
            <script>
                document.addEventListener( "DOMContentLoaded", function() {
                    document.body.classList.add( "fooconvert-ai-preview-embed" );
                } );
            </script>
        <?php endif; ?>
        <div class="wrap">
            <div style="max-width:920px;padding:24px 0;">
                <h1 style="margin-bottom:8px;"><?php esc_html_e( 'Popup Preview', 'fooconvert' ); ?></h1>
                <p style="margin-top:0;color:#50575e;">
                    <?php esc_html_e( 'This is the real FooConvert frontend preview for the current AI-generated draft.', 'fooconvert' ); ?>
                </p>
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:20px 0 8px;">
                    <a id="fooconvert-widget-preview" class="button button-primary" href="#preview">
                        <?php esc_html_e( 'Replay Preview', 'fooconvert' ); ?>
                    </a>
                    <?php if ( $widget instanceof \WP_Post ) : ?>
                        <a class="button" href="<?php echo esc_url( fooconvert_admin_url_widget_edit( $widget_id ) ); ?>" target="_blank" rel="noreferrer">
                            <?php esc_html_e( 'Edit In New Tab', 'fooconvert' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <p style="color:#50575e;">
                    <?php esc_html_e( 'The popup opens automatically once the preview loads. Use the replay button if you close it.', 'fooconvert' ); ?>
                </p>
            </div>
        </div>
        <script>
            window.addEventListener( "load", function() {
                window.setTimeout( function() {
                    var button = document.getElementById( "fooconvert-widget-preview" );
                    if ( button ) {
                        button.click();
                    }
                }, 200 );
            } );
        </script>
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
                'chatPath'        => '/fooconvert/v1/ai-popup-builder/chat',
                'savePath'        => '/fooconvert/v1/ai-popup-builder/save',
                'deleteMediaPath' => '/fooconvert/v1/ai-popup-builder/media',
                'brandPath'       => '/fooconvert/v1/ai-popup-builder/brand',
                'extractBrandPath' => '/fooconvert/v1/ai-popup-builder/brand/extract',
            ),
            'restRoot'         => esc_url_raw( get_rest_url() ),
            'restNonce'        => wp_create_nonce( 'wp_rest' ),
            'previewBaseUrl'   => admin_url( fooconvert_admin_url_ai_popup_preview_base() ),
            'labels'           => array(
                FOOCONVERT_POPUP_TYPE_BAR    => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_BAR ),
                FOOCONVERT_POPUP_TYPE_FLYOUT => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_FLYOUT ),
                FOOCONVERT_POPUP_TYPE_POPUP  => fooconvert_get_popup_type_label( FOOCONVERT_POPUP_TYPE_POPUP ),
            ),
            'templates'        => PopupBlueprint::get_template_library(),
            'blockCatalog'     => PopupBlueprint::get_block_catalog(),
            'playbook'         => PopupBlueprint::get_conversion_playbook(),
            'systemPrompt'     => PopupBuilderAI::get_default_system_instruction_preview(),
            'mediaItems'       => PopupMedia::list_generated_images( 12 ),
            'aiClientAvailable' => function_exists( 'wp_ai_client_prompt' ),
            'imageGenerationAvailable' => function_exists( 'wp_ai_client_prompt' ) && PopupMedia::can_manage_media(),
            'canUploadMedia'   => PopupMedia::can_manage_media(),
            'abilitiesAvailable' => Abilities::wp_api_available(),
            'abilities'        => Abilities::get_allowed_abilities(),
            'brand'            => array(
                'savedBrand'    => BrandManager::get_saved_brand(),
                'hasSavedBrand' => BrandManager::has_saved_brand(),
                'defaultBrand'  => BrandManager::get_default_brand(),
            ),
            'starterPrompts'   => array(
                __( 'Build a Black Friday popup that offers 15% off the first order and captures email addresses.', 'fooconvert' ),
                __( 'Create a minimal flyout for a free shipping threshold campaign with one primary CTA.', 'fooconvert' ),
                __( 'Make a newsletter popup for a content brand that feels premium and low pressure.', 'fooconvert' ),
                __( 'Design a mobile-friendly announcement bar promoting a limited launch offer.', 'fooconvert' ),
            ),
        );
    }

    /**
     * Queues the preview popup when the dedicated preview page is loaded.
     *
     * @return void
     */
    public function enqueue_preview_widget(): void {
        if ( ! fooconvert_is_admin_ai_popup_preview_page() ) {
            return;
        }

        $widget_id = isset( $_GET['widget_id'] ) ? absint( $_GET['widget_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $widget_id > 0 ) {
            FooConvert::plugin()->display_rules->add_to_queue( $widget_id, 'admin_ai_preview' );
        }
    }

    /**
     * Renders queued preview widgets in the admin footer.
     *
     * @return void
     */
    public function render_preview_widget(): void {
        if ( fooconvert_is_admin_ai_popup_preview_page() ) {
            FooConvert::plugin()->display_rules->render_enqueued();
        }
    }

    /**
     * Overrides preview widget triggers so the popup can be replayed on demand.
     *
     * @param array<string,mixed> $attributes Widget attributes.
     * @param int                 $instance_id Widget instance ID.
     * @param string              $tag_name Widget tag name.
     * @param array<string,mixed> $block Block payload.
     * @return array<string,mixed>
     */
    public function override_preview_attributes( array $attributes, $instance_id, $tag_name, $block ): array {
        if ( ! fooconvert_is_admin_ai_popup_preview_page() ) {
            return $attributes;
        }

        $preview_id = isset( $_GET['widget_id'] ) ? absint( $_GET['widget_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $preview_id <= 0 || $preview_id !== absint( $instance_id ) ) {
            return $attributes;
        }

        $attributes['settings']['trigger'] = array(
            'version'   => 2,
            'lifetime'  => 'page',
            'frequency' => array(
                'mode'            => 'repeat',
                'cooldownSeconds' => 0,
            ),
            'steps'     => array(
                array(
                    'event' => 'fc.anchor.click',
                    'where' => array(
                        'ids' => array( 'fooconvert-widget-preview' ),
                    ),
                ),
            ),
        );

        return $attributes;
    }

    /**
     * Enqueues assets required to render the real popup preview page.
     *
     * @return void
     */
    private function enqueue_preview_assets(): void {
        FooConvert::plugin()->ensure_frontend_assets_enqueued();

        require_once ABSPATH . 'wp-includes/block-editor.php';

        do_action( 'enqueue_block_assets' );
    }

    /**
     * Checks whether the current hook is the preview page hook.
     *
     * @param string $hook Admin hook.
     * @return bool
     */
    private function is_preview_hook( string $hook ): bool {
        return 'admin_page_' . FOOCONVERT_MENU_SLUG_AI_POPUP_PREVIEW === $hook;
    }
}
