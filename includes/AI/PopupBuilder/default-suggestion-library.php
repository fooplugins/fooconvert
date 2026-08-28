<?php
/**
 * Default prompt suggestions for the AI popup builder.
 *
 * @package FooPlugins\FooConvert
 */

defined( 'ABSPATH' ) || exit;

return array(
    array(
        'text'  => __( 'Create an exit-intent overlay popup for first-time shoppers offering 15% off. Use the free Coupon block with code WELCOME15 and the Countdown block set to expire in 2 hours. Add a confident headline, one reassurance line, and a clear shop-now CTA. Use a warm gradient background with soft depth, or follow my branding if brand styles are available.', 'fooconvert' ),
        'tags'  => array( 'Create', 'Popup', 'Discount' ),
        'phase' => 'initial',
    ),
    array(
        'text'  => __( 'Create a newsletter signup flyout for readers that offers weekly tips and a practical downloadable guide. Use the Sign Up block as an email-only form with friendly placeholders and button copy like "Send me the guide". Use a light background gradient following my branding.', 'fooconvert' ),
        'tags'  => array( 'Create', 'Popup', 'Signup' ),
        'phase' => 'initial',
    ),
    array(
        'text'  => __( 'Create a compact product-launch bar for visitors announcing a new product launch. Use the Countdown block for launch urgency, concise headline copy, and one clear CTA. Keep the layout short enough for a bar and use a dark high-contrast gradient background from my brand profile if available.', 'fooconvert' ),
        'tags'  => array( 'Create', 'Bar', 'Launch' ),
        'phase' => 'initial',
    ),
    array(
        'text'           => __( 'Add a countdown timer for 2 hours in the future.', 'fooconvert' ),
        'tags'           => array( 'Countdown', 'Urgency' ),
        'phase'          => 'edit',
        'requiredBlocks' => array( 'fc/countdown' ),
    ),
    array(
        'text'  => __( 'Change the popup styling to use my branding.', 'fooconvert' ),
        'tags'  => array( 'Brand', 'Style' ),
        'phase' => 'edit',
    ),
    array(
        'text'              => __( 'Change this popup to be a bar and shorten all the wording used.', 'fooconvert' ),
        'tags'              => array( 'Bar', 'Shorten' ),
        'phase'             => 'edit',
        'excludePopupTypes' => array( 'bar' ),
    ),
    array(
        'text'              => __( 'Convert this popup to a flyout with a softer tone and one clear CTA.', 'fooconvert' ),
        'tags'              => array( 'Flyout', 'Tone' ),
        'phase'             => 'edit',
        'excludePopupTypes' => array( 'flyout' ),
    ),
    array(
        'text'  => __( 'Make the CTA button copy more specific and urgent.', 'fooconvert' ),
        'tags'  => array( 'Copy', 'CTA' ),
        'phase' => 'edit',
    ),
    array(
        'text'           => __( 'Add a coupon code block for SAVE15.', 'fooconvert' ),
        'tags'           => array( 'Coupon', 'Discount' ),
        'phase'          => 'edit',
        'requiredBlocks' => array( 'fc/coupon' ),
    ),
    array(
        'text'  => __( 'Remove extra copy and make the layout easier to scan.', 'fooconvert' ),
        'tags'  => array( 'Copy', 'Layout' ),
        'phase' => 'edit',
    ),
    array(
        'text'  => __( 'Rewrite this for mobile visitors with shorter lines.', 'fooconvert' ),
        'tags'  => array( 'Mobile', 'Copy' ),
        'phase' => 'edit',
    ),
    array(
        'text'  => __( 'Add social proof using a short testimonial-style paragraph.', 'fooconvert' ),
        'tags'  => array( 'Proof', 'Copy' ),
        'phase' => 'edit',
    ),
    array(
        'text'  => __( 'Replace the offer with 10% off a first order.', 'fooconvert' ),
        'tags'  => array( 'Offer', 'Discount' ),
        'phase' => 'edit',
    ),
    array(
        'text'                    => __( 'Generate a new background image that matches this offer.', 'fooconvert' ),
        'tags'                    => array( 'Image', 'Style' ),
        'phase'                   => 'edit',
        'requiresImageGeneration' => true,
    ),
    array(
        'text'  => __( 'Change the trigger to fire after 50% scroll.', 'fooconvert' ),
        'tags'  => array( 'Trigger', 'Scroll' ),
        'phase' => 'edit',
    ),
    array(
        'text'  => __( 'Add urgency without making the copy sound pushy.', 'fooconvert' ),
        'tags'  => array( 'Urgency', 'Tone' ),
        'phase' => 'edit',
    ),
);
