const isPlainObject = ( value ) =>
	Boolean( value ) &&
	Object.prototype.toString.call( value ) === '[object Object]';

const toStringList = ( value ) => {
	return Array.isArray( value )
		? value.map( ( item ) => `${ item || '' }`.trim() ).filter( Boolean )
		: [];
};

const normalizeMessage = ( value ) => {
	if ( ! isPlainObject( value ) ) {
		return null;
	}

	const content = `${ value?.content || '' }`.trim();
	if ( content.length === 0 ) {
		return null;
	}

	return {
		role: value?.role === 'assistant' ? 'assistant' : 'user',
		content,
	};
};

const normalizeMediaItem = ( value ) => {
	if ( ! isPlainObject( value ) ) {
		return null;
	}

	const url = `${ value?.url || '' }`.trim();
	const previewUrl = `${
		value?.previewUrl || value?.preview_url || url
	}`.trim();

	if ( url.length === 0 && previewUrl.length === 0 ) {
		return null;
	}

	const id = Number( value?.id );

	return {
		id: Number.isFinite( id ) ? id : 0,
		url,
		previewUrl,
		alt: `${ value?.alt || '' }`.trim(),
		title: `${ value?.title || '' }`.trim(),
		prompt: `${ value?.prompt || '' }`.trim(),
		editUrl: `${ value?.editUrl || value?.edit_url || '' }`.trim(),
	};
};

export const getDefaultAiBuilderMetadata = () => ( {
	source: '',
	saved_at: '',
	messages: [],
	response: {
		assistant_message: '',
		clarifying_question: '',
		suggested_prompts: [],
		popup_draft: null,
		validation: null,
		media_items: [],
	},
	options: {
		generate_images: false,
		force_image_generation: false,
	},
} );

export const normalizeAiBuilderMetadata = ( value ) => {
	const defaults = getDefaultAiBuilderMetadata();
	const metadata = isPlainObject( value ) ? value : {};
	const response = isPlainObject( metadata?.response )
		? metadata.response
		: {};
	const validation = isPlainObject( response?.validation )
		? response.validation
		: null;
	const popupDraft = isPlainObject( response?.popup_draft )
		? response.popup_draft
		: null;

	return {
		source: `${ metadata?.source || '' }`.trim(),
		saved_at: `${ metadata?.saved_at || '' }`.trim(),
		messages: Array.isArray( metadata?.messages )
			? metadata.messages.map( normalizeMessage ).filter( Boolean )
			: defaults.messages,
		response: {
			assistant_message: `${ response?.assistant_message || '' }`.trim(),
			clarifying_question: `${
				response?.clarifying_question || ''
			}`.trim(),
			suggested_prompts: toStringList( response?.suggested_prompts ),
			popup_draft: popupDraft,
			validation: validation
				? {
						score: Number.isFinite( Number( validation?.score ) )
							? Number( validation.score )
							: null,
						strengths: toStringList( validation?.strengths ),
						warnings: toStringList( validation?.warnings ),
						suggestions: toStringList( validation?.suggestions ),
				  }
				: defaults.response.validation,
			media_items: Array.isArray( response?.media_items )
				? response.media_items
						.map( normalizeMediaItem )
						.filter( Boolean )
				: defaults.response.media_items,
		},
		options: {
			generate_images: Boolean( metadata?.options?.generate_images ),
			force_image_generation: Boolean(
				metadata?.options?.force_image_generation
			),
		},
	};
};

export const hasAiBuilderMetadata = ( value ) => {
	const metadata = normalizeAiBuilderMetadata( value );

	if ( metadata.source === 'ai-popup-builder' ) {
		return true;
	}

	return (
		metadata.messages.length > 0 ||
		metadata.response.assistant_message.length > 0 ||
		metadata.response.clarifying_question.length > 0 ||
		metadata.response.media_items.length > 0 ||
		isPlainObject( metadata.response.popup_draft )
	);
};

export const formatAiBuilderDate = ( value ) => {
	const date = new Date( value );
	if ( Number.isNaN( date.getTime() ) ) {
		return '';
	}

	try {
		return new Intl.DateTimeFormat( undefined, {
			dateStyle: 'medium',
			timeStyle: 'short',
		} ).format( date );
	} catch {
		return value;
	}
};

export const truncateAiBuilderText = ( value, maxLength = 140 ) => {
	const text = `${ value || '' }`.trim();

	if ( text.length <= maxLength ) {
		return text;
	}

	return `${ text.slice( 0, maxLength - 1 ).trim() }…`;
};
