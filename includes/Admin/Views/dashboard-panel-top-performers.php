<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fooconvert_top_performers_sort_options = fooconvert_popup_metric_options();
$fooconvert_top_performers_sort = fooconvert_top_performers_sort();
$fooconvert_stats_last_updated = fooconvert_stats_last_updated();
?>
<div class="fooconvert-panel">
    <div class="fooconvert-panel-section fooconvert-panel-section-flex">
        <h2>🏆 <?php esc_html_e( 'Top Performers', 'fooconvert' ); ?></h2>
        <div class="fooconvert-panel-section-right">
            <label>
                <?php esc_html_e( 'Sort by', 'fooconvert' ); ?>
                <select class="fooconvert-top-performers-sort">
                    <?php
                    foreach ( $fooconvert_top_performers_sort_options as $fooconvert_metric_key => $fooconvert_sort_object ) {
                        if ( isset( $fooconvert_sort_object['dropdown_option'] ) ) {
                            echo '<option ' . selected( $fooconvert_metric_key, $fooconvert_top_performers_sort ) . ' value="' . esc_attr( $fooconvert_metric_key ) . '">' . esc_html( $fooconvert_sort_object['dropdown_option'] ) . '</option>';
                        }
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
            <span class="fooconvert-last-updated"><?php echo esc_html( $fooconvert_stats_last_updated ); ?></span>
        </p>
    </div>
</div>
