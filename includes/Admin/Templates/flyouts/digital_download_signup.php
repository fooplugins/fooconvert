<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name' => 'flyout__digital_download_signup',
	'title' => __( 'Digital Download Signup', 'fooconvert' ),
	'description' => __( 'Digital download signup themed flyout.', 'fooconvert' ),
	'thumbnail' => FOOCONVERT_ASSETS_URL . 'media/templates/template__digital_download_signup.png',
	'picker' => array(
		'category' => 'lead-capture',
		'tags' => array( 'email', 'download' ),
		'availability' => 'included',
		'preview' => FOOCONVERT_ASSETS_URL . 'media/templates/fullsize/template__digital_download_signup.png',
	),
	'attributes' => array(
		'template' => 'flyout__digital_download_signup',
		'viewState' => 'open',
		'settings' => array(
			'transitions' => true,
			'position' => 'right-bottom'
		),
		'openButton' => array(
			'styles' => array(
				'border' => array(
					'shadow' => '6px 6px 9px #00000000'
				),
				'color' => array(
					'background' => '#4f4c4c',
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
		'content' => array(
			'styles' => array(
				'color' => array(
					'text' => '#ffffff'
				),
				'background' => array(
					'backgroundImage' => array(
						'url' => FOOCONVERT_ASSETS_URL . 'media/template__flyout__digital.jpg',
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
				'width' => 'fit-content'
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
										'align' => 'center',
										'content' => 'Grow Your Traffic in 7 Days ',
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
									'core/paragraph',
									array(
										'align' => 'center',
										'content' => 'Download our FREE Guide!',
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
	'scope' => array(
		'block'
	)
);
