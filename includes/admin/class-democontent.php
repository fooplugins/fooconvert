<?php
namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\FooConvert;

/**
 * FooConvert Admin DemoContent Class
 * Runs all classes that need to run in the admin
 */

if ( !class_exists( 'FooPlugins\FooConvert\Admin\DemoContent' ) ) {

    class DemoContent
    {
        /**
         * Init constructor.
         */
        function __construct()
        {

        }

        function cleanup_old_demo_content( $widget_post_types ) {
            // Check if old demo content already exists
            $old_demo_content = get_posts( [
                'meta_key' => FOOCONVERT_META_KEY_DEMO_CONTENT_V1,
                'meta_value' => '1',
                'post_type' => $widget_post_types,
                'post_status' => 'any',
                'numberposts' => -1
            ] );

            if ( !empty( $old_demo_content ) ) {
                // Old demo content exists; Delete it all!
                foreach ( $old_demo_content as $post ) {
                    wp_delete_post( $post->ID, true );
                }
            }
        }

        function run() {
            $widget_post_types = [];

            // We need to make sure the CPT's are registered.
            $widgets = FooConvert::plugin()->widgets->get_instances();
            foreach ( $widgets as $widget ) {
                $widget_post_types[] = $widget->get_post_type();
                $widget->register_post_type();
            }

            // Cleanup old demo content
            $this->cleanup_old_demo_content( $widget_post_types );

            // Check if demo content already exists
            $existing_posts = get_posts( [
                'meta_key' => FOOCONVERT_META_KEY_DEMO_CONTENT,
                'post_type' => $widget_post_types,
                'post_status' => 'any',
                'meta_value' => '1',
                'posts_per_page' => 1,
            ] );

            if ( !empty( $existing_posts ) ) {
                // Demo content already exists; do nothing.
                return;
            }

            foreach ( $this->get_demo_content() as $content) {
                $content_for_insert = $content;
                $post_content = $content['post_content'];

                // Remove the post content from the content array, as we will update it later.
                unset( $content_for_insert['post_content'] );

                if ( !array_key_exists( 'meta_input', $content_for_insert ) ) {
                    $content_for_insert['meta_input'] = [];
                }

                $content_for_insert['meta_input'][FOOCONVERT_META_KEY_DEMO_CONTENT] = '1'; // Mark as demo content

                // We first need to insert the post, and get back a post ID
                $post_id = wp_insert_post( $content_for_insert );

                if ( is_wp_error( $post_id ) ) {
                    continue;
                }

                $post_content = str_replace( '||POST_ID||', $post_id, $post_content );

                wp_update_post( array(
                    'ID' => $post_id,
                    'post_content' => $post_content
                ) );
            }
        }

        function get_demo_content() {
            return [
                [
                    'post_title' => 'Black Friday Bar [Demo]',
                    'post_status' => 'draft',
                    'post_type' => 'fc-bar',
                    'meta_input' => [
                        FOOCONVERT_META_KEY_DISPLAY_RULES => [
                            'location' => [
                                [
                                    'type' => 'general:front_page',
                                    'data' => []
                                ]
                            ],
                            'exclude' => [],
                            'users' => [ 'general:all_users' ]
                        ]
                    ],
                    'post_content' =>
'<!-- wp:fc/bar {"postId":||POST_ID||,"settings":{"trigger":{"type":"timer","data":3},"transitions":true},"openButton":{"settings":{"hidden":true}},"closeButton":{"settings":{"icon":{"slug":"default__close-small","size":"48px"}}},"content":{"styles":{"color":{"background":"linear-gradient(135deg,rgb(6,147,227) 0%,rgb(157,85,225) 100%)"},"border":{"radius":"18px","color":"#111111","style":"solid","width":"3px"},"dimensions":{"margin":"5px","padding":"3px","gap":"16px"}}}} -->
<!-- wp:fc/bar-open-button /-->

<!-- wp:fc/bar-container -->
<!-- wp:fc/bar-close-button /-->

<!-- wp:fc/bar-content -->
<!-- wp:paragraph -->
<p><strong>🔥Black Friday deals are finally here - LIMITED STOCK - act fast!</strong>⚡</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"style":{"border":{"radius":"54px"}}} -->
<div class="wp-block-button" id="cta"><a class="wp-block-button__link wp-element-button" href="/shop" style="border-radius:54px">Save 70%</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:fc/bar-content -->
<!-- /wp:fc/bar-container -->
<!-- /wp:fc/bar -->'
                ],
                [
                    'post_title' => 'Cookie Consent Bar [Demo]',
                    'post_status' => 'draft',
                    'post_type' => 'fc-bar',
                    'meta_input' => [
                        FOOCONVERT_META_KEY_DISPLAY_RULES => [
                            'location' => [
                                [
                                    'type' => 'general:entire_site',
                                    'data' => []
                                ]
                            ],
                            'exclude' => [],
                            'users' => [ 'general:all_users' ]
                        ]
                    ],
                    'post_content' =>
                        '<!-- wp:fc/bar {"postId":||POST_ID||,"styles":{"dimensions":{"padding":"0px"}},"settings":{"position":"bottom","transitions":true,"trigger":{"type":"immediate"},"closeAnchor":"accept"},"openButton":{"settings":{"hidden":true}},"closeButton":{"settings":{"hidden":true}},"content":{"styles":{"color":{"background":"#76736e","text":"#ffffff"},"border":{"radius":"0px","style":"none","width":"0px"},"dimensions":{"margin":"0px","gap":"16px","padding":"0px"}}}} -->
<!-- wp:fc/bar-open-button /-->

<!-- wp:fc/bar-container -->
<!-- wp:fc/bar-close-button /-->

<!-- wp:fc/bar-content -->
<!-- wp:paragraph -->
<p>🍪 by continuing, you consent to our use of cookies</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"border":{"width":"2px"},"spacing":{"padding":{"top":"3px","bottom":"3px"}}},"fontSize":"small"} -->
<div class="wp-block-button has-custom-font-size is-style-outline has-small-font-size" id="accept"><a class="wp-block-button__link wp-element-button" style="border-width:2px;padding-top:3px;padding-bottom:3px">Accept</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:fc/bar-content -->
<!-- /wp:fc/bar-container -->
<!-- /wp:fc/bar -->'
                ],
            ];
        }
    }
}