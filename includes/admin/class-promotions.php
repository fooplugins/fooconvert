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
            // Only show the promotion if the analytics addon is NOT active!
            if ( !fooconvert_is_analytics_addon_active() ) {
                add_action( 'fooconvert_widget_stats_html-metrics', array( $this, 'render_metrics' ), 10, 2 );
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
	}
}
