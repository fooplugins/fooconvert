<?php
/**
 * FooConvert Fonts Class
 */

namespace FooPlugins\FooConvert;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( __NAMESPACE__ . '\Fonts' ) ) {

    /**
     * Registers configured Google Fonts for FooConvert editor and frontend output.
     */
    class Fonts {

        /**
         * Hooks font registration into the admin and frontend asset lifecycle.
         *
         * @return void
         */
        public function __construct() {
            if ( is_admin() ) {
                add_filter( 'fooconvert_admin_settings', array( $this, 'change_settings' ) );
                add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_fonts_in_editor' ) );
                add_action( 'enqueue_block_assets', array( $this, 'enqueue_fonts_in_editor' ) );
                add_filter( 'block_editor_settings_all', array( $this, 'register_fonts_editor' ) );
            }
            add_action( 'wp_after_insert_post', array( $this, 'after_insert_should_compile' ), 10, 4 );
            add_filter( 'fooconvert_queueable_popup', array( $this, 'append_font_slugs' ), 10, 3 );
            add_action( 'fooconvert_enqueue_assets', array( $this, 'enqueue_assets' ) );
        }

        /**
         * Adds the fonts settings tab to the admin settings configuration.
         *
         * @param array $settings Existing settings configuration.
         * @return array
         */
        function change_settings( $settings ) {
            $fonts_tab = array(
                'id'     => 'fonts',
                'label'  => __( 'Fonts', 'fooconvert' ),
                'icon'   => 'dashicons-text',
                'order'  => 20,
                'fields' => array(
                    array(
                        'id'    => 'font_help',
                        'label' => __( 'Font Help', 'fooconvert' ),
                        'text'  => $this->get_font_help_text(),
                        'type'  => 'help',
                    ),
                    array(
                        'id'                     => 'fonts',
                        'type'                   => 'repeater',
                        'add_button_text'        => __( 'Add Font', 'fooconvert' ),
                        'no_data_message'        => __( 'No Google fonts have been added.', 'fooconvert' ),
                        'no_data_message_escape' => false,
                        'fields'                 => array(
                            array(
                                'id'   => 'index',
                                'type' => 'repeater-index'
                            ),
                            array(
                                'id'    => 'name',
                                'label' => __( 'Font Name', 'fooconvert' ),
                                'desc'  => __( 'The name of the font.', 'fooconvert' ),
                                'type'  => 'text',
                            ),
                            array(
                                'id'            => 'url',
                                'label'         => __( 'Font Family', 'fooconvert' ),
                                'desc'          => __( 'The Google Font family code, for example "Handlee" or "Montserrat:ital,wght@0,100..900;1,100..900".', 'fooconvert' ),
                                'type'          => 'text',
                                'value_encoder' => 'fooconvert_fix_google_font_url',
                            ),
                            array(
                                'id'   => 'manage',
                                'type' => 'repeater-delete',
                            ),
                        )
                    )
                )
            );

            $settings['fonts'] = $fonts_tab;

            return $settings;
        }

        /**
         * Returns the help text shown on the Fonts settings tab.
         *
         * @return string
         */
        private function get_font_help_text(): string {
            $message = __( 'Add Google Fonts here to make them available while editing your popups.', 'fooconvert' );
            $included_font_names = $this->get_included_font_names();

            if ( empty( $included_font_names ) ) {
                return $message;
            }

            return sprintf(
                /* translators: 1: existing help text. 2: comma-separated list of bundled font names. */
                __( '%1$s Included fonts already available for bundled templates: %2$s.', 'fooconvert' ),
                $message,
                implode( ', ', $included_font_names )
            );
        }

        /**
         * Returns the names of fonts already registered by bundled templates.
         *
         * @return array<int,string>
         */
        private function get_included_font_names(): array {
            $font_names = array_filter( wp_list_pluck( $this->get_included_fonts(), 'name' ) );

            return array_values( array_unique( $font_names, SORT_STRING ) );
        }

        /**
         * Returns the Google Font definitions configured in plugin settings.
         *
         * @return array<string,array<string,string>>
         */
        private function get_configured_fonts(): array {
            $fonts_from_settings = fooconvert_get_setting( 'fonts', [] );

            $fonts = [];

            foreach ( $fonts_from_settings as $font ) {
                fooconvert_add_font( $fonts, $font['name'], $font['url'] );
            }

            return $fonts;
        }

        /**
         * Returns the Google Font definitions registered by bundled templates.
         *
         * @return array<string,array<string,string>>
         */
        private function get_included_fonts(): array {
            return apply_filters( 'fooconvert_get_fonts', [] );
        }

        /**
         * Enqueues configured Google Fonts inside the block editor.
         *
         * @return void
         */
        function enqueue_fonts_in_editor() {
            if ( !function_exists( 'get_current_screen' ) ) {
                return;
            }

            $screen = get_current_screen();

            if ( !$screen || $screen->base !== 'post' ) {
                return;
            }

            $this->enqueue_font_styles( 'fooconvert-google-fonts-editor', $this->get_fonts(), true );
        }

        /**
         * Registers configured fonts with the editor typography settings.
         *
         * @param array $editor_settings Existing editor settings.
         * @return array
         */
        function register_fonts_editor( $editor_settings ) {
            if ( !isset( $editor_settings['__experimentalFeatures'] ) ) {
                $editor_settings['__experimentalFeatures'] = [];
            }

            if ( !isset( $editor_settings['__experimentalFeatures']['typography'] ) ) {
                $editor_settings['__experimentalFeatures']['typography'] = [];
            }

            if ( !isset( $editor_settings['__experimentalFeatures']['typography']['fontFamilies'] ) ) {
                $editor_settings['__experimentalFeatures']['typography']['fontFamilies'] = [];
            }

            if ( !isset( $editor_settings['__experimentalFeatures']['typography']['fontFamilies']['theme'] ) ) {
                $editor_settings['__experimentalFeatures']['typography']['fontFamilies']['theme'] = [];
            }

            foreach ( $this->get_fonts() as $font ) {
                $editor_settings['__experimentalFeatures']['typography']['fontFamilies']['theme'][] = [
                    'fontFamily' => $font['name'],
                    'name'       => $font['name'],
                    'slug'       => $font['slug'],
                ];
            }

            return $editor_settings;
        }

        /**
         * Returns the configured Google Font definitions.
         *
         * @return array<string,array<string,string>>
         */
        function get_fonts() {
            return apply_filters( 'fooconvert_get_fonts', $this->get_configured_fonts() );
        }

        /**
         * Recompile the popup font metadata whenever a popup is saved.
         *
         * @param int          $post_id The post id that was inserted.
         * @param WP_Post      $post The post object for the post.
         * @param bool         $updated Whether the post was updated or created.
         * @param WP_Post|null $post_before The previous post value if updated.
         * @return void
         */
        public function after_insert_should_compile( int $post_id, WP_Post $post, bool $updated, ?WP_Post $post_before ): void {
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            if ( $post->post_type !== FOOCONVERT_CPT_POPUP ) {
                return;
            }

            $content = isset( $post->post_content ) && is_string( $post->post_content ) ? $post->post_content : '';
            $this->compile( $post_id, $content );
        }

        /**
         * Compiles and stores the set of configured font slugs used by a popup.
         *
         * @param int    $post_id Popup post ID.
         * @param string $content Optional raw popup content.
         * @return void
         */
        public function compile( int $post_id, string $content = '' ): void {
            if ( $content === '' && function_exists( 'get_post_field' ) ) {
                $content = get_post_field( 'post_content', $post_id );
            }

            if ( !is_string( $content ) ) {
                $content = '';
            }

            update_post_meta( $post_id, FOOCONVERT_META_KEY_USED_FONTS, $this->get_compiled_font_slugs( $content ) );
        }

        /**
         * Attaches compiled font slugs to queued popups so request-time checks are deterministic.
         *
         * @param array<string,mixed> $queueable Queueable popup payload.
         * @param int                 $resolved_post_id Popup post ID after resolution.
         * @param array<string,mixed> $context_data Queue context information.
         * @return array<string,mixed>
         */
        public function append_font_slugs( array $queueable, int $resolved_post_id, array $context_data ): array {
            if ( empty( $queueable ) || $resolved_post_id <= 0 ) {
                return $queueable;
            }

            $queueable['fontSlugs'] = $this->get_saved_font_slugs( $resolved_post_id );

            return $queueable;
        }

        /**
         * Enqueues the subset of configured fonts used by the queued popups.
         *
         * @param array $popups Popups being rendered on the current request.
         * @return void
         */
        function enqueue_assets( $popups ) {
            if ( empty( $popups ) ) {
                return;
            }

            $fonts = $this->get_fonts();

            if ( count( $fonts ) === 0 ) {
                return;
            }

            $used_fonts = [];

            foreach ( $popups as $popup ) {
                if ( count( $fonts ) === count( $used_fonts ) ) {
                    break;
                }

                $font_slugs = isset( $popup['fontSlugs'] ) ? $this->normalize_font_slugs( $popup['fontSlugs'] ) : [];
                foreach ( $font_slugs as $slug ) {
                    if ( isset( $fonts[ $slug ] ) && !isset( $used_fonts[ $slug ] ) ) {
                        $used_fonts[ $slug ] = $fonts[ $slug ];
                    }
                }
            }

            $this->enqueue_font_styles( 'fooconvert-google-fonts', $used_fonts );
        }

        /**
         * Returns normalized font slugs saved for a popup.
         *
         * @param int $post_id Popup post ID.
         * @return string[]
         */
        private function get_saved_font_slugs( int $post_id ): array {
            return $this->normalize_font_slugs( get_post_meta( $post_id, FOOCONVERT_META_KEY_USED_FONTS, true ) );
        }

        /**
         * Compiles the set of configured font slugs used by popup content.
         *
         * This scan runs only at save-time. Request-time checks use the compiled
         * popup meta attached to the queueable payload.
         *
         * @param string $content Raw popup post content.
         * @return string[]
         */
        private function get_compiled_font_slugs( string $content ): array {
            $fonts = $this->get_fonts();
            if ( empty( $fonts ) ) {
                return [];
            }

            $used_fonts = [];

            if ( $content !== '' ) {
                $this->collect_used_fonts_from_content( $content, $fonts, $used_fonts );
            }

            if ( count( $fonts ) === count( $used_fonts ) || !function_exists( 'parse_blocks' ) || $content === '' ) {
                return array_values( array_keys( $used_fonts ) );
            }

            $blocks = parse_blocks( $content );
            if ( !is_array( $blocks ) || empty( $blocks ) ) {
                return array_values( array_keys( $used_fonts ) );
            }

            $this->collect_used_fonts_from_blocks( $blocks, $fonts, $used_fonts );

            return array_values( array_keys( $used_fonts ) );
        }

        /**
         * Adds fonts referenced by class markers found directly in the saved content.
         *
         * @param string                                 $content Raw popup post content.
         * @param array<string,array<string,string>>     $fonts Configured fonts keyed by slug.
         * @param array<string,array<string,string>>     $used_fonts Mutable list of detected fonts.
         * @return void
         */
        private function collect_used_fonts_from_content( string $content, array $fonts, array &$used_fonts ): void {
            foreach ( $fonts as $slug => $font ) {
                if ( !isset( $used_fonts[ $slug ] ) && ( strpos( $content, "has-{$slug}-font-family" ) !== false || strpos( $content, "uses-{$slug}-font-family" ) !== false ) ) {
                    $used_fonts[ $slug ] = $font;
                }
            }
        }

        /**
         * Walks parsed blocks and records any configured fonts referenced in attrs.
         *
         * @param array<int,array<string,mixed>>          $blocks Parsed blocks.
         * @param array<string,array<string,string>>     $fonts Configured fonts keyed by slug.
         * @param array<string,array<string,string>>     $used_fonts Mutable list of detected fonts.
         * @return void
         */
        private function collect_used_fonts_from_blocks( array $blocks, array $fonts, array &$used_fonts ): void {
            foreach ( $blocks as $block ) {
                if ( count( $fonts ) === count( $used_fonts ) ) {
                    return;
                }

                $attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];
                if ( !empty( $attrs ) ) {
                    $this->collect_used_fonts_from_value( $attrs, $fonts, $used_fonts );
                }

                $inner_blocks = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? $block['innerBlocks'] : [];
                if ( !empty( $inner_blocks ) ) {
                    $this->collect_used_fonts_from_blocks( $inner_blocks, $fonts, $used_fonts );
                }
            }
        }

        /**
         * Recursively inspects a block attribute value for font-family references.
         *
         * @param mixed                                  $value Arbitrary attribute value.
         * @param array<string,array<string,string>>     $fonts Configured fonts keyed by slug.
         * @param array<string,array<string,string>>     $used_fonts Mutable list of detected fonts.
         * @return void
         */
        private function collect_used_fonts_from_value( $value, array $fonts, array &$used_fonts ): void {
            if ( !is_array( $value ) ) {
                return;
            }

            if ( array_key_exists( 'fontFamily', $value ) ) {
                $this->maybe_add_used_font( $value['fontFamily'], $fonts, $used_fonts );
            }

            foreach ( $value as $child_value ) {
                if ( count( $fonts ) === count( $used_fonts ) ) {
                    return;
                }

                if ( is_array( $child_value ) ) {
                    $this->collect_used_fonts_from_value( $child_value, $fonts, $used_fonts );
                }
            }
        }

        /**
         * Adds a configured font to the detected font list when the candidate matches.
         *
         * @param mixed                                  $font_value Candidate font value from block attrs.
         * @param array<string,array<string,string>>     $fonts Configured fonts keyed by slug.
         * @param array<string,array<string,string>>     $used_fonts Mutable list of detected fonts.
         * @return void
         */
        private function maybe_add_used_font( $font_value, array $fonts, array &$used_fonts ): void {
            $slug = $this->match_font_slug( $font_value, $fonts );
            if ( $slug !== null && !isset( $used_fonts[ $slug ] ) ) {
                $used_fonts[ $slug ] = $fonts[ $slug ];
            }
        }

        /**
         * Resolves a block font-family value to a configured font slug.
         *
         * Supports core slugs, FooConvert font objects, legacy name-only values,
         * and serialized CSS family strings with fallbacks.
         *
         * @param mixed                              $font_value Candidate font value from block attrs.
         * @param array<string,array<string,string>> $fonts Configured fonts keyed by slug.
         * @return string|null
         */
        private function match_font_slug( $font_value, array $fonts ): ?string {
            if ( is_string( $font_value ) ) {
                $candidate = trim( $font_value );
                if ( $candidate === '' ) {
                    return null;
                }

                if ( isset( $fonts[ $candidate ] ) ) {
                    return $candidate;
                }

                $candidate_slug = sanitize_title( $candidate );
                if ( isset( $fonts[ $candidate_slug ] ) ) {
                    return $candidate_slug;
                }

                foreach ( $fonts as $slug => $font ) {
                    $font_name = isset( $font['name'] ) && is_string( $font['name'] ) ? $font['name'] : '';
                    if ( $font_name !== '' && ( strcasecmp( $candidate, $font_name ) === 0 || stripos( $candidate, $font_name ) !== false ) ) {
                        return $slug;
                    }
                }

                return null;
            }

            if ( is_array( $font_value ) ) {
                $candidates = [];

                if ( isset( $font_value['key'] ) && is_string( $font_value['key'] ) ) {
                    $candidates[] = $font_value['key'];
                }
                if ( isset( $font_value['slug'] ) && is_string( $font_value['slug'] ) ) {
                    $candidates[] = $font_value['slug'];
                }
                if ( isset( $font_value['name'] ) && is_string( $font_value['name'] ) ) {
                    $candidates[] = $font_value['name'];
                }
                if (
                    isset( $font_value['style'] )
                    && is_array( $font_value['style'] )
                    && isset( $font_value['style']['fontFamily'] )
                    && is_string( $font_value['style']['fontFamily'] )
                ) {
                    $candidates[] = $font_value['style']['fontFamily'];
                }

                foreach ( $candidates as $candidate ) {
                    $slug = $this->match_font_slug( $candidate, $fonts );
                    if ( $slug !== null ) {
                        return $slug;
                    }
                }
            }

            return null;
        }

        /**
         * Normalizes stored font slugs to a unique list of strings.
         *
         * @param mixed $value Raw stored font slugs.
         * @return string[]
         */
        private function normalize_font_slugs( $value ): array {
            if ( !is_array( $value ) ) {
                return [];
            }

            $slugs = [];
            foreach ( $value as $slug ) {
                if ( is_string( $slug ) ) {
                    $slug = sanitize_title( $slug );
                    if ( $slug !== '' && !in_array( $slug, $slugs, true ) ) {
                        $slugs[] = $slug;
                    }
                }
            }

            return $slugs;
        }

        /**
         * Enqueues a Google Fonts stylesheet and matching utility classes.
         *
         * @param string $handle Style handle to enqueue.
         * @param array  $fonts Font definitions keyed by slug.
         * @param bool   $include_editor_selector Whether editor-specific selectors should be added.
         * @return void
         */
        private function enqueue_font_styles( string $handle, array $fonts, bool $include_editor_selector = false ) {
            if ( empty( $fonts ) ) {
                return;
            }

            $urls = array_map( fn( $font ) => $font['url'], $fonts );

            $fonts_url = add_query_arg(
                [ 'family' => implode( '&family=', $urls ), 'display' => 'swap' ],
                'https://fonts.googleapis.com/css2'
            );

            wp_enqueue_style( $handle, $fonts_url, [], null );

            $css = '';

            foreach ( $fonts as $slug => $font ) {
                $font_family = $font['name'];
                $css .= ".has-{$slug}-font-family, .uses-{$slug}-font-family { font-family: {$font_family}; }\n";
                if ( $include_editor_selector ) {
                    $css .= ".editor-styles-wrapper .has-{$slug}-font-family, .editor-styles-wrapper .uses-{$slug}-font-family { font-family: {$font_family}; }\n";
                }
            }

            if ( !empty( $css ) ) {
                wp_add_inline_style( $handle, $css );
            }
        }
    }
}
