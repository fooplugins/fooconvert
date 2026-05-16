import { describe, expect, it } from 'vitest';

import {
	buildRootAttributes,
	extractListItems,
	fooconvertBlockMetadata,
	normalizeDraftBlockAttributes,
	resolveTriggerEvent,
	supportedTriggerEvents,
} from './serializer-support';

describe( 'AI popup builder serializer support', () => {
	it( 'uses the real popup block metadata for custom blocks', () => {
		const popupBlock = fooconvertBlockMetadata.find(
			( metadata ) => metadata.name === 'fc/overlay'
		);
		const signUpBlock = fooconvertBlockMetadata.find(
			( metadata ) => metadata.name === 'fc/sign-up'
		);

		expect( popupBlock.apiVersion ).toBe( 3 );
		expect( popupBlock.attributes ).toHaveProperty( 'template' );
		expect( popupBlock.attributes ).toHaveProperty( 'settings' );
		expect( popupBlock.attributes ).toHaveProperty( 'content' );

		expect( signUpBlock.apiVersion ).toBe( 3 );
		expect( signUpBlock.attributes ).toHaveProperty( 'settings' );
		expect( signUpBlock.attributes ).toHaveProperty( 'inputs' );
		expect( signUpBlock.attributes ).toHaveProperty( 'button' );
	} );

	it( 'normalizes list items from both structured items and legacy HTML values', () => {
		expect(
			extractListItems( {
				items: [
					'Free shipping on every order',
					'Early access to new drops',
				],
			} )
		).toEqual( [
			'Free shipping on every order',
			'Early access to new drops',
		] );

		expect(
			extractListItems( {
				values: [
					'Claim your first-order discount',
					'Get launch updates by email',
				],
			} )
		).toEqual( [
			'Claim your first-order discount',
			'Get launch updates by email',
		] );

		expect(
			extractListItems( {
				values: '<li>Claim your first-order discount</li><li>Get launch updates by email</li>',
			} )
		).toEqual( [
			'Claim your first-order discount',
			'Get launch updates by email',
		] );
	} );

	it( "maps shorthand sign-up aliases into the block's nested attribute shape", () => {
		expect(
			normalizeDraftBlockAttributes( 'fc/sign-up', {
				buttonText: 'Unlock 15% Off',
				successMessage: 'Code sent!',
				closeOnSuccess: true,
				emailOnly: true,
				emailPlaceholder: 'Enter your email',
			} )
		).toMatchObject( {
			settings: {
				successMessage: 'Code sent!',
				closeOnSuccess: true,
			},
			inputs: {
				settings: {
					emailOnly: true,
					emailPlaceholder: 'Enter your email',
				},
			},
			button: {
				settings: {
					text: 'Unlock 15% Off',
				},
			},
		} );
	} );

	it( 'maps legacy image aliases into the core/image attribute shape', () => {
		expect(
			normalizeDraftBlockAttributes( 'core/image', {
				src: 'https://example.test/generated.jpg',
				mediaId: 55,
				altText: 'Generated popup image',
			} )
		).toMatchObject( {
			url: 'https://example.test/generated.jpg',
			id: 55,
			alt: 'Generated popup image',
		} );
	} );

	it( 'normalizes core text alignment for saved block markup', () => {
		const paragraphAttributes = normalizeDraftBlockAttributes(
			'core/paragraph',
			{
				align: 'center',
				textAlign: 'right',
				style: {
					typography: {
						fontSize: '12px',
					},
				},
			}
		);

		expect( paragraphAttributes ).toMatchObject( {
			align: 'center',
			style: {
				typography: {
					fontSize: '12px',
					textAlign: 'center',
				},
			},
		} );
		expect( paragraphAttributes ).not.toHaveProperty( 'textAlign' );

		const headingAttributes = normalizeDraftBlockAttributes(
			'core/heading',
			{
				align: 'center',
				style: {
					typography: {
						fontSize: '32px',
					},
				},
			}
		);

		expect( headingAttributes ).toMatchObject( {
			textAlign: 'center',
			style: {
				typography: {
					fontSize: '32px',
					textAlign: 'center',
				},
			},
		} );
		expect( headingAttributes ).not.toHaveProperty( 'align' );
	} );

	it( 'builds conversion-ready root attributes for popup and flyout drafts', () => {
		const popupAttributes = buildRootAttributes(
			{
				popup_type: 'popup',
				template_slug: 'popup__newsletter_subscribe',
				trigger: {
					type: 'exit_intent',
					delay_seconds: 7,
					scroll_percent: 20,
					lifetime: 'page',
					frequency: 'once',
				},
				root_attributes: {
					content: {
						styles: {
							width: '720px',
						},
					},
				},
			},
			{}
		);

		expect( popupAttributes.template ).toBe(
			'popup__newsletter_subscribe'
		);
		expect( popupAttributes.settings.trigger.steps[ 0 ].event ).toBe(
			'fc.exit_intent'
		);
		expect(
			popupAttributes.settings.trigger.steps[ 0 ].where.delaySeconds
		).toBe( 7 );
		expect( popupAttributes.content.styles.width ).toBe( '720px' );
		expect( popupAttributes.openButton ).toBeUndefined();

		const flyoutAttributes = buildRootAttributes(
			{
				popup_type: 'flyout',
				template_slug: '',
				trigger: {
					type: 'scroll_percent',
					delay_seconds: 0,
					scroll_percent: 35,
					lifetime: 'session',
					frequency: 'repeat',
				},
				root_attributes: {},
			},
			{}
		);

		expect( flyoutAttributes.viewState ).toBe( 'open' );
		expect( flyoutAttributes.openButton.settings.hidden ).toBe( true );
		expect( flyoutAttributes.settings.trigger.steps[ 0 ].event ).toBe(
			'fc.scroll.percent'
		);
		expect(
			flyoutAttributes.settings.trigger.steps[ 0 ].where.percent
		).toBe( 35 );
	} );

	it( 'preserves advanced Pro trigger events from AI drafts', () => {
		expect( supportedTriggerEvents ).toContain( 'cart.add' );
		expect( supportedTriggerEvents ).toContain( 'product.high_intent' );
		expect( resolveTriggerEvent( { type: 'delay' }, 'popup' ) ).toBe(
			'fc.timer.elapsed'
		);

		const cartAttributes = buildRootAttributes(
			{
				popup_type: 'flyout',
				template_slug: '',
				trigger: {
					type: 'cart.add',
					event: 'cart.add',
					where: {
						productIds: [ 42 ],
					},
					lifetime: 'session',
					frequency: 'repeat',
				},
				root_attributes: {},
			},
			{}
		);

		expect( cartAttributes.settings.trigger.steps[ 0 ].event ).toBe(
			'cart.add'
		);
		expect(
			cartAttributes.settings.trigger.steps[ 0 ].where.productIds
		).toEqual( [ 42 ] );
		expect( cartAttributes.settings.trigger.frequency.mode ).toBe(
			'repeat'
		);

		const productIntentAttributes = buildRootAttributes(
			{
				popup_type: 'popup',
				template_slug: '',
				trigger: {
					type: 'product.high_intent',
					where: {
						productIds: [ 15 ],
						scrollPercent: 70,
						timeSeconds: 30,
						viewCount: 2,
					},
					lifetime: 'page',
					frequency: 'once',
				},
				root_attributes: {},
			},
			{}
		);

		expect(
			productIntentAttributes.settings.trigger.steps[ 0 ].event
		).toBe( 'product.high_intent' );
		expect(
			productIntentAttributes.settings.trigger.steps[ 0 ].where
				.scrollPercent
		).toBe( 70 );
		expect(
			productIntentAttributes.settings.trigger.steps[ 0 ].where
				.timeSeconds
		).toBe( 30 );
		expect(
			productIntentAttributes.settings.trigger.steps[ 0 ].where.viewCount
		).toBe( 2 );

		const cartIdleAttributes = buildRootAttributes(
			{
				popup_type: 'popup',
				template_slug: '',
				trigger: {
					type: 'cart.idle',
					delay_seconds: 90,
					lifetime: 'session',
					frequency: 'once',
				},
				root_attributes: {},
			},
			{}
		);

		expect( cartIdleAttributes.settings.trigger.steps[ 0 ].event ).toBe(
			'cart.idle'
		);
		expect(
			cartIdleAttributes.settings.trigger.steps[ 0 ].where.delaySeconds
		).toBe( 90 );
	} );
} );
