import { cloneDeep, isPlainObject } from './serializer-support';

export const defaultLoadPopupPath = '/fooconvert/v1/ai-popup-builder/popup';

const normalizeText = ( value ) => String( value || '' ).trim();

const normalizeStringList = ( value ) =>
	Array.isArray( value )
		? value.map( normalizeText ).filter( Boolean )
		: [];

const normalizeMessages = ( value ) =>
	Array.isArray( value )
		? value.reduce( ( messages, message ) => {
				const content = normalizeText( message?.content );

				if ( content.length === 0 ) {
					return messages;
				}

				messages.push( {
					role: message?.role === 'assistant' ? 'assistant' : 'user',
					content,
				} );

				return messages;
		  }, [] )
		: [];

const appendAssistantSummaryMessage = ( messages, assistantMessage ) => {
	const content = normalizeText( assistantMessage );

	if ( content.length === 0 ) {
		return messages;
	}

	const lastMessage = messages[ messages.length - 1 ];
	if (
		lastMessage?.role === 'assistant' &&
		normalizeText( lastMessage?.content ) === content
	) {
		return messages;
	}

	return [ ...messages, { role: 'assistant', content } ];
};

export const buildLoadPopupPath = ( loadPopupPath, postId ) => {
	const numericPostId = Number( postId );
	const basePath =
		typeof loadPopupPath === 'string' && loadPopupPath.trim().length > 0
			? loadPopupPath.trim()
			: defaultLoadPopupPath;

	if ( ! Number.isFinite( numericPostId ) || numericPostId <= 0 ) {
		return basePath.replace( /\/+$/, '' );
	}

	return `${ basePath.replace( /\/+$/, '' ) }/${ Math.floor(
		numericPostId
	) }`;
};

export const normalizeLoadedPopupResponse = ( response ) => {
	const draft = isPlainObject( response?.draft )
		? cloneDeep( response.draft )
		: null;
	const validation = isPlainObject( response?.validation )
		? cloneDeep( response.validation )
		: null;
	const mediaItems = Array.isArray( response?.mediaItems )
		? cloneDeep( response.mediaItems )
		: [];
	const suggestedPrompts = normalizeStringList( response?.suggestedPrompts );
	const assistantMessage = normalizeText( response?.assistantMessage );
	const clarifyingQuestion = normalizeText( response?.clarifyingQuestion );
	const messages = appendAssistantSummaryMessage(
		normalizeMessages( response?.messages ),
		clarifyingQuestion || assistantMessage
	);
	const postId = Number( response?.postId );
	const title = normalizeText( response?.title || draft?.title );
	const popupType = normalizeText( response?.popupType || draft?.popup_type );

	return {
		draft,
		validation,
		messages,
		mediaItems,
		suggestedPrompts,
		saveTitle: title,
		lastResponse: {
			assistant_message: assistantMessage,
			clarifying_question: clarifyingQuestion,
			suggested_prompts: suggestedPrompts,
			media_items: mediaItems,
			popup_draft: draft,
			validation,
		},
		savedPopup: {
			postId:
				Number.isFinite( postId ) && postId > 0
					? Math.floor( postId )
					: 0,
			title,
			status: normalizeText( response?.status ),
			popupType,
			editUrl: normalizeText( response?.editUrl ),
			previewUrl: normalizeText( response?.previewUrl ),
			updatedExisting: true,
		},
	};
};
