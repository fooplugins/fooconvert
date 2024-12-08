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

        public function init_promotions() {

            // If hide_promos is enabled, do not show any promotions!
            if ( fooconvert_get_setting( 'hide_promos' ) === 'on' ) {
                return;
            }

            // Only show the promotion if the analytics addon is NOT active!
            if ( !fooconvert_is_analytics_addon_active() ) {
                add_action( 'fooconvert_widget_stats_html-metrics', array( $this, 'render_metrics' ), 10, 2 );
                add_filter( 'fooconvert_top_performers_sort_options', array( $this, 'adjust_top_performers_sort_options' ) );
            }
        }

        public function render_metrics( $widget_id, $widget ) {
            ?>
            <div class="metric pro-feature">
                <p><?php echo( rand( 0, 100 ) ); ?></p>
                <h2><?php _e('Total Clicks', 'fooconvert'); ?></h2>
                <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Total number of clicks made within the widget.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php echo( rand( 0, 100 ) ); ?>%</p>
                <h2><?php _e('Click Through Rate', 'fooconvert'); ?></h2>
                <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Click Through Rate for the widget.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php echo( rand( 0, 100 ) ); ?></p>
                <h2><?php _e('Total Conversions', 'fooconvert'); ?></h2>
                <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Total number of conversions for the widget.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php echo( rand( 0, 100 ) ); ?>%</p>
                <h2><?php _e('Conversion Rate', 'fooconvert'); ?></h2>
                <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Conversion Rate for the widget.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php echo( rand( 0, 100 ) ); ?>%</p>
                <h2><?php _e('Engagement Rate', 'fooconvert'); ?></h2>
                <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Engagement Rate for the widget.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php echo( rand( 0, 100 ) ); ?></p>
                <h2><?php _e('Positive Engagements', 'fooconvert'); ?></h2>
                <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Total number of positive engagements for the widget (like clicks).', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p><?php echo( rand( 0, 100 ) ); ?></p>
                <h2><?php _e('Negative Engagements', 'fooconvert'); ?></h2>
                <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Total number of negative engagements for the widget (like dismissals).', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>
            <div class="metric pro-feature">
                <p>Sentiment</p>
                <h2><?php _e('Overall Sentiment', 'fooconvert'); ?></h2>
                <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Overall sentiment for the widget which ranges from very negative to very positive. It can also be unknown if there are no engagements, or neutral.', 'fooconvert' ); ?>">
                    <i class="dashicons dashicons-editor-help"></i>
                </span>
            </div>

            <!-- PRO Info Panel -->
            <div class="pro-info-panel">
                <i class="dashicons dashicons-star-filled"></i>
                <p><?php _e('These advanced metrics are available in the PRO Analytics Addon.', 'fooconvert'); ?> <a href="<?php echo esc_url( fooconvert_admin_url_addons() ); ?>"><?php _e('Buy PRO Analytics!', 'fooconvert'); ?></a></p>
            </div>
<?php
        }

        function adjust_top_performers_sort_options( $options ) {
            $options['engagement-rate'] = [
                'dropdown_option' => __( 'engagement rate (PRO)', 'fooconvert' ),
                'pro_feature'     => true,
                'pro_message'     => __('Top performers by engagement rate is only available in the PRO Analytics Addon.', 'fooconvert')
            ];

            $options['clicks'] = [
                'dropdown_option' => __( 'clicks (PRO)', 'fooconvert' ),
                'pro_feature'     => true,
                'pro_message'     => __('Top performers by clicks is only available in the PRO Analytics Addon.', 'fooconvert')
            ];

            $options['click-rate'] = [
                'dropdown_option' => __( 'click rate (PRO)', 'fooconvert' ),
                'pro_feature'     => true,
                'pro_message'     => __('Top performers by click rate is only available in the PRO Analytics Addon.', 'fooconvert')
            ];

            $options['conversions'] = [
                'dropdown_option' => __( 'conversions (PRO)', 'fooconvert' ),
                'pro_feature'     => true,
                'pro_message'     => __('Top performers by conversions is only available in the PRO Analytics Addon.', 'fooconvert')
            ];

            $options['conversion-rate'] = [
                'dropdown_option' => __( 'conversion rate (PRO)', 'fooconvert' ),
                'pro_feature'     => true,
                'pro_message'     => __('Top performers by conversion rate is only available in the PRO Analytics Addon.', 'fooconvert')
            ];

            return $options;
        }
	}
}
