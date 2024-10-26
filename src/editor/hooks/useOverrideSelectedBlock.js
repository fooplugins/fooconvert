import { store as blockEditorStore } from "@wordpress/block-editor";
import { useDispatch } from "@wordpress/data";
import { useEffect } from "@wordpress/element";

/**
 * Creates an effect that overrides the selected block and selects the block specified by the clientId.
 *
 * @param {boolean} isSelected
 * @param {string|null} clientId
 */
const useOverrideSelectedBlock = ( isSelected, clientId ) => {
    const { selectBlock } = useDispatch( blockEditorStore );
    useEffect( () => {
        if ( isSelected && typeof clientId === "string" ) {
            // noinspection JSIgnoredPromiseFromCall,JSCheckFunctionSignatures
            selectBlock( clientId );
        }
    }, [ isSelected, clientId ] );
};

export default useOverrideSelectedBlock;