import { describe, expect, it } from 'vitest';

import {
	applyMediaItemToDraft,
	removeMediaItemFromDraft,
} from './media-support';

describe( 'AI popup builder media support', () => {
	const mediaItem = {
		id: 42,
		url: 'https://example.test/generated-popup-image.jpg',
		alt: 'Launch weekend popup visual',
		title: 'Launch Weekend Image',
	};

	it( 'inserts a generated image before the primary action when no image exists yet', () => {
		const draft = {
			popup_type: 'popup',
			content_blocks: [
				{
					name: 'core/heading',
					attributes: {
						content: 'Launch Weekend Offer',
					},
				},
				{
					name: 'core/paragraph',
					attributes: {
						content: 'Join the list for 15% off.',
					},
				},
				{
					name: 'fc/sign-up',
					attributes: {},
				},
			],
		};

		const nextDraft = applyMediaItemToDraft( draft, mediaItem );

		expect( nextDraft.content_blocks[ 2 ].name ).toBe( 'core/image' );
		expect( nextDraft.content_blocks[ 2 ].attributes.id ).toBe( 42 );
		expect( nextDraft.content_blocks[ 2 ].attributes.url ).toBe(
			mediaItem.url
		);
		expect( nextDraft.content_blocks[ 3 ].name ).toBe( 'fc/sign-up' );
	} );

	it( 'replaces the first existing image block when the draft already contains one', () => {
		const draft = {
			popup_type: 'popup',
			content_blocks: [
				{
					name: 'core/image',
					attributes: {
						id: 10,
						url: 'https://example.test/old.jpg',
					},
				},
				{
					name: 'core/button',
					attributes: {
						text: 'Claim Offer',
					},
				},
			],
		};

		const nextDraft = applyMediaItemToDraft( draft, mediaItem );

		expect( nextDraft.content_blocks[ 0 ].attributes.id ).toBe( 42 );
		expect( nextDraft.content_blocks[ 0 ].attributes.url ).toBe(
			mediaItem.url
		);
	} );

	it( 'removes matching generated image blocks by attachment ID', () => {
		const draft = {
			popup_type: 'popup',
			content_blocks: [
				{
					name: 'core/group',
					inner_blocks: [
						{
							name: 'core/image',
							attributes: {
								id: 42,
								url: mediaItem.url,
							},
						},
						{
							name: 'core/paragraph',
							attributes: {
								content: 'Support copy',
							},
						},
					],
				},
			],
		};

		const nextDraft = removeMediaItemFromDraft( draft, mediaItem );

		expect( nextDraft.content_blocks[ 0 ].inner_blocks ).toHaveLength( 1 );
		expect( nextDraft.content_blocks[ 0 ].inner_blocks[ 0 ].name ).toBe(
			'core/paragraph'
		);
	} );
} );
