import { isPlainObject } from './serializer-support';

export const DEFAULT_WOOCOMMERCE_BLOCK_NAMES = [
	'woocommerce/all-reviews',
	'woocommerce/cart-link',
	'woocommerce/coupon-code',
	'woocommerce/featured-category',
	'woocommerce/featured-product',
	'woocommerce/mini-cart',
	'woocommerce/payment-method-icons',
	'woocommerce/product-collection',
	'woocommerce/product-filters',
	'woocommerce/product-image-gallery',
	'woocommerce/product-meta',
	'woocommerce/product-search',
	'woocommerce/reviews-by-category',
	'woocommerce/reviews-by-product',
	'woocommerce/single-product',
	'woocommerce/product-details',
	'woocommerce/product-reviews',
	'woocommerce/product-review-form',
	'woocommerce/cart',
	'woocommerce/checkout',
];

export const normalizeDisabledParamName = ( value ) =>
	String( value || '' )
		.trim()
		.replace( /([a-z0-9])([A-Z])/g, '$1_$2' )
		.toLowerCase()
		.replace( /[^a-z0-9_.-]+/g, '_' )
		.replace( /^[_\-.]+|[_\-.]+$/g, '' )
		.replace( /-/g, '_' );

export const normalizeDisabledParams = ( value ) => {
	const items = Array.isArray( value )
		? value
		: String( value || '' ).split( /[\r\n,]+/ );
	const params = new Set();

	items.forEach( ( item ) => {
		const param = normalizeDisabledParamName( item );

		if ( param.length > 0 ) {
			params.add( param );
		}
	} );

	return Array.from( params );
};

export const getBlockSource = ( blockName ) => {
	const name = String( blockName || '' );

	if ( name.startsWith( 'fc/' ) ) {
		return 'fooconvert';
	}

	if ( name.startsWith( 'woocommerce/' ) ) {
		return 'woocommerce';
	}

	if ( name.startsWith( 'core/' ) ) {
		return 'core';
	}

	return 'other';
};

export const normalizeBlockNameList = ( value ) => {
	const items = Array.isArray( value )
		? value
		: String( value || '' ).split( /[\r\n,]+/ );
	const blockNames = new Set();

	items.forEach( ( item ) => {
		const blockName = String( item || '' ).trim();

		if ( blockName.length > 0 ) {
			blockNames.add( blockName );
		}
	} );

	return Array.from( blockNames );
};

export const getDefaultSelectedBlockNames = ( blockCatalog = [] ) => {
	const defaultWooBlockNames = new Set( DEFAULT_WOOCOMMERCE_BLOCK_NAMES );

	return blockCatalog
		.map( ( block ) => String( block?.name || '' ) )
		.filter( ( blockName ) => {
			if ( blockName.length === 0 ) {
				return false;
			}

			return (
				! blockName.startsWith( 'woocommerce/' ) ||
				defaultWooBlockNames.has( blockName )
			);
		} );
};

export const sanitizeSelectedBlockNames = (
	value,
	blockCatalog = [],
	fallbackToDefault = true
) => {
	const availableBlockNames = new Set(
		blockCatalog
			.map( ( block ) => String( block?.name || '' ) )
			.filter( Boolean )
	);
	const selectedBlockNames = normalizeBlockNameList( value ).filter(
		( blockName ) => availableBlockNames.has( blockName )
	);

	if ( selectedBlockNames.length === 0 && fallbackToDefault ) {
		return getDefaultSelectedBlockNames( blockCatalog );
	}

	return selectedBlockNames;
};

export const normalizeAiSettings = ( settings, blockCatalog = [] ) => {
	const source = isPlainObject( settings ) ? settings : {};
	const timeoutDefault =
		Number.isFinite( Number( source?.timeoutDefault ) ) &&
		Number( source.timeoutDefault ) > 0
			? Number( source.timeoutDefault )
			: 45;
	const maxToolCallsDefault =
		Number.isFinite( Number( source?.maxToolCallsDefault ) ) &&
		Number( source.maxToolCallsDefault ) > 0
			? Number( source.maxToolCallsDefault )
			: 10;
	let disabledParamsText = '';
	if ( typeof source?.disabledParamsText === 'string' ) {
		disabledParamsText = source.disabledParamsText;
	} else if ( Array.isArray( source?.disabledParams ) ) {
		disabledParamsText = source.disabledParams.join( '\n' );
	}
	const disabledParams = normalizeDisabledParams(
		Array.isArray( source?.disabledParams )
			? source.disabledParams
			: disabledParamsText
	);
	const timeout = Number( source?.timeout );
	const maxToolCalls = Number( source?.maxToolCalls );

	return {
		overrideModel:
			typeof source?.overrideModel === 'string'
				? source.overrideModel
				: '',
		disabledParams,
		disabledParamsText:
			disabledParamsText.length > 0
				? disabledParamsText
				: disabledParams.join( '\n' ),
		timeout:
			Number.isFinite( timeout ) && timeout > 0
				? Math.floor( timeout )
				: timeoutDefault,
		timeoutDefault,
		maxToolCalls:
			Number.isFinite( maxToolCalls ) && maxToolCalls > 0
				? Math.floor( maxToolCalls )
				: maxToolCallsDefault,
		maxToolCallsDefault,
		selectedBlockNames: sanitizeSelectedBlockNames(
			source?.selectedBlockNames,
			blockCatalog
		),
		canManage: source?.canManage !== false,
	};
};

export const buildAiSettingsPayload = ( settings, blockCatalog = [] ) => {
	const normalized = normalizeAiSettings( settings, blockCatalog );

	return {
		overrideModel: normalized.overrideModel.trim(),
		disabledParams: normalized.disabledParams,
		disabledParamsText: normalized.disabledParams.join( '\n' ),
		timeout: normalized.timeout,
		maxToolCalls: normalized.maxToolCalls,
		selectedBlockNames: normalized.selectedBlockNames,
	};
};

export const serializeAiSettingsComparable = (
	settings,
	blockCatalog = []
) => {
	const normalized = buildAiSettingsPayload( settings, blockCatalog );

	return JSON.stringify( normalized );
};
