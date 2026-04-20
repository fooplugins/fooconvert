<?php

namespace FooPlugins\FooConvert\Components;

use FooPlugins\FooConvert\Components\Base\BaseComponent;
use FooPlugins\FooConvert\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BorderToolsPanel.
 */
class BorderToolsPanel extends BaseComponent {
    private BorderControl $border_control;
    private BorderRadiusControl $border_radius_control;

    /**
     * Initializes the BorderToolsPanel.
     */
    function __construct( BorderControl $border_control, BorderRadiusControl $border_radius_control ) {
        parent::__construct();
        $this->border_control = $border_control;
        $this->border_radius_control = $border_radius_control;
    }

    /**
     * Returns the styles.
     */
    function get_styles( array $value, bool $style_required = false ): array {
        $styles = array();
        $shadow = Utils::get_string( $value, 'shadow' );
        if ( !empty( $shadow ) ) {
            $styles['box-shadow'] = $shadow;
        }
        $radius = Utils::get_key( $value, 'radius' );
        if ( !empty( $radius ) ) {
            $styles = array_merge(
                $styles,
                $this->border_radius_control->get_styles( $radius )
            );
        }
        return array_merge(
            $styles,
            $this->border_control->get_styles( $value, $style_required )
        );
    }
}