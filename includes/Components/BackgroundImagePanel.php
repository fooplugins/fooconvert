<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BackgroundImagePanel.
 */
class BackgroundImagePanel extends BaseComponent {

    /**
     * Returns the styles.
     */
    function get_styles( array $value ): array {
        $styles = array();
        $background_image = Utils::get_array( $value, 'backgroundImage' );
        if ( !empty( $background_image ) ) {
            $background_url = Utils::get_string( $background_image, 'url' );
            if ( !empty( $background_url ) ) {
                $styles['background-image'] = "url($background_url)";

                $background_position = Utils::get_string( $value, 'backgroundPosition' );
                if ( !empty( $background_position ) ) {
                    $styles['background-position'] = $background_position;
                }

                $background_repeat = Utils::get_string( $value, 'backgroundRepeat' );
                if ( !empty( $background_repeat ) ) {
                    $styles['background-repeat'] = $background_repeat;
                }

                $background_size = Utils::get_string( $value, 'backgroundSize' );
                if ( !empty( $background_size ) ) {
                    $styles['background-size'] = $background_size;
                }

                $background_attachment = Utils::get_string( $value, 'backgroundAttachment' );
                if ( !empty( $background_attachment ) ) {
                    $styles['background-attachment'] = $background_attachment;
                }
            }
        }
        return $styles;
    }
}