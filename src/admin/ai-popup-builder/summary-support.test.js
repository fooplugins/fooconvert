import { describe, expect, it } from 'vitest';

import {
	buildLastAssistantMessage,
	getActionSummary,
	getTriggerSummary,
	truncateText,
} from './summary-support';

describe( 'AI popup builder summary support', () => {
	it( 'summarizes primary actions from draft blocks', () => {
		expect(
			getActionSummary( {
				content_blocks: [
					{
						name: 'fc/sign-up',
					},
				],
			} )
		).toBe( 'Lead capture form included' );

		expect(
			getActionSummary( {
				content_blocks: [
					{
						name: 'core/buttons',
						inner_blocks: [
							{
								name: 'core/button',
							},
						],
					},
				],
			} )
		).toBe( 'Primary CTA button included' );
	} );

	it( 'summarizes trigger timing', () => {
		expect(
			getTriggerSummary( {
				popup_type: 'popup',
				trigger: {
					event: 'fc.timer.elapsed',
					where: {
						seconds: 8,
					},
				},
			} )
		).toBe( '8s delay' );

		expect(
			getTriggerSummary( {
				popup_type: 'popup',
				trigger: {
					event: 'fc.scroll.percent',
					where: {
						percent: 55,
					},
				},
			} )
		).toBe( '55% scroll' );
	} );

	it( 'uses the latest successful assistant message', () => {
		expect(
			buildLastAssistantMessage( [
				{ role: 'assistant', content: 'First' },
				{
					role: 'assistant',
					content: 'Failed',
					requestStatus: 'error',
				},
				{ role: 'user', content: 'Try again' },
			] )
		).toBe( 'First' );
	} );

	it( 'truncates long labels', () => {
		expect( truncateText( '  abcdef  ', 4 ) ).toBe( 'abc…' );
	} );
} );
