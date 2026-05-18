import { describe, expect, it, vi } from 'vitest';

vi.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
} ) );

import {
	getSuggestionPromptLibrary,
	getSuggestionPrompts,
} from './suggestion-support';

const selectedBlockNames = new Set( [
	'core/heading',
	'core/paragraph',
	'fc/countdown',
	'fc/coupon',
] );

describe( 'AI popup builder suggestions', () => {
	it( 'defines 20 tagged suggestions', () => {
		const prompts = getSuggestionPromptLibrary();

		expect( prompts ).toHaveLength( 20 );
		expect(
			prompts.every(
				( prompt ) =>
					typeof prompt.text === 'string' &&
					prompt.text.length > 0 &&
					Array.isArray( prompt.tags ) &&
					prompt.tags.length > 0
			)
		).toBe( true );
	} );

	it( 'shows five initial creation suggestions before a draft exists', () => {
		const prompts = getSuggestionPrompts( {
			selectedBlockNames,
			limit: 5,
		} );

		expect( prompts ).toHaveLength( 5 );
		expect( prompts.every( ( prompt ) => prompt.phase === 'initial' ) ).toBe(
			true
		);
	} );

	it( 'shows edit suggestions after a draft exists', () => {
		const prompts = getSuggestionPrompts( {
			draft: {
				popup_type: 'popup',
			},
			selectedBlockNames,
			limit: 5,
		} );

		expect( prompts ).toHaveLength( 5 );
		expect( prompts.every( ( prompt ) => prompt.phase === 'edit' ) ).toBe(
			true
		);
		expect( prompts.map( ( prompt ) => prompt.text ) ).toContain(
			'Add a countdown timer for 2 hours in the future.'
		);
	} );

	it( 'hides suggestions that are invalid for the current popup context', () => {
		const prompts = getSuggestionPrompts( {
			draft: {
				popup_type: 'bar',
			},
			selectedBlockNames: new Set( [ 'core/heading' ] ),
			imageGenerationAvailable: false,
			limit: 20,
		} );
		const promptText = prompts.map( ( prompt ) => prompt.text );

		expect( promptText ).not.toContain(
			'Change this popup to be a bar and shorten all the wording used.'
		);
		expect( promptText ).not.toContain(
			'Add a countdown timer for 2 hours in the future.'
		);
		expect( promptText ).not.toContain(
			'Generate a new background image that matches this offer.'
		);
	} );
} );
