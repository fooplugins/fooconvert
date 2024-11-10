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
        <div class="metric">
            <h2><?php _e('Total Views', 'fooconvert'); ?></h2>
            <p id="total-views"><?php _e('Loading...', 'fooconvert'); ?></p>
        </div>
        <div class="metric">
            <h2><?php _e('Total Clicks', 'fooconvert'); ?></h2>
            <p id="total-clicks"><?php _e('Loading...', 'fooconvert'); ?></p>
        </div>
        <div class="metric">
            <h2><?php _e('Click-Through Rate (CTR)', 'fooconvert'); ?></h2>
            <p id="click-through-rate"><?php _e('Loading...', 'fooconvert'); ?></p>
        </div>
    </div>

    <!-- Recent Activity Chart -->
    <div class="fooconvert-chart-container">
        <h2><?php _e('Recent Activity (7-Day)', 'fooconvert'); ?></h2>
        <canvas id="lineChart"></canvas>
    </div>

    <!-- Future Metrics
    <div class="fooconvert-pro-metrics">
        <div class="metric">
            <h2><?php _e('Conversion Rate', 'fooconvert'); ?></h2>
            <p id="conversion-rate"><?php _e('Loading...', 'fooconvert'); ?></p>
        </div>
        <div class="metric">
            <h2><?php _e('Geographic Breakdown', 'fooconvert'); ?></h2>
            <p id="geo-breakdown"><?php _e('Loading...', 'fooconvert'); ?></p>
        </div>
        <div class="metric">
            <h2><?php _e('Device & Browser Analytics', 'fooconvert'); ?></h2>
            <p id="device-browser-analytics"><?php _e('Loading...', 'fooconvert'); ?></p>
        </div>
    </div>

    <div class="fooconvert-chart-container">
        <h2><?php _e('Conversion Rate Breakdown', 'fooconvert'); ?></h2>
        <canvas id="pieChart"></canvas>
    </div>
    <div class="fooconvert-chart-container">
        <h2><?php _e('Detailed Engagement Trends', 'fooconvert'); ?></h2>
        <canvas id="engagementTrendChart"></canvas>
    </div>

    -->
</div>
