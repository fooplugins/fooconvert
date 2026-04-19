<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name' => 'bar__newsletter_subscribe',
	'title' => __( 'Newsletter Subscribe', 'fooconvert' ),
	'description' => __( 'Newsletter Subscribe marketing themed bar.', 'fooconvert' ),
	'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__newsletter_subscribe.png',
	'picker' => array(
		'category' => 'lead-capture',
		'tags' => array( 'newsletter', 'email' ),
		'availability' => 'included',
		'preview' => FOOCONVERT_ASSETS_URL . 'media/templates/preview/preview-bar-newsletter-subscribe.webp',
	),
	'attributes' => array(
		'viewState' => 'open',
		'template' => 'bar__newsletter_subscribe',
		'settings' => array(
			'transitions' => true,
			'maxWidth' => '800px',
			'trigger' => array(
				'type' => 'scroll',
				'data' => 50,
				'once' => true
			)
		),
		'styles' => array(
			'dimensions' => array(
				'padding' => array(
					'top' => '20px',
					'right' => '24px',
					'bottom' => '20px',
					'left' => '24px'
				)
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
					'icon' => '#ffffff'
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
						'url' => FOOCONVERT_ASSETS_URL . 'media/template__bar__i-cafe.jpg'
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
						'top' => '48px',
						'right' => '32px',
						'bottom' => '48px',
						'left' => '32px'
					)
				)
			)
		),
		'variation' => ''
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
								'style' => array(
									'spacing' => array(
										'blockGap' => '50px'
									)
								),
								'layout' => array(
									'type' => 'flex',
									'flexWrap' => 'nowrap'
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
											'justifyContent' => 'left',
											'flexWrap' => 'wrap'
										)
									),
									array(
										array(
											'core/paragraph',
											array(
												'content' => __( 'Get FREE Weekly Tips to help Grow Your business...', 'fooconvert' ),
												'dropCap' => false,
												'style' => array(
													'typography' => array(
														'fontSize' => '30px'
													)
												),
												'fontFamily' => 'handlee'
											),
											array()
										),
										array(
											'core/spacer',
											array(
												'height' => '23px',
												'style' => array(
													'layout' => array(
														'flexSize' => '23px',
														'selfStretch' => 'fixed'
													)
												)
											),
											array()
										),
										array(
											'core/paragraph',
											array(
												'align' => 'left',
												'content' => __( 'Join 5,000+ readers getting exclusive content &amp; tools.', 'fooconvert' ),
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
								),
								array(
									'fc/sign-up',
									array(
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
										'settings' => array(
											'layout' => 'stack',
											'successMessage' => __( 'Thanks for joining!', 'fooconvert' ),
											'closeOnSuccess' => true
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
										),
										'styles' => array(
											'typography' => array(
												'fontFamily' => 'Montserrat'
											)
										)
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
