<?php

return array(
    'name'        => 'bar__watch_the_video',
    'title'       => __( 'Watch the Video', 'fooconvert' ),
    'description' => __( 'Guide visitors to a watch a specific video.', 'fooconvert' ),
    'thumbnail'   => FOOCONVERT_ASSETS_URL . 'media/templates/template__video.png',
    'attributes'  => array(
        'template'    => 'bar__watch_the_video',
        'settings'    => array(
            'transitions' => true,
			'trigger' => array(
				'type' => 'scroll',
				'data' => 50,
				'once' => false
			)
        ),
        'openButton'  => array(
            'styles' => array(
                'border' => array(
                    'color'  => '#A92020',
                    'style'  => 'solid',
                    'width'  => '1px',
                    'shadow' => '6px 6px 9px #00000000'
                ),
                'color'  => array(
                    'background' => '#E51919',
                    'icon'       => '#ffffff'
                )
            ),
			'settings' => array(
				'hidden' => true
			)
        ),
        'closeButton' => array(
            'styles' => array(
                'color' => array(
                    'icon' => '#ffffff'
                )
            )
        ),
        'content'     => array(
            'styles' => array(
                'color'      => array(
                    'background' => 'radial-gradient(rgb(229,25,25) 0%,rgb(169,32,32) 100%)',
                    'text'       => '#ffffff'
                ),
                'dimensions' => array(
                    'gap'     => '32px',
                    'padding' => '32px'
                ),
                'border'     => array(
                    'shadow' => '6px 6px 9px #00000000',
                    'color'  => '#981d1d',
                    'style'  => 'solid',
                    'width'  => '1px',
                    'radius' => '0px'
                )
            )
        )
    ),
    'innerBlocks' => array(
        array(
            'fc/bar-open-button',
            array(),
            array()
        ),
        array(
            'fc/bar-container',
            array(),
            array(
                array(
                    'fc/bar-close-button',
                    array(),
                    array()
                ),
                array(
                    'fc/bar-content',
                    array(),
                    array(
                        array(
                            'core/group',
                            array(
                                'tagName' => 'div',
                                'layout'  => array(
                                    'type'        => 'flex',
                                    'orientation' => 'vertical'
                                )
                            ),
                            array(
                                array(
                                    'core/paragraph',
                                    array(
                                        //'content' => __( 'Get 500+ new followers<br>using our tried &amp; true methods', 'fooconvert' ),
                                        'placeholder' => __( 'Add video description...', 'fooconvert' ),
                                        'dropCap'     => false,
                                        'style'       => array(
                                            'typography' => array(
                                                'fontSize'   => '24px',
                                                'lineHeight' => '1.2'
                                            ),
                                            'spacing'    => array(
                                                'padding' => array(
                                                    'top'    => '0',
                                                    'bottom' => '0'
                                                ),
                                                'margin'  => array(
                                                    'top'    => '0',
                                                    'bottom' => '0'
                                                )
                                            )
                                        )
                                    ),
                                    array()
                                )
                            )
                        ),
                        array(
                            'core/buttons',
                            array(
                                'layout' => array(
                                    'type' => 'flex'
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
                                        'fontFamily' => 'system-font',
                                        'url' => '#video'
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

