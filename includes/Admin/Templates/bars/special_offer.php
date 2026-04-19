<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
    'name'        => 'bar__special_offer',
    'title'       => __( 'Special Offer Countdown', 'fooconvert' ),
    'description' => __( 'Guide visitors to specific offer with a countdown timer.', 'fooconvert' ),
    'thumbnail'   => FOOCONVERT_ASSETS_URL . 'media/templates/template__special_offer.png',
    'picker'      => array(
        'category'     => 'promotion',
        'tags'         => array( 'offer', 'countdown' ),
        'availability' => 'included',
        'preview'      => FOOCONVERT_ASSETS_URL . 'media/templates/preview/preview-bar-special-offer.webp',
    ),
    'attributes'  => array(
        'template'    => 'bar__special_offer',
        'settings'    => array(
            'transitions' => true,
            'trigger'     => array(
                'type' => 'timer',
                'once' => true
            ),
            'closeAnchor' => 'special-offer'
        ),
        'openButton'  => array(
            'styles' => array(
                'border' => array(
                    'color'  => '#0F6F00', // #0F6F00
                    'style'  => 'solid',
                    'width'  => '1px',
                    'shadow' => '6px 6px 9px #00000000'
                ),
                'color'  => array(
                    'background' => '#0F6F00',  // #0F6F00
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
                    'background' => 'radial-gradient(rgb(60,171,0) 2%,rgb(15,111,0) 100%)',
                    'text'       => '#ffffff'
                ),
                'dimensions' => array(
                    'gap'     => '32px',
                    'padding' => '32px'
                ),
                'border' => array(
					'radius' => '0px',
					'style' => 'none',
					'width' => '0px',
					'shadow' => '6px 6px 9px #00000000'
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
									'flexWrap' => 'wrap'
								)
							),
							array(
								array(
									'core/paragraph',
									array(
										'content' => 'Special Offer:',
										'dropCap' => false,
										'style' => array(
											'typography' => array(
												'textTransform' => 'uppercase',
												'letterSpacing' => '3px',
												'fontSize' => '24px',
												'lineHeight' => '1.2'
											),
											'elements' => array(
												'link' => array(
													'color' => array(
														'text' => '#000000'
													)
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
										'content' => 'ENDS SOON!',
										'dropCap' => false,
										'placeholder' => 'Add special offer...',
										'style' => array(
											'typography' => array(
												'textTransform' => 'uppercase',
												'fontSize' => '24px',
												'lineHeight' => '1.2',
												'fontStyle' => 'normal',
												'fontWeight' => '600'
											),
											'spacing' => array(
												'padding' => array(
													'top' => '0',
													'bottom' => '0'
												),
												'margin' => array(
													'top' => '0',
													'bottom' => '0'
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
							'fc/countdown',
							array(
								'uniqueId' => '426766db-4456-472d-92e1-8aec0a2ed27d',
								'styles' => array(
									'border' => array(
										'radius' => '0px'
									),
									'dimensions' => array(
										'gap' => '0px',
										'margin' => '-20px',
										'padding' => '0px'
									)
								),
								'segment' => array(
									'styles' => array(
										'color' => array(
											'background' => '#00000030'
										),
										'border' => array(
											'radius' => '15px'
										),
										'typography' => array(
											'fontSize' => '0.8rem'
										),
										'dimensions' => array(
											'gap' => '0px',
											'padding' => '15px',
											'margin' => '5px'
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
									'type' => 'flex'
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
													'top' => '8px',
													'bottom' => '8px'
												)
											),
											'color' => array(
												'background' => '#000000',
												'text' => '#ffffff'
											),
											'shadow' => 'var:preset|shadow|natural'
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
