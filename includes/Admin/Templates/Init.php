<?php

namespace FooPlugins\FooConvert\Admin\Templates;

if ( !class_exists( __NAMESPACE__ . '\Init' ) ) {

    class Init {
        function __construct() {
            add_filter( 'fooconvert_editor_variations-fc-bar', array( $this, 'add_editor_bar_variations' ) );
            add_filter( 'fooconvert_editor_variations-fc-flyout', array( $this, 'add_editor_flyout_variations' ) );
            add_filter( 'fooconvert_editor_variations-fc-popup', array( $this, 'add_editor_popup_variations' ) );
            add_filter( 'fooconvert_get_fonts', array( $this, 'add_fonts' ) );
        }

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

        function add_fonts( $fonts ) {
            return array_merge( $fonts, $this->get_fonts() );
        }

        function add_editor_bar_variations( $variations ) {
            $variations[] = require __DIR__ . '/bars/watch_the_video.php';
            $variations[] = require __DIR__ . '/bars/special_offer.php';
            $variations[] = require __DIR__ . '/bars/digital_download_signup.php';
            $variations[] = require __DIR__ . '/bars/smart_exit_offer.php';
            $variations[] = require __DIR__ . '/bars/newsletter_subscribe.php';

            return $variations;
        }

        function add_editor_popup_variations( $variations ) {
            $variations[] = require __DIR__ . '/popups/watch_the_video.php';
            $variations[] = require __DIR__ . '/popups/special_offer.php';
            $variations[] = require __DIR__ . '/popups/digital_download_signup.php';
            $variations[] = require __DIR__ . '/popups/smart_exit_offer.php';
            $variations[] = require __DIR__ . '/popups/newsletter_subscribe.php';

            return $variations;
        }

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

