<?php

namespace FooPlugins\FooConvert;

use WP_Post;

if ( !class_exists( __NAMESPACE__ . '\Event_Hooks' ) ) {

    /**
     * Class for all event related hooks.
     */
    class Event_Hooks {

        public function __construct() {
            add_filter( 'fooconvert_event_data', array( $this, 'adjust_event_data' ), 10, 2 );
            add_action( 'before_delete_post', array( $this, 'delete_widget_events' ) );
            add_action( 'post_updated', array( $this, 'store_post_update_event' ), 10, 3 );
        }

        /**
         * Delete all events associated with a given post.
         *
         * @param int $post_id The post id to delete events for.
         *
         * @since 1.0.0
         */
        public function delete_widget_events( $post_id ) {
            // Check post type if necessary
            $post_type = get_post_type( $post_id );
            if ( !fooconvert_is_valid_post_type( $post_type ) ) {
                return;
            }

            $event = new Event();
            $event->delete_widget_events( $post_id );
        }

        /**
         * Allows adjusting the event data before it is saved.
         *
         * @param array $data The event data to be saved.
         * @param string $post_type The type of post that the event is associated with.
         * @param string $template The name of the template that the event is associated with.
         *
         */
        function adjust_event_data( $data, $meta ) {
            // We want to override event_type in certain scenarios.
            // e.g. if a button is clicked in a bar, then event_type will be 'conversion'

            // We want to check events to determine if a subtype is needed.
            // e.g. if the event_type is 'click' then set the subtype to 'engagement';
            // e.g. if the event_type is 'open' and the visitor manually opened it, then set the subtype to 'engagement';

            // We also want to determine sentiment for certain events.
            // e.g. if the event_type is 'close' then check how quickly it was closed to determine a negative sentiment;
            // e.g. if the event_type is 'open' and the visitor manually opened it, then set positive sentiment;

            if ( empty( $meta ) ) {
                $meta = [];
            }

            // Post type and template not used at the moment.
            //$post_type = isset( $meta['post_type'] ) ? $meta['post_type'] : null;
            //$template = isset( $meta['template'] ) ? $meta['template'] : null;

            $event_type = isset( $data['event_type'] ) ? $data['event_type'] : null;
            $extra_data = isset( $data['extra_data'] ) ? $data['extra_data'] : [];

            $conversion = false;

            // Check clicks
            switch ( $event_type ) {
                case FOOCONVERT_EVENT_TYPE_CLICK:

                    // Any click is considered a positive engagement.
                    $data['event_subtype'] = FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT;
                    $data['sentiment'] = 1; // positive sentiment.

                    // check for conversions.
                    $tag_name = isset( $extra_data['tagName'] ) ? $extra_data['tagName'] : null;
                    // button or link clicks are considered conversions.
                    if ( $tag_name === 'a' || $tag_name === 'button' ) {
                        $conversion = true;
                    }

                    break;
                case FOOCONVERT_EVENT_TYPE_OPEN:
                    $trigger = isset( $extra_data['trigger'] ) ? $extra_data['trigger'] : null;

                    // A manual open is considered a positive engagement.
                    if ( $trigger === 'open-button' ) {
                        $data['event_subtype'] = FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT;
                        $data['sentiment'] = 1; // positive sentiment.
                    }

                    break;
                case FOOCONVERT_EVENT_TYPE_CLOSE:
                    $data['event_subtype'] = FOOCONVERT_EVENT_SUBTYPE_ENGAGEMENT;

                    $duration = isset( $extra_data['duration'] ) ? intval( $extra_data['duration'] ) : 0;

                    // A close after 5 seconds is considered neutral.
                    if ( $duration > apply_filters( 'fooconvert_close_sentiment_positive', 5000 ) ) {
                        $data['sentiment'] = null; // neutral sentiment.
                    } else {
                        $data['sentiment'] = 0; // negative sentiment.
                    }

                    break;
            }

            if ( $conversion ) {
                $data['conversion'] = 1;
            }

            return $data;
        }

        /**
         * Store an event when a post is updated
         *
         * @param int $post_id The post ID
         * @param WP_Post $post_after Post object following the update
         * @param WP_Post $post_before Post object before the update
         */
        public function store_post_update_event( $post_id, $post_after, $post_before ) {
            // Verify post type
            if ( !fooconvert_is_valid_post_type( $post_after->post_type ) ) {
                return;
            }

            $extra_data = [];
            $sentiment = null; // Default to null sentiment (neutral).

            // Check for status changes.
            if ( $post_before->post_status !== $post_after->post_status ) {
                $change = [
                    'has_value' => false
                ];
                if ( $post_after->post_status === 'publish' ) {
                    $change['reason'] = __( 'Published', 'fooconvert' );
                    $sentiment = 1; // Post was published = positive sentiment.
                } elseif ( $post_before->post_status === 'publish' ) {
                    $change['reason'] = __( 'Un-published', 'fooconvert' );
                    $sentiment = 0; // Post was un-published = negative sentiment.
                } else {
                    $change['reason'] = __( 'Status Updated', 'fooconvert' );
                    $change['has_value'] = true;
                    $change['value'] = $post_after->post_status;
                }
                
                $extra_data['changes'][] = $change;
            }

            $block_before = $this->extract_block_from_content( $post_before->post_content );
            $block_after = $this->extract_block_from_content( $post_after->post_content );

            if ( !is_null( $block_before ) && !is_null( $block_after ) ) {

                $content_before = maybe_serialize( $block_before['innerBlocks'] );
                $content_after = maybe_serialize( $block_after['innerBlocks'] );

                // Check for content changes.
                if ( $content_before !== $content_after ) {
                    $change = [
                        'reason' => __( 'Content Updated', 'fooconvert' ),
                        'has_value' => false
                    ];
                    $extra_data['changes'][] = $change;
                }

                $attributes_before = !empty( $block_before['attrs'] ) ? $block_before['attrs'] : [];
                $attributes_after = !empty( $block_after['attrs'] ) ? $block_after['attrs'] : [];

                $settings_before = !empty( $attributes_before['settings'] ) ? $attributes_before['settings'] : [];
                $settings_after = !empty( $attributes_after['settings'] ) ? $attributes_after['settings'] : [];

                $trigger_before = !empty( $settings_before['trigger'] ) ? $settings_before['trigger'] : [];
                $trigger_after = !empty( $settings_after['trigger'] ) ? $settings_after['trigger'] : [];

                if ( !empty( $trigger_before ) && !empty( $trigger_after )
                    && $trigger_before['type'] !== $trigger_after['type'] ) {
                    $change = [
                        'reason' => __( 'Open Trigger Changed', 'fooconvert' ),
                        'has_value' => true,
                        'value' => $trigger_after['type']
                    ];
                    $extra_data['changes'][] = $change;
                }

                $content_before = !empty( $attributes_before['content'] ) ? $attributes_before['content'] : [];
                $content_after = !empty( $attributes_after['content'] ) ? $attributes_after['content'] : [];

                $styles_before = maybe_serialize( !empty( $content_before['styles'] ) ? $content_before['styles'] : [] );
                $styles_after = maybe_serialize( !empty( $content_after['styles'] ) ? $content_after['styles'] : [] );

                if ( $styles_before !== $styles_after ) {
                    $change = [
                        'reason' => __( 'Styling Updated', 'fooconvert' ),
                        'has_value' => false
                    ];
                    $extra_data['changes'][] = $change;
                }
            }

            $extra_data['sentiment'] = $sentiment;

            $event = new Event();
            $event->create(
                [
                    'widget_id' => $post_id,
                    'event_type' => FOOCONVERT_EVENT_TYPE_UPDATE,
                    'extra_data' => $extra_data,
                ],
                [
                    'post_type' => $post_after->post_type
                ]
            );
        }

        private function extract_block_from_content( $post_content ) {
            // Parse the blocks in the post content
            $blocks = parse_blocks( $post_content );

            foreach ( $blocks as $block ) {
                // Check if the block type matches
                if ( isset( $block['blockName'] ) && strpos( $block['blockName'], 'fc/' ) === 0 && isset( $block['attrs'] ) ) {
                    return $block;
                }
            }

            // Return null if nothing is found
            return null;
        }
    }
}