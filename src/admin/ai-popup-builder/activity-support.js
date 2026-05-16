import { __ } from '@wordpress/i18n';

const preparingContextActivityStep = {
	type: 'status',
	label: __( 'Preparing popup context', 'fooconvert' ),
	summary: __(
		'Packing the current brand, draft, media, and conversation into the request.',
		'fooconvert'
	),
};

const callingModelActivityStep = {
	type: 'status',
	label: __( 'Calling AI model', 'fooconvert' ),
	summary: __(
		'The model may request template, block, validation, or media tools before it answers.',
		'fooconvert'
	),
};

const buildingDraftActivityStep = {
	type: 'status',
	label: __( 'Building popup draft', 'fooconvert' ),
	summary: __(
		'Creating the initial popup draft from the request.',
		'fooconvert'
	),
};

const updatingDraftActivityStep = {
	type: 'status',
	label: __( 'Updating popup draft', 'fooconvert' ),
	summary: __(
		'Applying the requested changes to the current popup draft.',
		'fooconvert'
	),
};

export const pendingActivitySteps = [
	preparingContextActivityStep,
	callingModelActivityStep,
	buildingDraftActivityStep,
];

export const pendingUpdateActivitySteps = [
	callingModelActivityStep,
	updatingDraftActivityStep,
];

export const getPendingActivitySteps = ( { hasExistingDraft = false } = {} ) =>
	hasExistingDraft ? pendingUpdateActivitySteps : pendingActivitySteps;

const normalizeActivityItems = ( items ) =>
	Array.isArray( items ) ? items.filter( Boolean ) : [];

const findMatchingToolCallIndex = ( rows, item ) => {
	for ( let index = rows.length - 1; index >= 0; index-- ) {
		const row = rows[ index ];

		if (
			row?.type === 'tool_call' &&
			row?.label === item?.label &&
			! row?.hasResult
		) {
			return index;
		}
	}

	return -1;
};

export const combineActivityResultItems = ( items ) =>
	normalizeActivityItems( items ).reduce( ( rows, item ) => {
		if ( item?.type !== 'tool_result' ) {
			rows.push( item );
			return rows;
		}

		const toolCallIndex = findMatchingToolCallIndex( rows, item );

		if ( toolCallIndex === -1 ) {
			rows.push( item );
			return rows;
		}

		rows[ toolCallIndex ] = {
			...rows[ toolCallIndex ],
			hasResult: true,
			resultSummary: item?.summary || '',
		};

		return rows;
	}, [] );

export const getActivityTimelineMode = ( { isSending, liveActivityLog } ) => {
	if ( ! isSending ) {
		return 'complete';
	}

	return normalizeActivityItems( liveActivityLog ).length > 0
		? 'live'
		: 'placeholder';
};

export const getDisplayActivityLog = ( {
	isSending,
	liveActivityLog,
	activityLog,
	hasExistingDraft = false,
} ) => {
	const liveRows = normalizeActivityItems( liveActivityLog );

	if ( isSending ) {
		return combineActivityResultItems(
			liveRows.length > 0
				? liveRows
				: getPendingActivitySteps( { hasExistingDraft } )
		);
	}

	return combineActivityResultItems( activityLog );
};

export const getFailedRequestActivityLog = ( {
	liveActivityLog,
	activeIndex,
	hasExistingDraft = false,
} ) => {
	const liveRows = normalizeActivityItems( liveActivityLog );

	if ( liveRows.length > 0 ) {
		return liveRows;
	}

	const pendingSteps = getPendingActivitySteps( { hasExistingDraft } );
	const lastVisibleIndex = Math.min(
		pendingSteps.length - 1,
		Math.max(
			0,
			Number.isFinite( Number( activeIndex ) ) ? Number( activeIndex ) : 0
		)
	);

	return pendingSteps.slice( 0, lastVisibleIndex + 1 );
};

export const getActivityItemState = ( {
	mode,
	index,
	rowCount,
	activeIndex,
} ) => {
	if ( mode === 'live' ) {
		return index === rowCount - 1 ? 'current' : 'complete';
	}

	if ( mode === 'placeholder' ) {
		if ( index < activeIndex ) {
			return 'complete';
		}

		if ( index === activeIndex ) {
			return 'current';
		}

		return 'pending';
	}

	return 'complete';
};
