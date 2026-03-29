<?php
/**
 * FooConvert Fonts Class
 */

namespace FooPlugins\FooConvert;

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
                        'text'  => __( 'Add Google Fonts here to make them available while editing your widgets.', 'fooconvert' ),
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
            $fonts_from_settings = fooconvert_get_setting( 'fonts', [] );

            $fonts = [];

            foreach ( $fonts_from_settings as $font ) {
                fooconvert_add_font( $fonts, $font['name'], $font['url'] );
            }

            return apply_filters( 'fooconvert_get_fonts', $fonts );
        }

        /**
         * Enqueues the subset of configured fonts used by the queued widgets.
         *
         * @param array $widgets Widgets being rendered on the current request.
         * @return void
         */
        function enqueue_assets( $widgets ) {
            if ( empty( $widgets ) ) {
                return;
            }

            $fonts = $this->get_fonts();

            if ( count( $fonts ) === 0 ) {
                return;
            }

            $used_fonts = [];

            foreach ( $widgets as $widget ) {
                if ( count( $fonts ) === count( $used_fonts ) ) {
                    break;
                }

                $content = $widget['content'];

                if ( empty( $content ) ) {
                    continue;
                }

                foreach ( $fonts as $slug => $font ) {
                    if ( !isset( $used_fonts[ $slug ] ) && ( strpos( $content, "has-{$slug}-font-family" ) !== false || strpos( $content, "uses-{$slug}-font-family" ) !== false ) ) {
                        $used_fonts[ $slug ] = $font;
                    }
                }
            }

            $this->enqueue_font_styles( 'fooconvert-google-fonts', $used_fonts );
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
                $css .= ".has-{$slug}-font-family { font-family: {$font_family}; }\n";
                if ( $include_editor_selector ) {
                    $css .= ".editor-styles-wrapper .has-{$slug}-font-family { font-family: {$font_family}; }\n";
                }
            }

            if ( !empty( $css ) ) {
                wp_add_inline_style( $handle, $css );
            }
        }
    }
}
