<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the popup ID from the URL
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
$popup_title = __( 'Unknown', 'fooconvert' );
$filter_days = intval( get_option( FOOCONVERT_OPTION_RECENT_ACTIVITY_DAYS, FOOCONVERT_METRICS_DAYS_DEFAULT ) );
$filter_options = apply_filters( 'fooconvert_popup_stats_recent_activity_options',
    [
        1                               => __( 'Last day', 'fooconvert' ),
        2                               => __( 'Last 2 days', 'fooconvert' ),
        FOOCONVERT_METRICS_DAYS_DEFAULT => __( 'Last 7 days', 'fooconvert' )
    ]
);
$edit_link = '';
$preview_link = '';

if ( $post_id ) {
    $popup = get_post( $post_id );
    if ( $popup ) {
        $popup_title = fooconvert_get_popup_title( $popup );
        $edit_url = fooconvert_admin_url_popup_edit( $post_id );
        $preview_url = fooconvert_popup_preview_url( $post_id );
        $popup_type = fooconvert_get_popup_type_label( $popup );
        $new_tab_icon = '<span class="screen-reader-text">' . esc_html__( ' (opens in a new tab)', 'fooconvert' ) . '</span><span class="dashicons dashicons-external fooconvert-button-with-icon__icon" aria-hidden="true"></span>';
        /* translators: %s: popup type label, for example "Bar" or "Overlay". */
        $edit_label = esc_html( sprintf( _x( 'Edit %s', 'stats screen action for popup type', 'fooconvert' ), $popup_type ) );
        /* translators: %s: popup type label, for example "Bar" or "Overlay". */
        $preview_label = esc_html( sprintf( _x( 'Preview %s', 'stats screen action for popup type', 'fooconvert' ), $popup_type ) );
        $edit_link = '<a class="button fooconvert-button-with-icon" href="' . esc_url( $edit_url ) . '" target="_blank" rel="noopener noreferrer">' . $edit_label . $new_tab_icon . '</a>';
        $preview_link = '<a class="button fooconvert-button-with-icon" href="' . esc_url( $preview_url ) . '" target="_blank" rel="noopener noreferrer">' . $preview_label . $new_tab_icon . '</a>';
    }
} else {
    // Redirect to the popup list page if the popup ID is not provided
    wp_redirect( admin_url( 'admin.php?page=fooconvert' ) );
    exit;
}
?>

<?php $has_sales_panel = false !== has_action( 'fooconvert_popup_stats_html-sales_panel' ); ?>

<div class="fooconvert-stats-container<?php echo $has_sales_panel ? ' has-sales-panel' : ''; ?>" data-popup-id="<?php echo esc_attr( $post_id ); ?>">
    <div class="fooconvert-stats-header">
        <h2><?php
            // Translators: %s refers to the title of the popup.
            echo sprintf( esc_html__( 'Stats for %s', 'fooconvert' ), esc_html( $popup_title ) );
            ?>
        </h2>
        <div class="right">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $edit_link;
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $preview_link;
            ?>
            <select class="fooconvert-recent-activity-days">
                <?php foreach ( $filter_options as $days => $label ) : ?>
                    <option value="<?php echo esc_attr( $days ); ?>" <?php selected( $filter_days, $days ); ?>><?php echo esc_html( $label ); ?></option>
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
        <?php do_action( 'fooconvert_popup_stats_html-metrics', $post_id, $popup ); ?>
    </div>

    <!-- Recent Activity Chart -->
    <div class="fooconvert-recent-activity-container loading">
        <h2>
            <?php
            // Translators: %s refers to the popup type.
            echo sprintf( esc_html__( '%s Activity', 'fooconvert' ), esc_html( $popup_type ) );
            ?>
        </h2>
        <canvas id="recentActivityChart"></canvas>
    </div>

    <?php if ( $has_sales_panel ) : ?>
        <div class="fooconvert-sales-panel-container fooconvert-sales-table-container loading">
            <?php do_action( 'fooconvert_popup_stats_html-sales_panel', $post_id, $popup ); ?>
        </div>
    <?php endif; ?>

</div>
