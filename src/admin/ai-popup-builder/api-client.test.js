import { beforeEach, describe, expect, it, vi } from 'vitest';

const apiFetchMock = vi.hoisted( () => {
	const mock = vi.fn();
	mock.use = vi.fn();
	mock.createRootURLMiddleware = vi.fn( ( root ) => ( {
		type: 'root',
		root,
	} ) );
	mock.createNonceMiddleware = vi.fn( ( nonce ) => ( {
		type: 'nonce',
		nonce,
	} ) );
	return mock;
} );

vi.mock( '@wordpress/api-fetch', () => ( {
	default: apiFetchMock,
} ) );

describe( 'AI popup builder API client', () => {
	beforeEach( () => {
		vi.resetModules();
		apiFetchMock.mockClear();
		apiFetchMock.use.mockClear();
		apiFetchMock.createRootURLMiddleware.mockClear();
		apiFetchMock.createNonceMiddleware.mockClear();
		delete window.FC_AI_POPUP_BUILDER;
		delete window.FC_AI_POPUP_BUILDER_API_FETCH_READY;
		delete window.wpApiSettings;
	} );

	it( 'does not mark api fetch ready when no REST config exists', async () => {
		const { setupApiFetch } = await import( './api-client' );

		expect( setupApiFetch() ).toBe( false );
		expect( apiFetchMock.use ).not.toHaveBeenCalled();
		expect( window.FC_AI_POPUP_BUILDER_API_FETCH_READY ).toBeUndefined();
	} );

	it( 'can retry setup after REST config becomes available', async () => {
		const { setupApiFetch } = await import( './api-client' );

		expect( setupApiFetch() ).toBe( false );

		window.FC_AI_POPUP_BUILDER = {
			restRoot: 'https://example.test/wp-json/',
			restNonce: 'abc123',
		};

		expect( setupApiFetch() ).toBe( true );
		expect( apiFetchMock.createRootURLMiddleware ).toHaveBeenCalledWith(
			'https://example.test/wp-json/'
		);
		expect( apiFetchMock.createNonceMiddleware ).toHaveBeenCalledWith(
			'abc123'
		);
		expect( apiFetchMock.use ).toHaveBeenCalledTimes( 2 );
		expect( window.FC_AI_POPUP_BUILDER_API_FETCH_READY ).toEqual( {
			restRoot: 'https://example.test/wp-json/',
			restNonce: 'abc123',
		} );
	} );

	it( 'falls back to wpApiSettings', async () => {
		window.wpApiSettings = {
			root: 'https://example.test/wp-json/',
			nonce: 'from-wp',
		};

		const { setupApiFetch } = await import( './api-client' );

		expect( setupApiFetch() ).toBe( true );
		expect( apiFetchMock.createRootURLMiddleware ).toHaveBeenCalledWith(
			'https://example.test/wp-json/'
		);
		expect( apiFetchMock.createNonceMiddleware ).toHaveBeenCalledWith(
			'from-wp'
		);
	} );
} );
