<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

if ( !class_exists( __NAMESPACE__ . '\AjaxButton' ) ) {

    class AjaxButton extends Field {

        function __construct( $container, $type, $field_config ) {
            parent::__construct( $container, $type, $field_config );

            //handle ajaxbutton field callbacks
            add_action( $this->field_ajax_action_name(), array( $this, 'ajax_handle_ajaxbutton' ) );
        }

        /**
         * Override the data attributes
         * @return array
         */
        function data_attributes() {
            $data_attributes = parent::data_attributes();
            $data_attributes['data-nonce'] = $this->create_nonce();
            return $data_attributes;
        }

        /**
         * Render the ajax button field
         */
        function render_input( $override_attributes = false ) {

            /// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
            $button_text = isset( $this->config['button'] ) ? $this->config['button'] : __( 'Run', $this->container->manager->text_domain );

            $attributes = array(
                'id'    => $this->unique_id,
                'class' => isset( $this->config['button_class'] ) ? $this->config['button_class'] : 'button button-primary button-large',
                'href'  => '#' . $this->unique_id
            );

            $value = $this->value();
            if ( isset( $value ) ) {
                $attributes['data-value'] = $value;
            }

            self::render_html_tag( 'a', $attributes, $button_text );

            self::render_html_tag( 'span', array( 'class' => 'spinner' ) );

            self::render_html_tag( 'span', array( 'class' => 'response-message' ) );
        }

        /**
         * Ajax handler for ajaxbutton fields
         */
        function ajax_handle_ajaxbutton() {
            if ( $this->verify_nonce() ) {
                $this->container->do_action( 'ajaxbutton_' . $this->unique_id, $this );

                if ( isset( $this->config['callback'] ) ) {
                    if ( is_callable( $this->config['callback'] ) ) {
                        if ( false === call_user_func( $this->config['callback'], $this ) ) {
                            wp_send_json_error( array(
                                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
                                'message' => __( 'An unexpected error occurred!', $this->container->manager->text_domain )
                            ) );
                        }
                    }
                }
            }
        }
    }
}
