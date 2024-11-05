import { useSelect, select, dispatch } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { isArray, isString } from "@steveush/utils";

/**
 *
 * @param {string} [blockName]
 * @returns {[object,((value:(object|undefined))=>void),string]|[]}
 */
const useRootAttributes = ( blockName = '' ) => {
    const rootLevel = useSelect(
        select => select( blockEditorStore )?.getBlocks(),
        [ blockName ]
    );
    if ( isArray( rootLevel, true ) ) {
        const rootBlock = isString( blockName, true )
            ? rootLevel?.find( block => block.name === blockName )
            : rootLevel[ 0 ];
        if ( rootBlock && typeof rootBlock?.clientId === 'string' ) {
            const clientId = rootBlock?.clientId;
            const attributes = select( blockEditorStore )?.getBlockAttributes( clientId ) ?? {};
            const setAttributes = value => {
                const nextValue = typeof value === 'object' ? value : {};
                dispatch( blockEditorStore )?.updateBlockAttributes( clientId, nextValue );
            };
            return [ attributes, setAttributes, clientId ];
        }
    }
    return [];
};

export default useRootAttributes;