import { describe, expect, it } from "vitest";

import getPopupEditorBackground, { normalizePopupEditorBackground } from "./popupEditorBackground";

describe( "popupEditorBackground utils", () => {
    it( "normalizes supported editor background values", () => {
        expect( normalizePopupEditorBackground( "WHITE" ) ).toBe( "white" );
        expect( normalizePopupEditorBackground( " black " ) ).toBe( "black" );
    } );

    it( "falls back to transparent for invalid values", () => {
        expect( normalizePopupEditorBackground( "checkerboard" ) ).toBe( "transparent" );
        expect( normalizePopupEditorBackground( null ) ).toBe( "transparent" );
    } );

    it( "reads the popup editor background from editor config", () => {
        expect( getPopupEditorBackground( { popupEditorBackground: "white" } ) ).toBe( "white" );
    } );
} );
