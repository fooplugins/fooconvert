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

	it( 'applies a generated image as the popup background', () => {
		const draft = {
			popup_type: 'popup',
			root_attributes: {},
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

		expect(
			nextDraft.root_attributes.content.styles.background.backgroundImage
		).toEqual( {
			id: 42,
			url: mediaItem.url,
			source: 'file',
			title: 'Launch Weekend Image',
		} );
		expect(
			nextDraft.root_attributes.content.styles.background.backgroundSize
		).toBe( 'cover' );
		expect(
			nextDraft.content_blocks.some(
				( block ) => block.name === 'core/image'
			)
		).toBe( false );
	} );

	it( 'preserves existing background settings while replacing the image', () => {
		const draft = {
			popup_type: 'popup',
			root_attributes: {
				content: {
					styles: {
						background: {
							backgroundImage: {
								id: 10,
								url: 'https://example.test/old.jpg',
							},
							backgroundPosition: '50% 50%',
							backgroundSize: 'contain',
						},
					},
				},
			},
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

		expect(
			nextDraft.root_attributes.content.styles.background.backgroundImage
				.id
		).toBe( 42 );
		expect(
			nextDraft.root_attributes.content.styles.background.backgroundImage
				.url
		).toBe( mediaItem.url );
		expect(
			nextDraft.root_attributes.content.styles.background
				.backgroundPosition
		).toBe( '50% 50%' );
		expect(
			nextDraft.root_attributes.content.styles.background.backgroundSize
		).toBe( 'cover' );
		expect( nextDraft.content_blocks[ 0 ].attributes.id ).toBe( 10 );
	} );

	it( 'clears a matching generated background image by attachment ID', () => {
		const draft = {
			popup_type: 'popup',
			root_attributes: {
				content: {
					styles: {
						background: {
							backgroundImage: {
								id: 42,
								url: mediaItem.url,
							},
							backgroundSize: 'cover',
							backgroundPosition: 'center center',
						},
					},
				},
			},
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

		expect(
			nextDraft.root_attributes.content.styles.background.backgroundImage
		).toBeUndefined();
		expect(
			nextDraft.root_attributes.content.styles.background.backgroundSize
		).toBeUndefined();
		expect(
			nextDraft.root_attributes.content.styles.background
				.backgroundPosition
		).toBe( 'center center' );
		expect( nextDraft.content_blocks[ 0 ].inner_blocks ).toHaveLength( 1 );
		expect( nextDraft.content_blocks[ 0 ].inner_blocks[ 0 ].name ).toBe(
			'core/paragraph'
		);
	} );
} );
