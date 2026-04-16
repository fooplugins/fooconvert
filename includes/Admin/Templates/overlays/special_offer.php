<?php

return array(
    'name'        => 'popup__special_offer',
    'title'       => __( 'Special Offer Countdown', 'fooconvert' ),
    'description' => __( 'Guide visitors to specific offer with a countdown timer.', 'fooconvert' ),
    'thumbnail'   => FOOCONVERT_ASSETS_URL . 'media/templates/template__special_offer.png',
    'picker'      => array(
        'category'     => 'promotion',
        'tags'         => array( 'offer', 'countdown' ),
        'availability' => 'included',
        'preview'      => FOOCONVERT_ASSETS_URL . 'media/templates/fullsize/template__special_offer.png',
    ),
    'attributes'  => array(
        'template'    => 'popup__special_offer',
        'content'     => array(
            'styles' => array(
                'color'      => array(
                    'background' => 'radial-gradient(rgb(60,171,0) 2%,rgb(15,111,0) 100%)',
                    'text'       => '#ffffff'
                ),
                'border'     => array(
                    'shadow' => '6px 6px 9px #00000000',
                    'color'  => '#0F6F00',
                    'style'  => 'solid',
                    'width'  => '0px',
                    'radius' => '16px'
                ),
                'dimensions' => array(
                    'padding' => '38px'
                ),
                'width'      => '720px'
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
            'trigger' => array(
                'type' => 'immediate',
                'once' => false
            ),
            'closeAnchor' => 'special-offer'
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
								'content' => 'Special Offer:',
								'dropCap' => false,
								'style' => array(
									'typography' => array(
										'fontSize' => '24px',
										'lineHeight' => '1.2',
										'letterSpacing' => '3px',
										'textTransform' => 'uppercase'
									),
									'elements' => array(
										'link' => array(
											'color' => array(
												'text' => '#000000'
											)
										)
									),
									'spacing' => array(
										'padding' => array(
											'top' => '0',
											'bottom' => '0',
											'left' => '0',
											'right' => '0'
										),
										'margin' => array(
											'top' => '32px',
											'bottom' => '32px',
											'left' => '32px',
											'right' => '32px'
										)
									),
									'color' => array(
										'text' => '#000000'
									)
								),
								'fontFamily' => 'system-font'
							),
							array()
						),
						array(
							'core/paragraph',
							array(
								'align' => 'center',
								'content' => 'OFFER ends soon! Act quickly',
								'dropCap' => false,
								'placeholder' => 'Add special offer...',
								'style' => array(
									'typography' => array(
										'fontSize' => '32px',
										'lineHeight' => '1.2',
										'textTransform' => 'uppercase',
										'fontStyle' => 'normal',
										'fontWeight' => '600'
									),
									'spacing' => array(
										'padding' => array(
											'top' => '0',
											'bottom' => '0',
											'left' => '0',
											'right' => '0'
										),
										'margin' => array(
											'top' => '32px',
											'bottom' => '32px',
											'left' => '32px',
											'right' => '32px'
										)
									),
									'elements' => array(
										'link' => array(
											'color' => array(
												'text' => '#ffffff'
											)
										)
									),
									'color' => array(
										'text' => '#ffffff'
									)
								)
							),
							array()
						),
						array(
							'fc/countdown',
							array(
								'styles' => array(
									'border' => array(
										'radius' => '0px',
										'color' => '#111111',
										'width' => '11px'
									)
								),
								'settings' => array(
									'fomoValue' => 12,
									'closeOnExpire' => true
								),
								'segment' => array(
									'settings' => array(
										'layout' => 'stack',
										'padDigits' => false
									),
									'styles' => array(
										'border' => array(
											'radius' => '16px',
											'style' => 'none',
											'width' => '0px',
											'shadow' => '6px 6px 9px #00000000'
										),
										'typography' => array(
											'fontSize' => '1rem'
										),
										'dimensions' => array(
											'padding' => '31px',
											'margin' => '27px',
											'gap' => '12px'
										)
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
										'url' => '#special-offer',
										'text' => 'Let\'s Go!',
										'className' => 'is-style-fill',
										'style' => array(
											'typography' => array(
												'fontSize' => '24px'
											),
											'border' => array(
												'radius' => '16px'
											),
											'elements' => array(
												'link' => array(
													'color' => array(
														'text' => '#ffffff'
													)
												)
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
												'background' => '#000000',
												'text' => '#ffffff'
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
