<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name' => 'bar__digital_download_signup',
	'title' => __( 'Digital Download Signup', 'fooconvert' ),
	'description' => __( 'Digital download signup lead magnet bar.', 'fooconvert' ),
	'thumbnail'   => FOOCONVERT_ASSETS_URL . 'media/templates/template__digital_download_signup.png',
	'picker' => array(
		'category' => 'lead-capture',
		'tags' => array( 'email', 'download' ),
		'availability' => 'included',
		'preview' => FOOCONVERT_ASSETS_URL . 'media/templates/preview/preview-bar-digitial-download-signup.webp',
	),
	'attributes' => array(
		'viewState' => 'open',
		'template' => 'bar__digital_download_signup',
		'settings' => array(
			'transitions' => true,
			'maxWidth' => '800px'
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
						'url' => FOOCONVERT_ASSETS_URL . 'media/template__bar__digital.jpg'
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
					'gap' => '32px',
					'padding' => '32px'
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
										'content' => __( 'Grow Your Traffic in 7 Days – Download our FREE Guide!', 'fooconvert' ),
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
										'settings' => array(
											'closeOnSuccess' => true,
											'successMessage' => __( 'Thanks! Please check your inbox.', 'fooconvert' )
										),
										'inputs' => array(
											'settings' => array(
												'emailOnly' => true,
												'stackLabels' => false,
												'noLabels' => true,
												'emailPlaceholder' => __( 'Enter your email...', 'fooconvert' )
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
												'text' => __( 'Download', 'fooconvert' )
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
										),
										'styles' => array(
											'color' => array(
												'background' => '#ffffff00'
											),
											'dimensions' => array(
												'gap' => '0px'
											)
										)
									),
									array()
								),
								array(
									'core/paragraph',
									array(
										'content' => __( 'Proven tips used by 7,000+ brands to boost traffic!', 'fooconvert' ),
										'dropCap' => false,
										'fontSize' => 'medium',
										'style' => array(
											'elements' => array(
												'link' => array(
													'color' => array(
														'text' => 'var:preset|color|base'
													)
												)
											)
										),
										'textColor' => 'base'
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
