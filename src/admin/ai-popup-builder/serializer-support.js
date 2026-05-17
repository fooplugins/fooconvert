import signUpMetadata from '../../blocks/sign-up/block.json';
import barMetadata from '../../popups/bar/block.json';
import barOpenButtonMetadata from '../../popups/bar/editor/blocks/open-button/block.json';
import barContainerMetadata from '../../popups/bar/editor/blocks/container/block.json';
import barCloseButtonMetadata from '../../popups/bar/editor/blocks/container/blocks/close-button/block.json';
import barContentMetadata from '../../popups/bar/editor/blocks/container/blocks/content/block.json';
import flyoutMetadata from '../../popups/flyout/block.json';
import flyoutOpenButtonMetadata from '../../popups/flyout/editor/blocks/open-button/block.json';
import flyoutContainerMetadata from '../../popups/flyout/editor/blocks/container/block.json';
import flyoutCloseButtonMetadata from '../../popups/flyout/editor/blocks/container/blocks/close-button/block.json';
import flyoutContentMetadata from '../../popups/flyout/editor/blocks/container/blocks/content/block.json';
import popupMetadata from '../../popups/overlay/block.json';
import popupContainerMetadata from '../../popups/overlay/editor/blocks/container/block.json';
import popupCloseButtonMetadata from '../../popups/overlay/editor/blocks/container/blocks/close-button/block.json';
import popupContentMetadata from '../../popups/overlay/editor/blocks/container/blocks/content/block.json';

export const fooconvertBlockMetadata = [
	popupMetadata,
	popupContainerMetadata,
	popupCloseButtonMetadata,
	popupContentMetadata,
	flyoutMetadata,
	flyoutOpenButtonMetadata,
	flyoutContainerMetadata,
	flyoutCloseButtonMetadata,
	flyoutContentMetadata,
	barMetadata,
	barOpenButtonMetadata,
	barContainerMetadata,
	barCloseButtonMetadata,
	barContentMetadata,
	signUpMetadata,
];

export const isPlainObject = ( value ) =>
	Boolean( value ) &&
	Object.prototype.toString.call( value ) === '[object Object]';

const supportedTextAlignments = new Set( [
	'left',
	'center',
	'right',
	'justify',
] );

export const cloneDeep = ( value ) => {
	if ( Array.isArray( value ) ) {
		return value.map( cloneDeep );
	}

	if ( isPlainObject( value ) ) {
		return Object.entries( value ).reduce( ( nextValue, [ key, item ] ) => {
			nextValue[ key ] = cloneDeep( item );
			return nextValue;
		}, {} );
	}

	return value;
};

const decodeAmpersandEntities = ( value ) => {
	let decoded = value;

	for ( let index = 0; index < 3; index++ ) {
		const nextDecoded = decoded.replace(
			/&(?:amp|#0*38|#x0*26);/gi,
			'&'
		);

		if ( nextDecoded === decoded ) {
			break;
		}

		decoded = nextDecoded;
	}

	return decoded;
};

export const normalizeAttributeTextEntities = ( value ) => {
	if ( typeof value === 'string' ) {
		return decodeAmpersandEntities( value );
	}

	if ( Array.isArray( value ) ) {
		return value.map( normalizeAttributeTextEntities );
	}

	if ( isPlainObject( value ) ) {
		return Object.entries( value ).reduce( ( nextValue, [ key, item ] ) => {
			nextValue[ key ] = normalizeAttributeTextEntities( item );
			return nextValue;
		}, {} );
	}

	return value;
};

const deepMerge = ( base, overrides ) => {
	if ( Array.isArray( overrides ) ) {
		return overrides.map( cloneDeep );
	}

	if ( ! isPlainObject( base ) || ! isPlainObject( overrides ) ) {
		return cloneDeep( overrides );
	}

	const merged = { ...cloneDeep( base ) };
	Object.entries( overrides ).forEach( ( [ key, value ] ) => {
		if ( isPlainObject( value ) && isPlainObject( merged[ key ] ) ) {
			merged[ key ] = deepMerge( merged[ key ], value );
			return;
		}

		merged[ key ] = cloneDeep( value );
	} );

	return merged;
};

export const supportedTriggerEvents = [
	'fc.immediate',
	'fc.anchor.click',
	'fc.element.visible',
	'fc.element.click',
	'fc.exit_intent',
	'fc.scroll.percent',
	'fc.timer.elapsed',
	'cart.add',
	'cart.updated',
	'cart.remove',
	'coupon.applied',
	'coupon.invalid',
	'checkout.error',
	'checkout.payment_failed',
	'cart.idle',
	'checkout.enter',
	'checkout.exit',
	'product.view',
	'product.high_intent',
];

const supportedTriggerEventSet = new Set( supportedTriggerEvents );

const triggerEventAliases = {
	immediate: 'fc.immediate',
	delay: 'fc.timer.elapsed',
	timer: 'fc.timer.elapsed',
	exit_intent: 'fc.exit_intent',
	'exit-intent': 'fc.exit_intent',
	scroll_percent: 'fc.scroll.percent',
	scroll: 'fc.scroll.percent',
	anchor: 'fc.anchor.click',
	anchor_click: 'fc.anchor.click',
	element: 'fc.element.click',
	element_click: 'fc.element.click',
	element_visible: 'fc.element.visible',
};

const repeatDefaultTriggerEvents = new Set( [
	'fc.anchor.click',
	'fc.element.click',
	'cart.add',
	'cart.updated',
	'cart.remove',
	'coupon.applied',
	'coupon.invalid',
	'checkout.error',
	'checkout.payment_failed',
] );

export const resolveTriggerEvent = ( trigger = {}, popupType = 'popup' ) => {
	const explicitEvent =
		typeof trigger?.event === 'string' ? trigger.event.trim() : '';
	if ( supportedTriggerEventSet.has( explicitEvent ) ) {
		return explicitEvent;
	}

	const type = typeof trigger?.type === 'string' ? trigger.type.trim() : '';
	if ( supportedTriggerEventSet.has( type ) ) {
		return type;
	}

	if ( triggerEventAliases[ type ] ) {
		return triggerEventAliases[ type ];
	}

	const normalizedPopupType = normalizePopupType( popupType );
	if ( normalizedPopupType === 'bar' ) {
		return 'fc.timer.elapsed';
	}

	if ( normalizedPopupType === 'flyout' ) {
		return 'fc.scroll.percent';
	}

	return 'fc.exit_intent';
};

const getNumberWithin = ( value, fallback, min, max ) => {
	const number = Number( value );
	if ( ! Number.isFinite( number ) ) {
		return fallback;
	}

	return Math.min( max, Math.max( min, number ) );
};

const getTriggerFrequencyMode = ( trigger = {}, event = '' ) => {
	const frequency = isPlainObject( trigger?.frequency )
		? trigger.frequency.mode
		: trigger?.frequency;

	if ( frequency === 'repeat' || frequency === 'once' ) {
		return frequency;
	}

	return repeatDefaultTriggerEvents.has( event ) ? 'repeat' : 'once';
};

const getTriggerCooldownSeconds = ( trigger = {} ) => {
	if ( ! isPlainObject( trigger?.frequency ) ) {
		return 0;
	}

	return getNumberWithin(
		trigger.frequency.cooldownSeconds,
		0,
		0,
		Number.MAX_SAFE_INTEGER
	);
};

const normalizeTriggerWhere = ( trigger = {}, event = '' ) => {
	const where = isPlainObject( trigger?.where )
		? cloneDeep( trigger.where )
		: {};

	switch ( event ) {
		case 'fc.immediate':
			return {};
		case 'fc.timer.elapsed':
			return {
				...where,
				seconds: getNumberWithin(
					where.seconds ?? trigger?.delay_seconds,
					4,
					0,
					100
				),
			};
		case 'fc.exit_intent':
			return {
				...where,
				delaySeconds: getNumberWithin(
					where.delaySeconds ?? trigger?.delay_seconds,
					5,
					0,
					100
				),
			};
		case 'fc.scroll.percent':
			return {
				...where,
				percent: getNumberWithin(
					where.percent ?? trigger?.scroll_percent,
					20,
					1,
					100
				),
			};
		case 'cart.idle':
			return {
				...where,
				delaySeconds: getNumberWithin(
					where.delaySeconds ?? trigger?.delay_seconds,
					60,
					5,
					600
				),
			};
		case 'product.high_intent':
			return {
				...where,
				scrollPercent: getNumberWithin(
					where.scrollPercent,
					70,
					1,
					100
				),
				timeSeconds: getNumberWithin( where.timeSeconds, 30, 1, 600 ),
				viewCount: getNumberWithin( where.viewCount, 2, 1, 20 ),
			};
		default:
			return where;
	}
};

export const normalizePopupType = ( value ) => {
	switch ( value ) {
		case 'bar':
		case 'flyout':
		case 'popup':
			return value;
		default:
			return 'popup';
	}
};

const getDefaultRootAttributes = ( popupType ) => {
	switch ( normalizePopupType( popupType ) ) {
		case 'bar':
			return {
				template: '',
				viewState: 'open',
				settings: {
					position: 'bottom',
					transitions: true,
				},
				openButton: {
					settings: {
						hidden: true,
					},
				},
				closeButton: {
					settings: {
						icon: {
							slug: 'default__close-small',
							size: '24px',
						},
					},
				},
				content: {
					styles: {
						color: {
							background: '#111827',
							text: '#ffffff',
						},
						border: {
							radius: '18px',
							style: 'solid',
							width: '0px',
							color: '#111827',
						},
						dimensions: {
							padding: '18px 24px',
							gap: '16px',
							margin: '16px',
						},
					},
				},
			};
		case 'flyout':
			return {
				template: '',
				viewState: 'open',
				settings: {
					transitions: true,
				},
				openButton: {
					settings: {
						hidden: true,
					},
				},
				closeButton: {
					settings: {
						icon: {
							slug: 'default__close-small',
							size: '28px',
						},
					},
				},
				content: {
					styles: {
						color: {
							background: '#ffffff',
							text: '#111827',
						},
						border: {
							radius: '22px',
							style: 'solid',
							width: '1px',
							color: '#d6dae1',
							shadow: '0 20px 48px rgba(15, 23, 42, 0.18)',
						},
						dimensions: {
							padding: '28px',
							gap: '16px',
							margin: '18px',
						},
						width: '420px',
					},
				},
			};
		case 'popup':
		default:
			return {
				template: '',
				settings: {
					transitions: true,
					hideScrollbar: true,
					maxOnMobile: true,
				},
				closeButton: {
					settings: {
						icon: {
							slug: 'default__close-small',
							size: '32px',
						},
					},
				},
				content: {
					styles: {
						color: {
							background: '#ffffff',
							text: '#111827',
						},
						border: {
							radius: '22px',
							style: 'solid',
							width: '1px',
							color: '#d6dae1',
							shadow: '0 24px 56px rgba(15, 23, 42, 0.2)',
						},
						dimensions: {
							padding: '32px',
							gap: '18px',
						},
						width: '640px',
					},
				},
			};
	}
};

const normalizeTriggerStep = ( step = {} ) => {
	if (
		! isPlainObject( step ) ||
		typeof step?.event !== 'string' ||
		! supportedTriggerEventSet.has( step.event )
	) {
		return null;
	}

	const normalizedStep = {
		event: step.event,
		where: isPlainObject( step?.where ) ? cloneDeep( step.where ) : {},
	};

	if ( Number.isFinite( Number( step?.withinSeconds ) ) ) {
		normalizedStep.withinSeconds = Math.max(
			0,
			Number( step.withinSeconds )
		);
	}

	return normalizedStep;
};

const buildTriggerConfig = ( trigger = {}, popupType = 'popup' ) => {
	const lifetime = [ 'page', 'session', 'visit' ].includes(
		trigger?.lifetime
	)
		? trigger.lifetime
		: 'page';
	const event = resolveTriggerEvent( trigger, popupType );
	const frequency = getTriggerFrequencyMode( trigger, event );
	const cooldownSeconds = getTriggerCooldownSeconds( trigger );
	const steps = Array.isArray( trigger?.steps )
		? trigger.steps.map( normalizeTriggerStep ).filter( Boolean )
		: [];

	if ( steps.length === 0 ) {
		steps.push( {
			event,
			where: normalizeTriggerWhere( trigger, event ),
		} );
	}

	return {
		version: 2,
		lifetime,
		frequency: {
			mode: frequency,
			cooldownSeconds,
		},
		steps,
	};
};

export const buildRootAttributes = ( draft, templatesBySlug = {} ) => {
	const popupType = normalizePopupType( draft?.popup_type );
	const template =
		typeof draft?.template_slug === 'string'
			? templatesBySlug[ draft.template_slug ]
			: null;

	let rootAttributes = deepMerge( {}, getDefaultRootAttributes( popupType ) );

	if ( template?.attributes ) {
		rootAttributes = deepMerge( rootAttributes, template.attributes );
	}

	if ( isPlainObject( draft?.root_attributes ) ) {
		rootAttributes = deepMerge( rootAttributes, draft.root_attributes );
	}

	rootAttributes.template =
		typeof draft?.template_slug === 'string'
			? draft.template_slug
			: rootAttributes.template || '';
	rootAttributes.settings = deepMerge( rootAttributes.settings || {}, {
		trigger: buildTriggerConfig( draft?.trigger, popupType ),
	} );

	if ( popupType !== 'popup' ) {
		rootAttributes.viewState = 'open';
		rootAttributes.openButton = deepMerge(
			{
				settings: {
					hidden: true,
				},
			},
			rootAttributes.openButton || {}
		);
	} else {
		delete rootAttributes.openButton;
	}

	return normalizeAttributeTextEntities( rootAttributes );
};

export const extractListItems = ( attributes ) => {
	if ( Array.isArray( attributes?.items ) && attributes.items.length > 0 ) {
		return attributes.items
			.map( ( item ) => String( item || '' ).trim() )
			.filter( Boolean );
	}

	if ( Array.isArray( attributes?.values ) && attributes.values.length > 0 ) {
		return attributes.values
			.map( ( item ) => String( item || '' ).trim() )
			.filter( Boolean );
	}

	if (
		typeof attributes?.values !== 'string' ||
		attributes.values.trim().length === 0
	) {
		return [];
	}

	const matches = [
		...attributes.values.matchAll( /<li\b[^>]*>([\s\S]*?)<\/li>/gi ),
	];
	if ( matches.length === 0 ) {
		return [ attributes.values.trim() ];
	}

	return matches
		.map( ( [ , item ] ) => String( item || '' ).trim() )
		.filter( Boolean );
};

const normalizeTextAlignment = ( value ) => {
	const alignment =
		typeof value === 'string' ? value.trim().toLowerCase() : '';
	return supportedTextAlignments.has( alignment ) ? alignment : '';
};

const applyTextBlockAlignment = ( attributes, primaryAttribute ) => {
	const style = isPlainObject( attributes.style )
		? cloneDeep( attributes.style )
		: {};
	const typography = isPlainObject( style.typography )
		? cloneDeep( style.typography )
		: {};
	const secondaryAttribute =
		primaryAttribute === 'align' ? 'textAlign' : 'align';
	const alignment =
		normalizeTextAlignment( attributes[ primaryAttribute ] ) ||
		normalizeTextAlignment( attributes[ secondaryAttribute ] ) ||
		normalizeTextAlignment( typography.textAlign );

	if ( ! alignment ) {
		return attributes;
	}

	attributes[ primaryAttribute ] = alignment;
	delete attributes[ secondaryAttribute ];

	typography.textAlign = alignment;
	attributes.style = {
		...style,
		typography,
	};

	return attributes;
};

export const normalizeDraftBlockAttributes = ( blockName, attributes ) => {
	const nextAttributes = normalizeAttributeTextEntities(
		isPlainObject( attributes ) ? cloneDeep( attributes ) : {}
	);

	switch ( blockName ) {
		case 'core/heading':
			return applyTextBlockAlignment( nextAttributes, 'textAlign' );
		case 'core/paragraph':
			return applyTextBlockAlignment( nextAttributes, 'align' );
		case 'core/list':
			if ( ! Array.isArray( nextAttributes.items ) ) {
				nextAttributes.items = extractListItems( nextAttributes );
			}
			return nextAttributes;
		case 'core/button':
			if (
				typeof nextAttributes?.text !== 'string' &&
				typeof nextAttributes?.content === 'string'
			) {
				nextAttributes.text = nextAttributes.content;
			}
			return nextAttributes;
		case 'core/image':
			if (
				typeof nextAttributes?.url !== 'string' &&
				typeof nextAttributes?.src === 'string'
			) {
				nextAttributes.url = nextAttributes.src;
			}

			if (
				! Number.isFinite( Number( nextAttributes?.id ) ) &&
				Number.isFinite( Number( nextAttributes?.mediaId ) )
			) {
				nextAttributes.id = Number( nextAttributes.mediaId );
			}

			if (
				! Number.isFinite( Number( nextAttributes?.id ) ) &&
				Number.isFinite( Number( nextAttributes?.attachmentId ) )
			) {
				nextAttributes.id = Number( nextAttributes.attachmentId );
			}

			if (
				typeof nextAttributes?.alt !== 'string' &&
				typeof nextAttributes?.altText === 'string'
			) {
				nextAttributes.alt = nextAttributes.altText;
			}

			return nextAttributes;
		case 'fc/sign-up':
			nextAttributes.settings = isPlainObject( nextAttributes.settings )
				? nextAttributes.settings
				: {};
			nextAttributes.inputs = isPlainObject( nextAttributes.inputs )
				? nextAttributes.inputs
				: {};
			nextAttributes.inputs.settings = isPlainObject(
				nextAttributes.inputs.settings
			)
				? nextAttributes.inputs.settings
				: {};
			nextAttributes.button = isPlainObject( nextAttributes.button )
				? nextAttributes.button
				: {};
			nextAttributes.button.settings = isPlainObject(
				nextAttributes.button.settings
			)
				? nextAttributes.button.settings
				: {};

			if (
				typeof nextAttributes.buttonText === 'string' &&
				typeof nextAttributes.button.settings.text !== 'string'
			) {
				nextAttributes.button.settings.text = nextAttributes.buttonText;
			}

			if (
				typeof nextAttributes.successMessage === 'string' &&
				typeof nextAttributes.settings.successMessage !== 'string'
			) {
				nextAttributes.settings.successMessage =
					nextAttributes.successMessage;
			}

			if (
				typeof nextAttributes.closeOnSuccess === 'boolean' &&
				typeof nextAttributes.settings.closeOnSuccess !== 'boolean'
			) {
				nextAttributes.settings.closeOnSuccess =
					nextAttributes.closeOnSuccess;
			}

			if (
				typeof nextAttributes.emailOnly === 'boolean' &&
				typeof nextAttributes.inputs.settings.emailOnly !== 'boolean'
			) {
				nextAttributes.inputs.settings.emailOnly =
					nextAttributes.emailOnly;
			}

			if (
				typeof nextAttributes.emailPlaceholder === 'string' &&
				typeof nextAttributes.inputs.settings.emailPlaceholder !==
					'string'
			) {
				nextAttributes.inputs.settings.emailPlaceholder =
					nextAttributes.emailPlaceholder;
			}

			if (
				typeof nextAttributes.namePlaceholder === 'string' &&
				typeof nextAttributes.inputs.settings.namePlaceholder !==
					'string'
			) {
				nextAttributes.inputs.settings.namePlaceholder =
					nextAttributes.namePlaceholder;
			}

			return nextAttributes;
		default:
			return nextAttributes;
	}
};

export const flattenBlocks = ( blocks = [] ) => {
	return blocks.reduce( ( nextBlocks, block ) => {
		if ( ! isPlainObject( block ) ) {
			return nextBlocks;
		}

		const childBlocks = Array.isArray( block.inner_blocks )
			? flattenBlocks( block.inner_blocks )
			: [];
		return [ ...nextBlocks, block, ...childBlocks ];
	}, [] );
};
