import { describe, expect, it } from 'vitest';

import {
	appendReasoningDelta,
	createAssistantChatMessage,
	getConversationPayloadMessages,
} from './chat-support';

describe( 'AI popup builder chat support', () => {
	it( 'creates assistant messages with UI-only activity and reasoning metadata', () => {
		expect(
			createAssistantChatMessage( {
				content: 'Here is a popup direction.',
				reasoningSummary:
					'Reviewed the offer and selected a concise CTA.',
				activityLog: [
					{
						type: 'tool_call',
						label: 'fooconvert/get-conversion-playbook',
						summary: 'Goal: lead capture',
					},
					{
						type: 'tool_result',
						label: 'fooconvert/get-conversion-playbook',
						summary: 'Returned 4 tactics',
					},
				],
			} )
		).toEqual( {
			role: 'assistant',
			content: 'Here is a popup direction.',
			reasoningSummary: 'Reviewed the offer and selected a concise CTA.',
			activityLog: [
				{
					type: 'tool_call',
					label: 'fooconvert/get-conversion-playbook',
					summary: 'Goal: lead capture',
					hasResult: true,
					resultSummary: 'Returned 4 tactics',
				},
			],
		} );
	} );

	it( 'keeps activity and failed assistant UI entries out of API payload messages', () => {
		const messages = [
			{ role: 'user', content: 'Build a popup' },
			{
				role: 'assistant',
				content: 'Here is the popup.',
				reasoningSummary: 'Checked the playbook.',
				activityLog: [ { type: 'status', label: 'Calling AI model' } ],
			},
			{
				role: 'assistant',
				content: 'The request failed.',
				requestStatus: 'error',
				activityLog: [ { type: 'status', label: 'Calling AI model' } ],
			},
			{ role: 'user', content: '   ' },
		];

		expect( getConversationPayloadMessages( messages ) ).toEqual( [
			{ role: 'user', content: 'Build a popup' },
			{ role: 'assistant', content: 'Here is the popup.' },
		] );
	} );

	it( 'appends reasoning deltas and replaces them with completed summary text', () => {
		expect( appendReasoningDelta( '', 'Reviewed the offer' ) ).toBe(
			'Reviewed the offer'
		);
		expect( appendReasoningDelta( 'Reviewed the offer', ' and CTA' ) ).toBe(
			'Reviewed the offer and CTA'
		);
		expect(
			appendReasoningDelta(
				'Reviewed the offer',
				'Reviewed the offer and CTA.'
			)
		).toBe( 'Reviewed the offer and CTA.' );
		expect( appendReasoningDelta( 'Reviewed the offer', '' ) ).toBe(
			'Reviewed the offer'
		);
	} );
} );
