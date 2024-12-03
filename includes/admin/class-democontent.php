<?php
namespace FooPlugins\FooConvert\Admin;

use FooPlugins\FooConvert\Event;
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

        /**
         * Cleans up old demo content.
         *
         * This function will delete any posts of the given post types
         * that have the meta key set to the given value.
         *
         * @param array $widget_post_types The post types to search for.
         * @param string $meta_key The meta key to search for.
         *
         * @return void
         */
        function cleanup_old_demo_content( $widget_post_types, $meta_key ) {
            // Check if old demo content already exists
            $old_demo_content = get_posts( [
                'meta_key' => $meta_key,
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

        /**
         * Deletes all demo content.
         *
         * This function will delete all demo content created by the `create` method.
         *
         * @since 1.0.0
         */
        function delete() {
            $this->cleanup_old_demo_content( $this->register_and_get_widget_post_types(), FOOCONVERT_META_KEY_DEMO_CONTENT );
        }

        /**
         * Create demo content for the plugin.
         *
         * This function will create demo content for the plugin, unless
         * demo content already exists. If $force is set to true, it will
         * delete any existing demo content, and then create the demo content.
         *
         * @param bool $force If set to true, will delete existing demo content.
         * @return int The number of demo content created.
         */
        function create( $force = false ) {
            $widget_post_types = $this->register_and_get_widget_post_types();

            if ( $force === true ) {
                // Cleanup old demo content
                $this->cleanup_old_demo_content( $widget_post_types, FOOCONVERT_META_KEY_DEMO_CONTENT );
            }

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
                return 0;
            }

            $count = 0;
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

                $meta = [
                    'post_type' => $content_for_insert['post_type'],
                    'template' => $content_for_insert['template']
                ];

                // Create some events for the demo content.
                $this->create_events( $post_id, $meta, mt_rand( 500, 1000 ) );

                $count++;
            }

            return $count;
        }


        /**
         * Creates demo event data for the widget.
         *
         * @param $widget_id
         * @return void
         */
        function create_events( $widget_id, $meta, $num_events = 1000 ) {
            $event = new Event();

            // Define event types and probabilities (more positive events)
            $event_types = [
                FOOCONVERT_EVENT_TYPE_OPEN => 0.7,        // 70% chance of 'view'
                FOOCONVERT_EVENT_TYPE_CLICK => 0.2,       // 20% chance of 'click'
                FOOCONVERT_EVENT_TYPE_CLOSE => 0.1        // 10% chance of 'dismiss'
            ];

            // Generate event data
            for ( $i = 0; $i < $num_events; $i++ ) {
                // Randomly pick an event type based on probabilities
                $event_type = $this->weighted_random_event( $event_types );

                $conversion = null;

                if ( $event_type === FOOCONVERT_EVENT_TYPE_CLICK ) {
                    // not every click is a conversion for demo data.
                    $conversion = mt_rand( 0, 1 );
                }

                // TODO : figure out subtype based off event_type.
                $event_subtype = null;

                // TODO : figure out sentiment, based off event_type.
                $sentiment = null;

                // Random timestamp within the last 30 days
                $timestamp = date('Y-m-d H:i:s', strtotime("-" . mt_rand(0, 30) . " days -" . mt_rand(0, 86400) . " seconds"));

                // Randomly select either a user_id or an anonymous_user_guid
                if (mt_rand(0, 1) === 1) {
                    $user_id = mt_rand(1, 10);  // Random user ID for logged-in users
                    $anonymous_user_guid = null;
                } else {
                    $user_id = 0;
                    $anonymous_user_guid = bin2hex( random_bytes( 32 ) );  // Generate random GUID for anonymous users
                }

                // Random device type
                $device_types = ['desktop', 'mobile', 'tablet'];
                $device_type = $device_types[array_rand( $device_types )];

                // Deal with extra data.
                $extra_data = [];
                if ( $conversion === 1 ) {
                    $extra_data = [
                        'conversion_type' => 'woocommerce_order',
                        'order_id' => mt_rand( 1, 100 ),
                        'order_value' => mt_rand( 100 * 100, 500 * 100 ) / 100
                    ];
                }

                // Insert the generated event into the database
                $event->create(
                    [
                        'widget_id' => $widget_id,
                        'event_type' => $event_type,
                        'event_subtype' => $event_subtype,
                        'conversion' => $conversion,
                        'sentiment' => $sentiment,
                        'page_url' => home_url( '/page-' . mt_rand(1, 10) ),
                        'device_type' => $device_type,
                        'user_id' => $user_id,
                        'anonymous_user_guid' => $anonymous_user_guid,
                        'extra_data' => $extra_data,
                        'timestamp' => $timestamp
                    ],
                    $meta
                );
            }
        }

        // Helper function to select an event type based on weighted probabilities
        private function weighted_random_event($weights) {
            $rand = mt_rand() / mt_getrandmax();
            $cumulative = 0;

            foreach ($weights as $event => $weight) {
                $cumulative += $weight;
                if ($rand < $cumulative) {
                    return $event;
                }
            }
            return FOOCONVERT_EVENT_TYPE_OPEN;  // Fallback (shouldn’t happen if weights add up to 1)
        }

        function get_demo_content() {
            return [
                [
                    'post_title' => 'Black Friday Bar [Demo]',
                    'post_status' => 'draft',
                    'post_type' => 'fc-bar',
                    'template' => 'black_friday_bar',
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
'<!-- wp:fc/bar {"postId":||POST_ID||,"template":"black_friday_bar","settings":{"trigger":{"type":"timer","data":3},"transitions":true},"openButton":{"settings":{"hidden":true}},"closeButton":{"settings":{"icon":{"slug":"default__close-small","size":"48px"}}},"content":{"styles":{"color":{"background":"linear-gradient(135deg,rgb(6,147,227) 0%,rgb(157,85,225) 100%)"},"border":{"radius":"18px","color":"#111111","style":"solid","width":"3px"},"dimensions":{"margin":"5px","padding":"3px","gap":"16px"}}}} -->
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
                    'template' => 'cookie_consent_bar',
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
                        '<!-- wp:fc/bar {"postId":||POST_ID||,"template":"cookie_consent_bar","styles":{"dimensions":{"padding":"0px"}},"settings":{"position":"bottom","transitions":true,"trigger":{"type":"immediate"},"closeAnchor":"accept"},"openButton":{"settings":{"hidden":true}},"closeButton":{"settings":{"hidden":true}},"content":{"styles":{"color":{"background":"#76736e","text":"#ffffff"},"border":{"radius":"0px","style":"none","width":"0px"},"dimensions":{"margin":"0px","gap":"16px","padding":"0px"}}}} -->
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

        /**
         * @param array $widget_post_types
         * @return array
         */
        public function register_and_get_widget_post_types()
        {
            // We need to make sure the CPT's are registered.
            $widgets = FooConvert::plugin()->widgets->get_instances();
            foreach ($widgets as $widget) {
                $post_type = $widget->get_post_type();
                $widget_post_types[] = $post_type;
                if (!post_type_exists($post_type)) {
                    $widget->register_post_type();
                }
            }
            return $widget_post_types;
        }
    }
}