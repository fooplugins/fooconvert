<?php
$top_performers_sort_options = fooconvert_top_performers_sort_options();
$top_performers_sort = fooconvert_top_performers_sort();
$stats_last_updated = fooconvert_stats_last_updated();

?><div class="fooconvert-dashboard-container">
    <div class="fooconvert-dashboard-columns">
        <div class="fooconvert-dashboard-column fooconvert-dashboard-left">
            <?php require_once FOOCONVERT_INCLUDES_PATH . 'admin/views/dashboard-panel-getting-started.php'; ?>
            <?php require_once FOOCONVERT_INCLUDES_PATH . 'admin/views/dashboard-panel-help.php'; ?>
            <?php do_action( 'fooconvert_admin_dashboard_left' ); ?>
        </div>
        <div class="fooconvert-dashboard-column fooconvert-dashboard-right">
            <div class="fooconvert-panel">
                <div class="fooconvert-panel-section fooconvert-panel-section-flex">
                    <h2><?php esc_html_e( 'Top Performers', 'fooconvert' ); ?></h2>
                    <div class="fooconvert-panel-section-right">
                        <label>
                            <?php esc_html_e( 'Sort by', 'fooconvert' ); ?>
                            <select class="fooconvert-top-performers-sort">
                                <?php
                                foreach ( $top_performers_sort_options as $key => $sort_object ) {
                                    echo '<option ' . selected( $key, $top_performers_sort ) . ' value="' . esc_attr( $key ) . '">' . esc_html( $sort_object['dropdown_option'] ) . '</option>';
                                }
                                ?>
                            </select>
                        </label>
                    </div>
                </div>
                <div class="fooconvert-panel-section fooconvert-panel-no-padding">
                    <div class="fooconvert-top-performers-container">
                        <span class="spinner is-active fooconvert-top-performers-spinner"></span>
                    </div>
                </div>
                <div class="fooconvert-panel-section fooconvert-panel-section-flex">
                    <p class="fooconvert-flex fooconvert-update-stats-container">
                        <button class="button button-secondary button-large fooconvert-update-stats">
                            <?php esc_html_e( 'Update Stats', 'fooconvert' ); ?>
                        </button>
                        <span class="spinner fooconvert-update-stats-spinner"></span>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Last Updated : ', 'fooconvert' ); ?></strong>
                        <span class="fooconvert-last-updated"><?php echo esc_html( $stats_last_updated ); ?></span>
                    </p>
                </div>
            </div>
            <?php do_action( 'fooconvert_admin_dashboard_right' ); ?>
        </div>
    </div>
</div>

