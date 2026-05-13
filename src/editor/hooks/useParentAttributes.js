import { useCallback } from "@wordpress/element";
import { useDispatch, useSelect } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";

const EMPTY_OBJECT = {};

/**
 *
 * @param {string} clientId
 * @param {string} parentName
 * @param {boolean} [last]
 * @returns {[object,((value:(object|undefined))=>void),string]|[]}
 */
const useParentAttributes = ( clientId, parentName, last = false ) => {
    const parentClientId = useSelect(
        select => {
            const blockEditorSelect = select( blockEditorStore );
            const getBlockName = blockEditorSelect?.getBlockName;
            const getBlockParents = blockEditorSelect?.getBlockParents;

            if ( typeof getBlockName !== "function" || typeof getBlockParents !== "function" ) {
                return "";
            }

            const parents = ( getBlockParents( clientId ) ?? [] ).filter( parentClientId => {
                return getBlockName( parentClientId ) === parentName;
            } );

            if ( parents.length === 0 ) {
                return "";
            }

            return last ? parents[ 0 ] : parents[ parents.length - 1 ];
        },
        [ clientId, parentName, last ]
    );

    const attributes = useSelect(
        select => {
            if ( typeof parentClientId !== "string" || parentClientId === "" ) {
                return EMPTY_OBJECT;
            }

            return select( blockEditorStore )?.getBlockAttributes( parentClientId ) ?? EMPTY_OBJECT;
        },
        [ parentClientId ]
    );
    const { updateBlockAttributes } = useDispatch( blockEditorStore );
    const setAttributes = useCallback(
        value => {
            if ( typeof parentClientId !== "string" || parentClientId === "" ) {
                return;
            }

            const nextValue = typeof value === 'object' ? value : {};
            updateBlockAttributes( parentClientId, nextValue );
        },
        [ parentClientId, updateBlockAttributes ]
    );

    if ( typeof parentClientId === "string" && parentClientId !== "" ) {
        return [ attributes, setAttributes, parentClientId ];
    }

    return [];
};

export default useParentAttributes;
