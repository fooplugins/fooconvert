<?php

namespace FooPlugins\FooConvert\Admin\Templates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( __NAMESPACE__ . '\Init' ) ) {

    /**
     * Registers bundled popup templates and their supporting fonts.
     */
    class Init {
        /**
         * Hooks the template variations into the editor configuration.
         *
         * @return void
         */
        function __construct() {
            add_filter( 'fooconvert_editor_variations-fc-bar', array( $this, 'add_editor_bar_variations' ) );
            add_filter( 'fooconvert_editor_variations-fc-flyout', array( $this, 'add_editor_flyout_variations' ) );
            add_filter( 'fooconvert_editor_variations-fc-popup', array( $this, 'add_editor_overlay_variations' ) );
            add_filter( 'fooconvert_get_fonts', array( $this, 'add_fonts' ) );
        }

        /**
         * Returns the extra Google Fonts required by the bundled templates.
         *
         * @return array<string,array<string,string>>
         */
        private function get_fonts(): array {
            return array(
                'handlee' => array(
                    'slug' => 'handlee',
                    'name' => 'Handlee',
                    'url' => 'Handlee',
                ),
                'montserrat' => array(
                    'slug' => 'montserrat',
                    'name' => 'Montserrat',
                    'url' => 'Montserrat:ital,wght@0,100..900;1,100..900',
                ),
            );
        }

        /**
         * Merges the bundled template fonts into the registered font list.
         *
         * @param array<string,array<string,string>> $fonts Existing font definitions.
         * @return array<string,array<string,string>>
         */
        function add_fonts( $fonts ) {
            return array_merge( $fonts, $this->get_fonts() );
        }

        /**
         * Appends bundled bar template variations for the editor.
         *
         * @param array<int,array<string,mixed>> $variations Existing block variations.
         * @return array<int,array<string,mixed>>
         */
        function add_editor_bar_variations( $variations ) {
            $variations[] = require __DIR__ . '/bars/watch_the_video.php';
            $variations[] = require __DIR__ . '/bars/special_offer.php';
            $variations[] = require __DIR__ . '/bars/digital_download_signup.php';
            $variations[] = require __DIR__ . '/bars/smart_exit_offer.php';
            $variations[] = require __DIR__ . '/bars/newsletter_subscribe.php';

            return $variations;
        }

        /**
         * Appends bundled overlay template variations for the editor.
         *
         * @param array<int,array<string,mixed>> $variations Existing block variations.
         * @return array<int,array<string,mixed>>
         */
        function add_editor_overlay_variations( $variations ) {
            $variations[] = require __DIR__ . '/overlays/watch_the_video.php';
            $variations[] = require __DIR__ . '/overlays/special_offer.php';
            $variations[] = require __DIR__ . '/overlays/digital_download_signup.php';
            $variations[] = require __DIR__ . '/overlays/smart_exit_offer.php';
            $variations[] = require __DIR__ . '/overlays/newsletter_subscribe.php';

            return $variations;
        }

        /**
         * Appends bundled flyout template variations for the editor.
         *
         * @param array<int,array<string,mixed>> $variations Existing block variations.
         * @return array<int,array<string,mixed>>
         */
        function add_editor_flyout_variations( $variations ) {
            $variations[] = require __DIR__ . '/flyouts/watch_the_video.php';
            $variations[] = require __DIR__ . '/flyouts/special_offer.php';
            $variations[] = require __DIR__ . '/flyouts/digital_download_signup.php';
            $variations[] = require __DIR__ . '/flyouts/smart_exit_offer.php';
            $variations[] = require __DIR__ . '/flyouts/newsletter_subscribe.php';

            return $variations;
        }
    }
}
