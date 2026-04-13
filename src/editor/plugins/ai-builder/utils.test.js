import {
    formatAiBuilderDate,
    getDefaultAiBuilderMetadata,
    hasAiBuilderMetadata,
    normalizeAiBuilderMetadata,
    truncateAiBuilderText,
} from "./utils";

describe( "normalizeAiBuilderMetadata", () => {
    it( "fills the expected defaults for empty metadata", () => {
        expect( normalizeAiBuilderMetadata() ).toEqual( getDefaultAiBuilderMetadata() );
    } );

    it( "retains saved chat metadata for editor display", () => {
        expect(
            normalizeAiBuilderMetadata( {
                source: "ai-popup-builder",
                saved_at: "2026-04-13T12:45:00Z",
                messages: [
                    { role: "user", content: "Build a popup" },
                    { role: "assistant", content: "Use a sharper offer." },
                ],
                response: {
                    assistant_message: "Lead with the discount.",
                    clarifying_question: "Should the trigger be exit intent?",
                    suggested_prompts: [ "Tighten the CTA" ],
                    popup_draft: {
                        popup_type: "popup",
                    },
                    validation: {
                        score: 92,
                        strengths: [ "Focused offer" ],
                    },
                    media_items: [
                        {
                            id: 18,
                            url: "https://example.test/generated.jpg",
                            preview_url: "https://example.test/generated-preview.jpg",
                            prompt: "Moody product shot",
                        },
                    ],
                },
                options: {
                    generate_images: true,
                },
            } )
        ).toEqual( {
            source: "ai-popup-builder",
            saved_at: "2026-04-13T12:45:00Z",
            messages: [
                { role: "user", content: "Build a popup" },
                { role: "assistant", content: "Use a sharper offer." },
            ],
            response: {
                assistant_message: "Lead with the discount.",
                clarifying_question: "Should the trigger be exit intent?",
                suggested_prompts: [ "Tighten the CTA" ],
                popup_draft: {
                    popup_type: "popup",
                },
                validation: {
                    score: 92,
                    strengths: [ "Focused offer" ],
                    warnings: [],
                    suggestions: [],
                },
                media_items: [
                    {
                        id: 18,
                        url: "https://example.test/generated.jpg",
                        previewUrl: "https://example.test/generated-preview.jpg",
                        alt: "",
                        title: "",
                        prompt: "Moody product shot",
                        editUrl: "",
                    },
                ],
            },
            options: {
                generate_images: true,
                force_image_generation: false,
            },
        } );
    } );
} );

describe( "hasAiBuilderMetadata", () => {
    it( "returns false for empty metadata", () => {
        expect( hasAiBuilderMetadata( getDefaultAiBuilderMetadata() ) ).toBe( false );
    } );

    it( "returns true when saved AI builder content exists", () => {
        expect( hasAiBuilderMetadata( {
            response: {
                assistant_message: "Draft ready.",
            },
        } ) ).toBe( true );
    } );
} );

describe( "utility helpers", () => {
    it( "formats valid saved dates", () => {
        expect( formatAiBuilderDate( "2026-04-13T12:45:00Z" ) ).not.toBe( "" );
    } );

    it( "truncates long text cleanly", () => {
        expect( truncateAiBuilderText( "1234567890", 6 ) ).toBe( "12345…" );
    } );
} );
