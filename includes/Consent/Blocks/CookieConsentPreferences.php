<?php

namespace FooPlugins\FooConvert\Consent\Blocks;

use FooPlugins\FooConvert\Blocks\Base\BaseBlock;
use FooPlugins\FooConvert\Consent\Consent;
use FooPlugins\FooConvert\Utils;
use WP_Block;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( __NAMESPACE__ . '\CookieConsentPreferences' ) ) {

    /**
     * Class CookieConsentPreferences.
     *
     * Server-rendered custom block that outputs the per-category preferences
     * panel used as layer 2 of the cookie consent banner. Written server-side
     * (not as a saved block markup) so the category list stays in sync with
     * settings — bumping a label in the admin updates every banner without
     * any re-save of popup posts.
     *
     * Inner markup uses only default-allowed HTML (div / span / p), so no
     * extension of the kses allowlist is needed for the inside. The custom
     * element wrapper `<fc-cookie-consent-preferences>` is allowed via this
     * block's `kses_definition()`.
     *
     * Interactive behaviour (toggle state + consent API wiring) is supplied
     * by the consent frontend runtime (separate phase). Without the runtime,
     * the block still renders the category list as text, which is correct
     * as a progressive-enhancement fallback.
     */
    class CookieConsentPreferences extends BaseBlock {

        public function kses_definition(): array {
            return array(
                $this->get_tag_name() => array(
                    'id'           => true,
                    'class'        => true,
                    'hidden'       => true,
                    'data-version' => true,
                    'data-categories' => true,
                ),
            );
        }

        public function get_block_name(): string {
            return 'fc/cookie-consent-preferences';
        }

        public function get_tag_name(): string {
            return 'fc-cookie-consent-preferences';
        }

        public function register_blocks() {
            return Utils::register_popup_blocks( array(
                array(
                    'file_or_folder' => FOOCONVERT_ASSETS_PATH . 'blocks/cookie-consent-preferences/block.json',
                    'args'           => array(
                        'render_callback' => array( $this, 'render' ),
                    ),
                ),
            ) );
        }

        /**
         * Builds the server-rendered inner markup and passes it to the
         * BaseBlock render so the custom element wrapping is consistent
         * with every other FC block.
         */
        public function render( array $attributes, string $content, WP_Block $block ) {
            return parent::render( $attributes, $this->build_inner_html(), $block );
        }

        /**
         * Exposes the admin-customised category copy to the editor bundle,
         * so the Edit component's preview reflects what visitors will see.
         * Shape matches what `Edit.js` expects.
         */
        public function get_editor_data(): array {
            $copy = Consent::get_category_copy();
            $categories = array();

            foreach ( Consent::KNOWN_CATEGORIES as $key ) {
                if ( !isset( $copy[ $key ] ) ) {
                    continue;
                }
                $categories[] = array(
                    'key'         => $key,
                    'label'       => $copy[ $key ]['label'],
                    'description' => $copy[ $key ]['description'],
                    'locked'      => ( $key === 'necessary' ),
                );
            }

            return array( 'categories' => $categories );
        }

        /**
         * Computes the `data-categories` attribute used by the frontend
         * runtime to seed initial state and by admins eyeballing the DOM
         * for debugging. Everything defaults off — the `necessary` flag
         * is always `1`.
         */
        public function get_frontend_attributes( string $instance_id, array $attributes, WP_Block $block ): array {
            $initial = array_fill_keys( Consent::KNOWN_CATEGORIES, false );
            $initial['necessary'] = true;

            $version = (int) Consent::get_setting( 'version', 1 );

            return array(
                'data-categories' => ( new Consent() )->serialize_categories( $initial ),
                'data-version'    => (string) max( 1, $version ),
                'hidden'          => '',
            );
        }

        /**
         * Renders the category rows as plain structural HTML. Intentionally
         * uses only tags that are already on the default WP `post` kses
         * allowlist so no kses extension is required for the inside.
         */
        private function build_inner_html(): string {
            $copy = Consent::get_category_copy();
            $html = '<div class="fc-cookie-consent-preferences__list">';

            foreach ( Consent::KNOWN_CATEGORIES as $key ) {
                if ( !isset( $copy[ $key ] ) ) {
                    continue;
                }

                $locked = ( $key === 'necessary' );
                $item_classes = 'fc-cookie-consent-preferences__item';
                if ( $locked ) {
                    $item_classes .= ' fc-cookie-consent-preferences__item--locked';
                }

                $state_label = $locked
                    ? __( 'Always on', 'fooconvert' )
                    : __( 'Off', 'fooconvert' );

                $html .= sprintf(
                    '<div class="%s" data-category="%s" data-state="%s" data-locked="%s">',
                    esc_attr( $item_classes ),
                    esc_attr( $key ),
                    esc_attr( $locked ? 'on' : 'off' ),
                    $locked ? 'true' : 'false'
                );
                $html .= '<div class="fc-cookie-consent-preferences__item-head">';
                $html .= '<span class="fc-cookie-consent-preferences__label">' . esc_html( $copy[ $key ]['label'] ) . '</span>';
                $html .= '<span class="fc-cookie-consent-preferences__state">' . esc_html( $state_label ) . '</span>';
                $html .= '</div>';

                if ( $copy[ $key ]['description'] !== '' ) {
                    $html .= '<p class="fc-cookie-consent-preferences__desc">' . esc_html( $copy[ $key ]['description'] ) . '</p>';
                }

                $html .= '</div>';
            }

            $html .= '</div>';

            return $html;
        }
    }
}
