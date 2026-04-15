<?php

return array(
	'name' => 'popup__digital_download_signup',
	'title' => __( 'Digital Download Signup', 'fooconvert' ),
	'description' => __( 'Digital download signup themed overlay.', 'fooconvert' ),
	'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__digital_download_signup.png',
	'attributes' => array(
		'template' => 'popup__digital_download_signup',
		'content' => array(
			'styles' => array(
				'color' => array(
					'text' => '#ffffff'
				),
				'background' => array(
					'backgroundImage' => array(
						'url' => FOOCONVERT_ASSETS_URL . 'media/template__popup__digital.jpg',
					),
					'backgroundSize' => 'cover'
				),
				'border' => array(
					'shadow' => '6px 6px 9px #00000000',
					'color' => '#FFFFFF',
					'style' => 'solid',
					'width' => '5px'
				),
				'dimensions' => array(
					'padding' => '32px'
				),
				'width' => '480px'
			)
		),
		'closeButton' => array(
			'styles' => array(
				'color' => array(
					'background' => '#ffffff00',
					'icon' => '#8f8f8f'
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
		'settings' => array(
			'transitions' => true
		),
		'variation' => ''
	),
	'innerBlocks' => array(
		array(
			'fc/overlay-container',
			array(),
			array(
				array(
					'fc/overlay-close-button',
					array(),
					array()
				),
				array(
					'fc/overlay-content',
					array(),
					array(
						array(
							'core/paragraph',
							array(
								'align' => 'center',
								'content' => 'Grow Your Traffic in 7 Days – Download our FREE Guide!',
								'dropCap' => false,
								'style' => array(
									'typography' => array(
										'fontSize' => '24px'
									),
									'color' => array(
										'text' => '#ffffff'
									)
								),
								'fontFamily' => 'montserrat'
							),
							array()
						),
						array(
							'fc/sign-up',
							array(
								'styles' => array(
									'color' => array(
										'background' => '#ffffff00'
									),
									'dimensions' => array(
										'gap' => '0px'
									)
								),
								'settings' => array(
									'closeOnSuccess' => true,
									'successMessage' => 'Thanks! Please check your inbox.'
								),
								'inputs' => array(
									'settings' => array(
										'emailOnly' => true,
										'stackLabels' => false,
										'noLabels' => true,
										'emailPlaceholder' => 'Enter your email...'
									),
									'styles' => array(
										'typography' => array(
											'fontSize' => '1.125rem'
										),
										'border' => array(
											'radius' => '30px',
											'width' => '0px',
											'style' => 'none'
										),
										'dimensions' => array(
											'padding' => array(
												'top' => '6px',
												'right' => '20px',
												'bottom' => '6px',
												'left' => '40px'
											),
											'margin' => array(
												'top' => '10px',
												'right' => '-20px',
												'bottom' => '10px',
												'left' => '10px'
											)
										)
									)
								),
								'button' => array(
									'settings' => array(
										'layout' => 'text-only',
										'text' => 'Download'
									),
									'styles' => array(
										'color' => array(
											'background' => '#ef4136'
										),
										'typography' => array(
											'fontSize' => '20px'
										),
										'border' => array(
											'radius' => '30px'
										),
										'dimensions' => array(
											'padding' => array(
												'top' => '4px',
												'right' => '22px',
												'bottom' => '4px',
												'left' => '20px'
											),
											'margin' => array(
												'top' => '7px',
												'right' => '7px',
												'bottom' => '7px',
												'left' => '-20px'
											)
										)
									)
								)
							),
							array()
						),
						array(
							'core/paragraph',
							array(
								'content' => 'Proven tips used by 7,000+ brands to boost traffic!',
								'dropCap' => false,
								'style' => array(
									'elements' => array(
										'link' => array(
											'color' => array(
												'text' => 'var:preset|color|base'
											)
										)
									)
								),
								'textColor' => 'base',
								'fontSize' => 'medium'
							),
							array()
						)
					)
				)
			)
		)
	),
	'scope' => array(
		'block'
	)
);

return array(
    'name'        => 'popup__digital',
    'title'       => __( 'Digital Marketing', 'fooconvert' ),
    'description' => __( 'Digital marketing themed overlay.', 'fooconvert' ),
    'icon'        => '',
    'thumbnail'   => '',
    'attributes'  => array(
        'template'    => 'popup__digital',
        'content'     => array(
            'styles' => array(
                'color'      => array(
                    'text'       => '#ffffff'
                ),
                'background' => array(
                    'backgroundImage' => array(
                        'url' => FOOCONVERT_ASSETS_URL . 'media/template__popup__digital.jpg',
                    ),
                    'backgroundSize' => 'cover',
                ),
                'border'     => array(
                    'shadow' => '6px 6px 9px #00000000',
                    'color'  => '#FFFFFF',
                    'style'  => 'solid',
                    'width'  => '5px'
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
                    'background' => '#FFFFFF00',
                    'icon' => '#ffffff'
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
        'settings'    => array(
            'transitions' => true
        )
    ),
    'innerBlocks' => array(
        array(
            'fc/overlay-container',
            array(),
            array(
                array(
                    'fc/overlay-close-button',
                    array(),
                    array()
                ),
                array(
                    'fc/overlay-content',
                    array(),
                    array(
                        array(
                            'core/paragraph',
                            array(
                                'align' => 'center',
                                'content' => 'Download Our',
                                'dropCap' => false,
                                'style' => array(
                                    'typography' => array(
                                        'fontSize' => '24px'
                                    ),
                                    'spacing' => array(
                                        'padding' => array(
                                            'top' => '32px',
                                            'bottom' => '0',
                                            'left' => '0',
                                            'right' => '0'
                                        ),
                                        'margin' => '0px'
                                    ),
                                    'color' => array(
                                        'text' => '#ffffff'
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
                                'content' => 'FREE GUIDE',
                                'dropCap' => false,
                                'style' => array(
                                    'typography' => array(
                                        'fontSize' => '36px',
                                        'lineHeight' => '1.2',
                                        'fontStyle' => 'normal',
                                        'fontWeight' => '600'
                                    ),
                                    'spacing' => array(
                                        'padding' => '0px',
                                        'margin' => '0px'
                                    ),
                                    'color' => array(
                                        'text' => '#ffffff'
                                    )
                                )
                            ),
                            array()
                        ),
                        array(
                            'core/paragraph',
                            array(
                                'content' => 'to Digital Marketing!',
                                'dropCap' => false,
                                'align' => 'center',
                                'fontFamily' => 'montserrat',
                                'style' => array(
                                    'typography' => array(
                                        'fontSize' => '24px'
                                    ),
                                    'spacing' => array(
                                        'margin' => '0px'
                                    )
                                )
                            ),
                            array()
                        ),
                        array(
                            'core/buttons',
                            array(
                                'style' => array(
                                    'spacing' => array(
                                        'margin' => array(
                                            'top' => '48px',
                                            'bottom' => '32px'
                                        )
                                    )
                                ),
                                'layout' => array(
                                    'type' => 'flex',
                                    'justifyContent' => 'center'
                                )
                            ),
                            array(
                                array(
                                    'core/button',
                                    array(
                                        'tagName' => 'a',
                                        'type' => 'button',
                                        'text' => 'Get It Now!',
                                        'className' => 'is-style-fill',
                                        'style' => array(
                                            'typography' => array(
                                                'fontSize' => '20px'
                                            ),
                                            'border' => array(
                                                'radius' => '30px'
                                            ),
                                            'spacing' => array(
                                                'padding' => array(
                                                    'left' => '32px',
                                                    'right' => '32px',
                                                    'top' => '12px',
                                                    'bottom' => '12px'
                                                )
                                            ),
                                            'color' => array(
                                                'background' => '#EF4136',
                                                'text' => '#ffffff'
                                            )
                                        ),
                                        'fontFamily' => 'montserrat'
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
