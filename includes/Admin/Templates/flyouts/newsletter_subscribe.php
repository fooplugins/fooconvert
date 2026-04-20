<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name' => 'flyout__newsletter_subscribe',
	'title' => __( 'Newsletter Subscribe', 'fooconvert' ),
	'description' => __( 'Newsletter subscribe themed flyout.', 'fooconvert' ),
	'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__newsletter_subscribe.png',
	'picker' => array(
		'category' => 'lead-capture',
		'tags' => array( 'newsletter', 'email' ),
		'availability' => 'included',
		'preview' => array(
			'url' => FOOCONVERT_ASSETS_URL . 'media/templates/preview/preview-flyout-newsletter-subscribe.webp',
			'width' => 300,
			'height' => 537,
		),
	),
	'attributes' => array(
		'viewState' => 'open',
		'template' => 'flyout__newsletter_subscribe',
		'settings' => array(
			'transitions' => true,
			'trigger' => array(
				'type' => 'scroll',
				'data' => 50,
				'once' => true
			)
		),
		'styles' => array(
			'dimensions' => array(
				'padding' => '24px'
			)
		),
		'openButton' => array(
			'styles' => array(
				'border' => array(
					'shadow' => '6px 6px 9px #00000000'
				),
				'color' => array(
					'background' => '#F4F4F4',
					'icon' => '#F7941D'
				)
			),
			'settings' => array(
				'hidden' => true
			)
		),
		'closeButton' => array(
			'styles' => array(
				'color' => array(
					'background' => '#d6d6d600',
					'icon' => '#d9d9d9'
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
						'url' => FOOCONVERT_ASSETS_URL . 'media/template__flyout__i-cafe.jpg'
					),
					'backgroundSize' => 'cover'
				),
				'border' => array(
					'radius' => '10px',
					'color' => '#F7941D',
					'style' => 'solid',
					'width' => '5px',
					'shadow' => '6px 6px 9px #00000000'
				),
				'dimensions' => array(
					'gap' => '32px',
					'padding' => array(
						'top' => '60px',
						'right' => '32px',
						'bottom' => '60px',
						'left' => '32px'
					)
				),
				'width' => '280px'
			)
		),
		'variation' => ''
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
								'content' => __( 'Get FREE Weekly Tips to help Grow Your business...', 'fooconvert' ),
								'dropCap' => false,
								'style' => array(
									'typography' => array(
										'fontSize' => '30px',
										'lineHeight' => '1.2'
									)
								),
								'fontFamily' => 'handlee',
								'align' => 'left'
							),
							array()
						),
						array(
							'core/paragraph',
							array(
								'align' => 'center',
                            'content' =>    __( 'Join 5,000+ readers getting exclusive content &amp; tools.', 'fooconvert' ),
								'dropCap' => false,
								'style' => array(
									'typography' => array(
										'fontSize' => '16px',
										'fontStyle' => 'italic',
										'fontWeight' => '400',
										'lineHeight' => '1.3'
									),
									'color' => array(
										'text' => '#7c7c7c'
									),
									'spacing' => array(
										'margin' => array(
											'top' => '-2px',
											'bottom' => '0',
											'right' => '0px'
										)
									)
								),
								'fontFamily' => 'system-font'
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
										'gap' => '8px',
										'margin' => array(
											'top' => '30px',
											'right' => '4px',
											'bottom' => '4px',
											'left' => '4px'
										)
									)
								),
								'settings' => array(
									'layout' => 'stack',
									'successMessage' => __( 'Thanks for joining!', 'fooconvert' ),
									'closeOnSuccess' => true
								),
								'inputs' => array(
									'settings' => array(
										'emailOnly' => true,
										'noLabels' => true,
										'emailPlaceholder' => __( 'Your email address', 'fooconvert' ),
										'stackLabels' => true
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
										'text' => __( 'Join The List', 'fooconvert' ),
										'justify' => 'flex-end',
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
											'background' => '#f7941d',
											'icon' => '#FFFFFF'
										),
										'typography' => array(
											'fontSize' => '1.2rem',
											'fontStyle' => 'normal',
											'fontWeight' => 700
										),
										'border' => array(
											'radius' => '10px',
											'color' => '#f7941d',
											'width' => '2px',
											'shadow' => '6px 6px 9px #00000000'
										)
									)
								)
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
