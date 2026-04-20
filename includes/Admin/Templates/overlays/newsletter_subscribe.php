<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name' => 'popup__newsletter_subscribe',
	'title' => __( 'Newsletter Subscribe', 'fooconvert' ),
	'description' => __( 'Newsletter Subscribe marketing themed overlay.', 'fooconvert' ),
	'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__newsletter_subscribe.png',
	'picker' => array(
		'category' => 'lead-capture',
		'tags' => array( 'newsletter', 'email' ),
		'availability' => 'included',
		'preview' => array(
			'url' => FOOCONVERT_ASSETS_URL . 'media/templates/preview/preview-overlay-newsletter-subscribe.webp',
			'width' => 740,
			'height' => 601,
		),
	),
	'attributes' => array(
		'template' => 'popup__newsletter_subscribe',
		'settings' => array(
			'transitions' => true,
			'maxOnMobile' => true
		),
		'styles' => array(
			'dimensions' => array(
				'padding' => '0px'
			)
		),
		'closeButton' => array(
			'styles' => array(
				'color' => array(
					'background' => '#d6d6d600',
					'icon' => '#b0b0b0'
				),
				'dimensions' => array(
					'margin' => '15px',
					'padding' => '0px'
				)
			),
			'settings' => array(
				'icon' => array(
					'size' => '24px'
				)
			)
		),
		'content' => array(
			'styles' => array(
				'background' => array(
					'backgroundImage' => array(
						'url' => FOOCONVERT_ASSETS_URL . 'media/template__popup__i-cafe.jpg',
					),
					'backgroundSize' => 'cover',
					'backgroundPosition' => '50% 50%',
					'backgroundAttachment' => 'scroll'
				),
				'border' => array(
					'shadow' => '6px 6px 9px #00000000',
					'color' => '#F7941D',
					'style' => 'solid',
					'width' => '5px'
				),
				'dimensions' => array(
					'gap' => '32px',
					'padding' => '60px'
				),
				'width' => '720px'
			)
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
							'core/group',
							array(
								'tagName' => 'div',
								'style' => array(
									'spacing' => array(
										'blockGap' => '50px',
										'padding' => array(
											'right' => '0',
											'top' => '0px',
											'bottom' => '0px',
											'left' => '0'
										)
									)
								),
								'layout' => array(
									'type' => 'flex',
									'orientation' => 'vertical',
									'justifyContent' => 'center'
								)
							),
							array(
								array(
									'core/spacer',
									array(
										'height' => '36px',
										'style' => array(
											'layout' => array(
												'flexSize' => '36px',
												'selfStretch' => 'fixed'
											)
										)
									),
									array()
								),
								array(
									'core/paragraph',
									array(
										'content' => 'Get FREE Weekly Tips to help Grow Your business...',
										'dropCap' => false,
										'style' => array(
											'typography' => array(
												'fontSize' => '30px'
											),
											'border' => array(
												'width' => '0px',
												'style' => 'none'
											),
											'color' => array(
												'background' => '#ffffff00'
											),
											'spacing' => array(
												'padding' => array(
													'top' => '0',
													'bottom' => '0'
												)
											)
										),
										'fontFamily' => 'handlee',
										'align' => 'center'
									),
									array()
								),
								array(
									'fc/sign-up',
									array(
										'styles' => array(
											'typography' => array(
												'fontFamily' => 'Montserrat'
											),
											'dimensions' => array(
												'padding' => '0px'
											)
										),
										'settings' => array(
											'layout' => 'stack',
											'successMessage' => 'Thanks for joining!',
											'closeOnSuccess' => true
										),
										'inputs' => array(
											'settings' => array(
												'emailOnly' => false,
												'noLabels' => true,
												'emailPlaceholder' => 'Your email address',
												'stackLabels' => true,
												'namePlaceholder' => 'Your full name'
											),
											'styles' => array(
												'typography' => array(
													'fontSize' => '1rem',
													'lineHeight' => '1.1'
												),
												'border' => array(
													'radius' => '10px',
													'width' => '1px',
													'style' => 'solid',
													'color' => '#949494'
												),
												'dimensions' => array(
													'padding' => array(
														'top' => '15px',
														'right' => '3px',
														'bottom' => '15px',
														'left' => '23px'
													),
													'margin' => array(
														'top' => '0px',
														'right' => '0px',
														'bottom' => '0px',
														'left' => '0px'
													)
												)
											)
										),
										'button' => array(
											'settings' => array(
												'text' => 'Join The List',
												'justify' => 'center',
												'layout' => 'icon-text',
												'icon' => array(
													'slug' => 'default__send',
													'size' => '32px'
												),
												'width' => 'fit-content'
											),
											'styles' => array(
												'color' => array(
													'text' => '#111111',
													'icon' => '#FFFFFF',
													'background' => '#f7941d'
												),
												'typography' => array(
													'fontSize' => '1.2rem',
													'fontStyle' => 'normal',
													'fontWeight' => 700
												),
												'border' => array(
													'radius' => '10px',
													'color' => '#FFFFFF',
													'width' => '2px',
													'shadow' => '6px 6px 9px #00000000'
												)
											)
										)
									),
									array()
								),
								array(
									'core/paragraph',
									array(
										'align' => 'center',
										'content' => 'Join 5,000+ readers getting exclusive content &amp; tools.',
										'dropCap' => false,
										'style' => array(
											'typography' => array(
												'fontSize' => '16px',
												'fontStyle' => 'italic',
												'fontWeight' => '400',
												'lineHeight' => '1.3'
											),
											'color' => array(
												'text' => '#7b7b7b'
											),
											'spacing' => array(
												'margin' => array(
													'top' => '-14px',
													'bottom' => '0',
													'right' => '19px'
												)
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
	'scope' => array(
		'block'
	)
);

return array(
    'name'        => 'popup__i-cafe',
    'title'       => __( 'i-Cafe', 'fooconvert' ),
    'description' => __( 'i-Cafe marketing themed overlay.', 'fooconvert' ),
    'icon'        => '',
    'thumbnail'   => '',
    'attributes'  => array(
        'template'    => 'popup__i-cafe',
        'settings'    => array(
            'transitions' => true
        ),
        'styles' => array(
            'dimensions' => array(
                'padding' => '24px'
            )
        ),
        'closeButton' => array(
            'styles' => array(
                'color' => array(
                    'background' => '#D7D7D7',
                    'icon' => '#ffffff'
                ),
                'dimensions' => array(
                    'margin' => '15px',
                    'padding' => '0px'
                )
            ),
            'settings' => array(
                'position' => 'left'
            )
        ),
        'content'     => array(
            'styles' => array(
                'background' => array(
                    'backgroundImage' => array(
                        'url' => FOOCONVERT_ASSETS_URL . 'media/template__popup__i-cafe.jpg',
                    ),
                    'backgroundSize' => 'cover',
                ),
                'border'     => array(
                    'shadow' => '6px 6px 9px #00000000',
                    'color'  => '#F7941D',
                    'style'  => 'solid',
                    'width'  => '5px'
                ),
                'dimensions' => array(
                    'gap'     => '32px',
                    'padding' => array(
                        'top' => '100px',
                        'right' => '32px',
                        'bottom' => '60px',
                        'left' => '32px'
                    )
                ),
                'width' => '480px'
            )
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
                            'core/group',
                            array(
                                'tagName' => 'div',
                                'style' => array(
                                    'spacing' => array(
                                        'blockGap' => '50px',
                                        'padding' => array(
                                            'right' => '100px'
                                        )
                                    )
                                ),
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
                                        'style' => array(
                                            'spacing' => array(
                                                'blockGap' => '0'
                                            )
                                        ),
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
                                                'content' => 'Enjoying our blog?',
                                                'dropCap' => false,
                                                'style' => array(
                                                    'typography' => array(
                                                        'fontSize' => '40px',
                                                        'lineHeight' => '1.2'
                                                    ),
                                                    'spacing' => array(
                                                        'margin' => array(
                                                            'top' => '36px'
                                                        )
                                                    )
                                                ),
                                                'fontFamily' => 'handlee'
                                            ),
                                            array()
                                        ),
                                        array(
                                            'core/paragraph',
                                            array(
                                                'align' => 'center',
                                                'content' => 'Subscribe now<br>to get exclusive tips!',
                                                'dropCap' => false,
                                                'style' => array(
                                                    'typography' => array(
                                                        'fontSize' => '16px',
                                                        'fontStyle' => 'italic',
                                                        'fontWeight' => '400',
                                                        'lineHeight' => '1.3'
                                                    ),
                                                    'color' => array(
                                                        'text' => '#bcbec0'
                                                    ),
                                                    'spacing' => array(
                                                        'margin' => array(
                                                            'top' => '-12px',
                                                            'bottom' => '0',
                                                            'right' => '0px',
                                                            'left' => '90px'
                                                        )
                                                    )
                                                ),
                                                'fontFamily' => 'system-font'
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
                                                'tagName' => 'a',
                                                'type' => 'button',
                                                'text' => 'Subscribe',
                                                'className' => 'is-style-fill',
                                                'style' => array(
                                                    'typography' => array(
                                                        'fontSize' => '20px',
                                                        'fontStyle' => 'normal',
                                                        'fontWeight' => '600'
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
                                                        'background' => '#FFFFFF',
                                                        'text' => '#F7941D'
                                                    ),
                                                    'shadow' => 'var:preset|shadow|natural'
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
            )
        )
    ),
    'scope'       => array(
        'block'
    )
);
