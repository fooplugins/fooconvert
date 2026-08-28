<?php

namespace FooPlugins\FooConvert;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles standalone popup preview requests.
 */
class Preview {

    /**
     * Popup display rules service.
     *
     * @var DisplayRules
     */
    private DisplayRules $display_rules;

    /**
     * Registers popup preview hooks.
     *
     * @param DisplayRules $display_rules Popup display rules service.
     */
    public function __construct( DisplayRules $display_rules ) {
        $this->display_rules = $display_rules;

        add_action( 'template_redirect', array( $this, 'maybe_render_popup_preview' ), 0 );
        add_filter( 'show_admin_bar', array( $this, 'maybe_hide_admin_bar_for_popup_preview' ) );
        add_filter( 'fooconvert-popup-frontend-attributes', array( $this, 'override_popup_preview_attributes' ), 10, 4 );
    }

    /**
     * Hide the admin bar on the standalone popup preview page.
     *
     * @param bool $show Whether the admin bar should be shown.
     * @return bool
     */
    public function maybe_hide_admin_bar_for_popup_preview( bool $show ): bool {
        return fooconvert_is_popup_preview_request() ? false : $show;
    }

    /**
     * Force standalone popup previews to open immediately.
     *
     * @param array  $attributes Popup block attributes.
     * @param string $instance_id Popup instance id.
     * @param string $tag_name Popup tag name.
     * @param mixed  $block Current block instance.
     * @return array
     */
    public function override_popup_preview_attributes( $attributes, $instance_id, $tag_name, $block ) {
        if ( !fooconvert_is_popup_preview_request() ) {
            return $attributes;
        }

        $settings = Utils::get_array( $attributes, 'settings' );
        $settings['trigger'] = array(
            'version'   => 2,
            'lifetime'  => 'page',
            'frequency' => array(
                'mode'            => 'repeat',
                'cooldownSeconds' => 0,
            ),
            'steps'     => array(
                array(
                    'event' => 'fc.immediate',
                    'where' => array(),
                ),
            ),
        );
        $attributes['settings'] = $settings;

        return $attributes;
    }

    /**
     * Render popup previews in a dedicated frontend request so theme and block styles load normally.
     *
     * @return void
     */
    public function maybe_render_popup_preview(): void {
        if ( !fooconvert_is_popup_preview_request() ) {
            return;
        }

        $post_id = isset( $_GET['fooconvert_popup_preview'] ) ? absint( wp_unslash( $_GET['fooconvert_popup_preview'] ) ) : 0;
        $nonce = isset( $_GET['_fcpreviewnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_fcpreviewnonce'] ) ) : '';

        if ( $post_id <= 0 || empty( $nonce ) || !wp_verify_nonce( $nonce, 'fooconvert-popup-preview-' . $post_id ) || !current_user_can( 'edit_post', $post_id ) ) {
            status_header( 403 );
            nocache_headers();
            wp_die( esc_html__( 'You are not allowed to preview this popup.', 'fooconvert' ), '', array( 'response' => 403 ) );
        }

        $post = get_post( $post_id );
        if ( !$post instanceof \WP_Post || fooconvert_get_popup_type( $post ) === '' ) {
            status_header( 404 );
            nocache_headers();
            wp_die( esc_html__( 'Popup preview not found.', 'fooconvert' ), '', array( 'response' => 404 ) );
        }

        $this->display_rules->add_to_queue( $post_id, 'preview' );
        $this->display_rules->enqueue_popup_assets();

        wp_register_style( 'fooconvert-popup-preview-shell', false );
        wp_add_inline_style( 'fooconvert-popup-preview-shell', '
            html, body { min-height: 100%; margin: 0; }
            body.fooconvert-popup-preview-page {
                background: linear-gradient(180deg, #f6f7fb 0%, #eef1f6 100%);
            }
            .fooconvert-popup-preview-page__notice {
                position: fixed;
                top: 16px;
                left: 16px;
                z-index: 999999;
                max-width: min(420px, calc(100vw - 32px));
                padding: 10px 14px;
                border-radius: 10px;
                background: rgba(17, 24, 39, 0.88);
                color: #fff;
                font: 14px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
            }
            .fooconvert-popup-preview-page__notice-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                margin-bottom: 2px;
            }
            .fooconvert-popup-preview-page__notice strong {
                display: block;
                font-size: 13px;
                letter-spacing: 0.02em;
                text-transform: uppercase;
                opacity: 0.72;
            }
            .fooconvert-popup-preview-page__refresh {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 22px;
                height: 22px;
                color: #fff;
                opacity: 0.86;
                text-decoration: none;
            }
            .fooconvert-popup-preview-page__refresh:hover,
            .fooconvert-popup-preview-page__refresh:focus {
                color: #fff;
                opacity: 1;
            }
            .fooconvert-popup-preview-page__refresh svg {
                display: block;
                width: 16px;
                height: 16px;
                fill: none;
                stroke: currentColor;
                stroke-width: 2;
                stroke-linecap: round;
                stroke-linejoin: round;
            }
            .fooconvert-popup-preview-page__title {
                margin-bottom: 6px;
            }
            .fooconvert-popup-preview-page__edit-link {
                color: #fff;
                font-size: 13px;
                font-weight: 600;
                text-decoration: underline;
                text-underline-offset: 2px;
            }
            .fooconvert-popup-preview-page__edit-link:hover,
            .fooconvert-popup-preview-page__edit-link:focus {
                color: #fff;
            }
        ' );
        wp_enqueue_style( 'fooconvert-popup-preview-shell' );

        $title = fooconvert_get_popup_title( $post );
        $preview_url = fooconvert_popup_preview_url( $post_id );
        $edit_url = fooconvert_admin_url_popup_edit( $post_id );

        status_header( 200 );
        nocache_headers();
        ?><!doctype html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php
            /* translators: %s: popup title shown in the preview page title. */
            echo esc_html( sprintf( __( 'Preview: %s', 'fooconvert' ), $title ) );
            ?></title>
            <?php wp_head(); ?>
        </head>
        <body <?php body_class( 'fooconvert-popup-preview-page' ); ?>>
            <?php wp_body_open(); ?>
            <div class="fooconvert-popup-preview-page__notice">
                <div class="fooconvert-popup-preview-page__notice-header">
                    <strong><?php esc_html_e( 'Popup Preview', 'fooconvert' ); ?></strong>
                    <a
                        class="fooconvert-popup-preview-page__refresh"
                        href="<?php echo esc_url( $preview_url ); ?>"
                        aria-label="<?php esc_attr_e( 'Refresh', 'fooconvert' ); ?>"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M21 12a9 9 0 1 1-2.64-6.36"></path>
                            <path d="M21 3v6h-6"></path>
                        </svg>
                    </a>
                </div>
                <div class="fooconvert-popup-preview-page__title"><?php echo esc_html( $title ); ?></div>
                <a class="fooconvert-popup-preview-page__edit-link" href="<?php echo esc_url( $edit_url ); ?>">
                    <?php esc_html_e( 'Edit Popup', 'fooconvert' ); ?>
                </a>
            </div>
            <?php wp_footer(); ?>
            <?php $this->render_direct_open_script(); ?>
        </body>
        </html><?php
        exit;
    }

    /**
     * Directly opens the preview popup after frontend scripts and custom elements load.
     *
     * @return void
     */
    private function render_direct_open_script(): void {
        ?>
        <script>
            ( function () {
                var selector = 'fc-overlay, fc-flyout, fc-bar';

                function getPreviewPopups() {
                    return Array.prototype.slice.call( document.querySelectorAll( selector ) );
                }

                function openPreviewPopups() {
                    getPreviewPopups().forEach( function ( popup ) {
                        if ( typeof popup.setOpen === 'function' && !popup.hasAttribute( 'open' ) ) {
                            popup.setOpen( true, { trigger: 'preview' } );
                        }
                    } );
                }

                function openWhenDefined() {
                    if ( !window.customElements || typeof window.customElements.whenDefined !== 'function' ) {
                        openPreviewPopups();
                        return;
                    }

                    getPreviewPopups().forEach( function ( popup ) {
                        window.customElements
                            .whenDefined( popup.localName )
                            .then( openPreviewPopups )
                            .catch( function () {} );
                    } );
                }

                openPreviewPopups();
                openWhenDefined();
                window.setTimeout( function () {
                    openPreviewPopups();
                    openWhenDefined();
                }, 0 );
            }() );
        </script>
        <?php
    }
}
