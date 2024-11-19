<?php

$demos_created = fooconvert_get_setting( 'demo_content' ) === 'on';

?><div class="fooconvert-dashboard-container">
    <div class="fooconvert-dashboard-columns">
        <div class="fooconvert-dashboard-column fooconvert-dashboard-left">
            <div class="fooconvert-panel">
                <div class="fooconvert-panel-section">
                    <h2><?php echo esc_html( 'Getting Started with FooConvert!', 'fooconvert' ); ?></h2>
                </div>
                <?php if ( ! $demos_created ) : ?>
                <div class="fooconvert-panel-section">
                    <p>
                        <?php echo esc_html( 'The easiest way to get started is by creating demo widgets, which will create 3 draft bars, flyouts and popups.', 'fooconvert' ); ?>
                        <strong><?php echo esc_html( 'It\'s also the best way to see the stats overview in action!', 'fooconvert' ); ?> </strong>
                    </p>
                    <p class="fooconvert-flex fooconvert-create-demo-container">
                        <button class="button button-primary button-large fooconvert-create-demo-widgets">
                            <?php echo esc_html( 'Create Demo Widgets', 'fooconvert' ); ?>
                        </button>
                        <span class="spinner fooconvert-create-demo-widgets-spinner"></span>
                    </p>
                    <p>
                        <strong><?php echo esc_html( 'Do not worry', 'fooconvert' ); ?></strong> -
                        <?php echo esc_html( 'the demo widgets will NOT be visible to your site visitors :)', 'fooconvert' ); ?>
                    </p>
                </div>
                <?php else : ?>
                    <div class="fooconvert-panel-section">
                        <p>
                            <?php echo esc_html( 'Awesome, you have already created the demo widgets!', 'fooconvert' ); ?> </strong>
                        </p>
                        <p class="fooconvert-flex fooconvert-delete-demo-container">
                            <button class="button button-secondary button-large fooconvert-delete-demo-widgets">
                                <?php echo esc_html( 'Delete Demo Widgets', 'fooconvert' ); ?>
                            </button>
                            <span class="spinner fooconvert-delete-demo-widgets-spinner"></span>
                        </p>
                        <p>
                            <?php echo esc_html( 'Deleting the demo widgets will also delete all stats for the widgets.', 'fooconvert' ); ?>
                        </p>
                    </div>
                <?php endif; ?>
                <div class="fooconvert-panel-section">
                    <h3 class="fooconvert-center"><?php echo esc_html( 'OR', 'fooconvert' ); ?></h3>
                    <p>
                        <?php echo esc_html( 'Create your own unique widget by following these simple steps:', 'fooconvert' ); ?>
                    </p>
                    <ol class="fooconvert-ordered-list">
                        <li>
                            <?php echo esc_html( 'Create a new widget.', 'fooconvert' ); ?>
                            (<a href="/post-new.php?post_type=fc-bar" target="_blank"><?php echo esc_html( 'create a new bar', 'fooconvert' ); ?></a>)
                        </li>
                        <li>
                            <?php echo esc_html( 'Select a pre-made template.', 'fooconvert' ); ?>
                            (<?php echo esc_html( 'e.g. Black Friday Bar', 'fooconvert' ); ?>)
                        </li>
                        <li>
                            <?php echo esc_html( 'Customize the look and feel &amp; change the content to your liking!', 'fooconvert' ); ?>
                        </li>
                        <li>
                            <?php echo esc_html( 'Set the Display Rules locations.', 'fooconvert' ); ?>
                            (<?php echo esc_html( 'e.g. either "Entire Site" or "Front Page"', 'fooconvert' ); ?>)
                        </li>
                        <li>
                            <?php echo esc_html( 'Select an Open Trigger.', 'fooconvert' ); ?>
                            (<?php echo esc_html( 'e.g. on page load, or exit intent', 'fooconvert' ); ?>)
                        </li>
                        <li>
                            <?php echo esc_html( 'Publish and you\'re done!', 'fooconvert' ); ?>
                        </li>
                    </ol>
                </div>
            </div>
            <div class="fooconvert-panel">
                <div class="fooconvert-panel-section">
                    <h2><?php echo esc_html( 'Need Help? We\'re here for you!', 'fooconvert' ); ?></h2>
                </div>
                <div class="fooconvert-panel-section">
                    <ul class="ul-disc">
                        <li>
                            <a href="https://fooplugins.com/documentation/fooconvert/" target="_blank">
                                <?php echo esc_html( 'Read FooConvert documentation', 'fooconvert' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://fooplugins.com/support/" target="_blank">
                                <?php echo esc_html( 'Get Support', 'fooconvert' ); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="fooconvert-dashboard-column fooconvert-dashboard-right">
            <div class="fooconvert-panel">
                <div class="fooconvert-panel-section">
                    <h2><?php echo esc_html( 'Stats Overview', 'fooconvert' ); ?></h2>
                </div>
                <div class="fooconvert-panel-section">
                    <h3><?php echo esc_html( 'Coming soon!', 'fooconvert' ); ?></h3>
                </div>
            </div>
            <div class="fooconvert-panel">
                <div class="fooconvert-panel-section">
                    <h2><?php echo esc_html( 'PRO Addons', 'fooconvert' ); ?></h2>
                </div>
                <div class="fooconvert-panel-section">
                    <h3><?php echo esc_html( 'Coming soon!', 'fooconvert' ); ?></h3>
                </div>
            </div>
        </div>

    </div>
</div>

