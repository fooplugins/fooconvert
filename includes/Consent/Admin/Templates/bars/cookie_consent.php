<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Default bar template for the cookie consent banner.
 *
 * Populates a compliant default layout so a fresh install renders a
 * lawful banner with zero editor work:
 *   - short intro paragraph (plain `core/paragraph`) with an inline
 *     Cookie Policy link,
 *   - three equally-weighted `core/button` blocks (Accept All, Reject All,
 *     Preferences) registered as block variations so they inherit every
 *     core Button styling feature and carry a `data-fc-consent-action`
 *     attribute for the runtime to bind click handlers to,
 *   - the `fc/cookie-consent-preferences` server-rendered panel (hidden
 *     by default, revealed by the Preferences button).
 *
 * Everything except the preferences panel is edit-freely — admins can
 * rearrange, restyle, reword, or swap in a themed button and it still
 * works.
 */
return array(
    'name'        => 'bar__cookie_consent',
    'title'       => __( 'Cookie Consent', 'fooconvert' ),
    'description' => __( 'EU-compliant cookie consent banner with Accept / Reject / Preferences.', 'fooconvert' ),
    'picker'      => array(
        'category'     => 'compliance',
        'tags'         => array( 'consent', 'cookies', 'gdpr' ),
        'availability' => 'included',
    ),
    'attributes'  => array(
        'viewState' => 'open',
        'template'  => 'bar__cookie_consent',
        'settings'  => array(
            'transitions' => true,
            'maxWidth'    => '1100px',
            'trigger'     => array(
                'type' => 'immediate',
                'once' => false,
            ),
        ),
        'styles'    => array(
            'dimensions' => array(
                'padding' => array(
                    'top'    => '16px',
                    'right'  => '24px',
                    'bottom' => '16px',
                    'left'   => '24px',
                ),
            ),
            'color'      => array(
                'background' => '#111111',
                'text'       => '#ffffff',
            ),
        ),
        'openButton'  => array(
            'settings' => array( 'hidden' => true ),
        ),
        'closeButton' => array(
            'settings' => array( 'hidden' => true ),
        ),
    ),
    'innerBlocks' => array(
        array(
            'fc/bar-open-button',
            array(),
            array(),
        ),
        array(
            'fc/bar-container',
            array(),
            array(
                array(
                    'fc/bar-content',
                    array(),
                    array(
                        array(
                            'core/group',
                            array(
                                'tagName' => 'div',
                                'layout'  => array(
                                    'type'           => 'flex',
                                    'orientation'    => 'horizontal',
                                    'justifyContent' => 'space-between',
                                    'flexWrap'       => 'wrap',
                                    'verticalAlignment' => 'center',
                                ),
                            ),
                            array(
                                array(
                                    'core/paragraph',
                                    array(
                                        'content' => __( 'We use cookies to make this site work, to understand how visitors use it, and — with your permission — for marketing. Read our <a href="#">Cookie Policy</a>.', 'fooconvert' ),
                                    ),
                                    array(),
                                ),
                                array(
                                    'core/buttons',
                                    array(
                                        'layout' => array(
                                            'type'           => 'flex',
                                            'justifyContent' => 'right',
                                            'flexWrap'       => 'wrap',
                                        ),
                                    ),
                                    array(
                                        array(
                                            'core/button',
                                            array(
                                                'text'          => __( 'Reject all', 'fooconvert' ),
                                                'consentAction' => 'reject',
                                                'className'     => 'is-style-outline',
                                            ),
                                            array(),
                                        ),
                                        array(
                                            'core/button',
                                            array(
                                                'text'          => __( 'Preferences', 'fooconvert' ),
                                                'consentAction' => 'preferences',
                                                'className'     => 'is-style-outline',
                                            ),
                                            array(),
                                        ),
                                        array(
                                            'core/button',
                                            array(
                                                'text'          => __( 'Accept all', 'fooconvert' ),
                                                'consentAction' => 'accept',
                                            ),
                                            array(),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'fc/cookie-consent-preferences',
                            array(),
                            array(),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
