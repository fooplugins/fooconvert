<?php

namespace FooPlugins\FooConvert;

if ( ! class_exists( __NAMESPACE__ . '\Event_Hooks' ) ) {

    /**
     * Class for all event related hooks.
     */
    class Event_Hooks {

        public function __construct() {
            add_filter( 'fooconvert_event_data', array( $this, 'adjust_event_data' ), 10, 2 );
            add_action('before_delete_post', array( $this, 'delete_widget_events' ) );
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
            if ( !fooconvert_is_valid_post_type($post_type ) ) {
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


    }

}