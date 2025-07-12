<?php
/**
 * PRO Popup Templates for FooConvert
 * Contains summary data for all PRO popup templates for use in promotions and template selection UI.
 */
return array(
    array(
        'name' => 'popup__digital_download_signup',
        'title' => __( 'Digital Download Signup', 'fooconvert' ),
        'description' => __( 'Digital download signup themed popup.', 'fooconvert' ),
        'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__digital_download_signup.png',
        'pro' => true,
        'upsell' => array(
            'title' => 'Digital Download Signup',
            'content' => '<p>example description <a href="#link">with a link</a></p>',
            'image' => FOOCONVERT_ASSETS_URL . 'media/templates/template__digital_download_signup.png',
            'primary' => array(
                'text' => 'View PRO Pricing',
                'href' => '#view_pro_pricing_url',
            ),
            'secondary' => array(
                'text' => 'Start Free Trial',
                'href' => '#start_free_trial_url',
            )
        )
    ),
    array(
        'name' => 'popup__newsletter_subscribe',
        'title' => __( 'Newsletter Subscribe', 'fooconvert' ),
        'description' => __( 'Newsletter Subscribe marketing themed popup.', 'fooconvert' ),
        'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__newsletter_subscribe.png',
        'pro' => true,
    ),
    array(
        'name' => 'popup__smart_exit_offer',
        'title' => __( 'Smart Exit Offer', 'fooconvert' ),
        'description' => __( 'Smart exit offer themed popup.', 'fooconvert' ),
        'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__smart_exit_offer.png',
        'pro' => true,
    ),
    array(
        'name' => 'popup__special_offer',
        'title' => __( 'Special Offer Countdown', 'fooconvert' ),
        'description' => __( 'Guide visitors to specific offer with a countdown timer.', 'fooconvert' ),
        'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__special_offer.png',
        'pro' => true,
    ),
    array(
        'name' => 'popup__watch_the_video',
        'title' => __( 'Watch the Video', 'fooconvert' ),
        'description' => __( 'Guide visitors to a watch a specific video.', 'fooconvert' ),
        'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__video.png',
        'pro' => true,
    ),
);
