<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fooconvert_hidden_panels = fooconvert_get_setting( 'hide_dashboard_panels', [] );

if ( in_array( 'recent', $fooconvert_hidden_panels ) ) {
    return;
}

$fooconvert_recent_popup_args = array(
    'post_type'      => FOOCONVERT_CPT_POPUP,
    'posts_per_page' => 5,
    'post_status'    => fooconvert_get_dashboard_popup_statuses(),
    'orderby'        => 'modified', // Orders by the last modified date
    'order'          => 'DESC',     // Newest first
);

$fooconvert_popups = get_posts( $fooconvert_recent_popup_args );
$fooconvert_has_popups = !empty( $fooconvert_popups );

?>
<div class="fooconvert-panel" data-panel="recent">
    <div class="fooconvert-panel-section fooconvert-panel-header">
        <h2>📅 <?php esc_html_e( 'Recently Updated', 'fooconvert' ); ?></h2>
        <div class="fooconvert-panel-section-right">
            <a class="fooconvert-hide-panel" data-panel="recent" href="#hide"
               title="<?php esc_html_e( 'Hide Panel', 'fooconvert' ); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </a>
        </div>
    </div>
    <div class="fooconvert-panel-section <?php echo $fooconvert_has_popups ? 'fooconvert-panel-no-padding' : ''; ?>">
        <?php if ( $fooconvert_has_popups ) : ?>
            <div>
                <style>
                    .fooconvert-dashboard-table .modified {
                        text-align: right;
                        color: #aaa;
                    }
                </style>
                <table class="fooconvert-dashboard-table fooconvert-recent-updated-table">
                    <tbody>
                    <?php
                    foreach ( $fooconvert_popups as $fooconvert_popup ) {
                        $id = intval( $fooconvert_popup->ID );
                        $fooconvert_popup_title = fooconvert_get_popup_title( $fooconvert_popup );
                        $fooconvert_edit_url = fooconvert_admin_url_popup_edit( $id );
                        $fooconvert_edit_link = '<a href="' . esc_url( $fooconvert_edit_url ) . '"><i class="dashicons dashicons-edit" title="' . esc_attr__( 'Edit Popup', 'fooconvert' ) . '"></i></a>';
                        $fooconvert_stats_url = fooconvert_admin_url_popup_stats( $id );
                        $fooconvert_stats_link = '<a href="' . esc_url( $fooconvert_stats_url ) . '"><i class="dashicons dashicons-chart-bar" title="' . esc_attr__( 'View Popup Stats', 'fooconvert' ) . '"></i></a>';
                        $post_type = fooconvert_get_popup_type_label( $fooconvert_popup );
                        $fooconvert_modified_time = get_post_modified_time( 'U', false, $fooconvert_popup );               // Get the Unix timestamp of the modified date
                        $fooconvert_modified_diff = human_time_diff( $fooconvert_modified_time, current_time( 'timestamp' ) );
                        // translators: %s: refers to the relative time since the popup was last updated.
                        $fooconvert_modified = sprintf( __( '%s ago', 'fooconvert' ), $fooconvert_modified_diff ); // Friendly time difference

                        echo '<tr><td>';
                        echo '<span>' . esc_html( $fooconvert_popup_title ) . '</span>';
                        echo '<span class="fooconvert-dashboard-pill">' . esc_html( $post_type ) . '</span>';
                        echo '<div class="fooconvert-dashboard-table-actions">';
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $fooconvert_edit_link . ' ' . $fooconvert_stats_link;
                        echo '</div>';
                        echo '</td>';
                        echo '<td class="modified">' . esc_html( $fooconvert_modified ) . '</td>';
                        echo '</tr>';
                    } ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p>
                <?php esc_html_e( 'No popups yet. Create a bar, flyout, or overlay to get started.', 'fooconvert' ); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="fooconvert-panel-section fooconvert-panel-section-flex">
        <p class="fooconvert-flex">
            <a href="<?php echo esc_url( fooconvert_admin_url_popup_new( FOOCONVERT_POPUP_TYPE_BAR ) ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Add New Bar', 'fooconvert' ); ?>
            </a>
            <a href="<?php echo esc_url( fooconvert_admin_url_popup_new( FOOCONVERT_POPUP_TYPE_FLYOUT ) ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Add New Flyout', 'fooconvert' ); ?>
            </a>
            <a href="<?php echo esc_url( fooconvert_admin_url_popup_new( FOOCONVERT_POPUP_TYPE_OVERLAY ) ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Add New Overlay', 'fooconvert' ); ?>
            </a>
        </p>
    </div>
</div>
