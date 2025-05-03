<?php

namespace FooPlugins\FooConvert\Admin\FooFields\Fields;

if ( !class_exists( __NAMESPACE__ . '\SelectizeMulti' ) ) {

    class SelectizeMulti extends Field {

        protected $selected_values = null;

        function __construct( $container, $type, $field_config ) {
            parent::__construct( $container, $type, $field_config );

            $hook = $this->container->build_hook_tag( 'aftersavepostmeta' );

            //save taxonomy mappings for selectize-multi fields
            add_action( $hook, array( $this, 'save_taxonomy_mapping' ), 10, 2 );

            //handle ajax selectize multiple add
            add_action( $this->field_ajax_action_name(), array( $this, 'ajax_handle_selectize_multi_add' ) );
        }

        /**
         * Render the selectize multi field
         *
         * @param $override_attributes
         */
        function render_input( $override_attributes = false ) {
            $inner = '<option value=""></option>';

            $choices = array();

            if ( isset( $this->config['choices'] ) ) {
                $choices = $this->config['choices'];
            } else if ( $this->is_bound_to_taxonomy() ) {
                $taxonomy = $this->config['binding']['taxonomy'];

                //get all the terms
                $terms = get_terms( array(
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                ) );

                $hierarchical = is_taxonomy_hierarchical( $taxonomy );
                $choices = array();

                foreach ( $terms as $term ) {
                    $name = $term->name;
                    if ( $hierarchical ) {
                        $name = $this->calculate_term_hierarchy_name( $term->term_id, $terms );
                    }
                    $choices[] = array(
                        'value'   => $term->term_id,
                        'display' => $name
                    );
                }
            } else if ( $this->is_bound_to_post() ) {
                $post_type = $this->config['binding']['post_type'];

                $posts = get_posts( array(
                    'numberposts' => 100,
                    'post_status' => 'any',
                    'post_type'   => $post_type
                ) );

                foreach ( $posts as $post ) {
                    $choices[] = array(
                        'value'   => $post->ID,
                        'display' => $post->post_title
                    );
                }
            }

            //build up options
            foreach ( $choices as $choice ) {
                $selected = in_array( $choice['value'], $this->get_selected_values() ) ? ' selected="selected"' : '';
                $inner .= '<option value="' . esc_attr( $choice['value'] ) . '"' . $selected . '>' . esc_html( $choice['display'] ) . '</option>';
            }

            self::render_html_tag( 'select', array(
                'id'   => $this->unique_id,
                'name' => $this->name . '[]'
            ), $inner, true, false );
        }

        /**
         * Calculates the term name for a hierarchical taxonomy
         *
         * @param $term_id
         * @param $terms
         *
         * @return string
         */
        function calculate_term_hierarchy_name( $term_id, $terms ) {
            $the_term = null;
            foreach ( $terms as $term ) {
                if ( $term->term_id === $term_id ) {
                    $the_term = $term;
                    break;
                }
            }

            if ( isset( $the_term ) ) {
                $parent_id = intval( $term->parent );

                if ( $parent_id > 0 ) {
                    return $this->calculate_term_hierarchy_name( $parent_id, $terms ) . ' / ' . $the_term->name;
                } else {
                    return $the_term->name;
                }
            }

            return '';
        }

        function get_selected_values() {
            if ( isset( $this->selected_values ) ) {
                return $this->selected_values;
            }

            global $post;

            $field_value = $this->value();

            $this->selected_values = is_array( $field_value ) ? $field_value : array();

            if ( $this->is_bound_to_taxonomy() ) {

                $taxonomy = $this->config['binding']['taxonomy'];

                //get related terms for the current post (if there is one)
                if ( isset( $post ) &&
                    count( $this->selected_values ) === 0 &&
                    isset( $this->config['binding']['sync_with_post'] ) &&
                    $this->config['binding']['sync_with_post'] ) {

                    $related_terms = get_the_terms( $post, $taxonomy );

                    if ( $related_terms !== false && !is_wp_error( $related_terms ) ) {
                        $this->selected_values = wp_list_pluck( $related_terms, 'term_id' );
                    }
                }
            }

            return $this->selected_values;
        }

        function data_attributes() {
            $data_attributes = parent::data_attributes();

            $data_attributes['nonce'] = $this->create_nonce();
            $data_attributes['action'] = $this->field_action_name();
            $data_attributes['create'] = isset( $this->config['create'] ) ? $this->config['create'] : false;

            $selectize_options = array(
                'items'            => $this->get_selected_values(),
                'closeAfterSelect' => isset( $this->config['close_after_select'] ) ? $this->config['close_after_select'] : true
            );

            if ( isset( $this->config['max_items'] ) ) {
                $selectize_options['maxItems'] = $this->config['max_items'];
            }

            if ( isset( $this->placeholder ) ) {
                $selectize_options['placeholder'] = $this->placeholder;
            }

            $data_attributes['selectize'] = $selectize_options;

            return $data_attributes;
        }

        /**
         * Save the taxonomy mappings for the post
         *
         * @param $post_id
         * @param $state
         */
        function save_taxonomy_mapping( $post_id, $state ) {

            if ( $this->is_bound_to_taxonomy_and_synced() ) {

                $taxonomy = $this->config['binding']['taxonomy'];

                $value = isset( $state[$this->config['id']] ) ? $state[$this->config['id']] : null;

                if ( empty( $value ) ) {

                    //remove all relationships between the object and any terms in a particular taxonomy
                    wp_delete_object_term_relationships( $post_id, $taxonomy );

                } else {
                    $result = wp_set_object_terms( $post_id, array_map( 'intval', $value ), $taxonomy, false );

                    if ( is_wp_error( $result ) ) {
                        //$state = $this->metabox_field_group->set_field_error( $state, $this->config, sprintf( __('Could not save mappings for taxonomy : %s' ), $taxonomy ) );
                        //TODO : the above just sets the error, we need to save the error state back to the post_meta
                    }
                }
            }
        }

        function is_bound_to_taxonomy() {
            return isset( $this->config['binding'] ) &&
                isset( $this->config['binding']['type'] ) &&
                $this->config['binding']['type'] === 'taxonomy' &&
                isset( $this->config['binding']['taxonomy'] );
        }

        function is_bound_to_taxonomy_and_synced() {
            return $this->is_bound_to_taxonomy() &&
                isset( $this->config['binding']['sync_with_post'] ) &&
                $this->config['binding']['sync_with_post'];
        }

        function is_bound_to_post() {
            return isset( $this->config['binding'] ) &&
                isset( $this->config['binding']['type'] ) &&
                $this->config['binding']['type'] === 'post' &&
                isset( $this->config['binding']['post_type'] );
        }

        /**
         * Ajax handler for Selectize Multi fields add
         */
        function ajax_handle_selectize_multi_add() {
            if ( $this->verify_nonce() ) {

                $thing_to_add = $this->sanitize_text( 'add' );

                if ( !empty( $thing_to_add ) ) {

                    if ( isset( $this->config['callback'] ) ) {
                        if ( is_callable( $this->config['callback'] ) ) {
                            call_user_func( $this->config['callback'], $this );
                        }
                    }

                    if ( $this->is_bound_to_taxonomy() ) {

                        $taxonomy = $this->config['binding']['taxonomy'];

                        $new_term = wp_insert_term( $thing_to_add, $taxonomy );

                        if ( !is_wp_error( $new_term ) ) {

                            wp_send_json( array(
                                'new' => array(
                                    'value'   => $new_term['term_id'],
                                    'display' => $thing_to_add
                                )
                            ) );
                        }
                    } else if ( $this->is_bound_to_post() ) {
                        $post_type = $this->config['binding']['post_type'];

                        $post = array(
                            'post_title'  => $thing_to_add,
                            'post_status' => 'draft',
                            'post_type'   => $post_type
                        );

                        $post_id = wp_insert_post( $post );

                        if ( !is_wp_error( $post_id ) ) {

                            wp_send_json( array(
                                'new' => array(
                                    'value'   => $post_id,
                                    'display' => $thing_to_add
                                )
                            ) );
                        }
                    }
                }
            }

            die();
        }
    }
}
