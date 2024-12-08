<?php

$demos_created = fooconvert_get_setting( 'demo_content' ) === 'on';
$top_performers_sort_options = fooconvert_top_performers_sort_options();

$top_performers_sort = fooconvert_top_performers_sort();
$stats_last_updated = fooconvert_stats_last_updated();

?><div class="fooconvert-dashboard-container">
    <div class="fooconvert-dashboard-columns">
        <div class="fooconvert-dashboard-column fooconvert-dashboard-left">
            <div class="fooconvert-panel">
                <div class="fooconvert-panel-section">
                    <h2><?php esc_html_e( 'Getting Started with FooConvert!', 'fooconvert' ); ?></h2>
                </div>
                <?php if ( ! $demos_created ) : ?>
                <div class="fooconvert-panel-section">
                    <p>
                        <?php esc_html_e( 'The easiest way to get started is by creating demo widgets, which will create 3 draft bars, flyouts and popups.', 'fooconvert' ); ?>
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
                            (<a href="/post-new.php?post_type=fc-bar" target="_blank"><?php esc_html_e( 'create a new bar', 'fooconvert' ); ?></a>)
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
            <div class="fooconvert-panel">
                <div class="fooconvert-panel-section">
                    <h2><?php esc_html_e( 'Need Help? We\'re here for you!', 'fooconvert' ); ?></h2>
                </div>
                <div class="fooconvert-panel-section">
                    <ul class="ul-disc">
                        <li>
                            <a href="https://fooplugins.com/documentation/fooconvert/" target="_blank">
                                <?php esc_html_e( 'Read FooConvert documentation', 'fooconvert' ); ?>
                            </a>
                        </li>
                        <li>
                            <a href="https://fooplugins.com/support/" target="_blank">
                                <?php esc_html_e( 'Get Support', 'fooconvert' ); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
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
            <div class="fooconvert-panel">
                <div class="fooconvert-panel-section">
                    <h2><?php esc_html_e( 'PRO Addons', 'fooconvert' ); ?></h2>
                </div>
                <div class="fooconvert-panel-section">
                    <h3><?php esc_html_e( 'Coming soon!', 'fooconvert' ); ?></h3>
                </div>
            </div>
        </div>

    </div>
</div>

