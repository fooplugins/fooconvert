import { describe, expect, it, vi } from 'vitest';

import {
	buildRestApiUrl,
	createEventStreamParser,
	streamChatRequest,
} from './stream-support';

describe( 'AI popup builder stream support', () => {
	it( 'joins REST root and path without dropping the wp-json base', () => {
		expect(
			buildRestApiUrl(
				'https://example.test/wp-json/',
				'/fooconvert/v1/ai-popup-builder/chat-stream'
			)
		).toBe(
			'https://example.test/wp-json/fooconvert/v1/ai-popup-builder/chat-stream'
		);
	} );

	it( 'parses chunked SSE payloads into discrete events', () => {
		const onEvent = vi.fn();
		const parser = createEventStreamParser( onEvent );

		parser.push( 'event: activity\n' );
		parser.push( 'data: {"label":"Preparing popup context"}\n\n' );
		parser.push( 'event: assistant_delta\n' );
		parser.push( 'data: {"content":"Hel' );
		parser.push( 'lo"}\n\n' );
		parser.end();

		expect( onEvent ).toHaveBeenCalledTimes( 2 );
		expect( onEvent ).toHaveBeenNthCalledWith(
			1,
			expect.objectContaining( {
				event: 'activity',
				data: {
					label: 'Preparing popup context',
				},
			} )
		);
		expect( onEvent ).toHaveBeenNthCalledWith(
			2,
			expect.objectContaining( {
				event: 'assistant_delta',
				data: {
					content: 'Hello',
				},
			} )
		);
	} );

	it( 'supports multiline data payloads', () => {
		const onEvent = vi.fn();
		const parser = createEventStreamParser( onEvent );

		parser.push( 'event: note\n' );
		parser.push( 'data: first line\n' );
		parser.push( 'data: second line\n\n' );
		parser.end();

		expect( onEvent ).toHaveBeenCalledWith(
			expect.objectContaining( {
				event: 'note',
				data: 'first line\nsecond line',
			} )
		);
	} );

	it( 'reports raw stream chunks while resolving the final result', async () => {
		const chunks = [
			'event: assistant_delta\ndata: {"content":"Hel"}\n\n',
			'event: result\ndata: {"ok":true}\n\n',
		];
		const reader = {
			read: vi
				.fn()
				.mockResolvedValueOnce( {
					value: new TextEncoder().encode( chunks[ 0 ] ),
					done: false,
				} )
				.mockResolvedValueOnce( {
					value: new TextEncoder().encode( chunks[ 1 ] ),
					done: false,
				} )
				.mockResolvedValueOnce( { done: true } ),
		};
		const onChunk = vi.fn();

		vi.stubGlobal(
			'fetch',
			vi.fn().mockResolvedValue( {
				ok: true,
				body: {
					getReader: () => reader,
				},
			} )
		);

		await expect(
			streamChatRequest( {
				restRoot: 'https://example.test/wp-json/',
				path: '/fooconvert/v1/ai-popup-builder/chat-stream',
				payload: {},
				onChunk,
			} )
		).resolves.toEqual( { ok: true } );

		expect( onChunk ).toHaveBeenCalledTimes( 2 );
		expect( onChunk ).toHaveBeenNthCalledWith( 1, chunks[ 0 ] );
		expect( onChunk ).toHaveBeenNthCalledWith( 2, chunks[ 1 ] );
	} );
} );
