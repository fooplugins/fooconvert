const trimLeadingSlash = ( value ) =>
	String( value || '' ).replace( /^\/+/, '' );

export const buildRestApiUrl = ( restRoot, path ) => {
	const root = String( restRoot || '' ).trim();

	if ( root.length === 0 ) {
		return String( path || '' ).trim();
	}

	return new URL(
		trimLeadingSlash( path ),
		root.endsWith( '/' ) ? root : `${ root }/`
	).toString();
};

const parseEventData = ( rawData ) => {
	if ( typeof rawData !== 'string' ) {
		return rawData;
	}

	const trimmed = rawData.trim();

	if ( trimmed.length === 0 ) {
		return '';
	}

	try {
		return JSON.parse( trimmed );
	} catch {
		return rawData;
	}
};

export const createEventStreamParser = ( onEvent ) => {
	let buffer = '';
	let currentEvent = 'message';
	let currentData = [];

	const dispatch = () => {
		if ( currentData.length === 0 ) {
			currentEvent = 'message';
			return;
		}

		const rawData = currentData.join( '\n' );

		onEvent?.( {
			event: currentEvent || 'message',
			data: parseEventData( rawData ),
			rawData,
		} );

		currentEvent = 'message';
		currentData = [];
	};

	return {
		push( chunk ) {
			buffer += String( chunk || '' );

			while ( true ) {
				const lineBreakIndex = buffer.indexOf( '\n' );

				if ( lineBreakIndex === -1 ) {
					break;
				}

				let line = buffer.slice( 0, lineBreakIndex );
				buffer = buffer.slice( lineBreakIndex + 1 );

				if ( line.endsWith( '\r' ) ) {
					line = line.slice( 0, -1 );
				}

				if ( line.length === 0 ) {
					dispatch();
					continue;
				}

				if ( line.startsWith( ':' ) ) {
					continue;
				}

				const separatorIndex = line.indexOf( ':' );
				const field =
					separatorIndex === -1
						? line
						: line.slice( 0, separatorIndex );
				let value =
					separatorIndex === -1
						? ''
						: line.slice( separatorIndex + 1 );

				if ( value.startsWith( ' ' ) ) {
					value = value.slice( 1 );
				}

				if ( field === 'event' ) {
					currentEvent = value || 'message';
				} else if ( field === 'data' ) {
					currentData.push( value );
				}
			}
		},
		end() {
			if ( buffer.length > 0 ) {
				this.push( '\n' );
			}

			if ( currentData.length > 0 ) {
				dispatch();
			}
		},
	};
};

const readErrorResponse = async ( response ) => {
	const contentType = response.headers.get( 'content-type' ) || '';

	if ( contentType.includes( 'application/json' ) ) {
		try {
			const payload = await response.json();

			if (
				typeof payload?.message === 'string' &&
				payload.message.length > 0
			) {
				return payload.message;
			}
		} catch {
			return '';
		}
	}

	try {
		return ( await response.text() ).trim();
	} catch {
		return '';
	}
};

export const streamChatRequest = async ( {
	restRoot,
	path,
	nonce,
	payload,
	signal,
	onEvent,
} ) => {
	const response = await fetch( buildRestApiUrl( restRoot, path ), {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			Accept: 'text/event-stream',
			'Content-Type': 'application/json',
			'X-WP-Nonce': String( nonce || '' ),
		},
		body: JSON.stringify( payload || {} ),
		signal,
	} );

	if ( ! response.ok ) {
		const message = await readErrorResponse( response );
		throw new Error( message || 'The AI popup builder request failed.' );
	}

	if ( ! response.body ) {
		throw new Error( 'The AI popup builder stream was not available.' );
	}

	const reader = response.body.getReader();
	const decoder = new TextDecoder();
	let result = null;
	let streamError = '';
	const parser = createEventStreamParser( ( event ) => {
		if ( event?.event === 'result' ) {
			result = event.data;
		} else if ( event?.event === 'error' ) {
			streamError =
				typeof event?.data?.message === 'string'
					? event.data.message
					: String( event?.rawData || '' ).trim();
		}

		onEvent?.( event );
	} );

	while ( true ) {
		const { value, done } = await reader.read();

		if ( done ) {
			break;
		}

		parser.push( decoder.decode( value, { stream: true } ) );
	}

	parser.push( decoder.decode() );
	parser.end();

	if ( streamError.length > 0 ) {
		throw new Error( streamError );
	}

	if ( ! result || typeof result !== 'object' ) {
		throw new Error(
			'The AI popup builder stream completed without a result.'
		);
	}

	return result;
};
