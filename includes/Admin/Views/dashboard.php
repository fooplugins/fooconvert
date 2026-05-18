<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="fooconvert-dashboard-container">
    <div class="fooconvert-dashboard-header">
        <h1 class="fooconvert-dashboard-heading"><?php esc_html_e( 'Popup Dashboard', 'fooconvert' ); ?></h1>
        <a class="fooconvert-ai-popup-builder-button" href="<?php echo esc_url( fooconvert_admin_url_ai_popup_builder() ); ?>" target="_top">
            <svg class="fooconvert-ai-popup-builder-button__icon" viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                <path d="M12 2.75l1.9 5.12 5.35 1.98-5.35 1.98L12 17.25l-1.9-5.42-5.35-1.98 5.35-1.98L12 2.75z" />
                <path d="m18.25 14.25.75 2.03 2 .72-2 .72-.75 2.03-.75-2.03-2-.72 2-.72.75-2.03z" />
                <path d="m5.75 14.75.5 1.32 1.25.43-1.25.43-.5 1.32-.5-1.32L4 16.5l1.25-.43.5-1.32z" />
            </svg>
            <span><?php esc_html_e( 'AI Popup Builder', 'fooconvert' ); ?></span>
        </a>
    </div>
    <div class="fooconvert-dashboard-columns">
        <div class="fooconvert-dashboard-column fooconvert-dashboard-left">
            <?php do_action( 'fooconvert_admin_dashboard_left_top' ); ?>
            <?php require_once FOOCONVERT_INCLUDES_PATH . 'Admin/Views/dashboard-panel-recent.php'; ?>
            <?php require_once FOOCONVERT_INCLUDES_PATH . 'Admin/Views/dashboard-panel-getting-started.php'; ?>
            <?php require_once FOOCONVERT_INCLUDES_PATH . 'Admin/Views/dashboard-panel-help.php'; ?>
            <?php do_action( 'fooconvert_admin_dashboard_left' ); ?>
        </div>
        <div class="fooconvert-dashboard-column fooconvert-dashboard-right">
            <?php require_once FOOCONVERT_INCLUDES_PATH . 'Admin/Views/dashboard-panel-top-performers.php'; ?>
            <?php do_action( 'fooconvert_admin_dashboard_right' ); ?>
        </div>
    </div>
</div>
