import { useDispatch, useSelect } from "@wordpress/data";
import { createBlocksFromInnerBlocksTemplate, store as blocksStore } from "@wordpress/blocks";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { store as editorStore } from "@wordpress/editor";
import { hasKeys, isPlainObject } from "@steveush/utils";

/**
 * Helper hook for block variations.
 *
 * @param {string} clientId - The clientId of the block with variations.
 * @param {Record<string, any>} [resetAttributes] - Optional. The attributes to apply when resetting the variation.
 * @return {{defaultVariation: WPBlockVariation, blockVariations: WPBlockVariation[], canReset: boolean, setVariation: ( value: WPBlockVariation ) => Promise<void>}}
 */
const useVariations = ( clientId, resetAttributes = {} ) => {
    const block = useSelect( select => select( blockEditorStore ).getBlock( clientId ), [ clientId ] );
    const canReset = hasKeys( resetAttributes );
    const { replaceInnerBlocks, updateBlockAttributes } = useDispatch( blockEditorStore );
    const { editPost } = useDispatch( editorStore );
    const meta = useSelect( select => select( editorStore )?.getEditedPostAttribute( "meta" ) || {}, [ clientId ] );
    const { defaultVariation, blockVariations } = useSelect( select => {
        const { getBlockVariations, getDefaultBlockVariation } = select( blocksStore );
        return {
            defaultVariation: getDefaultBlockVariation( block.name ),
            blockVariations: getBlockVariations( block.name )
        };
    }, [ block.name ] );

    return {
        canReset,
        defaultVariation,
        blockVariations,
        setVariation: async( value ) => {
            let innerBlocks = [];
            let attributes;
            if ( value ) {
                innerBlocks = createBlocksFromInnerBlocksTemplate( value?.innerBlocks ?? [] );
                attributes = value?.attributes ?? {};
            } else {
                attributes = resetAttributes;
            }
            // noinspection JSCheckFunctionSignatures
            await replaceInnerBlocks( clientId, innerBlocks, false );
            // noinspection JSCheckFunctionSignatures
            await updateBlockAttributes( clientId, attributes, false );

            if ( isPlainObject( value?.meta ) ) {
                // noinspection JSCheckFunctionSignatures
                await editPost( {
                    meta: {
                        ...meta,
                        ...value.meta
                    }
                } );
            }
        }
    };
};

export default useVariations;
