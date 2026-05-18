import { __ } from '@wordpress/i18n';

const normalizeText = ( value ) => String( value || '' ).trim();

const normalizePopupType = ( value ) => {
	const popupType = normalizeText( value );

	if ( popupType === 'overlay' ) {
		return 'popup';
	}

	return popupType;
};

const normalizeBlockNameSet = ( value ) => {
	if ( value instanceof Set ) {
		return value;
	}

	return new Set(
		Array.isArray( value ) ? value.map( normalizeText ).filter( Boolean ) : []
	);
};

const suggestionLibrary = [
	{
		text: __(
			'Create an exit-intent popup for first-time shoppers with 15% off and a confident headline.',
			'fooconvert'
		),
		tags: [ 'Create', 'Popup', 'Discount' ],
		phase: 'initial',
	},
	{
		text: __(
			'Build a free shipping flyout for active cart shoppers with a helpful, low-pressure tone.',
			'fooconvert'
		),
		tags: [ 'Create', 'Flyout', 'Cart' ],
		phase: 'initial',
	},
	{
		text: __(
			'Create a newsletter signup popup for returning readers that offers weekly tips.',
			'fooconvert'
		),
		tags: [ 'Create', 'Popup', 'Signup' ],
		phase: 'initial',
	},
	{
		text: __(
			'Create a product launch bar for mobile visitors with concise copy and one clear CTA.',
			'fooconvert'
		),
		tags: [ 'Create', 'Bar', 'Launch' ],
		phase: 'initial',
	},
	{
		text: __(
			'Create a cart recovery popup with a coupon, urgency, and a short reassurance line.',
			'fooconvert'
		),
		tags: [ 'Create', 'Popup', 'Coupon' ],
		phase: 'initial',
	},
	{
		text: __(
			'Add a countdown timer for 2 hours in the future.',
			'fooconvert'
		),
		tags: [ 'Countdown', 'Urgency' ],
		phase: 'edit',
		requiredBlocks: [ 'fc/countdown' ],
	},
	{
		text: __(
			'Change the popup styling to use my branding.',
			'fooconvert'
		),
		tags: [ 'Brand', 'Style' ],
		phase: 'edit',
	},
	{
		text: __(
			'Change this popup to be a bar and shorten all the wording used.',
			'fooconvert'
		),
		tags: [ 'Bar', 'Shorten' ],
		phase: 'edit',
		excludePopupTypes: [ 'bar' ],
	},
	{
		text: __(
			'Convert this popup to a flyout with a softer tone and one clear CTA.',
			'fooconvert'
		),
		tags: [ 'Flyout', 'Tone' ],
		phase: 'edit',
		excludePopupTypes: [ 'flyout' ],
	},
	{
		text: __(
			'Make the CTA button copy more specific and urgent.',
			'fooconvert'
		),
		tags: [ 'Copy', 'CTA' ],
		phase: 'edit',
	},
	{
		text: __( 'Add a coupon code block for SAVE15.', 'fooconvert' ),
		tags: [ 'Coupon', 'Discount' ],
		phase: 'edit',
		requiredBlocks: [ 'fc/coupon' ],
	},
	{
		text: __( 'Add an apply-coupon button for SAVE15.', 'fooconvert' ),
		tags: [ 'Coupon', 'WooCommerce' ],
		phase: 'edit',
		requiredBlocks: [ 'fc/apply-coupon' ],
	},
	{
		text: __(
			'Add free shipping progress for cart shoppers.',
			'fooconvert'
		),
		tags: [ 'Shipping', 'WooCommerce' ],
		phase: 'edit',
		requiredBlocks: [ 'fc/free-shipping-progress' ],
	},
	{
		text: __(
			'Remove extra copy and make the layout easier to scan.',
			'fooconvert'
		),
		tags: [ 'Copy', 'Layout' ],
		phase: 'edit',
	},
	{
		text: __(
			'Rewrite this for mobile visitors with shorter lines.',
			'fooconvert'
		),
		tags: [ 'Mobile', 'Copy' ],
		phase: 'edit',
	},
	{
		text: __(
			'Add social proof using a short testimonial-style paragraph.',
			'fooconvert'
		),
		tags: [ 'Proof', 'Copy' ],
		phase: 'edit',
	},
	{
		text: __(
			'Replace the offer with 10% off a first order.',
			'fooconvert'
		),
		tags: [ 'Offer', 'Discount' ],
		phase: 'edit',
	},
	{
		text: __(
			'Generate a new background image that matches this offer.',
			'fooconvert'
		),
		tags: [ 'Image', 'Style' ],
		phase: 'edit',
		requiresImageGeneration: true,
	},
	{
		text: __(
			'Change the trigger to fire after 50% scroll.',
			'fooconvert'
		),
		tags: [ 'Trigger', 'Scroll' ],
		phase: 'edit',
	},
	{
		text: __(
			'Add urgency without making the copy sound pushy.',
			'fooconvert'
		),
		tags: [ 'Urgency', 'Tone' ],
		phase: 'edit',
	},
];

const isSuggestionValid = ( suggestion, options ) => {
	const hasDraft = Boolean( options?.draft );
	const popupType = normalizePopupType( options?.draft?.popup_type );
	const selectedBlockNames = normalizeBlockNameSet(
		options?.selectedBlockNames
	);

	if ( suggestion.phase === 'initial' && hasDraft ) {
		return false;
	}

	if ( suggestion.phase === 'edit' && ! hasDraft ) {
		return false;
	}

	if (
		Array.isArray( suggestion.popupTypes ) &&
		! suggestion.popupTypes.includes( popupType )
	) {
		return false;
	}

	if (
		Array.isArray( suggestion.excludePopupTypes ) &&
		suggestion.excludePopupTypes.includes( popupType )
	) {
		return false;
	}

	if (
		Array.isArray( suggestion.requiredBlocks ) &&
		suggestion.requiredBlocks.some(
			( blockName ) => ! selectedBlockNames.has( blockName )
		)
	) {
		return false;
	}

	if (
		suggestion.requiresImageGeneration &&
		! options?.imageGenerationAvailable
	) {
		return false;
	}

	return true;
};

export const getSuggestionPromptLibrary = () =>
	suggestionLibrary.map( ( suggestion ) => ( { ...suggestion } ) );

export const getSuggestionPrompts = ( options = {} ) => {
	const limit = Number.isFinite( Number( options?.limit ) )
		? Math.max( 1, Math.floor( Number( options.limit ) ) )
		: 5;

	return suggestionLibrary
		.filter( ( suggestion ) => isSuggestionValid( suggestion, options ) )
		.slice( 0, limit )
		.map( ( suggestion ) => ( { ...suggestion } ) );
};
