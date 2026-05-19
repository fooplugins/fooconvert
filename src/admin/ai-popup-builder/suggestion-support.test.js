import { describe, expect, it } from 'vitest';

import {
	getSuggestionPromptLibrary,
	getSuggestionPrompts,
} from './suggestion-support';

const selectedBlockNames = new Set( [
	'core/heading',
	'core/paragraph',
	'fc/countdown',
	'fc/coupon',
] );

const suggestionLibrary = [
	{
		text: 'Create an exit-intent overlay popup for first-time shoppers offering 15% off. Use the free FooConvert Coupon block (fc/coupon) with code WELCOME15 and the Countdown block (fc/countdown) set to expire in 2 hours. Add a confident headline, one reassurance line, and a clear shop-now CTA. Use a warm product-lifestyle background with soft depth, or follow my branding if brand styles are available.',
		tags: [ 'Create', 'Popup', 'Discount' ],
		phase: 'initial',
	},
	{
		text: 'Create a newsletter signup overlay for returning readers that offers weekly tips and a practical downloadable guide. Use the free FooConvert Sign Up block (fc/sign-up) as an email-only form with friendly placeholders and button copy like "Send me the guide". Use the Split Layout block (fc/split-layout) with copy on one side and a calm editorial background image or brand-colored panel on the other.',
		tags: [ 'Create', 'Popup', 'Signup' ],
		phase: 'initial',
	},
	{
		text: 'Create a compact product-launch bar for mobile visitors announcing a new collection. Use the free FooConvert Countdown block (fc/countdown) for launch urgency, concise headline copy, and one clear CTA. Keep the layout short enough for a bar and use a bold background color from my branding, or a dark high-contrast product background if no brand profile is available.',
		tags: [ 'Create', 'Bar', 'Launch' ],
		phase: 'initial',
	},
	{
		text: 'Add a countdown timer for 2 hours in the future.',
		tags: [ 'Countdown', 'Urgency' ],
		phase: 'edit',
		requiredBlocks: [ 'fc/countdown' ],
	},
	{
		text: 'Change the popup styling to use my branding.',
		tags: [ 'Brand', 'Style' ],
		phase: 'edit',
	},
	{
		text: 'Change this popup to be a bar and shorten all the wording used.',
		tags: [ 'Bar', 'Shorten' ],
		phase: 'edit',
		excludePopupTypes: [ 'bar' ],
	},
	{
		text: 'Convert this popup to a flyout with a softer tone and one clear CTA.',
		tags: [ 'Flyout', 'Tone' ],
		phase: 'edit',
		excludePopupTypes: [ 'flyout' ],
	},
	{
		text: 'Make the CTA button copy more specific and urgent.',
		tags: [ 'Copy', 'CTA' ],
		phase: 'edit',
	},
	{
		text: 'Add a coupon code block for SAVE15.',
		tags: [ 'Coupon', 'Discount' ],
		phase: 'edit',
		requiredBlocks: [ 'fc/coupon' ],
	},
	{
		text: 'Add an apply-coupon button for SAVE15.',
		tags: [ 'Coupon', 'WooCommerce' ],
		phase: 'edit',
		requiredBlocks: [ 'fc/apply-coupon' ],
	},
	{
		text: 'Add free shipping progress for cart shoppers.',
		tags: [ 'Shipping', 'WooCommerce' ],
		phase: 'edit',
		requiredBlocks: [ 'fc/free-shipping-progress' ],
	},
	{
		text: 'Remove extra copy and make the layout easier to scan.',
		tags: [ 'Copy', 'Layout' ],
		phase: 'edit',
	},
	{
		text: 'Rewrite this for mobile visitors with shorter lines.',
		tags: [ 'Mobile', 'Copy' ],
		phase: 'edit',
	},
	{
		text: 'Add social proof using a short testimonial-style paragraph.',
		tags: [ 'Proof', 'Copy' ],
		phase: 'edit',
	},
	{
		text: 'Replace the offer with 10% off a first order.',
		tags: [ 'Offer', 'Discount' ],
		phase: 'edit',
	},
	{
		text: 'Generate a new background image that matches this offer.',
		tags: [ 'Image', 'Style' ],
		phase: 'edit',
		requiresImageGeneration: true,
	},
	{
		text: 'Change the trigger to fire after 50% scroll.',
		tags: [ 'Trigger', 'Scroll' ],
		phase: 'edit',
	},
	{
		text: 'Add urgency without making the copy sound pushy.',
		tags: [ 'Urgency', 'Tone' ],
		phase: 'edit',
	},
];

describe( 'AI popup builder suggestions', () => {
	it( 'normalizes configured tagged suggestions', () => {
		const prompts = getSuggestionPromptLibrary( {
			suggestionLibrary,
		} );

		expect( prompts ).toHaveLength( 18 );
		expect(
			prompts.every(
				( prompt ) =>
					typeof prompt.text === 'string' &&
					prompt.text.length > 0 &&
					Array.isArray( prompt.tags ) &&
					prompt.tags.length > 0
			)
		).toBe( true );
	} );

	it( 'shows three initial creation suggestions before a draft exists', () => {
		const prompts = getSuggestionPrompts( {
			suggestionLibrary,
			selectedBlockNames,
			limit: 5,
		} );

		expect( prompts ).toHaveLength( 3 );
		expect( prompts.every( ( prompt ) => prompt.phase === 'initial' ) ).toBe(
			true
		);
	} );

	it( 'keeps initial creation suggestions specific to free popup blocks and background direction', () => {
		const prompts = getSuggestionPrompts( {
			suggestionLibrary,
			selectedBlockNames,
			limit: 5,
		} );

		expect(
			prompts.every( ( prompt ) =>
				/fc\/(?:coupon|countdown|sign-up|split-layout)/.test(
					prompt.text
				)
			)
		).toBe( true );
		expect(
			prompts.every( ( prompt ) =>
				/(background|branding)/i.test( prompt.text )
			)
		).toBe( true );
		expect(
			prompts.some( ( prompt ) =>
				/(fc\/(?:apply-coupon|free-shipping-progress)|cart recovery|free-shipping flyout)/i.test(
					prompt.text
				)
			)
		).toBe( false );
	} );

	it( 'uses configured starter prompts when an extension supplies them', () => {
		const prompts = getSuggestionPrompts( {
			suggestionLibrary,
			selectedBlockNames,
			starterPrompts: [
				'Create a Pro cart recovery popup with fc/apply-coupon and fc/free-shipping-progress.',
				'',
			],
			limit: 5,
		} );

		expect( prompts ).toHaveLength( 1 );
		expect( prompts[ 0 ] ).toMatchObject( {
			text: 'Create a Pro cart recovery popup with fc/apply-coupon and fc/free-shipping-progress.',
			tags: [ 'Create' ],
			phase: 'initial',
		} );
	} );

	it( 'shows edit suggestions after a draft exists', () => {
		const prompts = getSuggestionPrompts( {
			suggestionLibrary,
			draft: {
				popup_type: 'popup',
			},
			selectedBlockNames,
			limit: 5,
		} );

		expect( prompts ).toHaveLength( 5 );
		expect( prompts.every( ( prompt ) => prompt.phase === 'edit' ) ).toBe(
			true
		);
		expect( prompts.map( ( prompt ) => prompt.text ) ).toContain(
			'Add a countdown timer for 2 hours in the future.'
		);
	} );

	it( 'hides suggestions that are invalid for the current popup context', () => {
		const prompts = getSuggestionPrompts( {
			suggestionLibrary,
			draft: {
				popup_type: 'bar',
			},
			selectedBlockNames: new Set( [ 'core/heading' ] ),
			imageGenerationAvailable: false,
			limit: 20,
		} );
		const promptText = prompts.map( ( prompt ) => prompt.text );

		expect( promptText ).not.toContain(
			'Change this popup to be a bar and shorten all the wording used.'
		);
		expect( promptText ).not.toContain(
			'Add a countdown timer for 2 hours in the future.'
		);
		expect( promptText ).not.toContain(
			'Generate a new background image that matches this offer.'
		);
	} );
} );
