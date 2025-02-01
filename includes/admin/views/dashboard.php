<?php
?><div class="fooconvert-dashboard-container">
    <div class="fooconvert-dashboard-columns">
        <div class="fooconvert-dashboard-column fooconvert-dashboard-left">
            <?php do_action( 'fooconvert_admin_dashboard_left_top' ); ?>
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

