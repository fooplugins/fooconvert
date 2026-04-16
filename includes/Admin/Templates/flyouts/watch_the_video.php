<?php

return array(
    'name'        => 'flyout__watch_the_video',
    'title'       => __( 'Watch the Video', 'fooconvert' ),
    'description' => __( 'Guide visitors to a watch a specific video.', 'fooconvert' ),
    'thumbnail'   => FOOCONVERT_ASSETS_URL . 'media/templates/template__video.png',
    'picker'      => array(
        'category'     => 'video',
        'tags'         => array( 'video' ),
        'availability' => 'included',
        'preview'      => FOOCONVERT_ASSETS_URL . 'media/templates/fullsize/template__video.png',
    ),
    'attributes'  => array(
        'template'    => 'flyout__watch_the_video',
        'content'     => array(
            'styles' => array(
                'color'      => array(
                    'background' => 'radial-gradient(rgb(229,25,25) 0%,rgb(169,32,32) 100%)',
                    'text'       => '#ffffff'
                ),
                'border'     => array(
                    'shadow' => '6px 6px 9px #00000000',
                    'color'  => '#981d1d',
                    'style'  => 'solid',
                    'width'  => '1px',
                    'radius' => '16px'
                ),
                'dimensions' => array(
                    'padding' => array(
                        'top'    => '12px',
                        'right'  => '24px',
                        'bottom' => '12px',
                        'left'   => '24px'
                    )
                ),
                'width'      => 'fit-content'
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
        'settings'    => array(
            'transitions' => true,
            'position'    => 'right-bottom',
        )
    ),
    'innerBlocks' => array(
        array(
            'fc/flyout-open-button',
            array(),
            array()
        ),
        array(
            'fc/flyout-container',
            array(),
            array(
                array(
                    'fc/flyout-close-button',
                    array(),
                    array()
                ),
                array(
                    'fc/flyout-content',
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
                                        'fontSize'   => '18px',
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
                                            'top'    => '12px',
                                            'bottom' => '12px',
                                            'left'   => '12px',
                                            'right'  => '12px',
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
                                            'top'    => '24px',
                                            'bottom' => '12px',
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
                                        'text'       => '<img class="wp-image-1083" style="width: 58px;" src="' . FOOCONVERT_ASSETS_URL . 'media/play-white.png" alt="">' . __( 'Watch the video', 'fooconvert' ),
                                        'className'  => 'fc-image-button is-style-fill',
                                        'style'      => array(
                                            'typography' => array(
                                                'textTransform' => 'uppercase',
                                                'fontStyle'     => 'normal',
                                                'fontWeight'    => '600',
                                                'fontSize'      => '20px'
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
