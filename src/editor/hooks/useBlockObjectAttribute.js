import { useDispatch, useSelect } from "@wordpress/data";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { cleanObject } from "@steveush/utils";

const useBlockObjectAttribute = ( clientId, attributeName ) => {

    const blockAttributes = useSelect( select => select( blockEditorStore )?.getBlockAttributes( clientId ) ?? {}, [ clientId ] );

    const { updateBlockAttributes } = useDispatch( blockEditorStore );

    const attribute = blockAttributes[ attributeName ];

    const setAttribute = ( value ) => {
        const previousValue = typeof attribute === 'object' ? attribute : {};
        let nextValue = typeof value === 'object' ? value : {};
        // noinspection JSIgnoredPromiseFromCall,JSCheckFunctionSignatures
        updateBlockAttributes( clientId, {
            [ attributeName ]: cleanObject( {
                ...previousValue,
                ...nextValue
            } )
        }, false );
    };

    return [ attribute, setAttribute ];
};

export default useBlockObjectAttribute;