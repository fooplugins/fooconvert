import { useDispatch, useSelect, select, dispatch } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { cleanObject, isArray } from "@steveush/utils";

/**
 *
 * @param {string} clientId
 * @param {string} parentName
 * @param {boolean} [last]
 * @returns {[object,((value:(object|undefined))=>void),string]|[]}
 */
const useParentAttributes = ( clientId, parentName, last = false ) => {
    const parents = useSelect(
        select => select( blockEditorStore )?.getBlockParentsByBlockName( clientId, [ parentName ] ) ?? [],
        [ clientId, parentName ]
    );
    if ( isArray( parents, true ) ) {
        const parentClientId = last ? parents.shift() : parents.pop();
        if ( typeof parentClientId === 'string' ) {
            const attributes = select( blockEditorStore )?.getBlockAttributes( parentClientId ) ?? {};
            const setAttributes = value => {
                const nextValue = typeof value === 'object' ? value : {};
                dispatch( blockEditorStore )?.updateBlockAttributes( parentClientId, nextValue );
            };
            return [ attributes, setAttributes, parentClientId ];
        }
    }
    // const attributes = useSelect( select => select( blockEditorStore )?.getBlockAttributes( parentClientId ) ?? {}, [ parentClientId ] );
    // const { updateBlockAttributes } = useDispatch( blockEditorStore );
    // if ( typeof parentClientId !== 'string' ) {
    //     return [];
    // }
    // const setAttributes = value => {
    //     let nextValue = typeof value === 'object' ? value : {};
    //     // noinspection JSIgnoredPromiseFromCall,JSCheckFunctionSignatures
    //     updateBlockAttributes( clientId, nextValue, false );
    // };
    return [];
};

export default useParentAttributes;