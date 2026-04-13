import {
    buildRootAttributes,
    extractListItems,
    fooconvertBlockMetadata,
    normalizeDraftBlockAttributes,
} from "./serializer-support";

describe( "AI popup builder serializer support", () => {
    it( "uses the real Fooconvert block metadata for custom blocks", () => {
        const popupBlock = fooconvertBlockMetadata.find( metadata => metadata.name === "fc/popup" );
        const signUpBlock = fooconvertBlockMetadata.find( metadata => metadata.name === "fc/sign-up" );

        expect( popupBlock.apiVersion ).toBe( 3 );
        expect( popupBlock.attributes ).toHaveProperty( "template" );
        expect( popupBlock.attributes ).toHaveProperty( "settings" );
        expect( popupBlock.attributes ).toHaveProperty( "content" );

        expect( signUpBlock.apiVersion ).toBe( 3 );
        expect( signUpBlock.attributes ).toHaveProperty( "settings" );
        expect( signUpBlock.attributes ).toHaveProperty( "inputs" );
        expect( signUpBlock.attributes ).toHaveProperty( "button" );
    } );

    it( "normalizes list items from both structured items and legacy HTML values", () => {
        expect(
            extractListItems( {
                items: [
                    "Free shipping on every order",
                    "Early access to new drops",
                ],
            } )
        ).toEqual( [
            "Free shipping on every order",
            "Early access to new drops",
        ] );

        expect(
            extractListItems( {
                values: [
                    "Claim your first-order discount",
                    "Get launch updates by email",
                ],
            } )
        ).toEqual( [
            "Claim your first-order discount",
            "Get launch updates by email",
        ] );

        expect(
            extractListItems( {
                values: "<li>Claim your first-order discount</li><li>Get launch updates by email</li>",
            } )
        ).toEqual( [
            "Claim your first-order discount",
            "Get launch updates by email",
        ] );
    } );

    it( "maps shorthand sign-up aliases into the block's nested attribute shape", () => {
        expect(
            normalizeDraftBlockAttributes( "fc/sign-up", {
                buttonText: "Unlock 15% Off",
                successMessage: "Code sent!",
                closeOnSuccess: true,
                emailOnly: true,
                emailPlaceholder: "Enter your email",
            } )
        ).toMatchObject( {
            settings: {
                successMessage: "Code sent!",
                closeOnSuccess: true,
            },
            inputs: {
                settings: {
                    emailOnly: true,
                    emailPlaceholder: "Enter your email",
                },
            },
            button: {
                settings: {
                    text: "Unlock 15% Off",
                },
            },
        } );
    } );

    it( "maps legacy image aliases into the core/image attribute shape", () => {
        expect(
            normalizeDraftBlockAttributes( "core/image", {
                src: "https://example.test/generated.jpg",
                mediaId: 55,
                altText: "Generated popup image",
            } )
        ).toMatchObject( {
            url: "https://example.test/generated.jpg",
            id: 55,
            alt: "Generated popup image",
        } );
    } );

    it( "builds conversion-ready root attributes for popup and flyout drafts", () => {
        const popupAttributes = buildRootAttributes(
            {
                popup_type: "popup",
                template_slug: "popup__newsletter_subscribe",
                trigger: {
                    type: "exit_intent",
                    delay_seconds: 7,
                    scroll_percent: 20,
                    lifetime: "page",
                    frequency: "once",
                },
                root_attributes: {
                    content: {
                        styles: {
                            width: "720px",
                        },
                    },
                },
            },
            {}
        );

        expect( popupAttributes.template ).toBe( "popup__newsletter_subscribe" );
        expect( popupAttributes.settings.trigger.steps[ 0 ].event ).toBe( "fc.exit_intent" );
        expect( popupAttributes.settings.trigger.steps[ 0 ].where.delaySeconds ).toBe( 7 );
        expect( popupAttributes.content.styles.width ).toBe( "720px" );
        expect( popupAttributes.openButton ).toBeUndefined();

        const flyoutAttributes = buildRootAttributes(
            {
                popup_type: "flyout",
                template_slug: "",
                trigger: {
                    type: "scroll_percent",
                    delay_seconds: 0,
                    scroll_percent: 35,
                    lifetime: "session",
                    frequency: "repeat",
                },
                root_attributes: {},
            },
            {}
        );

        expect( flyoutAttributes.viewState ).toBe( "open" );
        expect( flyoutAttributes.openButton.settings.hidden ).toBe( true );
        expect( flyoutAttributes.settings.trigger.steps[ 0 ].event ).toBe( "fc.scroll.percent" );
        expect( flyoutAttributes.settings.trigger.steps[ 0 ].where.percent ).toBe( 35 );
    } );
} );
