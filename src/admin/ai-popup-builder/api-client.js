import wpApiFetch from '@wordpress/api-fetch';
import { config } from './config';

const getRuntimeApiConfig = () => {
	const builderConfig = window.FC_AI_POPUP_BUILDER || config || {};
	const wpApiSettings = window.wpApiSettings || {};

	return {
		restRoot:
			typeof builderConfig?.restRoot === 'string' &&
			builderConfig.restRoot.length > 0
				? builderConfig.restRoot
				: wpApiSettings?.root || '',
		restNonce:
			typeof builderConfig?.restNonce === 'string' &&
			builderConfig.restNonce.length > 0
				? builderConfig.restNonce
				: wpApiSettings?.nonce || '',
	};
};

export const setupApiFetch = ( runtimeConfig = getRuntimeApiConfig() ) => {
	const restRoot =
		typeof runtimeConfig?.restRoot === 'string'
			? runtimeConfig.restRoot
			: '';
	const restNonce =
		typeof runtimeConfig?.restNonce === 'string'
			? runtimeConfig.restNonce
			: '';
	const readyState = window.FC_AI_POPUP_BUILDER_API_FETCH_READY;

	if (
		readyState &&
		typeof readyState === 'object' &&
		readyState.restRoot === restRoot &&
		readyState.restNonce === restNonce
	) {
		return true;
	}

	if ( restRoot.length === 0 && restNonce.length === 0 ) {
		return false;
	}

	if ( restRoot.length > 0 ) {
		wpApiFetch.use( wpApiFetch.createRootURLMiddleware( restRoot ) );
	}

	if ( restNonce.length > 0 ) {
		wpApiFetch.use( wpApiFetch.createNonceMiddleware( restNonce ) );
	}

	window.FC_AI_POPUP_BUILDER_API_FETCH_READY = {
		restRoot,
		restNonce,
	};

	return true;
};

export const apiFetch = ( options ) => wpApiFetch( options );
