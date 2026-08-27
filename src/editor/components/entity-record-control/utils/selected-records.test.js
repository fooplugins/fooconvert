import { describe, expect, it } from "vitest";

import { getRelativeSlug, makeSelectedRecordArgs } from "./selected-records";

describe( "selected entity records", () => {
    it( "builds a lightweight query that retains the allowed statuses", () => {
        expect( makeSelectedRecordArgs( {
            status: [ "publish", "draft" ],
            search_columns: [ "post_title" ],
            orderby: "relevance",
        }, [ 42, 84 ] ) ).toEqual( {
            status: [ "publish", "draft" ],
            include: [ 42, 84 ],
            per_page: 2,
            _fields: "id,link,slug",
        } );
    } );

    it( "uses the relative permalink path when available", () => {
        expect( getRelativeSlug( {
            link: "https://example.com/parent/landing-page/",
            slug: "landing-page",
        } ) ).toBe( "/parent/landing-page/" );
    } );

    it( "falls back to the record slug", () => {
        expect( getRelativeSlug( {
            link: "https://example.com/?page_id=42",
            slug: "landing-page",
        } ) ).toBe( "/landing-page/" );
    } );
} );
