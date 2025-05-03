<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

use FooPlugins\FooConvert\Admin\FooFields\Container;
use WP_User;

if ( !class_exists( __NAMESPACE__ . '\Repeater' ) ) {

    class Repeater extends Field {

        protected $add_button_text;
        protected $no_data_message;
        protected $table_class;
        protected $fields = false;
        protected $show_header = true;

        /**
         * Field constructor.
         *
         * @param $container Container
         * @param $type string
         * @param $field_config array
         */
        function __construct( $container, $type, $field_config ) {
            parent::__construct( $container, $type, $field_config );

            // phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
            $this->add_button_text = isset( $field_config['add_button_text'] ) ? $field_config['add_button_text'] : __( 'Add', $container->manager->text_domain );
            $this->no_data_message = isset( $field_config['no_data_message'] ) ? $field_config['no_data_message'] : __( 'Nothing found', $container->manager->text_domain );
            // phpcs:enable
            $this->table_class = isset( $field_config['table_class'] ) ? $field_config['table_class'] : 'wp-list-table widefat striped';
            $this->show_header = isset( $field_config['show_header'] ) ? $field_config['show_header'] : true;
            if ( isset( $field_config['fields'] ) ) {
                $this->fields = $field_config['fields'];
            }
        }

        /**
         * Add classes to field div
         */
        function pre_render() {
            parent::pre_render();

            //check if there were any rows
            if ( !$this->has_rows() ) {
                $this->classes[] = 'foofields-empty';
            }
        }

        /**
         * Returns true if the repeater has data
         *
         * @return bool
         */
        function has_rows() {
            $value = $this->value();
            return is_array( $value ) && count( $value ) > 0;
        }

        /**
         * Render the repeater field
         *
         * @return mixed|void
         */
        function render_input( $override_attributes = false ) {
            if ( false === $this->fields ) {
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
                echo esc_html__( 'ERROR No fields for repeater!', $this->container->manager->text_domain );
                return;
            }

            $value = $this->value();

            $has_rows = is_array( $value ) && count( $value ) > 0;

            self::render_html_tag( 'p', array(
                'class' => 'foofields-repeater-no-data-message'
            ), $this->no_data_message );

            self::render_html_tag( 'table', array(
                'class' => $this->table_class
            ), null, false );

            if ( $this->show_header ) {

                //render the table column headers
                echo '<thead><tr>';
                self::render_html_tag( 'th', array() );
                foreach ( $this->fields as $child_field ) {
                    $column_attributes = array();
                    if ( isset( $child_field['width'] ) ) {
                        $column_attributes['width'] = $child_field['width'];
                    }
                    self::render_html_tag( 'th', $column_attributes, isset( $child_field['label'] ) ? $child_field['label'] : '' );
                }
                echo '</tr></thead>';
            }

            //render the repeater rows
            echo '<tbody>';
            if ( $has_rows ) {
                $row_index = 0;
                foreach ( $value as $row ) {
                    echo '<tr>';
                    //output the hidden metadata cell for the row
                    echo '<td>';
                    $this->render_row_metadata_fields( $row, $row_index );
                    echo '</td>';
                    foreach ( $this->fields as $child_field ) {
                        echo '<td>';
                        $this->render_row_field( $child_field, $row, $row_index );
                        echo '</td>';
                    }
                    echo '</tr>';
                    $row_index++;
                }
            }
            echo '</tbody>';

            //render the repeater footer for adding
            echo '<tfoot><tr>';

            //output the hidden metadata cell for the row
            echo '<td>';
            $this->render_row_metadata_fields();
            echo '</td>';

            foreach ( $this->fields as $child_field ) {
                echo '<td>';
                $this->render_row_field( $child_field );
                echo '</td>';
            }

            echo '</tr></tfoot>';
            echo '</table>';

            self::render_html_tag( 'button', array(
                'class' => 'button foofields-repeater-add'
            ), $this->add_button_text );
        }

        /**
         * Render all repeater row hidden metadata fields
         *
         * @param array $row_state
         * @param int $row_index
         */
        function render_row_metadata_fields( $row_state = array(), $row_index = -1 ) {
            $this->render_row_metadata_field( '__id', $row_state, $row_index );
            $this->render_row_metadata_field( '__created', $row_state, $row_index );
            $this->render_row_metadata_field( '__created_by', $row_state, $row_index );
            $this->render_row_metadata_field( '__updated', $row_state, $row_index );
            $this->render_row_metadata_field( '__updated_by', $row_state, $row_index );
        }

        /**
         * Render a repeater row hidden metadata field
         *
         * @param $metadata_id
         * @param array $row_state
         * @param int $row_index
         */
        function render_row_metadata_field( $metadata_id, $row_state = array(), $row_index = -1 ) {
            if ( isset( $row_state[$metadata_id] ) ) {
                self::render_html_tag( 'input', array(
                    'type'  => 'hidden',
                    'name'  => $this->row_field_name( $metadata_id, $row_index ),
                    'value' => $row_state[$metadata_id]
                ) );
            }
        }

        /**
         * Render a repeater row field
         * @param $field_config
         * @param array $row_state
         * @param int $row_index
         */
        function render_row_field( $field_config, $row_state = array(), $row_index = -1 ) {
            $in_footer = $row_index === -1;

            if ( array_key_exists( $field_config['id'], $row_state ) ) {
                $field_config['value'] = $row_state[$field_config['id']];
            }
            $field_id = $field_config['id'];
            $field_config['id'] = $this->id . '_' . $field_id . '_' . $row_index;
            $field_config['row_index'] = $row_index;
            $field_config['original_id'] = $field_config['data']['original_id'] = $field_id;
            $field_config['override_id_for_action_name'] = $this->container->get_unique_id( array( 'id' => $this->id . '_' . $field_id ) );

            $field_object = $this->container->create_field_instance( $field_config['type'], $field_config );
            if ( !$in_footer ) {
                $field_object->override_value_function = array( $this, 'get_repeater_field_value' );
            }
            $field_object->name = $this->row_field_name( $field_id, $row_index );
            $field_object->pre_render();
            $field_object->render( false, $in_footer ? array( 'disabled' => 'disabled' ) : false );
        }

        function row_field_name( $field_id, $row_index ) {
            return $this->name . '[' . $row_index . '][' . $field_id . ']';
        }

        /**
         * Override function for the fields within a repeater to ensure the correct value is fetched from the state
         *
         * @param $repeater_field_config
         *
         * @return mixed|string
         */
        function get_repeater_field_value( $repeater_field_config ) {
            $state = $this->value();

            $row_index = intval( $repeater_field_config['row_index'] );

            if ( array_key_exists( $row_index, $state ) ) {
                $row_state = $state[$row_index];

                if ( array_key_exists( $repeater_field_config['original_id'], $row_state ) ) {
                    return $row_state[$repeater_field_config['original_id']];
                }
            }

            return '';
        }

        /**
         * Gets the data posted for the repeater
         *
         * @param $sanitized_data
         *
         * @return array
         */
        function get_posted_value( $sanitized_data ) {
            $results = parent::get_posted_value( $sanitized_data );

            $current_username = 'unknown';
            $current_user = wp_get_current_user();
            if ( $current_user instanceof WP_User ) {
                $current_username = $current_user->user_login;
            }

            if ( is_array( $results ) ) {

                // stored some extra info for each row
                // check if each row has an __id field,
                //   if not then add one, so we can figure out which row to delete later.
                //   Also add a __created_by field and set to currently logged on user.
                //   And also a __created field which is the UTC timestamp of when the field was created
                // if the __id field exists, then we doing an update.
                //   update the __updated_by field and __updated timestamp fields
                foreach ( $results as &$result ) {
                    if ( !isset( $result['__id'] ) ) {
                        $result['__id'] = wp_generate_password( 10, false, false );
                        $result['__created'] = time();
                        $result['__created_by'] = $current_username;
                    } else {
                        $result['__updated'] = time();
                        $result['__updated_by'] = $current_username;
                    }
                }
            }

            return $results;
        }
    }
}
