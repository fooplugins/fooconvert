<?php

return array(
    'name'        => 'bar__smart_exit_offer',
	'title' => __( 'Smart Exit Offer', 'fooconvert' ),
	'description' => __( 'A smart exit offer bar lead magnet to help prevent visitors from leaving your site.', 'fooconvert' ),
    'thumbnail'   => FOOCONVERT_ASSETS_URL . 'media/templates/template__smart_exit_offer.png',
    'attributes'  => array(
        'template'    => 'bar__smart_exit_offer',
        'settings'    => array(
            'transitions' => true,
            'maxWidth' => '800px',
            'trigger' => array(
				'type' => 'exit-intent',
				'data' => 5,
				'once' => true
			),
			'closeAnchor' => 'claim'
        ),
        'styles' => array(
            'dimensions' => array(
                'padding' => '24px'
            )
        ),
        'openButton'  => array(
            'styles' => array(
                'border' => array(
                    'shadow' => '6px 6px 9px #00000000'
                ),
                'color' => array(
                    'background' => '#7B1FBD',
                    'icon' => '#ffffff'
                )
            ),
			'settings' => array(
				'hidden' => true
			)
        ),
        'closeButton' => array(
            'styles' => array(
                'color' => array(
					'background' => '#ffffff00',
					'icon' => '#a200ff'
				),
                'dimensions' => array(
                    'margin' => '15px',
                    'padding' => '0px'
                ),
                'border' => array(
                    'radius' => '50%'
                )
            )
        ),
        'content'     => array(
            'styles' => array(
                'color'      => array(
                    'background' => '#7B1FBD',
                    'text'       => '#ffffff'
                ),
                'background' => array(
                    'backgroundImage' => array(
                        'url' => FOOCONVERT_ASSETS_URL . 'media/template__bar__purple_percent.jpg',
                    ),
                    'backgroundSize' => 'cover',
                ),
                'border'     => array(
                    'radius' => '30px',
                    'shadow' => '6px 6px 9px #00000000',
                    'color'  => '#FFFFFF',
                    'style'  => 'solid',
                    'width'  => '5px'
                ),
                'dimensions' => array(
                    'gap'     => '32px',
                    'padding' => '32px'
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
                                'layout' => array(
                                    'type' => 'flex',
                                    'orientation' => 'vertical',
                                    'justifyContent' => 'center'
                                )
                            ),
                            array(
                                array(
                                    'core/group',
                                    array(
                                        'tagName' => 'div',
                                        'layout' => array(
                                            'type' => 'flex',
                                            'orientation' => 'vertical',
                                            'justifyContent' => 'center'
                                        )
                                    ),
                                    array(
                                        array(
                                            'core/paragraph',
                                            array(
                                                'content' => __( 'HEY... Don’t Go Yet!', 'fooconvert' ),
                                                'dropCap' => false,
                                                'className' => 'fc-text-shadow-stroke',
                                                'style' => array(
                                                    'elements' => array(
                                                        'link' => array(
                                                            'color' => array(
                                                                'text' => '#f26522'
                                                            )
                                                        )
                                                    ),
                                                    'color' => array(
                                                        'text' => '#f26522'
                                                    ),
                                                    'typography' => array(
                                                        'fontSize' => '60px',
                                                        'fontStyle' => 'normal',
                                                        'fontWeight' => '800',
                                                        'lineHeight' => '1'
                                                    )
                                                ),
                                                'fontFamily' => 'montserrat'
                                            ),
                                            array()
                                        ),
                                        array(
                                            'core/paragraph',
                                            array(
                                                'align' => 'center',
                                                'content' => __( 'Take 10% Off Your First Order – Our Treat!', 'fooconvert' ),
                                                'dropCap' => false,
                                                'className' => 'fc-text-shadow',
                                                'style' => array(
                                                    'typography' => array(
                                                        'fontSize' => '24px',
                                                        'fontStyle' => 'normal',
                                                        'fontWeight' => '600'
                                                    ),
                                                    'color' => array(
                                                        'text' => '#ffffff'
                                                    )
                                                ),
                                                'fontFamily' => 'montserrat'
                                            ),
                                            array()
                                        )
                                    )
                                ),
                                array(
									'fc/coupon',
									array(
										'uniqueId' => '9b3fb64b-3632-4f9f-b705-f55a5cd3dcac',
										'styles' => array(
											'typography' => array(
												'fontSize' => '1.125rem'
											),
											'background' => array()
										),
										'settings' => array(
											'textAlign' => 'center',
											'noLabel' => true,
											'closeOnCopy' => true
										),
										'code' => array(
											'settings' => array(
												'text' => 'SAVE10',
												'textAlign' => 'center'
											),
											'styles' => array(
												'border' => array(
													'radius' => '35px'
												),
												'typography' => array(
													'fontFamily' => array(
														'key' => 'fira-code',
														'name' => 'Fira Code',
														'style' => array(
															'fontFamily' => '"Fira Code", monospace'
														)
													)
												),
												'innerPadding' => array(
													'top' => '5px',
													'right' => '20px',
													'bottom' => '5px',
													'left' => '20px'
												),
												'dimensions' => array(
													'padding' => '5px'
												)
											)
										),
										'button' => array(
											'settings' => array(
												'icon' => array(
													'slug' => 'default__copy',
													'size' => '24px'
												),
												'layout' => 'text-icon',
												'text' => 'Claim Now!'
											),
											'styles' => array(
												'border' => array(
													'radius' => '35px'
												),
												'color' => array(
													'background' => '#f26522'
												),
												'typography' => array(
													'fontFamily' => array(
														'key' => 'montserrat',
														'name' => 'Montserrat',
														'style' => array(
															'fontFamily' => 'Montserrat'
														)
													),
													'fontSize' => '1.125rem',
													'fontStyle' => 'normal',
													'fontWeight' => 600
												)
											)
										)
									),
									array()
								),
                                array(
									'core/paragraph',
									array(
										'content' => __( 'Only available while you’re still here...', 'fooconvert' ),
										'dropCap' => false,
										'fontSize' => 'small'
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

