<?php
$hidden_panels = fooconvert_get_setting( 'hide_dashboard_panels', [] );

if ( in_array( 'pro-features', $hidden_panels ) ) {
    return;
}
?>
<div class="fooconvert-panel fooconvert-panel-pro-features" data-panel="pro-features">
    <div class="fooconvert-panel-section fooconvert-panel-header">
        <h2>🚀 <?php esc_html_e( 'PRO Features', 'fooconvert' ); ?></h2>
        <div class="fooconvert-panel-section-right">
            <a class="fooconvert-hide-panel" data-panel="pro-features" href="#hide"
               title="<?php esc_html_e( 'Hide Panel', 'fooconvert' ); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </a>
        </div>
    </div>
    <div class="fooconvert-panel-section fooconvert-panel-no-bottom-border fooconvert-panel-section-pro-details">
        <h3><?php esc_html_e( 'Unlock these PRO features:', 'fooconvert' ); ?></h3>
        <ul class="ul-disc">
            <?php foreach ( function_exists('fooconvert_pro_features_list') ? fooconvert_pro_features_list() : [] as $feature ) : ?>
                <li>
                    <strong><?php echo esc_html( $feature['title'] ); ?>:</strong>
                    <?php echo esc_html( $feature['feature'] ); ?>
                    <?php if ( !empty( $feature['link'] ) ) : ?>
                        <a href="<?php echo esc_url( $feature['link'] ); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e('Read more', 'fooconvert'); ?>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="fooconvert-panel-section fooconvert-panel-section-offer fooconvert-pro-offer">
        <h3 class="fooconvert-pro-offer-title">
            <?php esc_html_e('Upgrade to PRO and Supercharge Your Conversions!', 'fooconvert'); ?>
        </h3>
        <a href="<?php echo esc_url( fooconvert_admin_url_pricing() ); ?>" class="button button-primary">
            <?php esc_html_e('View PRO Pricing', 'fooconvert'); ?>
        </a>
        <a href="<?php echo esc_url( fooconvert_admin_url_trial() ); ?>" class="button button-secondary">
            <?php esc_html_e('Start a Free Trial', 'fooconvert'); ?>
        </a>
    </div>
</div>
