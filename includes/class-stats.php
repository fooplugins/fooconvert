<?php

namespace FooPlugins\FooConvert;

if ( ! class_exists( __NAMESPACE__ . '\Stats' ) ) {

    /**
     * Class for stats related functions.
     */
    class Stats
    {

        /**
         * Fetches and updates the stats for all widgets with events.
         *
         * The metrics are stored in post meta for each widget. The keys of the post meta
         * are set to the 'meta_key' value of the item in the `fooconvert_top_performers_sort_options`
         * array. The value is the result of calling the `function` value of the item on the
         * value of the metric from the stats array.
         *
         * If the metric is not found in the stats array, the post meta is deleted.
         *
         * The time of the last update is stored in the `FOOCONVERT_OPTION_STATS_LAST_UPDATED` option.
         */
        public function update()
        {
            $event = new Event();

            // Find all widgets with events.
            $widgets = $event->get_all_widgets_with_events();

            foreach ( $widgets as $widget_id ) {
                // Get stats for the widget.
                // Passing in force will force a fetch and also store the metrics in post meta.
                $metrics = $event->get_widget_metrics( $widget_id, true );

                if ( is_array( $metrics ) ) {
                    foreach ( fooconvert_top_performers_sort_options() as $key => $option ) {
                        $metric_value = 0;
                        if ( isset( $metrics[ $option['metric'] ] ) ) {
                            $metric_value = $metrics[ $option['metric'] ];
                            if ( isset( $option['function'] ) && is_callable( $option['function'] ) ) {
                                $metric_value = call_user_func( $option['function'], $metric_value );
                            }
                        }
                        if ( $metric_value > 0 ) {
                            update_post_meta( $widget_id, $option['meta_key'], $metric_value );
                        } else {
                            delete_post_meta( $widget_id, $option['meta_key'] );
                        }
                    }
                }
            }

            update_option( FOOCONVERT_OPTION_STATS_LAST_UPDATED, time() );
        }

        /**
         * Returns an array of the top performing widgets for a given sort type.
         *
         * @param string $sort The type of sort to perform. Must be a key in the fooconvert_top_performers_sort_options() array.
         * @param int $limit The number of top performers to return. Defaults to 10.
         *
         * @return array An array of top performers, each containing the widget ID, title, and score.
         */
        public function get_top_performers( $sort, $limit = 10 ) {
            $sort_options = fooconvert_top_performers_sort_options();
            if ( array_key_exists( $sort, $sort_options ) === false ) {
                return [];
            }

            $sort_option = $sort_options[ $sort ];

            // We do not want to return anything for pro features.
            if ( isset( $sort_option['pro_feature'] ) && $sort_option['pro_feature'] ) {
                return [];
            }

            // Check if we have a meta key.
            if ( !isset( $sort_option['meta_key'] ) ) {
                return [];
            }

            $query = new \WP_Query( [
                'post_type'      => fooconvert_get_post_types(),
                'posts_per_page' => $limit,
                'meta_key'       => $sort_option['meta_key'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
            ] );

            $top_performers = [];
            $index = 1;
            foreach ( $query->posts as $post ) {
                $top_performers[$index] = [
                    'id' => $post->ID,
                    'title' => fooconvert_get_widget_title( $post ),
                    'score' => get_post_meta( $post->ID, $sort_option['meta_key'], true ),
                ];

                $index++;
            }

            return $top_performers;
        }
    }
}