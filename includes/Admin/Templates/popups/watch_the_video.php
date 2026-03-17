<?php

return array(
    'name'        => 'popup__watch_the_video',
    'title'       => __( 'Watch the Video', 'fooconvert' ),
    'description' => __( 'Guide visitors to a watch a specific video.', 'fooconvert' ),
    'icon'        => '',
    'thumbnail'   => FOOCONVERT_ASSETS_URL . 'media/templates/template__video.png',
    'attributes'  => array(
        'template'    => 'popup__watch_the_video',
        'content'     => array(
            'styles' => array(
                'color'      => array(
                    'background' => 'radial-gradient(rgb(229,25,25) 0%,rgb(169,32,32) 100%)',
                    'text'       => '#ffffff'
                ),
                'border'     => array(
                    'shadow' => '6px 6px 9px rgba(0, 0, 0, 0.2)',
                    'color'  => '#981d1d',
                    'style'  => 'solid',
                    'width'  => '1px',
                    'radius' => '16px'
                ),
                'dimensions' => array(
                    'padding' => '32px'
                ),
                'width'      => '480px'
            )
        ),
        'closeButton' => array(
            'styles' => array(
                'color' => array(
                    'icon' => '#ffffff'
                )
            )
        ),
        'settings'    => array(
            'transitions' => true
        )
    ),
    'innerBlocks' => array(
        array(
            'fc/popup-container',
            array(),
            array(
                array(
                    'fc/popup-close-button',
                    array(),
                    array()
                ),
                array(
                    'fc/popup-content',
                    array(),
                    array(
                        array(
                            'core/paragraph',
                            array(
                                'placeholder' => __( 'Add video description...', 'fooconvert' ),
                                'dropCap'     => false,
                                'align'       => 'center',
                                'style'       => array(
                                    'typography' => array(
                                        'fontSize'   => '24px',
                                        'lineHeight' => '1.2'
                                    ),
                                    'spacing'    => array(
                                        'padding' => array(
                                            'top'    => '0',
                                            'bottom' => '0',
                                            'left'   => '0',
                                            'right'  => '0'
                                        ),
                                        'margin'  => array(
                                            'top'    => '32px',
                                            'bottom' => '32px',
                                            'left'   => '32px',
                                            'right'  => '32px',
                                        )
                                    )
                                )
                            ),
                            array()
                        ),
                        array(
                            'core/buttons',
                            array(
                                'layout' => array(
                                    'type'           => 'flex',
                                    'justifyContent' => 'center'
                                ),
                                'style'  => array(
                                    'spacing' => array(
                                        'margin' => array(
                                            'top'    => '64px',
                                            'bottom' => '32px',
                                            'left'   => '32px',
                                            'right'  => '32px',
                                        )
                                    )
                                )
                            ),
                            array(
                                array(
                                    'core/button',
                                    array(
                                        'tagName'    => 'a',
                                        'type'       => 'button',
                                        'text'       => '<img class="wp-image-1083" style="width: 78px;" src="' . FOOCONVERT_ASSETS_URL . 'media/play-white.png" alt="">' . __( 'Watch the video', 'fooconvert' ),
                                        'className'  => 'fc-image-button is-style-fill',
                                        'style'      => array(
                                            'typography' => array(
                                                'textTransform' => 'uppercase',
                                                'fontStyle'     => 'normal',
                                                'fontWeight'    => '600',
                                                'fontSize'      => '24px'
                                            ),
                                            'border'     => array(
                                                'radius' => '16px'
                                            ),
                                            'elements'   => array(
                                                'link' => array(
                                                    'color' => array(
                                                        'text' => '#ffffff'
                                                    )
                                                )
                                            ),
                                            'spacing'    => array(
                                                'padding' => array(
                                                    'left'   => '18px',
                                                    'right'  => '18px',
                                                    'top'    => '12px',
                                                    'bottom' => '12px'
                                                )
                                            ),
                                            'color'      => array(
                                                'gradient' => 'linear-gradient(135deg,rgb(71,18,18) 0%,rgb(46,14,14) 100%)',
                                                'text'     => '#ffffff'
                                            )
                                        ),
                                        'fontFamily' => 'system-font'
                                    ),
                                    array()
                                )
                            )
                        )
                    )
                )
            )
        )
    ),
    'scope'       => array(
        'block'
    )
);

