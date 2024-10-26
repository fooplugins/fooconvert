import { useDispatch, useSelect } from "@wordpress/data";
import { store as editorStore } from "@wordpress/editor";
import { clone, hasKeys, isUndefined } from "@steveush/utils";

/**
 *
 * @template T
 * @param {string} key
 * @param {T} [initial]
 * @return {[ T, ( value: T, save?: boolean ) => void ]}
 */
const usePostMetaKey = ( key, initial ) => {
    const { editPost, savePost } = useDispatch( editorStore );
    const meta = useSelect( select => {
        return select( editorStore )?.getEditedPostAttribute( 'meta' );
    }, [ key ] );

    const defaults = clone( initial );

    const setPostMetaKey = ( value, save = false ) => {
        const updated = {
            meta: {
                ...meta,
                [ key ]: isUndefined( value ) ? defaults : value
            }
        };
        editPost( updated );
        if ( save ) {
            savePost( updated );
        }
    };

    let value = hasKeys( meta, key ) ? clone( meta[ key ] ) : defaults;
    return [ value, setPostMetaKey ];
};

export default usePostMetaKey;