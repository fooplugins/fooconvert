import { useSelect } from "@wordpress/data";
import { store as editorStore } from "@wordpress/editor";
import { store as coreStore } from "@wordpress/core-data";

const usePostTypeLabels = ( defaults = {} ) => {
    const currentPostType = useSelect( select => {
        return select( editorStore )?.getCurrentPostType();
    }, [] );

    const postType = useSelect( select => {
        return select( coreStore )?.getPostType( currentPostType );
    }, [ currentPostType ] );

    const found = postType?.labels ?? {};

    return {
        ...defaults,
        ...found
    };
};

export default usePostTypeLabels;