import { describe, expect, it } from "vitest";

import { getPopupTypeFromLocation, normalizePopupType } from "./popupType";

describe( "popupType utils", () => {
    it( "normalizes popup type aliases", () => {
        expect( normalizePopupType( "fc/overlay" ) ).toBe( "overlay" );
        expect( normalizePopupType( "FC-BAR" ) ).toBe( "bar" );
        expect( normalizePopupType( "fc-flyout" ) ).toBe( "flyout" );
        expect( normalizePopupType( "unknown" ) ).toBe( "" );
    } );

    it( "reads the popup type from the current location search", () => {
        expect( getPopupTypeFromLocation( "?post_type=fc-popup&popup_type=flyout" ) ).toBe( "flyout" );
    } );

    it( "returns an empty popup type when the query param is absent", () => {
        expect( getPopupTypeFromLocation( "?post_type=fc-popup" ) ).toBe( "" );
    } );
} );
