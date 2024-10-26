import { useDispatch, useSelect } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { cleanObject } from "@steveush/utils";

/**
 *
 * @param {string} clientId
 * @returns {[object,((value:(object|undefined))=>void)]}
 */
const useBlockAttributes = clientId => {
    const blockAttributes = useSelect( select => select( blockEditorStore )?.getBlockAttributes( clientId ) ?? {}, [ clientId ] );
    const { updateBlockAttributes } = useDispatch( blockEditorStore );
    const setBlockAttributes = value => {
        let nextValue = typeof value === 'object' ? value : {};
        // noinspection JSIgnoredPromiseFromCall,JSCheckFunctionSignatures
        updateBlockAttributes( clientId, nextValue, false );
    };

    return [ blockAttributes, setBlockAttributes ];
};

export default useBlockAttributes;