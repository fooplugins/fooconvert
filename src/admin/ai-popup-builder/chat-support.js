import { combineActivityResultItems } from './activity-support';

const normalizeText = ( value ) => String( value || '' ).trim();

export const appendReasoningDelta = ( currentSummary, delta ) => {
	const current = String( currentSummary || '' );
	const next = String( delta || '' );

	if ( next.length === 0 ) {
		return current;
	}

	if ( current.length > 0 && next.startsWith( current ) ) {
		return next;
	}

	if ( current.endsWith( next ) ) {
		return current;
	}

	return `${ current }${ next }`;
};

export const createAssistantChatMessage = ( {
	content = '',
	activityLog = [],
	reasoningSummary = '',
	requestStatus = 'complete',
} = {} ) => {
	const message = {
		role: 'assistant',
		content: String( content || '' ),
	};
	const displayActivityLog = combineActivityResultItems( activityLog );
	const displayReasoningSummary = normalizeText( reasoningSummary );

	if ( displayActivityLog.length > 0 ) {
		message.activityLog = displayActivityLog;
	}

	if ( displayReasoningSummary.length > 0 ) {
		message.reasoningSummary = displayReasoningSummary;
	}

	if ( requestStatus && requestStatus !== 'complete' ) {
		message.requestStatus = requestStatus;
	}

	return message;
};

export const getConversationPayloadMessages = ( messages ) =>
	Array.isArray( messages )
		? messages.reduce( ( rows, message ) => {
				if ( message?.requestStatus === 'error' ) {
					return rows;
				}

				const content = normalizeText( message?.content );

				if ( content.length === 0 ) {
					return rows;
				}

				rows.push( {
					role: message?.role === 'assistant' ? 'assistant' : 'user',
					content,
				} );

				return rows;
		  }, [] )
		: [];
