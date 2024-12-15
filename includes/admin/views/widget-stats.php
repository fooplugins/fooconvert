<?php
// Get the widget ID from the URL
$widget_id = isset( $_GET['widget_id'] ) ? intval( $_GET['widget_id'] ) : 0;
$widget_title = __( 'Unknown', 'fooconvert' );
$recent_activity_days = intval( get_option( FOOCONVERT_OPTION_RECENT_ACTIVITY_DAYS, FOOCONVERT_RETENTION_DEFAULT ) );
$recent_activity_options = apply_filters( 'fooconvert_widget_stats_recent_activity_options', [ FOOCONVERT_RETENTION_DEFAULT => __( 'Last 7 days', 'fooconvert' ) ] );
$edit_link = '';

if ( $widget_id ) {
    $widget = get_post( $widget_id );
    if ( $widget ) {
        $widget_title = fooconvert_get_widget_title( $widget );
        $edit_url = fooconvert_admin_url_widget_edit( $widget_id );
        $widget_type = fooconvert_get_widget_post_type_label( $widget );
        $edit_link = '<a class="button" href="' . esc_url( $edit_url ) . '">' . esc_html( sprintf( __( 'Edit %s', 'fooconvert' ), $widget_type ) ) . '</a>';
    }
} else {
    // Redirect to the widget list page if the widget ID is not provided
    wp_redirect( admin_url( 'admin.php?page=fooconvert' ) );
    exit;
}
?>

<div class="fooconvert-stats-container" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
    <div class="fooconvert-stats-header">
        <h2><?php
            // Translators: %s refers to the title of the widget.
            echo sprintf( esc_html__( 'Stats for #%s', 'fooconvert' ), esc_html( $widget_title ) );
            ?>
        </h2>
        <?php echo $edit_link; ?>
    </div>

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
    <div class="fooconvert-recent-activity-container loading">
        <h2>
            <?php _e('Recent Activity', 'fooconvert'); ?>
            <select class="fooconvert-recent-activity-days">
                <?php foreach ( $recent_activity_options as $days => $label ) : ?>
                    <option value="<?php echo esc_attr( $days ); ?>" <?php selected( $recent_activity_days, $days ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </h2>
        <canvas id="recentActivityChart"></canvas>
    </div>

</div>
