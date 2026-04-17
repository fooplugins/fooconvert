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
        $popup_type = fooconvert_get_popup_type_label( $popup );
        // Translators: %s refers to the link to edit the popup.
        $edit_link = '<a class="button" href="' . esc_url( $edit_url ) . '">' . esc_html( sprintf( __( 'Edit %s', 'fooconvert' ), $popup_type ) ) . '</a>';
        // Translators: %s refers to the link to preview the popup.
        $preview_link = '<a id="fooconvert-popup-preview" class="button" href="#preview">' . esc_html( sprintf( __( 'Preview %s', 'fooconvert' ), $popup_type ) ) . '</a>';
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
