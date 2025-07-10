<?php
/**
 * PRO Bar Templates for FooConvert
 * Contains summary data for all PRO bar templates for use in promotions and template selection UI.
 */
return array(
    array(
        'name' => 'bar__digital_download_signup',
        'title' => __( 'Digital Download Signup', 'fooconvert' ),
        'description' => __( 'Digital download signup lead magnet bar.', 'fooconvert' ),
        'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__digital_download_signup.png',
        'pro' => true,
        'upsell' => array(
            'heading' => 'example heading',
            'description' => 'example description',
            'image' => FOOCONVERT_ASSETS_URL . 'media/templates/sample.png',
            'primary_button_text' => 'example button text',
            'primary_button_url' => 'example button url',
            'secondary_button_text' => 'example button text',
            'secondary_button_url' => 'example button url',
        )
    ),
    array(
        'name' => 'bar__newsletter_subscribe',
        'title' => __( 'Newsletter Subscribe', 'fooconvert' ),
        'description' => __( 'Newsletter Subscribe marketing themed bar.', 'fooconvert' ),
        'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__newsletter_subscribe.png',
        'pro' => true,
    ),
    array(
        'name' => 'bar__smart_exit_offer',
        'title' => __( 'Smart Exit Offer', 'fooconvert' ),
        'description' => __( 'A smart exit offer bar lead magnet to help prevent visitors from leaving your site.', 'fooconvert' ),
        'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__smart_exit_offer.png',
        'pro' => true,
    ),
    array(
        'name' => 'bar__special_offer',
        'title' => __( 'Special Offer Countdown', 'fooconvert' ),
        'description' => __( 'Guide visitors to specific offer with a countdown timer.', 'fooconvert' ),
        'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__special_offer.png',
        'pro' => true,
    ),
    array(
        'name' => 'bar__watch_the_video',
        'title' => __( 'Watch the Video', 'fooconvert' ),
        'description' => __( 'Guide visitors to a watch a specific video.', 'fooconvert' ),
        'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__video.png',
        'pro' => true,
    ),
);
