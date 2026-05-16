import { describe, expect, it } from 'vitest';

import {
	getActivityItemState,
	getActivityTimelineMode,
	getDisplayActivityLog,
	getFailedRequestActivityLog,
	getPendingActivitySteps,
	pendingActivitySteps,
	pendingUpdateActivitySteps,
} from './activity-support';

describe( 'AI popup builder activity support', () => {
	it( 'marks the newest streamed activity row as current immediately', () => {
		const liveActivityLog = [
			{ type: 'status', label: 'Preparing popup context' },
			{ type: 'status', label: 'Calling AI model' },
			{
				type: 'tool_call',
				label: 'fooconvert/get-conversion-playbook',
				summary: 'Goal: lead capture',
			},
			{
				type: 'tool_result',
				label: 'fooconvert/get-conversion-playbook',
				summary: 'Returned 3 tactics',
			},
			{ type: 'status', label: 'Building popup draft' },
		];
		const mode = getActivityTimelineMode( {
			isSending: true,
			liveActivityLog,
		} );
		const displayActivityLog = getDisplayActivityLog( {
			isSending: true,
			liveActivityLog,
			activityLog: [],
		} );

		expect( mode ).toBe( 'live' );
		expect( displayActivityLog ).toEqual( [
			{ type: 'status', label: 'Preparing popup context' },
			{ type: 'status', label: 'Calling AI model' },
			{
				type: 'tool_call',
				label: 'fooconvert/get-conversion-playbook',
				summary: 'Goal: lead capture',
				hasResult: true,
				resultSummary: 'Returned 3 tactics',
			},
			{ type: 'status', label: 'Building popup draft' },
		] );
		expect(
			displayActivityLog.map( ( item, index ) =>
				getActivityItemState( {
					mode,
					index,
					rowCount: displayActivityLog.length,
					activeIndex: 0,
				} )
			)
		).toEqual( [ 'complete', 'complete', 'complete', 'current' ] );
	} );

	it( 'keeps the placeholder timeline for non-streamed in-flight requests', () => {
		const mode = getActivityTimelineMode( {
			isSending: true,
			liveActivityLog: [],
		} );
		const displayActivityLog = getDisplayActivityLog( {
			isSending: true,
			liveActivityLog: [],
			activityLog: [],
		} );

		expect( mode ).toBe( 'placeholder' );
		expect( displayActivityLog ).toEqual( pendingActivitySteps );
		expect(
			displayActivityLog.map( ( item, index ) =>
				getActivityItemState( {
					mode,
					index,
					rowCount: displayActivityLog.length,
					activeIndex: 1,
				} )
			)
		).toEqual( [ 'complete', 'current', 'pending' ] );
	} );

	it( 'uses update placeholders when a request is revising an existing draft', () => {
		const displayActivityLog = getDisplayActivityLog( {
			isSending: true,
			liveActivityLog: [],
			activityLog: [],
			hasExistingDraft: true,
		} );

		expect( getPendingActivitySteps( { hasExistingDraft: true } ) ).toEqual(
			pendingUpdateActivitySteps
		);
		expect( displayActivityLog.map( ( item ) => item.label ) ).toEqual( [
			'Calling AI model',
			'Updating popup draft',
		] );
	} );

	it( 'shows the completed full timeline without dropping streamed status rows and combines tool results', () => {
		const fullActivityLog = [
			{ type: 'status', label: 'Preparing popup context' },
			{ type: 'status', label: 'Calling AI model' },
			{
				type: 'tool_call',
				label: 'fooconvert/get-conversion-playbook',
				summary: 'Goal: lead capture',
			},
			{
				type: 'tool_result',
				label: 'fooconvert/get-conversion-playbook',
				summary: 'Returned 3 tactics',
			},
			{ type: 'status', label: 'Building popup draft' },
		];
		const mode = getActivityTimelineMode( {
			isSending: false,
			liveActivityLog: fullActivityLog,
		} );
		const displayActivityLog = getDisplayActivityLog( {
			isSending: false,
			liveActivityLog: fullActivityLog,
			activityLog: fullActivityLog,
		} );

		expect( mode ).toBe( 'complete' );
		expect( displayActivityLog.map( ( item ) => item.label ) ).toEqual( [
			'Preparing popup context',
			'Calling AI model',
			'fooconvert/get-conversion-playbook',
			'Building popup draft',
		] );
		expect( displayActivityLog[ 2 ] ).toMatchObject( {
			type: 'tool_call',
			hasResult: true,
			resultSummary: 'Returned 3 tactics',
		} );
		expect(
			displayActivityLog.map( ( item, index ) =>
				getActivityItemState( {
					mode,
					index,
					rowCount: displayActivityLog.length,
					activeIndex: 0,
				} )
			)
		).toEqual( [ 'complete', 'complete', 'complete', 'complete' ] );
	} );

	it( 'keeps the latest visible activity when a request fails', () => {
		const liveActivityLog = [
			{ type: 'status', label: 'Preparing popup context' },
			{ type: 'status', label: 'Calling AI model' },
		];

		expect(
			getFailedRequestActivityLog( {
				liveActivityLog,
				activeIndex: 2,
			} )
		).toEqual( liveActivityLog );

		expect(
			getFailedRequestActivityLog( {
				liveActivityLog: [],
				activeIndex: 1,
			} )
		).toEqual( pendingActivitySteps.slice( 0, 2 ) );

		expect(
			getFailedRequestActivityLog( {
				liveActivityLog: [],
				activeIndex: 1,
				hasExistingDraft: true,
			} )
		).toEqual( pendingUpdateActivitySteps );
	} );
} );
