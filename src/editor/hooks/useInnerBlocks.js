import { useSelect } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";

/**
 * Returns an object containing a boolean indicating whether a block has inner blocks as well as the inner blocks themselves.
 *
 * @param {string} clientId - The client ID of the block.
 * @returns {{ hasInnerBlocks: boolean, innerBlocks: WPBlock[] }} - An object containing a hasInnerBlocks boolean value and an array of the innerBlocks themselves.
 */
const useInnerBlocks = ( clientId ) => {
    let innerBlocks = useSelect(
        ( select ) => select( blockEditorStore )?.getBlock( clientId )?.innerBlocks,
        [ clientId ]
    );
    innerBlocks = Array.isArray( innerBlocks ) ? innerBlocks : [];
    return {
        hasInnerBlocks: innerBlocks.length !== 0,
        innerBlocks
    };
};

export default useInnerBlocks;