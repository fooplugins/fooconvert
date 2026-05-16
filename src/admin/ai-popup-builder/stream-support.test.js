import { describe, expect, it, vi } from 'vitest';

import { buildRestApiUrl, createEventStreamParser } from './stream-support';

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
} );
