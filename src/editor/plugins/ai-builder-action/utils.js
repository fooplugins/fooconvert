const temporaryOrigin = 'https://fooconvert.invalid';

const normalizePostId = ( postId ) => {
	const parsedPostId = Number.parseInt( postId, 10 );

	return Number.isFinite( parsedPostId ) && parsedPostId > 0
		? parsedPostId
		: 0;
};

export const addPostIdToUrl = ( url, postId ) => {
	const normalizedPostId = normalizePostId( postId );

	if ( ! url || normalizedPostId <= 0 ) {
		return '';
	}

	try {
		const hasScheme = /^[a-z][a-z\d+\-.]*:/i.test( url );
		const isProtocolRelative = url.startsWith( '//' );
		const parsedUrl = new URL(
			url,
			hasScheme ? undefined : temporaryOrigin
		);

		parsedUrl.searchParams.set( 'post_id', String( normalizedPostId ) );

		if ( hasScheme ) {
			return parsedUrl.toString();
		}

		if ( isProtocolRelative ) {
			return `//${ parsedUrl.host }${ parsedUrl.pathname }${ parsedUrl.search }${ parsedUrl.hash }`;
		}

		const pathname = url.startsWith( '/' )
			? parsedUrl.pathname
			: parsedUrl.pathname.replace( /^\//, '' );

		return `${ pathname }${ parsedUrl.search }${ parsedUrl.hash }`;
	} catch ( error ) {
		return '';
	}
};

export const buildAiBuilderEditUrl = ( builderUrl, postId ) =>
	addPostIdToUrl( builderUrl, postId );

export const buildPopupStatsUrl = ( statsUrlBase, postId ) =>
	addPostIdToUrl( statsUrlBase, postId );

export const getAiBuilderActionState = ( {
	builderUrl = '',
	statsUrlBase = '',
	currentPostId = 0,
	currentPostType = '',
	hasUnsavedChanges = false,
	isSaving = false,
} = {} ) => {
	if ( currentPostType !== 'fc-popup' ) {
		return {
			rows: [],
			shouldRender: false,
		};
	}

	const statsUrl = buildPopupStatsUrl( statsUrlBase, currentPostId );
	const aiBuilderUrl = buildAiBuilderEditUrl( builderUrl, currentPostId );
	const rows = [];

	if ( statsUrl ) {
		rows.push( {
			type: 'stats',
			href: statsUrl,
			target: '_blank',
		} );
	}

	if ( aiBuilderUrl ) {
		const disabled = Boolean( hasUnsavedChanges || isSaving );

		rows.push( {
			type: 'ai-builder',
			disabled,
			disabledReason: isSaving
				? 'saving'
				: hasUnsavedChanges
				? 'dirty'
				: '',
			href: disabled ? '' : aiBuilderUrl,
		} );
	}

	return {
		rows,
		shouldRender: rows.length > 0,
	};
};
