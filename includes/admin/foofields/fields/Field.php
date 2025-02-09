<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

use FooPlugins\FooConvert\Admin\FooFields\Base;
use FooPlugins\FooConvert\Admin\FooFields\Container;

if ( !class_exists( __NAMESPACE__ . '\Field' ) ) {

    class Field extends Base {

        /**
         * The container object that will house the field
         * @var Container
         */
        public $container;

        /**
         * The field type
         * @var string
         */
        public $type;

        /**
         * An array of the field config / options
         * @var array
         */
        public $config;

        /**
         * An internal id for the field
         * @var string
         */
        public $id;

        /**
         * A unique identifier for the field within a container
         * @var string
         */
        public $unique_id;

        /**
         * The field input name
         * @var string
         */
        public $name;

        /**
         * The field layout
         * @var string
         */
        public $layout;

        /**
         * The field classes
         * @var string
         */
        public $classes;

        public $error = false;

        public $label;

        public $description;

        public $required;

        public $tooltip;

        public $choices;

        public $groups;

        public $default;

        public $placeholder;

        public $override_value_function;

        /**
         * The parent_id for the current field, usually the id of the tab
         *
         * @var null
         */
        public $parent_id = null;

        /**
         * Field constructor.
         *
         * @param $container Container
         * @param $type string
         * @param $field_config array
         */
        function __construct( $container, $type, $field_config ) {
            $this->container = $container;
            $this->type = $type;
            $this->config = $field_config;
            if ( !isset( $field_config['id'] ) ) {
                $field_config['id'] = 'unknown';
            }
            $this->id = $field_config['id'];
            $this->unique_id = $container->get_unique_id( $field_config );
            $this->name = $container->get_field_name( $field_config );
            $this->layout = isset( $field_config['layout'] ) ? $field_config['layout'] : 'auto';
            $this->label = isset( $field_config['label'] ) ? $field_config['label'] : null;
            $this->description = isset( $field_config['desc'] ) ? $field_config['desc'] : null;
            $this->required = isset( $field_config['required'] ) ? $field_config['required'] : null;
            $this->tooltip = isset( $field_config['tooltip'] ) ? $field_config['tooltip'] : null;
            $this->choices = isset( $field_config['choices'] ) ? $field_config['choices'] : array();
            $this->groups = isset( $field_config['groups'] ) ? $field_config['groups'] : array();
            $this->default = isset( $field_config['default'] ) ? $field_config['default'] : null;
            $this->placeholder = isset( $field_config['placeholder'] ) ? $field_config['placeholder'] : null;

            $this->classes[] = 'foofields-field';
            if ( isset( $field_config['class'] ) ) {
                $this->classes = array_merge( $this->classes, explode( ' ', $field_config['class'] ) );
            }
        }

        /**
         * Returns the data attributes for the field
         *
         * @return array|mixed
         */
        function data_attributes() {
            if ( isset( $this->config['data'] ) && is_array( $this->config['data'] ) ) {
                return $this->config['data'];
            }

            return array();
        }

        /**
         * Makes any changes we need for a field before a field is rendered
         */
        function pre_render() {
            //check if there were any processing errors
            $this->get_error_from_state();

            if ( $this->error !== false ) {
                $this->classes[] = 'foofields-error';
            }

            $this->classes[] = "foofields-type-{$this->type}";
            $this->classes[] = "foofields-layout-{$this->layout}";

            if ( !$this->visible() ) {
                $this->classes[] = 'foofields-hidden';
            }
        }

        /**
         * function for rendering the entire field
         *
         * @param bool $render_label
         * @param bool $override_attributes
         */
        function render( $render_label = true, $override_attributes = false ) {
            $field_attributes = array(
                'id'    => $this->unique_id . '-field',
                'class' => implode( ' ', $this->classes )
            );
            $data_attributes = $this->data_attributes();

            if ( count( $data_attributes ) > 0 ) {
                $field_attributes = array_merge( $field_attributes, $this->container->process_data_attributes( $data_attributes ) );
            }

            self::render_html_tag( 'div', $field_attributes, null, false );

            if ( $render_label ) {
                $this->render_label();
            }
            $this->render_input_container( $override_attributes );

            echo '</div>';
        }

        /**
         * Render a field label
         */
        function render_label() {
            if ( isset( $this->label ) ) {
                echo '<div class="foofields-label">';
                $label = $this->label;
                if ( isset( $this->required ) && $this->required === true ) {
                    $label .= ' *';
                }
                self::render_html_tag( 'label', array( 'for' => $this->unique_id ), $label, false );
                $this->render_tooltip();
                echo '</label>';
                echo '</div>';
            }
        }

        /**
         * Render a tooltip
         */
        function render_tooltip() {
            if ( isset( $this->tooltip ) ) {
                $icon = 'dashicons-editor-help';
                $tooltip_attributes = $this->get_tooltip_attributes( $this->tooltip );
                if ( is_array( $this->tooltip ) ) {
                    if ( isset( $this->tooltip['icon'] ) ) {
                        $icon = $this->tooltip['icon'];
                    }
                }
                self::render_html_tag( 'span', $tooltip_attributes, null, false );
                self::render_html_tag( 'i', array( 'class' => 'dashicons ' . $icon ) );
                echo '</span>';
            }
        }

        /**
         * Get data attributes for a tooltip from some tooltip config
         *
         * @param $tooltip_config
         * @param string $default_position
         * @param string $default_length
         *
         * @return array
         */
        function get_tooltip_attributes( $tooltip_config, $default_position = 'down', $default_length = 'medium' ) {
            $tooltip = $tooltip_config;
            $position = $default_position;
            $length = $default_length;

            if ( is_array( $tooltip_config ) ) {
                if ( isset( $tooltip_config['length'] ) ) {
                    $length = $tooltip_config['length'];
                }
                if ( isset( $tooltip_config['position'] ) ) {
                    $position = $tooltip_config['position'];
                }
                if ( isset( $tooltip_config['text'] ) ) {
                    $tooltip = $tooltip_config['text'];
                }
            }

            return array(
                'data-balloon-length' => $length,
                'data-balloon-pos'    => $position,
                'data-balloon'        => $tooltip
            );
        }

        /**
         * Render the field input container
         *
         * @param bool $override_attributes
         */
        function render_input_container( $override_attributes = false ) {
            self::render_html_tag( 'div', array( 'class' => 'foofields-field-input' ), null, false );

            if ( isset( $this->config['before_input_render'] ) && is_callable( $this->config['before_input_render'] ) ) {
                call_user_func( $this->config['before_input_render'], $this );
            }

            $this->render_input( $override_attributes );

            if ( isset( $this->config['after_input_render'] ) && is_callable( $this->config['after_input_render'] ) ) {
                call_user_func( $this->config['after_input_render'], $this );
            }

            $this->render_description();

            echo '</div>';
        }

        /**
         * Render the description after a field
         */
        function render_description() {
            if ( !empty( $this->description ) ) {
                self::render_html_tag( 'span', array(
                    'class' => 'foofields-field-description'
                ), $this->description, true, false );
            }
        }

        /**
         * Render the field input
         *
         * @param bool $override_attributes
         */
        function render_input( $override_attributes = false ) {
            $field_value = $this->value();

            $attributes = array(
                'id'   => $this->unique_id,
                'name' => $this->name
            );

            if ( $override_attributes !== false ) {
                $attributes = wp_parse_args( $override_attributes, $attributes );
            }

            switch ( $this->type ) {

                case 'html':
                    if ( isset( $this->config['html'] ) ) {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $this->config['html'];
                    }
                    break;

                case 'select':
                    self::render_html_tag( 'select', $attributes, null, false );
                    $this->render_select_options( $field_value );
                    echo '</select>';

                    break;

                case 'text':
                    if ( isset( $this->config['placeholder'] ) ) {
                        $attributes['placeholder'] = $this->config['placeholder'];
                    }
                    $attributes['type'] = 'text';
                    $attributes['value'] = $field_value;
                    self::render_html_tag( 'input', $attributes );

                    break;

                case 'number':
                    if ( isset( $this->config['placeholder'] ) ) {
                        $attributes['placeholder'] = $this->config['placeholder'];
                    }
                    $attributes['min'] = isset( $this->config['min'] ) ? $this->config['min'] : 0;
                    if ( isset( $this->config['max'] ) ) {
                        $attributes['max'] = $this->config['max'];
                    }
                    $attributes['step'] = isset( $this->config['step'] ) ? $this->config['step'] : 1;
                    $attributes['type'] = 'number';
                    $attributes['value'] = $field_value;
                    self::render_html_tag( 'input', $attributes );

                    break;

                case 'date':
                    if ( isset( $this->config['placeholder'] ) ) {
                        $attributes['placeholder'] = $this->config['placeholder'];
                    }
                    $attributes['type'] = 'date';
                    $attributes['min'] = isset( $this->config['min'] ) ? $this->config['min'] : '1970-01-01';
                    $attributes['max'] = isset( $this->config['max'] ) ? $this->config['max'] : '';
                    $attributes['value'] = $field_value;
                    self::render_html_tag( 'input', $attributes );

                    break;

                case 'color':
                    $attributes['type'] = 'color';
                    if ( !empty( $field_value ) ) {
                        // Check for empty otherwise Chrome reports the following warning when the value attribute is empty:
                        // The specified value "" does not conform to the required format.  The format is "#rrggbb" where rr, gg, bb are two-digit hexadecimal numbers.
                        $attributes['value'] = $field_value;
                    }
                    self::render_html_tag( 'input', $attributes );

                    break;

                case 'colorpicker':
                    $attributes['type'] = 'text';
                    $attributes['value'] = $field_value;
                    if ( isset( $this->config['alpha'] ) ) {
                        $attributes['data-alpha-enabled'] = 'true';
                    }
                    if ( isset( $this->config['default'] ) ) {
                        $attributes['data-default-color'] = $this->config['default'];
                    }
                    self::render_html_tag( 'input', $attributes );

                    break;

                case 'checkbox':
                    if ( 'on' === $field_value ) {
                        $attributes['checked'] = 'checked';
                    }
                    $attributes['value'] = 'on';
                    $attributes['type'] = 'checkbox';
                    self::render_html_tag( 'input', $attributes );
                    break;


                case 'readonly':
                    $attributes['type'] = 'hidden';

                    self::render_html_tag( 'input', $attributes );

                    $inner = $field_value;

                    if ( isset( $this->config['render'] ) && is_callable( $this->config['render'] ) ) {
                        call_user_func( $this->config['render'], $this );
                    } else {
                        self::render_html_tag( 'span', array(), $inner );
                    }

                    break;

                default:
                    //the field type is not natively supported
                    if ( isset( $this->config['render'] ) && is_callable( $this->config['render'] ) ) {
                        call_user_func( $this->config['render'], $this );
                    }
                    break;
            }
        }

        /**
         * Gets the value of the field from the container state
         * @return mixed|string
         */
        function value() {
            $return_value = null;

            if ( isset( $this->override_value_function ) && is_callable( $this->override_value_function ) ) {
                $return_value = call_user_func( $this->override_value_function, $this->config );
            } else {
                $return_value = $this->container->get_state_value( $this->config );
            }

            if ( isset( $this->config['value_decoder'] ) && is_callable( $this->config['value_decoder'] ) ) {
                $return_value = call_user_func( $this->config['value_decoder'], $return_value );
            }

            return $return_value;
        }

        /**
         * verify the nonce for a ajax callback
         *
         * @return bool
         */
        protected function verify_nonce() {
            $nonce = $this->sanitize_key( 'nonce' );

            if ( null !== $nonce ) {
                return wp_verify_nonce( $nonce, $this->unique_id_for_action() );
            }
            return false;
        }

        /**
         * Create a nonce for the field
         *
         * @return false|string
         */
        protected function create_nonce() {
            return wp_create_nonce( $this->unique_id_for_action() );
        }

        /**
         * Get the value of the field from an array of posted data
         * @param $sanitized_form_data
         *
         * @return mixed
         */
        public function get_posted_value( $sanitized_form_data ) {
            $return_value = null;
            if ( isset( $sanitized_form_data ) && is_array( $sanitized_form_data ) ) {

                if ( !array_key_exists( $this->id, $sanitized_form_data ) ) {
                    //the field had no posted value, check for a default
                    if ( isset( $this->default ) ) {
                        $return_value = $this->default;
                    }
                } else {
                    $return_value = $this->process_posted_value( $sanitized_form_data[$this->id] );
                }
            }

            if ( isset( $this->config['value_encoder'] ) && is_callable( $this->config['value_encoder'] ) ) {
                $return_value = call_user_func( $this->config['value_encoder'], $return_value );
            }

            return $return_value;
        }

        /**
         * Process a value for the field, including sanitization
         *
         * @param $unsanitized_value
         *
         * @return array|string
         */
        public function process_posted_value( $unsanitized_value ) {
            return $this->clean( $unsanitized_value );
        }

        /**
         * Returns the key used to store data for this field
         * @return string
         */
        public function field_data_key() {
            return $this->id;
        }

        /**
         * Validates the field based on a value
         *
         * @param $posted_value
         *
         * @return bool
         */
        public function validate( $posted_value ) {

            //check for required fields
            if ( isset( $this->required ) ) {
                // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
                $text_domain = $this->container->manager->text_domain;
                if ( true === $this->required && empty( $posted_value ) ) {
                    // Translators: %s is the field label.
                    $this->error = sprintf( __( '%s is required!', $text_domain ), $this->label );
                } else if ( is_array( $this->required ) ) {
                    //check for more advanced required rules
                    if ( isset( $this->required['minimum'] ) && intval( $this->required['minimum'] ) > 0 ) {
                        // Translators: %1$d is the minimum number, %2$s is the field label.
                        $message = isset( $this->required['message'] ) ? $this->required['message'] : __( 'Please select a minimum of %1$d item(s) for %2$s!', $text_domain );
                        if ( !isset( $posted_value ) || ( is_array( $posted_value ) && count( $posted_value ) < intval( $this->required['minimum'] ) ) ) {
                            $this->error = sprintf( $message, intval( $this->required['minimum'] ), $this->label );
                        }
                    } else if ( isset( $this->required['maximum'] ) && intval( $this->required['maximum'] ) > 0 ) {
                        // Translators: %1$d is the maximum number, %2$s is the field label.
                        $message = isset( $this->required['message'] ) ? $this->required['message'] : __( 'Please select a maximum of %1$d item(s) for %2$s!', $text_domain );
                        if ( is_array( $posted_value ) && count( $posted_value ) > intval( $this->required['maximum'] ) ) {
                            $this->error = sprintf( $message, intval( $this->required['maximum'] ), $this->label );
                        }
                    } else if ( isset( $this->required['exact'] ) && intval( $this->required['exact'] ) > 0 ) {
                        // Translators: %1$d is the exact number, %2$s is the field label.
                        $message = isset( $this->required['message'] ) ? $this->required['message'] : __( 'Please select exactly %1$d item(s) for %2$s!', $text_domain );
                        if ( !isset( $posted_value ) || ( is_array( $posted_value ) && count( $posted_value ) !== intval( $this->required['exact'] ) ) ) {
                            $this->error = sprintf( $message, intval( $this->required['exact'] ), $this->label );
                        }
                    } else if ( isset( $this->required['validation_function'] ) ) {
                        $validation_result = call_user_func( $this->required['validation_function'], $posted_value, $this );
                        if ( $validation_result === false ) {
                            // Translators: %s is the field label.
                            $message = isset( $this->required['message'] ) ? $this->required['message'] : __( 'Custom validation failed for %s!', $text_domain );
                            $this->error = sprintf( $message, $this->label );
                        }
                    }
                }
                // phpcs:enable
            }

            return $this->error === false;
        }

        /**
         * Get the error message from the state if it exists
         */
        function get_error_from_state() {
            $state = $this->container->get_state();

            if ( isset( $state['__errors'] ) && array_key_exists( $this->field_data_key(), $state['__errors'] ) ) {
                $this->error = $state['__errors'][$this->field_data_key()]['message'];
            }
        }

        /**
         * Builds up an ajax action for the field
         *
         * @return string
         */
        function field_ajax_action_name() {
            return 'wp_ajax_' . $this->field_action_name();
        }

        /**
         * Builds up the action name used in ajax calls
         *
         * @return string
         */
        function field_action_name() {
            return 'foofields_' . $this->unique_id_for_action() . '-field';
        }

        /**
         * Returns the unique id that will be used for ajax actions
         * @return mixed|string
         */
        function unique_id_for_action() {
            $unique_id = $this->unique_id;

            if ( isset( $this->config['override_id_for_action_name'] ) ) {
                $unique_id = $this->config['override_id_for_action_name'];
            }

            return $unique_id;
        }

        /**
         *
         * @return bool
         */
        function visible() {
            return $this->container->show_rule_is_visible( $this->unique_id, 'fields' );
        }

        /**
         * Renders the select options and optgroups if they are provided
         *
         * @param $field_value
         * @param null $options
         */
        protected function render_select_options( $field_value, $options = null ) {
            if ( isset( $options ) && is_array( $options ) ) {
                foreach ( $options as $value => $option ) {
                    $option_attributes = array(
                        'value' => $value
                    );
                    if ( $field_value == $value ) {
                        $option_attributes['selected'] = 'selected';
                    }
                    $label = $option;
                    if ( is_array( $option ) ) {
                        if ( isset( $option['label'] ) ) {
                            $label = $option['label'];
                        }
                        if ( isset( $option['data'] ) ) {
                            $option_attributes += $this->container->process_data_attributes( $option['data'] );
                        }
                    }
                    self::render_html_tag( 'option', $option_attributes, $label );
                }
            } else if ( count( $this->groups ) > 0 ) {
                foreach ( $this->groups as $group ) {
                    self::render_html_tag( 'optgroup', array( 'label' => $group['label'] ), null, false );
                    $options = isset( $group['choices'] ) ? $group['choices'] : false;
                    if ( $options === false ) {
                        $options = isset( $group['options'] ) ? $group['options'] : array();
                    }

                    $this->render_select_options( $field_value, $options );

                    echo '</optgroup>';
                }
            } else if ( count( $this->choices ) > 0 ) {
                $this->render_select_options( $field_value, $this->choices );
            }
        }
    }
}
