import { describe, expect, it } from 'vitest';

import { buildCleanBuilderUrl } from './navigation-support';

describe( 'AI popup builder navigation support', () => {
	it( 'keeps only the builder page query parameter', () => {
		expect(
			buildCleanBuilderUrl(
				'https://example.test/wp-admin/admin.php?page=fooconvert-ai-popup-builder&post_id=42&fc_ai_context=brand#top'
			)
		).toBe(
			'https://example.test/wp-admin/admin.php?page=fooconvert-ai-popup-builder'
		);
	} );

	it( 'falls back to the builder page when the current URL cannot be parsed', () => {
		expect( buildCleanBuilderUrl( '' ) ).toBe(
			'admin.php?page=fooconvert-ai-popup-builder'
		);
	} );
} );
