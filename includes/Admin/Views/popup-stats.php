<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the popup ID from the URL
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
$fooconvert_popup_title = __( 'Unknown', 'fooconvert' );
$fooconvert_filter_days = intval( get_option( FOOCONVERT_OPTION_RECENT_ACTIVITY_DAYS, FOOCONVERT_METRICS_DAYS_DEFAULT ) );
$fooconvert_filter_options = apply_filters( 'fooconvert_popup_stats_recent_activity_options',
    [
        1                               => __( 'Last day', 'fooconvert' ),
        2                               => __( 'Last 2 days', 'fooconvert' ),
        FOOCONVERT_METRICS_DAYS_DEFAULT => __( 'Last 7 days', 'fooconvert' )
    ]
);
$fooconvert_edit_link = '';
$fooconvert_preview_link = '';

if ( $post_id ) {
    $fooconvert_popup = get_post( $post_id );
    if ( $fooconvert_popup ) {
        $fooconvert_popup_title = fooconvert_get_popup_title( $fooconvert_popup );
        $fooconvert_edit_url = fooconvert_admin_url_popup_edit( $post_id );
        $fooconvert_preview_url = fooconvert_popup_preview_url( $post_id );
        $fooconvert_popup_type = fooconvert_get_popup_type_label( $fooconvert_popup );
        $fooconvert_new_tab_icon = '<span class="screen-reader-text">' . esc_html__( ' (opens in a new tab)', 'fooconvert' ) . '</span><span class="dashicons dashicons-external fooconvert-button-with-icon__icon" aria-hidden="true"></span>';
        /* translators: %s: popup type label, for example "Bar" or "Overlay". */
        $fooconvert_edit_label = esc_html( sprintf( _x( 'Edit %s', 'stats screen action for popup type', 'fooconvert' ), $fooconvert_popup_type ) );
        /* translators: %s: popup type label, for example "Bar" or "Overlay". */
        $fooconvert_preview_label = esc_html( sprintf( _x( 'Preview %s', 'stats screen action for popup type', 'fooconvert' ), $fooconvert_popup_type ) );
        $fooconvert_edit_link = '<a class="button fooconvert-button-with-icon" href="' . esc_url( $fooconvert_edit_url ) . '" target="_blank" rel="noopener noreferrer">' . $fooconvert_edit_label . $fooconvert_new_tab_icon . '</a>';
        $fooconvert_preview_link = '<a class="button fooconvert-button-with-icon" href="' . esc_url( $fooconvert_preview_url ) . '" target="_blank" rel="noopener noreferrer">' . $fooconvert_preview_label . $fooconvert_new_tab_icon . '</a>';
    }
} else {
    // Redirect to the popup list page if the popup ID is not provided
    wp_safe_redirect( admin_url( 'admin.php?page=fooconvert' ) );
    exit;
}
?>

<?php $fooconvert_has_sales_panel = false !== has_action( 'fooconvert_popup_stats_html-sales_panel' ); ?>

<div class="fooconvert-stats-container<?php echo $fooconvert_has_sales_panel ? ' has-sales-panel' : ''; ?>" data-popup-id="<?php echo esc_attr( $post_id ); ?>">
    <div class="fooconvert-stats-header">
        <h2><?php
            // Translators: %s refers to the title of the popup.
            echo sprintf( esc_html__( 'Stats for %s', 'fooconvert' ), esc_html( $fooconvert_popup_title ) );
            ?>
        </h2>
        <div class="right">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $fooconvert_edit_link;
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $fooconvert_preview_link;
            ?>
            <select class="fooconvert-recent-activity-days">
                <?php foreach ( $fooconvert_filter_options as $fooconvert_days => $fooconvert_label ) : ?>
                    <option value="<?php echo esc_attr( $fooconvert_days ); ?>" <?php selected( $fooconvert_filter_days, $fooconvert_days ); ?>><?php echo esc_html( $fooconvert_label ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Basic Metrics -->
    <div class="fooconvert-basic-metrics">
        <div class="metric loading">
            <p id="metric-total_events">...</p>
            <h2><?php esc_html_e( 'Total Events', 'fooconvert' ); ?></h2>
            <span data-balloon-pos="down"
                  aria-label="<?php esc_attr_e( 'Total events logged for the popup over the time period.', 'fooconvert' ); ?>">
                <i class="dashicons dashicons-editor-help"></i>
            </span>
        </div>
        <div class="metric loading">
            <p id="metric-total_views">...</p>
            <h2><?php esc_html_e( 'Total Views', 'fooconvert' ); ?></h2>
            <span data-balloon-pos="down"
                  aria-label="<?php esc_attr_e( 'Total number of times the popup has been viewed by a visitor.', 'fooconvert' ); ?>">
                <i class="dashicons dashicons-editor-help"></i>
            </span>
        </div>
        <div class="metric loading">
            <p id="metric-total_unique_visitors">...</p>
            <h2><?php esc_html_e( 'Total Visitors', 'fooconvert' ); ?></h2>
            <span data-balloon-pos="down"
                  aria-label="<?php esc_attr_e( 'Total number of unique visitors that have viewed the popup.', 'fooconvert' ); ?>">
                <i class="dashicons dashicons-editor-help"></i>
            </span>
        </div>
        <div class="metric loading">
            <p id="metric-total_engagements">...</p>
            <h2><?php esc_html_e( 'Total Engagements', 'fooconvert' ); ?></h2>
            <span data-balloon-pos="down"
                  aria-label="<?php esc_attr_e( 'Total number of engagements that have been made with the popup (clicks, opens, etc).', 'fooconvert' ); ?>">
                <i class="dashicons dashicons-editor-help"></i>
            </span>
        </div>
        <?php do_action( 'fooconvert_popup_stats_html-metrics', $post_id, $fooconvert_popup ); ?>
    </div>

    <!-- Recent Activity Chart -->
    <div class="fooconvert-recent-activity-container loading">
        <h2>
            <?php
            // Translators: %s refers to the popup type.
            echo sprintf( esc_html__( '%s Activity', 'fooconvert' ), esc_html( $fooconvert_popup_type ) );
            ?>
        </h2>
        <canvas id="recentActivityChart"></canvas>
    </div>

    <?php if ( $fooconvert_has_sales_panel ) : ?>
        <div class="fooconvert-sales-panel-container fooconvert-sales-table-container loading">
            <?php do_action( 'fooconvert_popup_stats_html-sales_panel', $post_id, $fooconvert_popup ); ?>
        </div>
    <?php endif; ?>

</div>
