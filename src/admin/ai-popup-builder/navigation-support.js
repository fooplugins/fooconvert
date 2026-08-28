export const aiPopupBuilderPageSlug = 'fooconvert-ai-popup-builder';

export const buildCleanBuilderUrl = (
	currentHref,
	pageSlug = aiPopupBuilderPageSlug
) => {
	const fallbackPageSlug =
		String( pageSlug || '' ).trim() || aiPopupBuilderPageSlug;

	try {
		const url = new URL( String( currentHref || '' ) );
		const page = url.searchParams.get( 'page' ) || fallbackPageSlug;

		url.search = '';
		url.hash = '';
		url.searchParams.set( 'page', page );

		return url.toString();
	} catch {
		return `admin.php?page=${ encodeURIComponent( fallbackPageSlug ) }`;
	}
};
