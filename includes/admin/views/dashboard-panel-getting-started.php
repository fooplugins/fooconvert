<?php
$demos_created = fooconvert_get_setting( 'demo_content' ) === 'on';
$hidden_panels = fooconvert_get_setting( 'hide_dashboard_panels', [] );

if ( in_array( 'getting-started', $hidden_panels ) ) {
    return;
}
?>
<div class="fooconvert-panel" data-panel="getting-started">
    <div class="fooconvert-panel-section fooconvert-panel-section-flex">
        <h2>🚀<?php esc_html_e( 'Getting Started with FooConvert!', 'fooconvert' ); ?></h2>
        <div class="fooconvert-panel-section-right">
            <a class="fooconvert-hide-panel" data-panel="getting-started" href="#hide"
               title="<?php esc_html_e( 'Hide Panel', 'fooconvert' ); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </a>
        </div>
    </div>
    <?php if ( !$demos_created ) : ?>
        <div class="fooconvert-panel-section">
            <p>
                <?php esc_html_e( 'The easiest way to get started is by creating demo widgets, which will create 4 draft bars, flyouts and popups.', 'fooconvert' ); ?>
                <strong><?php esc_html_e( 'It\'s also the best way to see the top performers in action!', 'fooconvert' ); ?> </strong>
            </p>
            <p class="fooconvert-flex fooconvert-create-demo-container">
                <button class="button button-primary button-large fooconvert-create-demo-widgets">
                    <?php esc_html_e( 'Create Demo Widgets', 'fooconvert' ); ?>
                </button>
                <span class="spinner fooconvert-create-demo-widgets-spinner"></span>
            </p>
            <p>
                <strong><?php esc_html_e( 'Do not worry', 'fooconvert' ); ?></strong> -
                <?php esc_html_e( 'the demo widgets will NOT be visible to your site visitors :)', 'fooconvert' ); ?>
            </p>
        </div>
    <?php else : ?>
        <div class="fooconvert-panel-section">
            <p>
                <?php esc_html_e( 'Awesome, you have already created the demo widgets!', 'fooconvert' ); ?> </strong>
            </p>
            <p class="fooconvert-flex fooconvert-delete-demo-container">
                <button class="button button-secondary button-large fooconvert-delete-demo-widgets">
                    <?php esc_html_e( 'Delete Demo Widgets', 'fooconvert' ); ?>
                </button>
                <span class="spinner fooconvert-delete-demo-widgets-spinner"></span>
            </p>
            <p>
                <?php esc_html_e( 'Deleting the demo widgets will also delete all stats for the widgets.', 'fooconvert' ); ?>
            </p>
        </div>
    <?php endif; ?>
    <div class="fooconvert-panel-section">
        <h3 class="fooconvert-center"><?php esc_html_e( 'OR', 'fooconvert' ); ?></h3>
        <p>
            <?php esc_html_e( 'Create your own unique widget by following these simple steps:', 'fooconvert' ); ?>
        </p>
        <ol class="fooconvert-ordered-list">
            <li>
                <?php esc_html_e( 'Create a new widget.', 'fooconvert' ); ?>
                (<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=fc-bar' ) ); ?>"
                    target="_blank"><?php esc_html_e( 'create a new bar', 'fooconvert' ); ?></a>)
            </li>
            <li>
                <?php esc_html_e( 'Select a pre-made template.', 'fooconvert' ); ?>
                (<?php esc_html_e( 'e.g. Black Friday Bar', 'fooconvert' ); ?>)
            </li>
            <li>
                <?php esc_html_e( 'Customize the look and feel &amp; change the content to your liking!', 'fooconvert' ); ?>
            </li>
            <li>
                <?php esc_html_e( 'Set the Display Rules locations.', 'fooconvert' ); ?>
                (<?php esc_html_e( 'e.g. either "Entire Site" or "Front Page"', 'fooconvert' ); ?>)
            </li>
            <li>
                <?php esc_html_e( 'Select an Open Trigger.', 'fooconvert' ); ?>
                (<?php esc_html_e( 'e.g. on page load, or exit intent', 'fooconvert' ); ?>)
            </li>
            <li>
                <?php esc_html_e( 'Publish and you\'re done!', 'fooconvert' ); ?>
            </li>
        </ol>
    </div>
</div>
