import { __, sprintf } from '@wordpress/i18n';
import { isPlainObject, resolveTriggerEvent } from './serializer-support';

export const flattenDraftBlocks = ( blocks ) =>
	( Array.isArray( blocks ) ? blocks : [] ).reduce( ( flattened, block ) => {
		if ( ! isPlainObject( block ) ) {
			return flattened;
		}

		flattened.push( block );

		if ( Array.isArray( block?.inner_blocks ) ) {
			flattened.push( ...flattenDraftBlocks( block.inner_blocks ) );
		}

		return flattened;
	}, [] );

export const countByName = ( blocks, blockName ) =>
	blocks.filter( ( block ) => block?.name === blockName ).length;

const getFirstFiniteNumber = ( values, fallback ) => {
	for ( const value of values ) {
		if ( Number.isFinite( Number( value ) ) ) {
			return Number( value );
		}
	}

	return fallback;
};

export const getActionSummary = ( draft ) => {
	const blocks = flattenDraftBlocks(
		Array.isArray( draft?.content_blocks ) ? draft.content_blocks : []
	);
	const signupCount = countByName( blocks, 'fc/sign-up' );

	if ( signupCount > 0 ) {
		return __( 'Lead capture form included', 'fooconvert' );
	}

	const buttonCount = countByName( blocks, 'core/button' );

	if ( buttonCount > 0 ) {
		return __( 'Primary CTA button included', 'fooconvert' );
	}

	return __( 'No CTA yet', 'fooconvert' );
};

export const getTriggerSummary = ( draft ) => {
	const trigger = isPlainObject( draft?.trigger ) ? draft.trigger : {};
	const event = resolveTriggerEvent( trigger, draft?.popup_type );
	const where = isPlainObject( trigger?.where ) ? trigger.where : {};

	switch ( event ) {
		case 'fc.timer.elapsed':
			return sprintf(
				/* translators: %d is the delay duration in seconds. */
				__( '%ds delay', 'fooconvert' ),
				getFirstFiniteNumber(
					[ where?.seconds, trigger?.delay_seconds ],
					4
				)
			);
		case 'fc.scroll.percent':
			return sprintf(
				/* translators: %d is the scroll percentage that triggers the popup. */
				__( '%d%% scroll', 'fooconvert' ),
				getFirstFiniteNumber(
					[ where?.percent, trigger?.scroll_percent ],
					20
				)
			);
		case 'fc.immediate':
			return __( 'Immediate', 'fooconvert' );
		case 'fc.anchor.click':
			return __( 'Anchor click', 'fooconvert' );
		case 'fc.element.visible':
			return __( 'Anchor visible', 'fooconvert' );
		case 'fc.element.click':
			return __( 'Element click', 'fooconvert' );
		case 'cart.add':
			return __( 'Product added to cart', 'fooconvert' );
		case 'cart.updated':
			return __( 'Cart updated', 'fooconvert' );
		case 'cart.remove':
			return __( 'Product removed from cart', 'fooconvert' );
		case 'coupon.applied':
			return __( 'Coupon applied', 'fooconvert' );
		case 'coupon.invalid':
			return __( 'Invalid coupon', 'fooconvert' );
		case 'checkout.error':
			return __( 'Checkout error', 'fooconvert' );
		case 'checkout.payment_failed':
			return __( 'Payment failed', 'fooconvert' );
		case 'cart.idle':
			return __( 'Cart idle', 'fooconvert' );
		case 'checkout.enter':
			return __( 'Checkout viewed', 'fooconvert' );
		case 'checkout.exit':
			return __( 'Checkout exit intent', 'fooconvert' );
		case 'product.view':
			return __( 'Product viewed', 'fooconvert' );
		case 'product.high_intent':
			return __( 'Product high intent', 'fooconvert' );
		case 'fc.exit_intent':
		default:
			return __( 'Exit intent', 'fooconvert' );
	}
};

export const truncateText = ( value, maxLength = 160 ) => {
	const text = String( value || '' ).trim();

	if ( text.length <= maxLength ) {
		return text;
	}

	return `${ text.slice( 0, maxLength - 1 ).trim() }…`;
};

export const buildLastAssistantMessage = ( messages ) => {
	const lastAssistantEntry = [
		...( Array.isArray( messages ) ? messages : [] ),
	]
		.reverse()
		.find(
			( message ) =>
				message?.role === 'assistant' &&
				message?.requestStatus !== 'error'
		);

	return lastAssistantEntry?.content || '';
};
