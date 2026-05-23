import { describe, expect, it } from 'vitest';

import {
	buildAiSettingsPayload,
	getDefaultSelectedBlockNames,
	normalizeAiSettings,
	normalizeDisabledParams,
	sanitizeSelectedBlockNames,
} from './settings-support';

const blockCatalog = [
	{ name: 'core/paragraph' },
	{ name: 'fc/sign-up' },
	{ name: 'woocommerce/cart' },
	{ name: 'woocommerce/product-template' },
];

describe( 'AI popup builder settings support', () => {
	it( 'normalizes disabled params from text and aliases', () => {
		expect(
			normalizeDisabledParams(
				'temperature, responseFormat\nresponse-format\ntools\nfunctionDeclarations\noutputMimeType'
			)
		).toEqual( [ 'temperature', 'output_mime_type' ] );
	} );

	it( 'selects default blocks without broad WooCommerce blocks', () => {
		expect( getDefaultSelectedBlockNames( blockCatalog ) ).toEqual( [
			'core/paragraph',
			'fc/sign-up',
			'woocommerce/cart',
		] );
	} );

	it( 'sanitizes selected block names against the catalog', () => {
		expect(
			sanitizeSelectedBlockNames(
				[ 'fc/sign-up', 'missing/block' ],
				blockCatalog
			)
		).toEqual( [ 'fc/sign-up' ] );

		expect( sanitizeSelectedBlockNames( [], blockCatalog, false ) ).toEqual(
			[]
		);
	} );

	it( 'builds the saved settings payload', () => {
		expect(
			buildAiSettingsPayload(
				normalizeAiSettings(
					{
						overrideModel: '  gpt-test  ',
						overrideImageModel: '  gpt-image-test  ',
						disabledParamsText: 'temperature',
						optimizeImageOutput: false,
						timeout: '20',
						maxToolCalls: '7',
						selectedBlockNames: [ 'fc/sign-up' ],
					},
					blockCatalog
				),
				blockCatalog
			)
		).toEqual( {
			overrideModel: 'gpt-test',
			overrideImageModel: 'gpt-image-test',
			optimizeImageOutput: false,
			disabledParams: [ 'temperature' ],
			disabledParamsText: 'temperature',
			timeout: 20,
			maxToolCalls: 7,
			selectedBlockNames: [ 'fc/sign-up' ],
		} );
	} );

	it( 'defaults generated image optimization on', () => {
		expect(
			normalizeAiSettings( {}, blockCatalog ).optimizeImageOutput
		).toBe( true );
	} );
} );
