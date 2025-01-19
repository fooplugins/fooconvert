<?php

namespace FooPlugins\FooConvert\Admin;

/**
 * FooConvert Admin Promotion Class
 * Adds all the promotion related info.
 */

if ( !class_exists( __NAMESPACE__ . '\Promotions' ) ) {

    class Promotions {

        /**
         * Init constructor.
         */
        function __construct() {
            add_action( 'init', array( $this, 'init_promotions' ) );
        }

        /**
         * Initialises all promotions.
         *
         * @since 1.0.0
         */
        public function init_promotions() {

            // If hide_promos is enabled, do not show any promotions!
            if ( fooconvert_get_setting( 'hide_promos' ) === 'on' ) {
                return;
            }

            add_action( 'fooconvert_admin_dashboard_right', array( $this, 'render_addons_panel' ) );

            // Only show the promotion if the analytics addon is NOT active!
            if ( !fooconvert_is_analytics_addon_active() ) {
                add_action( 'fooconvert_widget_stats_html-metrics', array( $this, 'render_metrics' ), 10, 2 );
                add_filter( 'fooconvert_widget_metric_options', array( $this, 'adjust_widget_metric_options' ) );
            }
        }

        /**
         * Render the premium addon slider in the FooConvert admin dashboard
         *
         * @since 1.0.0
         */
        public function render_addons_panel() {
            $fs = fooconvert_fs();

            /**
             * @var \FS_Plugin[]
             */
            $addons = $fs->get_addons();

            $has_addons = ( is_array( $addons ) && 0 < count( $addons ) );

            if ( !$has_addons ) {
                return;
            }

            ?>
            <div class="fooconvert-panel">
                <div class="fooconvert-panel-section fooconvert-panel-header">
                    <h2><?php esc_html_e( 'Premium Addons', 'fooconvert' ); ?></h2>
                    <div class="fooconvert-slider-nav">
                        <button class="fooconvert-slider-prev button button-small"><span
                                    class="dashicons dashicons-arrow-left-alt2"></span></button>
                        <button class="fooconvert-slider-next button button-small"><span
                                    class="dashicons dashicons-arrow-right-alt2"></span></button>
                    </div>
                </div>
                <div class="fooconvert-slider">
                    <div class="fooconvert-slider-wrapper">


                        <?php

                        foreach ( $addons as $addon ) :

                            if ( is_null( $addon->info ) ) {
                                $addon->info = new \stdClass();
                            }

                            $addon_description = !empty( $addon->info->short_description ) ? $addon->info->short_description : '';
                            $add_features = [];

                            if ( !empty( $addon->info->selling_point_0 ) ) {
                                $add_features[] = $addon->info->selling_point_0;
                            }

                            if ( !empty( $addon->info->selling_point_1 ) ) {
                                $add_features[] = $addon->info->selling_point_1;
                            }

                            if ( !empty( $addon->info->selling_point_2 ) ) {
                                $add_features[] = $addon->info->selling_point_2;
                            }

                            ?>
                            <div class="fooconvert-slide">


                                <h3><?php echo esc_html( $addon->title ); ?></h3>

                                <p>
                                    <strong><?php echo esc_html( $addon_description ); ?></strong>
                                </p>
                                <?php if ( !empty( $add_features ) ) : ?>
                                    <ul>
                                        <?php foreach ( $add_features as $feature ) : ?>
                                            <li>
                                                <span class="dashicons dashicons-yes"></span>
                                                <?php echo esc_html( $feature ); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <p>
                                    <a href="<?php echo esc_url( fooconvert_admin_url_addons() ); ?>"
                                       class="button button-primary"
                                       target="_blank"><?php esc_html_e( 'Buy Now!', 'fooconvert' ); ?></a>
                                </p>

                            </div>

                        <?php endforeach;

                        ?>
                    </div>
                </div>
            </div>
            <?php
        }

        /**
         * Renders advanced PRO metrics for a widget.
         *
         * @param int $widget_id The ID of the widget.
         * @param array $widget The widget data.
         */
        public function render_metrics( $widget_id, $widget ) {
            ?>
            <div class="metric pro-feature">
                <p><?php esc_html( wp_rand( 0, 100 ) ); ?></p>
                <h2><?php esc_html_e( 'Total Clicks', 'fooconvert' ); ?></h2>
                <span data-balloon-pos="down"
                      aria-label="<?php esc_attr_e( 'Total number of clicks made within the widget.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php esc_html( wp_rand( 0, 100 ) ); ?>%</p>
                <h2><?php esc_html_e( 'Click Through Rate', 'fooconvert' ); ?></h2>
                <span data-balloon-pos="down"
                      aria-label="<?php esc_attr_e( 'Click Through Rate for the widget.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php esc_html( wp_rand( 0, 100 ) ); ?></p>
                <h2><?php esc_html_e( 'Total Conversions', 'fooconvert' ); ?></h2>
                <span data-balloon-pos="down"
                      aria-label="<?php esc_attr_e( 'Total number of conversions for the widget.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php esc_html( wp_rand( 0, 100 ) ); ?>%</p>
                <h2><?php esc_html_e( 'Conversion Rate', 'fooconvert' ); ?></h2>
                <span data-balloon-pos="down"
                      aria-label="<?php esc_attr_e( 'Conversion Rate for the widget.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php esc_html( wp_rand( 0, 100 ) ); ?>%</p>
                <h2><?php esc_html_e( 'Engagement Rate', 'fooconvert' ); ?></h2>
                <span data-balloon-pos="down"
                      aria-label="<?php esc_attr_e( 'Engagement Rate for the widget.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php esc_html( wp_rand( 0, 100 ) ); ?></p>
                <h2><?php esc_html_e( 'Positive Engagements', 'fooconvert' ); ?></h2>
                <span data-balloon-pos="down"
                      aria-label="<?php esc_attr_e( 'Total number of positive engagements for the widget (like clicks).', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php esc_html( wp_rand( 0, 100 ) ); ?></p>
                <h2><?php esc_html_e( 'Negative Engagements', 'fooconvert' ); ?></h2>
                <span data-balloon-pos="down"
                      aria-label="<?php esc_attr_e( 'Total number of negative engagements for the widget (like dismissals).', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p>Sentiment</p>
                <h2><?php esc_html_e( 'Overall Sentiment', 'fooconvert' ); ?></h2>
                <span data-balloon-pos="down"
                      aria-label="<?php esc_attr_e( 'Overall sentiment for the widget which ranges from very negative to very positive. It can also be unknown if there are no engagements, or neutral.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>

            <!-- PRO Info Panel -->
            <div class="pro-info-panel">
                <i class="dashicons dashicons-star-filled"></i>
                <p><?php esc_html_e( 'These advanced metrics are available in the PRO Analytics Addon.', 'fooconvert' ); ?>
                    <a href="<?php echo esc_url( fooconvert_admin_url_addons() ); ?>"><?php esc_html_e( 'Buy PRO Analytics!', 'fooconvert' ); ?></a>
                </p>
            </div>
            <?php
        }

        /**
         * Adjusts the widget metric options to include PRO features.
         *
         * @param array $options The current widget metric options.
         * @return array The updated widget metric options.
         */
        function adjust_widget_metric_options( $options ) {
            $options['engagement-rate'] = [
                'dropdown_option' => __( 'engagement rate (PRO)', 'fooconvert' ),
                'pro_feature' => true,
                'pro_message' => __( 'Top performers by engagement rate is only available in the PRO Analytics Addon.', 'fooconvert' )
            ];

            $options['clicks'] = [
                'dropdown_option' => __( 'clicks (PRO)', 'fooconvert' ),
                'pro_feature' => true,
                'pro_message' => __( 'Top performers by clicks is only available in the PRO Analytics Addon.', 'fooconvert' )
            ];

            $options['click-rate'] = [
                'dropdown_option' => __( 'click rate (PRO)', 'fooconvert' ),
                'pro_feature' => true,
                'pro_message' => __( 'Top performers by click rate is only available in the PRO Analytics Addon.', 'fooconvert' )
            ];

            $options['conversions'] = [
                'dropdown_option' => __( 'conversions (PRO)', 'fooconvert' ),
                'pro_feature' => true,
                'pro_message' => __( 'Top performers by conversions is only available in the PRO Analytics Addon.', 'fooconvert' )
            ];

            $options['conversion-rate'] = [
                'dropdown_option' => __( 'conversion rate (PRO)', 'fooconvert' ),
                'pro_feature' => true,
                'pro_message' => __( 'Top performers by conversion rate is only available in the PRO Analytics Addon.', 'fooconvert' )
            ];

            return $options;
        }
    }
}
