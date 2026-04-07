<?php
$hidden_panels = fooconvert_get_setting( 'hide_dashboard_panels', [] );

if ( in_array( 'recent', $hidden_panels ) ) {
    return;
}

$recent_widget_args = array(
    'post_type'      => FOOCONVERT_CPT_POPUP,
    'posts_per_page' => 5,
    'post_status'    => 'any',
    'orderby'        => 'modified', // Orders by the last modified date
    'order'          => 'DESC',     // Newest first
);

$widgets = get_posts( $recent_widget_args );

if ( empty( $widgets ) || count( $widgets ) === 0 ) {
    return;
}

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
    <div class="fooconvert-panel-section fooconvert-panel-no-padding">
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
                foreach ( $widgets as $widget ) {
                    $id = intval( $widget->ID );
                    $widget_title = fooconvert_get_widget_title( $widget );
                    $edit_url = fooconvert_admin_url_widget_edit( $id );
                    $edit_link = '<a href="' . esc_url( $edit_url ) . '"><i class="dashicons dashicons-edit" title="' . esc_attr__( 'Edit Popup', 'fooconvert' ) . '"></i></a>';
                    $stats_url = fooconvert_admin_url_widget_stats( $id );
                    $stats_link = '<a href="' . esc_url( $stats_url ) . '"><i class="dashicons dashicons-chart-bar" title="' . esc_attr__( 'View Popup Stats', 'fooconvert' ) . '"></i></a>';
                    $post_type = fooconvert_get_widget_post_type_label( $widget );
                    $modified_time = get_post_modified_time( 'U', false, $widget );               // Get the Unix timestamp of the modified date
                    $modified_diff = human_time_diff( $modified_time, current_time( 'timestamp' ) );
                    // translators: %s: refers to the time last modified.
                    $modified = sprintf( __( 'modified %s ago', 'fooconvert' ), $modified_diff ); // Friendly time difference

                    echo '<tr><td>';
                    echo '<span>' . esc_html( $widget_title ) . '</span>';
                    echo '<span class="fooconvert-dashboard-pill">' . esc_html( $post_type ) . '</span>';
                    echo '<div class="fooconvert-dashboard-table-actions">';
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $edit_link . ' ' . $stats_link;
                    echo '</div>';
                    echo '</td>';
                    echo '<td class="modified">' . esc_html( $modified ) . '</td>';
                    echo '</tr>';
                } ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="fooconvert-panel-section fooconvert-panel-section-flex">
        <p class="fooconvert-flex">
            <a href="<?php echo esc_url( fooconvert_admin_url_widget_new( FOOCONVERT_POPUP_TYPE_BAR ) ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Add New Bar', 'fooconvert' ); ?>
            </a>
            <a href="<?php echo esc_url( fooconvert_admin_url_widget_new( FOOCONVERT_POPUP_TYPE_FLYOUT ) ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Add New Flyout', 'fooconvert' ); ?>
            </a>
            <a href="<?php echo esc_url( fooconvert_admin_url_widget_new( FOOCONVERT_POPUP_TYPE_POPUP ) ); ?>" class="button button-secondary">
                <?php esc_html_e( 'Add New Popup', 'fooconvert' ); ?>
            </a>
        </p>
    </div>
</div>
