import { describe, expect, it } from 'vitest';

import {
	buildLoadPopupPath,
	normalizeLoadedPopupResponse,
} from './initial-popup-support';

describe( 'AI popup builder initial popup support', () => {
	it( 'builds the load endpoint path with a post id', () => {
		expect( buildLoadPopupPath( '/custom/load/', 42 ) ).toBe(
			'/custom/load/42'
		);

		expect( buildLoadPopupPath( '', 7 ) ).toBe(
			'/fooconvert/v1/ai-popup-builder/popup/7'
		);
	} );

	it( 'normalizes a loaded popup response into active builder state', () => {
		const loaded = normalizeLoadedPopupResponse( {
			postId: 123,
			title: 'Existing Published Popup',
			status: 'publish',
			popupType: 'bar',
			editUrl: 'https://example.test/wp-admin/post.php?post=123&action=edit',
			previewUrl: 'https://example.test/?fooconvert_popup_preview=123',
			draft: {
				title: 'Existing Published Popup',
				popup_type: 'bar',
				content_blocks: [
					{
						name: 'core/heading',
						attributes: { content: 'Save today' },
					},
				],
			},
			validation: { score: 88 },
			messages: [ { role: 'user', content: 'Make it sharper' } ],
			mediaItems: [ { id: 9, url: 'https://example.test/image.jpg' } ],
			suggestedPrompts: [ 'Shorten the heading', '' ],
			assistantMessage: 'Loaded prior AI context.',
		} );

		expect( loaded.draft.popup_type ).toBe( 'bar' );
		expect( loaded.savedPopup ).toMatchObject( {
			postId: 123,
			status: 'publish',
			popupType: 'bar',
			updatedExisting: true,
		} );
		expect( loaded.messages ).toEqual( [
			{ role: 'user', content: 'Make it sharper' },
			{ role: 'assistant', content: 'Loaded prior AI context.' },
		] );
		expect( loaded.lastResponse.popup_draft ).toEqual( loaded.draft );
		expect( loaded.mediaItems ).toEqual( [
			{ id: 9, url: 'https://example.test/image.jpg' },
		] );
		expect( loaded.suggestedPrompts ).toEqual( [
			'Shorten the heading',
		] );
	} );

	it( 'does not duplicate an assistant summary already in messages', () => {
		const loaded = normalizeLoadedPopupResponse( {
			postId: 5,
			draft: { title: 'Popup', popup_type: 'popup' },
			messages: [
				{ role: 'assistant', content: 'Already summarized.' },
			],
			assistantMessage: 'Already summarized.',
		} );

		expect( loaded.messages ).toEqual( [
			{ role: 'assistant', content: 'Already summarized.' },
		] );
	} );
} );
