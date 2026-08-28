import { describe, expect, it } from "vitest";

import {
    makeSearchArgs,
    makeSlugSearchArgs,
    stringifyEntityRecordSuggestion
} from "./search";

describe( "entity record search arguments", () => {
    const queryArgs = {
        status: [ "publish", "draft" ],
        search_columns: [ "post_title" ],
        orderby: "relevance",
    };

    it( "builds a title-only search that includes drafts", () => {
        expect( makeSearchArgs( queryArgs, "landing", 2, 20 ) ).toEqual( {
            ...queryArgs,
            search: "landing",
            per_page: 20,
        } );
    } );

    it( "builds a companion exact-slug search", () => {
        expect( makeSlugSearchArgs( queryArgs, " landing-page ", 2, 20 ) ).toEqual( {
            status: [ "publish", "draft" ],
            slug: [ "landing-page" ],
            per_page: 20,
        } );
    } );

    it( "does not enable searches below the minimum length", () => {
        expect( makeSearchArgs( queryArgs, "a", 2, 20 ) ).not.toHaveProperty( "search" );
        expect( makeSlugSearchArgs( queryArgs, "a", 2, 20 ) ).not.toHaveProperty( "slug" );
    } );

    it( "keeps server-approved title and slug matches visible to FormTokenField", () => {
        const suggestion = stringifyEntityRecordSuggestion(
            { id: 42, label: "A Different Display Title" },
            "landing-page"
        );

        expect( suggestion ).toContain( "landing-page" );
        expect( JSON.parse( suggestion ) ).toEqual( {
            id: 42,
            label: "A Different Display Title",
            search: "landing-page",
        } );
    } );
} );
