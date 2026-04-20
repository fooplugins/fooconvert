import { useRootAttributes } from "#editor";
import { BlockControls, store as blockEditorStore } from "@wordpress/block-editor";
import { ToolbarButton, ToolbarGroup } from "@wordpress/components";
import { useDispatch, useSelect } from "@wordpress/data";
import { __, sprintf } from "@wordpress/i18n";

const BLOCK_NAME_TARGETS = {
    "fc/bar": {
        closed: "fc/bar-open-button",
        open: "fc/bar-content",
    },
    "fc/flyout": {
        closed: "fc/flyout-open-button",
        open: "fc/flyout-content",
    },
};

const findDescendantClientId = ( block, blockName ) => {
    if ( !block || typeof blockName !== "string" || blockName.length === 0 ) {
        return "";
    }
    if ( block.name === blockName ) {
        return block.clientId;
    }
    if ( Array.isArray( block.innerBlocks ) ) {
        for ( const innerBlock of block.innerBlocks ) {
            const clientId = findDescendantClientId( innerBlock, blockName );
            if ( clientId ) {
                return clientId;
            }
        }
    }
    return "";
};

const ViewStateControls = ( { rootAttributeName } ) => {
    const [ attributes, setAttributes, rootClientId ] = useRootAttributes( rootAttributeName );
    const { selectBlock } = useDispatch( blockEditorStore );
    const { viewState = "open" } = attributes ?? {};
    const targetClientIds = useSelect(
        select => {
            const rootBlock = rootClientId
                ? select( blockEditorStore )?.getBlock( rootClientId )
                : null;
            const targetNames = BLOCK_NAME_TARGETS?.[ rootAttributeName ] ?? {};
            return {
                closed: findDescendantClientId( rootBlock, targetNames.closed ),
                open: findDescendantClientId( rootBlock, targetNames.open ),
            };
        },
        [ rootAttributeName, rootClientId ]
    );
    const toggleViewState = () => {
        const nextViewState = viewState === "open" ? "closed" : "open";
        const targetClientId = targetClientIds?.[ nextViewState ] ?? "";
        setAttributes( { viewState: nextViewState } );
        if ( targetClientId ) {
            setTimeout( () => selectBlock( targetClientId ), 0 );
        }
    };
    const viewStateLabel = viewState === "open" ? __( "Closed", "fooconvert" ) : __( "Open", "fooconvert" );
    const label = sprintf(
        // translators: %s: Open/Closed State
        __( "%s View", "fooconvert" ),
        viewStateLabel
    );

    return (
        <BlockControls group="other">
            <ToolbarGroup>
                <ToolbarButton label={ __( "Toggle View", "fooconvert" ) } onClick={ toggleViewState }>
                    { label }
                </ToolbarButton>
            </ToolbarGroup>
        </BlockControls>
    );
};

export default ViewStateControls;
