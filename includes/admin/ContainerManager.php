<?php

namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Admin\FooFields\Manager;

/**
 * FooConvert FooFields Manager Class
 */

if ( !class_exists( 'FooPlugins\FooConvert\Admin\ContainerManager' ) ) {

    class ContainerManager extends Manager {

        public function __construct() {
            parent::__construct( array(
                'id'             => FOOCONVERT_SLUG,
                'text_domain'    => FOOCONVERT_SLUG,
                'plugin_url'     => FOOCONVERT_URL,
                'plugin_version' => FOOCONVERT_VERSION
            ) );
        }
    }
}
