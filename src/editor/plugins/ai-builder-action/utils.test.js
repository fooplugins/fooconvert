import { describe, expect, it } from 'vitest';

import {
	addPostIdToUrl,
	buildAiBuilderEditUrl,
	buildPopupStatsUrl,
	getAiBuilderActionState,
} from './utils';

describe( 'addPostIdToUrl', () => {
	it( 'adds a post ID to a relative admin URL', () => {
		expect(
			addPostIdToUrl(
				'admin.php?page=fooconvert-ai-popup-builder',
				42
			)
		).toBe(
			'admin.php?page=fooconvert-ai-popup-builder&post_id=42'
		);
	} );

	it( 'replaces an existing post ID while preserving other query args', () => {
		expect(
			addPostIdToUrl(
				'admin.php?page=fooconvert-ai-popup-builder&post_id=7&tab=builder',
				42
			)
		).toBe(
			'admin.php?page=fooconvert-ai-popup-builder&post_id=42&tab=builder'
		);
	} );

	it( 'preserves absolute admin URLs and hash fragments', () => {
		expect(
			addPostIdToUrl(
				'https://example.test/wp-admin/admin.php?page=fooconvert-ai-popup-builder#top',
				42
			)
		).toBe(
			'https://example.test/wp-admin/admin.php?page=fooconvert-ai-popup-builder&post_id=42#top'
		);
	} );

	it( 'returns an empty string for invalid input', () => {
		expect( addPostIdToUrl( '', 42 ) ).toBe( '' );
		expect(
			addPostIdToUrl(
				'admin.php?page=fooconvert-ai-popup-builder',
				0
			)
		).toBe( '' );
	} );
} );

describe( 'builder URLs', () => {
	it( 'builds the AI builder edit URL', () => {
		expect(
			buildAiBuilderEditUrl(
				'admin.php?page=fooconvert-ai-popup-builder',
				12
			)
		).toBe(
			'admin.php?page=fooconvert-ai-popup-builder&post_id=12'
		);
	} );

	it( 'builds the popup stats URL', () => {
		expect(
			buildPopupStatsUrl(
				'admin.php?page=fooconvert-popup-stats',
				12
			)
		).toBe( 'admin.php?page=fooconvert-popup-stats&post_id=12' );
	} );
} );

describe( 'getAiBuilderActionState', () => {
	const baseState = {
		builderUrl: 'admin.php?page=fooconvert-ai-popup-builder',
		statsUrlBase: 'admin.php?page=fooconvert-popup-stats',
		currentPostId: 12,
		currentPostType: 'fc-popup',
	};

	it( 'renders stats before the AI builder action for popup posts', () => {
		expect( getAiBuilderActionState( baseState ) ).toEqual( {
			shouldRender: true,
			rows: [
				{
					type: 'stats',
					href: 'admin.php?page=fooconvert-popup-stats&post_id=12',
					target: '_blank',
				},
				{
					type: 'ai-builder',
					disabled: false,
					disabledReason: '',
					href: 'admin.php?page=fooconvert-ai-popup-builder&post_id=12',
				},
			],
		} );
	} );

	it( 'does not render outside popup posts', () => {
		expect(
			getAiBuilderActionState( {
				...baseState,
				currentPostType: 'post',
			} )
		).toEqual( {
			shouldRender: false,
			rows: [],
		} );
	} );

	it( 'removes the AI href while the editor has unsaved changes', () => {
		expect(
			getAiBuilderActionState( {
				...baseState,
				hasUnsavedChanges: true,
			} ).rows[ 1 ]
		).toEqual( {
			type: 'ai-builder',
			disabled: true,
			disabledReason: 'dirty',
			href: '',
		} );
	} );

	it( 'removes the AI href while the editor is saving', () => {
		expect(
			getAiBuilderActionState( {
				...baseState,
				isSaving: true,
			} ).rows[ 1 ]
		).toEqual( {
			type: 'ai-builder',
			disabled: true,
			disabledReason: 'saving',
			href: '',
		} );
	} );

	it( 'hides the AI row when the builder URL is unavailable', () => {
		expect(
			getAiBuilderActionState( {
				...baseState,
				builderUrl: '',
			} )
		).toEqual( {
			shouldRender: true,
			rows: [
				{
					type: 'stats',
					href: 'admin.php?page=fooconvert-popup-stats&post_id=12',
					target: '_blank',
				},
			],
		} );
	} );
} );
