<?php

namespace FooPlugins\FooConvert;

if ( !class_exists( __NAMESPACE__ . '\Stats' ) ) {

    /**
     * Class for stats related functions.
     */
    class Stats {
        /**
         * Values that should not be treated as meaningful top performer scores.
         *
         * @var array<int,mixed>
         */
        private $ignored_metric_values = [ 0, '0', '0%', 'NA', 'N/A', null ];

        /**
         * Fetches and updates the stats for all popups with events.
         *
         * The metrics are stored in post meta for each popup. The keys of the post meta
         * are set to the 'meta_key' value of the item in the `fooconvert_popup_metric_options`
         * array. The value is the result of calling the `function` value of the item on the
         * value of the metric from the stats array.
         *
         * If the metric is not found in the stats array, the post meta is deleted.
         *
         * The time of the last update is stored in the `FOOCONVERT_OPTION_STATS_LAST_UPDATED` option.
         */
        public function update() {
            $event = new Event();

            // Find all popups with events.
            $all_popup_metrics = $event->get_all_popup_metrics();

            foreach ( $all_popup_metrics as $metrics ) {
                $post_id = intval( $metrics['post_id'] );

                foreach ( fooconvert_popup_metric_options() as $key => $option ) {
                    $metric_value = 0;
                    if ( array_key_exists( 'metric', $option ) ) {
                        if ( isset( $metrics[$option['metric']] ) ) {
                            $metric_value = $metrics[$option['metric']];
                        } elseif ( isset( $option['function'] ) && is_callable( $option['function'] ) ) {
                            $metric_value = call_user_func( $option['function'], $metric_value );
                        }
                    } else {
                        if ( isset( $option['function'] ) && is_callable( $option['function'] ) ) {
                            $metric_value = call_user_func( $option['function'], $metrics );
                        }
                    }

                    if ( !in_array( $metric_value, $this->ignored_metric_values ) ) {
                        update_post_meta( $post_id, $option['meta_key'], $metric_value );
                    } else {
                        delete_post_meta( $post_id, $option['meta_key'] );
                    }
                }
            }

            // Find all popups with no events.
            $popups_with_no_events = $event->get_all_popups_with_no_events();

            // Delete all post meta for the popups with no events, so the stats are correct.
            // This is done to cater for popups whose events have been deleted, older than the retention period, etc.
            foreach ( $popups_with_no_events as $post_id ) {
                foreach ( fooconvert_popup_metric_options() as $key => $option ) {
                    delete_post_meta( $post_id, $option['meta_key'] );
                }
            }

            update_option( FOOCONVERT_OPTION_STATS_LAST_UPDATED, time() );
        }

        /**
         * Returns an array of the top performing popups for a given sort type.
         *
         * @param string $sort The type of sort to perform. Must be a key in the fooconvert_popup_metric_options() array.
         * @param int $limit The number of top performers to return. Defaults to 10.
         *
         * @return array An array of top performers, each containing the popup ID, title, and score.
         */
        public function get_top_performers( $sort, $limit = 10 ) {
            $sort_options = fooconvert_popup_metric_options();
            if ( array_key_exists( $sort, $sort_options ) === false ) {
                return [];
            }

            $sort_option = $sort_options[$sort];

            // We do not want to return anything for pro features.
            if ( isset( $sort_option['pro_feature'] ) && $sort_option['pro_feature'] ) {
                return [];
            }

            // Check if we have a meta key.
            if ( !isset( $sort_option['meta_key'] ) ) {
                return [];
            }

            $query = new \WP_Query( [
                'post_type'      => FOOCONVERT_CPT_POPUP,
                'posts_per_page' => $limit,
                'meta_key'       => $sort_option['meta_key'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
            ] );

            $top_performers = $this->build_top_performers_from_posts( $query->posts, $sort_option['meta_key'] );
            if ( !empty( $top_performers ) ) {
                return $top_performers;
            }

            if ( isset( $sort_option['metric'] ) && $sort_option['metric'] === 'total_sales' ) {
                $top_performers = $this->get_top_performers_from_sales_rows( Data\Query::get_sales_totals_by_popup( $limit ) );
                if ( !empty( $top_performers ) ) {
                    return $top_performers;
                }
            }

            return $this->get_top_performers_from_metrics( $sort_option, $limit );
        }

        /**
         * Builds a top performers payload from posts ordered by cached metric values.
         *
         * @param array<int,\WP_Post> $posts Matching posts ordered by cached metric meta.
         * @param string              $meta_key Post meta key storing the cached score.
         * @return array<int,array<string,mixed>>
         */
        private function build_top_performers_from_posts( array $posts, $meta_key ) {
            $top_performers = [];
            $index = 1;

            foreach ( $posts as $post ) {
                if ( !$post instanceof \WP_Post ) {
                    continue;
                }

                $top_performers[$index] = [
                    'id'        => $post->ID,
                    'title'     => fooconvert_get_popup_title( $post ),
                    'post_type' => fooconvert_get_popup_type( $post ),
                    'score'     => get_post_meta( $post->ID, $meta_key, true ),
                ];

                $index++;
            }

            return $top_performers;
        }

        /**
         * Falls back to raw event metrics when cached popup meta is unavailable.
         *
         * @param array<string,mixed> $sort_option Metric option definition.
         * @param int                 $limit Number of performers to return.
         * @return array<int,array<string,mixed>>
         */
        private function get_top_performers_from_metrics( array $sort_option, $limit ) {
            $event = new Event();
            $all_popup_metrics = $event->get_all_popup_metrics();

            if ( empty( $all_popup_metrics ) ) {
                return [];
            }

            $ranked_popups = [];

            foreach ( $all_popup_metrics as $metrics ) {
                if ( !is_array( $metrics ) || !isset( $metrics['post_id'] ) ) {
                    continue;
                }

                $score = $this->resolve_metric_score( $metrics, $sort_option, false );
                if ( !$this->has_meaningful_metric_value( $score ) ) {
                    continue;
                }

                $post = get_post( intval( $metrics['post_id'] ) );
                if ( !$post instanceof \WP_Post || $post->post_type !== FOOCONVERT_CPT_POPUP ) {
                    continue;
                }

                $ranked_popups[] = [
                    'id'         => $post->ID,
                    'title'      => fooconvert_get_popup_title( $post ),
                    'post_type'  => fooconvert_get_popup_type( $post ),
                    'score'      => $score,
                    'sort_score' => $this->normalize_metric_score( $this->resolve_metric_score( $metrics, $sort_option, true ) ),
                ];
            }

            if ( empty( $ranked_popups ) ) {
                return [];
            }

            usort( $ranked_popups, function( $left, $right ) {
                if ( $left['sort_score'] === $right['sort_score'] ) {
                    return 0;
                }

                return $left['sort_score'] < $right['sort_score'] ? 1 : -1;
            } );

            $ranked_popups = array_slice( $ranked_popups, 0, max( 1, intval( $limit ) ) );

            $top_performers = [];
            $index = 1;
            foreach ( $ranked_popups as $popup ) {
                unset( $popup['sort_score'] );
                $top_performers[$index] = $popup;
                $index++;
            }

            return $top_performers;
        }

        /**
         * Builds a top performers payload from raw sales totals.
         *
         * @param array<int,array<string,mixed>> $sales_rows Sales totals grouped by popup.
         * @return array<int,array<string,mixed>>
         */
        private function get_top_performers_from_sales_rows( array $sales_rows ) {
            $top_performers = [];
            $index = 1;

            foreach ( $sales_rows as $row ) {
                if ( !is_array( $row ) || !isset( $row['post_id'] ) || !isset( $row['total_sales'] ) ) {
                    continue;
                }

                if ( !$this->has_meaningful_metric_value( $row['total_sales'] ) ) {
                    continue;
                }

                $post = get_post( intval( $row['post_id'] ) );
                if ( !$post instanceof \WP_Post || $post->post_type !== FOOCONVERT_CPT_POPUP ) {
                    continue;
                }

                $top_performers[$index] = [
                    'id'        => $post->ID,
                    'title'     => fooconvert_get_popup_title( $post ),
                    'post_type' => fooconvert_get_popup_type( $post ),
                    'score'     => $row['total_sales'],
                ];

                $index++;
            }

            return $top_performers;
        }

        /**
         * Resolves a popup metric score from the provided metric row and metric option.
         *
         * @param array<string,mixed> $metrics Metric row keyed by metric names.
         * @param array<string,mixed> $sort_option Metric option definition.
         * @param bool                $normalize Whether to apply the metric normalization callback.
         * @return mixed
         */
        private function resolve_metric_score( array $metrics, array $sort_option, $normalize = false ) {
            if ( isset( $sort_option['metric'] ) ) {
                $metric_key = $sort_option['metric'];
                if ( !array_key_exists( $metric_key, $metrics ) ) {
                    return null;
                }

                $score = $metrics[$metric_key];
                if ( $normalize && isset( $sort_option['function'] ) && is_callable( $sort_option['function'] ) ) {
                    return call_user_func( $sort_option['function'], $score );
                }

                return $score;
            }

            if ( isset( $sort_option['function'] ) && is_callable( $sort_option['function'] ) ) {
                return call_user_func( $sort_option['function'], $metrics );
            }

            return null;
        }

        /**
         * Determines whether a metric score should be included in top performer results.
         *
         * @param mixed $score Raw metric score.
         * @return bool
         */
        private function has_meaningful_metric_value( $score ) {
            if ( in_array( $score, $this->ignored_metric_values ) ) {
                return false;
            }

            if ( is_string( $score ) && trim( $score ) === '' ) {
                return false;
            }

            return true;
        }

        /**
         * Normalizes a metric score into a numeric value that can be used for sorting.
         *
         * @param mixed $score Raw or normalized score.
         * @return float
         */
        private function normalize_metric_score( $score ) {
            if ( is_numeric( $score ) ) {
                return (float) $score;
            }

            if ( is_string( $score ) ) {
                return (float) fooconvert_percentage_to_float( $score );
            }

            return 0.0;
        }
    }
}
