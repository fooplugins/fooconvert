import { beforeAll, describe, expect, it } from "vitest";

globalThis.FOOCONVERT_CONFIG = {
    endpoint: {
        url: "https://example.test/wp-admin/admin-ajax.php",
        nonce: "test-nonce"
    },
    popups: [ "fc-test-triggered" ]
};

let TestTriggeredElement;

beforeAll( async () => {
    const { default: TriggeredElement } = await import( "./TriggeredElement" );

    TestTriggeredElement = class extends TriggeredElement {
        logCalls = [];

        get config() {
            return {
                postId: 123,
                postType: "fc-popup",
                template: "test"
            };
        }

        log( type, data ) {
            this.logCalls.push( { type, data } );
            return Promise.resolve( { success: true } );
        }
    };

    if ( !customElements.get( "fc-test-triggered" ) ) {
        customElements.define( "fc-test-triggered", TestTriggeredElement );
    }
} );

describe( "TriggeredElement", () => {
    it( "normalizes missing metadata when opening", () => {
        const element = document.createElement( "fc-test-triggered" );
        let openEvents = 0;

        element.addEventListener( "open", () => {
            openEvents += 1;
        } );

        expect( () => element.setOpen( true ) ).not.toThrow();
        expect( openEvents ).toBe( 1 );
        expect( element.logCalls ).toEqual( [
            { type: "open", data: {} }
        ] );
    } );

    it( "normalizes missing metadata when closing", () => {
        const element = document.createElement( "fc-test-triggered" );
        let closeEvents = 0;

        element.addEventListener( "close", () => {
            closeEvents += 1;
        } );

        expect( () => element.onOpenChanged( false ) ).not.toThrow();
        expect( closeEvents ).toBe( 1 );
        expect( element.logCalls ).toEqual( [
            { type: "close", data: {} }
        ] );
    } );
} );
