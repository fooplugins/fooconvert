<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
    'name'        => 'flyout__special_offer',
    'title'       => __( 'Special Offer Countdown', 'fooconvert' ),
    'description' => __( 'Guide visitors to specific offer with a countdown timer.', 'fooconvert' ),
    'thumbnail'   => FOOCONVERT_ASSETS_URL . 'media/templates/template__special_offer.png',
    'picker'      => array(
        'category'     => 'promotion',
        'tags'         => array( 'offer', 'countdown' ),
        'availability' => 'included',
        'preview'      => FOOCONVERT_ASSETS_URL . 'media/templates/preview/preview-flyout-special-offer.webp',
    ),
    'attributes'  => array(
        'template'    => 'flyout__special_offer',
        'content'     => array(
            'styles' => array(
                'color'      => array(
                    'background' => 'radial-gradient(rgb(60,171,0) 2%,rgb(15,111,0) 100%)',
                    'text'       => '#ffffff'
                ),
                'border' => array(
					'radius' => '0px',
					'style' => 'none',
					'width' => '0px',
					'shadow' => '6px 6px 9px #00000000',
					'radius' => '16px'
				),
                'dimensions' => array(
                    'padding' => array(
                        'top'    => '24px',
                        'right'  => '38px',
                        'bottom' => '24px',
                        'left'   => '32px'
                    )
                ),
                'width'      => 'fit-content'
            )
        ),
        'openButton'  => array(
            'styles' => array(
                'border' => array(
                    'color'  => '#0F6F00',
                    'style'  => 'solid',
                    'width'  => '1px',
                    'shadow' => '6px 6px 9px #00000000'
                ),
                'color'  => array(
                    'background' => '#0F6F00',
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
            'trigger'     => array(
                'type' => 'timer',
                'once' => true
            ),
            'closeAnchor' => 'special-offer'
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
								'content' => 'Special Offer:',
								'dropCap' => false,
								'style' => array(
									'elements' => array(
										'link' => array(
											'color' => array(
												'text' => '#000000'
											)
										)
									),
									'typography' => array(
										'textTransform' => 'uppercase',
										'letterSpacing' => '3px',
										'lineHeight' => '1.2'
									),
									'color' => array(
										'text' => '#000000'
									)
								),
								'fontSize' => 'medium',
								'fontFamily' => 'system-font',
								'align' => 'center'
							),
							array()
						),
						array(
							'core/paragraph',
							array(
								'align' => 'center',
								'content' => 'times running out!',
								'dropCap' => false,
								'placeholder' => 'Add special offer...',
								'style' => array(
									'typography' => array(
										'fontSize' => '28px',
										'lineHeight' => '1.2',
										'textTransform' => 'uppercase',
										'fontStyle' => 'normal',
										'fontWeight' => '600'
									),
									'spacing' => array(
										'margin' => array(
											'top' => '0px',
											'bottom' => '0px',
											'left' => '0px',
											'right' => '0px'
										)
									),
									'color' => array(
										'text' => '#ffffff'
									)
								),
								'fontFamily' => 'system-font'
							),
							array()
						),
						array(
							'fc/countdown',
							array(
								'uniqueId' => '59f934bc-ec82-4041-a72a-62d9fa535f7a',
								'settings' => array(
									'fomoValue' => 13,
									'closeOnExpire' => true
								),
								'segment' => array(
									'settings' => array(
										'layout' => 'stack',
										'padDigits' => false
									),
									'styles' => array(
										'color' => array(
											'background' => '#00000030'
										),
										'typography' => array(
											'fontSize' => '1rem'
										),
										'border' => array(
											'radius' => '15px',
											'shadow' => '6px 6px 9px #00000000'
										),
										'dimensions' => array(
											'margin' => '17px',
											'padding' => '19px'
										)
									)
								),
								'text' => array(
									'styles' => array(
										'typography' => array(
											'fontSize' => 'clamp(1.75rem, 3vw, 2.25rem)'
										)
									)
								),
								'styles' => array(
									'dimensions' => array(
										'margin' => '10px'
									)
								)
							),
							array()
						),
						array(
							'core/buttons',
							array(
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
												'fontSize' => '20px'
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
													'left' => '18px',
													'right' => '18px',
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
