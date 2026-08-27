import { isNumber, isPlainObject, isString } from "@steveush/utils";

import isEntityRecordToken from "./isEntityRecordToken";

const hasSearch = ( search, minChars ) => isString( search ) && search.length >= minChars;

/**
 * Build the query used for the primary entity search.
 *
 * @param {{}} queryArgs Additional query arguments.
 * @param {string} search Current search value.
 * @param {number} minChars Minimum search length.
 * @param {number} perPage Maximum records to request.
 * @return {{}} Query arguments.
 */
export const makeSearchArgs = ( queryArgs, search, minChars, perPage ) => {
    const args = isPlainObject( queryArgs ) ? { ...queryArgs } : {};
    if ( hasSearch( search, minChars ) ) {
        args.search = search;
    }
    if ( isNumber( perPage ) ) {
        args.per_page = perPage;
    }
    return args;
};

/**
 * Build a companion exact-slug query from the primary search arguments.
 *
 * REST post collections cannot include slugs in `search_columns`, so slug
 * matching must use the collection's dedicated `slug` parameter.
 *
 * @param {{}} queryArgs Additional query arguments.
 * @param {string} search Current search value.
 * @param {number} minChars Minimum search length.
 * @param {number} perPage Maximum records to request.
 * @return {{}} Query arguments.
 */
export const makeSlugSearchArgs = ( queryArgs, search, minChars, perPage ) => {
    const args = isPlainObject( queryArgs ) ? { ...queryArgs } : {};

    delete args.search;
    delete args.search_columns;
    delete args.slug;

    if ( args.orderby === "relevance" ) {
        delete args.orderby;
    }

    if ( hasSearch( search, minChars ) ) {
        args.slug = [ search.trim() ];
    }
    if ( isNumber( perPage ) ) {
        args.per_page = perPage;
    }
    return args;
};

/**
 * Stringify a suggestion with the active search value included.
 *
 * FormTokenField performs its own substring filtering against the serialized
 * suggestion. Including the server-approved search value prevents it from
 * discarding title or slug matches before rendering them.
 *
 * @param {EntityRecordToken} token Entity token.
 * @param {string} search Active search value.
 * @return {string|null} Serialized suggestion.
 */
export const stringifyEntityRecordSuggestion = ( token, search ) => {
    if ( !isEntityRecordToken( token ) || !isString( search, true ) ) {
        return null;
    }
    return JSON.stringify(
        { ...token, search },
        [ 'id', 'label', 'search' ]
    );
};
