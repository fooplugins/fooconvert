<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fooconvert_hidden_panels = fooconvert_get_setting( 'hide_dashboard_panels', [] );

if ( in_array( 'help', $fooconvert_hidden_panels ) ) {
    return;
}
?>
<div class="fooconvert-panel" data-panel="help">
    <div class="fooconvert-panel-section fooconvert-panel-section-flex">
        <h2>❓<?php esc_html_e( 'Need Help? We\'re here for you!', 'fooconvert' ); ?></h2>
        <div class="fooconvert-panel-section-right">
            <a class="fooconvert-hide-panel" data-panel="help" href="#hide"
               title="<?php esc_html_e( 'Hide Panel', 'fooconvert' ); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </a>
        </div>
    </div>
    <div class="fooconvert-panel-section">
        <ul class="ul-disc">
            <li>
                <a href="<?php echo esc_url( FOOCONVERT_HOME_URL ); ?>" target="_blank">
                    <?php esc_html_e( 'Visit the FooConvert homepage', 'fooconvert' ); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( FOOCONVERT_DOCS_URL ); ?>" target="_blank">
                    <?php esc_html_e( 'Read documentation', 'fooconvert' ); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( FOOCONVERT_SUPPORT_URL ); ?>" target="_blank">
                    <?php esc_html_e( 'Get Support', 'fooconvert' ); ?>
                </a>
            </li>
        </ul>
    </div>
</div>
