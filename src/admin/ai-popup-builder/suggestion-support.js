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

const normalizeSuggestionTags = ( value ) => {
	const tags = Array.isArray( value ) ? value.map( normalizeText ) : [];

	return tags.filter( Boolean );
};

const normalizeStringList = ( value ) =>
	( Array.isArray( value ) ? value : [] )
		.map( normalizeText )
		.filter( Boolean );

const normalizeSuggestionPhase = ( value, fallback = 'initial' ) => {
	const phase = normalizeText( value );

	return phase === 'edit' || phase === 'initial' ? phase : fallback;
};

const normalizeSuggestion = ( value, fallbackPhase = 'initial' ) => {
	if ( typeof value === 'string' ) {
		const text = normalizeText( value );

		return text
			? {
					text,
					tags: [ 'Create' ],
					phase: fallbackPhase,
			  }
			: null;
	}

	if ( value && Object.prototype.toString.call( value ) === '[object Object]' ) {
		const text = normalizeText( value.text );

		if ( ! text ) {
			return null;
		}

		const suggestion = {
			text,
			tags: normalizeSuggestionTags( value.tags ),
			phase: normalizeSuggestionPhase( value.phase, fallbackPhase ),
		};

		[
			'popupTypes',
			'excludePopupTypes',
			'requiredBlocks',
		].forEach( ( key ) => {
			const items = normalizeStringList( value[ key ] );

			if ( items.length > 0 ) {
				suggestion[ key ] = items;
			}
		} );

		if ( value.requiresImageGeneration ) {
			suggestion.requiresImageGeneration = true;
		}

		return suggestion;
	}

	return null;
};

const normalizeSuggestionList = ( value, fallbackPhase = 'initial' ) =>
	( Array.isArray( value ) ? value : [] )
		.map( ( item ) => normalizeSuggestion( item, fallbackPhase ) )
		.filter( Boolean )
		.map( ( suggestion ) => ( {
			...suggestion,
			tags:
				Array.isArray( suggestion.tags ) && suggestion.tags.length > 0
					? suggestion.tags
					: [ 'Create' ],
		} ) );

const normalizeStarterSuggestions = ( value ) =>
	normalizeSuggestionList( value, 'initial' ).map( ( suggestion ) => ( {
		...suggestion,
		phase: 'initial',
	} ) );

const getSuggestionLibrary = ( options = {} ) => {
	const configuredLibrary = normalizeSuggestionList(
		options?.suggestionLibrary
	);
	const starterSuggestions = normalizeStarterSuggestions(
		options?.starterPrompts
	);

	if ( starterSuggestions.length === 0 ) {
		return configuredLibrary;
	}

	return [
		...starterSuggestions,
		...configuredLibrary.filter(
			( suggestion ) => suggestion.phase !== 'initial'
		),
	];
};

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

export const getSuggestionPromptLibrary = ( options = {} ) =>
	getSuggestionLibrary( options ).map( ( suggestion ) => ( {
		...suggestion,
	} ) );

export const getSuggestionPrompts = ( options = {} ) => {
	const limit = Number.isFinite( Number( options?.limit ) )
		? Math.max( 1, Math.floor( Number( options.limit ) ) )
		: 5;

	return getSuggestionLibrary( options )
		.filter( ( suggestion ) => isSuggestionValid( suggestion, options ) )
		.slice( 0, limit )
		.map( ( suggestion ) => ( { ...suggestion } ) );
};
