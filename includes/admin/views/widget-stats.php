<?php
// Get the widget ID from the URL
$widget_id = isset( $_GET['widget_id'] ) ? intval( $_GET['widget_id'] ) : 0;
$widget_name = __( 'Unknown', 'fooconvert' );

if ( $widget_id ) {
    $widget = get_post( $widget_id );
    if ( $widget ) {
        $widget_name = $widget->post_title;
    }
}
?>

<div class="fooconvert-stats-container" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
    <h1><?php echo sprintf( esc_html__( 'Stats for Widget #%d', 'fooconvert' ), esc_html( $widget_id ) ); ?></h1>
    <h2><?php _e('Widget Title : ', 'fooconvert'); ?> <?php echo esc_html( $widget_name ); ?></h2>

    <!-- Basic Metrics -->
    <div class="fooconvert-basic-metrics">
        <div class="metric loading">
            <p id="metric-total_events">...</p>
            <h2><?php _e('Total Events', 'fooconvert'); ?></h2>
            <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Total events logged for the widget for it\'s lifetime.', 'fooconvert' ); ?>">
                <i class="dashicons dashicons-editor-help"></i>
            </span>
        </div>
        <div class="metric loading">
            <p id="metric-total_views">...</p>
            <h2><?php _e('Total Views', 'fooconvert'); ?></h2>
            <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Total number of times the widget has been viewed by a visitor.', 'fooconvert' ); ?>">
                <i class="dashicons dashicons-editor-help"></i>
            </span>
        </div>
        <div class="metric loading">
            <p id="metric-total_unique_visitors">...</p>
            <h2><?php _e('Total Visitors', 'fooconvert'); ?></h2>
            <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Total number of unique visitors that have viewed the widget.', 'fooconvert' ); ?>">
                <i class="dashicons dashicons-editor-help"></i>
            </span>
        </div>
        <div class="metric loading">
            <p id="metric-total_engagements">...</p>
            <h2><?php _e('Total Engagements', 'fooconvert'); ?></h2>
            <span data-balloon-pos="down" aria-label="<?php esc_attr_e( 'Total number of engagements that have been made with the widget (clicks, opens, etc).', 'fooconvert' ); ?>">
                <i class="dashicons dashicons-editor-help"></i>
            </span>
        </div>
        <?php do_action( 'fooconvert_widget_stats_html-metrics', $widget_id, $widget ); ?>
    </div>

    <!-- Recent Activity Chart -->
    <div class="fooconvert-chart-container loading">
        <h2><?php _e('Recent Activity (7-Day)', 'fooconvert'); ?></h2>
        <canvas id="lineChart"></canvas>
    </div>

</div>
