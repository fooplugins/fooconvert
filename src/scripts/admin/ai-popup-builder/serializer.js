import { InnerBlocks } from "@wordpress/block-editor";
import { createBlock, getBlockType, getCategories, registerBlockType, serialize, setCategories } from "@wordpress/blocks";
import { registerCoreBlocks } from "@wordpress/block-library";
import { __ } from "@wordpress/i18n";
import {
    buildRootAttributes,
    extractListItems,
    fooconvertBlockMetadata,
    isPlainObject,
    normalizeDraftBlockAttributes,
    normalizePopupType,
} from "./serializer-support";

export { buildRootAttributes, flattenBlocks, normalizePopupType } from "./serializer-support";

let hasRegisteredBlocks = false;

const saveInnerBlocks = () => <InnerBlocks.Content />;
const saveEmpty = () => null;

const ensureFooconvertCategory = () => {
    const categories = Array.isArray( getCategories?.() ) ? getCategories() : [];

    if ( categories.some( category => category?.slug === "fooconvert" ) ) {
        return;
    }

    setCategories( [
        {
            slug: "fooconvert",
            title: __( "FooConvert", "fooconvert" ),
        },
        ...categories,
    ] );
};

const registerFooconvertBlock = ( metadata, save ) => {
    const name = typeof metadata === "string" ? metadata : metadata?.name;

    if ( getBlockType( name ) ) {
        return;
    }

    if ( typeof metadata === "string" ) {
        registerBlockType( name, {
            title: name,
            category: "widgets",
            save,
        } );
        return;
    }

    registerBlockType( metadata, { save } );
};

export const ensurePopupBuilderBlocksRegistered = () => {
    if ( hasRegisteredBlocks ) {
        return;
    }

    registerCoreBlocks();
    ensureFooconvertCategory();

    fooconvertBlockMetadata.forEach( metadata => {
        const save = [
            "fc/popup-close-button",
            "fc/flyout-open-button",
            "fc/flyout-close-button",
            "fc/bar-open-button",
            "fc/bar-close-button",
            "fc/sign-up",
        ].includes( metadata.name )
            ? saveEmpty
            : saveInnerBlocks;

        registerFooconvertBlock( metadata, save );
    } );

    hasRegisteredBlocks = true;
};

const buildContentBlock = ( block ) => {
    if ( !isPlainObject( block ) || typeof block?.name !== "string" || block.name.length === 0 ) {
        return null;
    }

    const attributes = normalizeDraftBlockAttributes( block.name, block?.attributes );
    const childBlocks = Array.isArray( block?.inner_blocks )
        ? block.inner_blocks.map( buildContentBlock ).filter( Boolean )
        : [];

    switch ( block.name ) {
        case "core/list": {
            // Serialize list content as explicit list-item blocks so current core
            // list markup keeps the items instead of collapsing to an empty <ul>.
            // The AI draft may provide either `items` or legacy `values`.
            // `core/list` handles the actual wrapper markup.
            // `items` is removed from attrs because it is not a list block attribute.
            const listAttributes = { ...attributes };
            delete listAttributes.items;
            delete listAttributes.values;
            return createBlock(
                "core/list",
                listAttributes,
                extractListItems( attributes ).map( item => createBlock( "core/list-item", { content: item }, [] ) )
            );
        }
        case "core/button":
            return createBlock( "core/button", attributes, [] );
        default:
            return createBlock( block.name, attributes, childBlocks );
    }
};

const buildPopupRootBlock = ( draft, templatesBySlug ) => {
    const popupType = normalizePopupType( draft?.popup_type );
    const rootAttributes = buildRootAttributes( draft, templatesBySlug );
    const contentBlocks = Array.isArray( draft?.content_blocks )
        ? draft.content_blocks.map( buildContentBlock ).filter( Boolean )
        : [];

    if ( popupType === "bar" ) {
        return createBlock(
            "fc/bar",
            rootAttributes,
            [
                createBlock( "fc/bar-open-button", {}, [] ),
                createBlock(
                    "fc/bar-container",
                    {},
                    [
                        createBlock( "fc/bar-close-button", {}, [] ),
                        createBlock( "fc/bar-content", {}, contentBlocks ),
                    ]
                ),
            ]
        );
    }

    if ( popupType === "flyout" ) {
        return createBlock(
            "fc/flyout",
            rootAttributes,
            [
                createBlock( "fc/flyout-open-button", {}, [] ),
                createBlock(
                    "fc/flyout-container",
                    {},
                    [
                        createBlock( "fc/flyout-close-button", {}, [] ),
                        createBlock( "fc/flyout-content", {}, contentBlocks ),
                    ]
                ),
            ]
        );
    }

    return createBlock(
        "fc/popup",
        rootAttributes,
        [
            createBlock(
                "fc/popup-container",
                {},
                [
                    createBlock( "fc/popup-close-button", {}, [] ),
                    createBlock( "fc/popup-content", {}, contentBlocks ),
                ]
            ),
        ]
    );
};

export const serializeDraftToMarkup = ( draft, templatesBySlug = {} ) => {
    ensurePopupBuilderBlocksRegistered();

    if ( !isPlainObject( draft ) ) {
        return "";
    }

    const rootBlock = buildPopupRootBlock( draft, templatesBySlug );
    return serialize( [ rootBlock ] );
};
