<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

use FooPlugins\FooConvert\Admin\FooFields\Fields\Field;
use FooPlugins\FooConvert\Utils;

if ( !class_exists( __NAMESPACE__ . '\Blurb' ) ) {

    class Blurb extends Field {

        public $title;
        public $features;
        public $summary;
        public $images;

        public function __construct( $container, $type, $field_config ) {
            parent::__construct( $container, $type, $field_config );
            $this->title = Utils::get_string( 'title', $field_config );
            if ( !isset( $this->title ) ) {
                $this->title = Utils::get_string( 'label', $field_config );
            }
            $this->features = Utils::get_array( 'features', $field_config );
            $this->summary = Utils::get_string( 'summary', $field_config );
            $this->images = Utils::get_array( 'images', $field_config );
        }

        function pre_render() {
            parent::pre_render();
            if ( isset( $this->images ) ) {
                $this->classes[] = 'foofields-blurb-has-media';
            }
        }

        function render_label() {}

        function render_input_container( $override_attributes = false ) {
            $this->render_text_container();
            if ( isset( $this->images ) ) {
                $this->render_media_container();
            }
        }

        function render_text_container() {
            self::render_html_tag( 'div', array( 'class' => 'foofields-blurb-text' ), null, false );
            if ( isset( $this->title ) ) {
                self::render_html_tag( 'h3', array( 'class' => 'foofields-blurb-text__title' ), $this->title, true, false );
            }
            if ( isset( $this->description ) ) {
                self::render_html_tag( 'p', array( 'class' => 'foofields-blurb-text__description' ), $this->description, false );
            }
            if ( isset( $this->features ) ) {
                self::render_html_tag( 'ul', array( 'class' => 'foofields-blurb-text__features' ), null, false, false );
                foreach ( $this->features as $feature ) {
                    self::render_html_tag( 'li', array( 'class' => 'foofields-blurb-text__feature' ), $feature, true, false );
                }
                echo '</ul>';
            }
            if ( isset( $this->summary ) ) {
                self::render_html_tag( 'p', array( 'class' => 'foofields-blurb-text__summary' ), $this->summary );
            }
            echo '</div>';
        }

        function render_media_container() {
            self::render_html_tag( 'div', array( 'class' => 'foofields-blurb-media' ), null, false );
            foreach ( $this->images as $image ) {
                self::render_html_tag( 'figure', array( 'class' => 'foofields-blurb-media__figure' ), null, false );
                if ( is_array( $image ) && array_key_exists( 'src', $image ) ) {
                    self::render_html_tag( 'img', array(
                        'class' => 'foofields-blurb-media__image',
                        'src'   => esc_url( $image['src'] )
                    ) );
                    if ( array_key_exists( 'caption', $image ) ) {
                        self::render_html_tag( 'figcaption', array( 'class' => 'foofields-blurb-media__figcaption' ), $image['caption'] );
                    }
                } else if ( is_string( $image ) ) {
                    self::render_html_tag( 'img', array(
                        'class' => 'foofields-blurb-media__image',
                        'src'   => esc_url( $image )
                    ) );
                }
                echo '</figure>';
            }
            echo '</div>';
        }
    }
}
