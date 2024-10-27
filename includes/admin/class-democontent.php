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

        function run() {
            // We need to make sure the CPT's are registered.
            $widgets = FooConvert::plugin()->widgets->get_instances();

            foreach ( $widgets as $widget ) {
                $widget->register_post_type();
            }

            // Check if demo content already exists
            $existing_posts = get_posts( [
                'post_status' => 'draft',
                'meta_key' => '_fooconvert_demo_content',
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

                $content_for_insert['meta_input'][] = [
                    '_fooconvert_demo_content' => '1' // Mark as demo content
                ];

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
                        '_fooconvert_display_rules_metafield' => [
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
'<!-- wp:fc/bar {"clientId":"","postId":||POST_ID||,"styles":{"color":{"background":"linear-gradient(90deg,rgba(6,147,227,1) 0%,rgb(155,81,224) 100%)","text":"#ffffff"},"border":{"radius":"21px","color":"#111111","style":"solid","width":"4px"},"dimensions":{"margin":"10px","padding":"0px","gap":"16px"}},"button":{"styles":{"dimensions":{"margin":"3px"},"color":{"icon":"#111111"},"border":{"radius":"0px","style":"none","width":"0px"}},"icon":{"size":"32px","close":{"slug":"wordpress-closeSmall","svg":"\u003csvg xmlns=\u0022http://www.w3.org/2000/svg\u0022 viewBox=\u00220 0 24 24\u0022 slot=\u0022button-icon\u0022 width=\u002232px\u0022 height=\u002232px\u0022 class=\u0022button-icon button-icon\u002d\u002dclose\u0022 aria-hidden=\u0022true\u0022\u003e\u003cpath d=\u0022M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z\u0022\u003e\u003c/path\u003e\u003c/svg\u003e"}}},"trigger":{"type":"timer","data":3},"transitions":true,"pagePush":true} -->
<!-- wp:fc/bar-button /-->
<!-- wp:fc/bar-content -->
<!-- wp:paragraph -->
<p><strong>Black Friday deals are finally here - LIMITED STOCK – act fast!</strong></p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#shop">Save 70%!</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:fc/bar-content -->
<!-- /wp:fc/bar -->'
                ],
                [
                    'post_title' => 'Cookie Consent Bar [Demo]',
                    'post_status' => 'draft',
                    'post_type' => 'fc-bar',
                    'meta_input' => [
                        '_fooconvert_display_rules_metafield' => [
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
                        '<!-- wp:fc/bar {"clientId":"","postId":||POST_ID||,"styles":{"color":{"background":"linear-gradient(90deg,rgb(0,166,166) 0%,rgb(41,17,81) 100%)","text":"#ffffff"},"border":{"radius":"0px","top":{"color":"#291151","style":"solid","width":"2px"},"right":{"width":"4px"},"bottom":{"width":"4px"},"left":{"width":"4px"}},"dimensions":{"margin":"0px","padding":"1px","gap":"16px"}},"button":{"styles":{"dimensions":{"margin":"3px"},"color":{"icon":"#111111"},"border":{"radius":"0px","style":"none","width":"0px"}},"icon":{"size":"32px","close":{"slug":"wordpress-closeSmall","svg":"\u003csvg xmlns=\u0022http =\u003e //www.w3.org/2000/svg\u0022 viewBox=\u00220 0 24 24\u0022 slot=\u0022button-icon\u0022 width=\u002232px\u0022 height=\u002232px\u0022 class=\u0022button-icon button-icon\u002d\u002dclose\u0022 aria-hidden=\u0022true\u0022\u003e\u003cpath d=\u0022M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z\u0022\u003e\u003c/path\u003e\u003c/svg\u003e"}}},"position":"bottom","trigger":{"type":"timer","data":5},"closeAnchor":"accept","hideButton":true,"transitions":true} -->
<!-- wp:fc/bar-button /-->
<!-- wp:fc/bar-content -->
<!-- wp:paragraph -->
<p>🍪 by continuing, you consent to our use of cookies</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","style":{"spacing":{"padding":{"top":"5px","bottom":"5px"}}}} -->
<div class="wp-block-button is-style-outline" id="accept"><a class="wp-block-button__link wp-element-button" href="#accept" style="padding-top:5px;padding-bottom:5px">Accept All</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
<!-- /wp:fc/bar-content -->
<!-- /wp:fc/bar -->'
                ],
            ];
        }
    }
}