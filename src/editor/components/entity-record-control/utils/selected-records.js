import { isNumber, isPlainObject, isString } from "@steveush/utils";

/**
 * Build a lightweight query for the records currently selected by the control.
 *
 * @param {{}} queryArgs Additional query arguments.
 * @param {number[]} ids Selected record IDs.
 * @return {{}} Query arguments.
 */
export const makeSelectedRecordArgs = ( queryArgs, ids ) => {
    const args = isPlainObject( queryArgs ) ? { ...queryArgs } : {};
    const include = Array.isArray( ids ) ? ids.filter( isNumber ) : [];

    delete args.orderby;
    delete args.search;
    delete args.search_columns;
    delete args.slug;

    args.include = include;
    args.per_page = Math.max( include.length, 1 );
    args._fields = 'id,link,slug';

    return args;
};

/**
 * Get a record's permalink path relative to the current site.
 *
 * @param {{link?: string, slug?: string}} record Entity record.
 * @return {string} Relative permalink path, or an empty string.
 */
export const getRelativeSlug = record => {
    if ( isString( record?.link, true ) ) {
        try {
            const pathname = new URL( record.link, 'https://example.invalid' ).pathname;
            if ( pathname !== '/' ) {
                return pathname;
            }
        } catch {
            // Fall back to the record slug below.
        }
    }

    if ( isString( record?.slug, true ) ) {
        return `/${ record.slug.replace( /^\/+|\/+$/g, '' ) }/`;
    }

    return '';
};
