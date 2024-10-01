import { useSelect } from "@wordpress/data";
import { store as editorStore } from "@wordpress/editor";
import { store as coreStore } from "@wordpress/core-data";

const usePostTypeLabels = () => {
    const currentPostType = useSelect( select => {
        return select( editorStore )?.getCurrentPostType();
    }, [] );

    const postType = useSelect( select => {
        return select( coreStore )?.getPostType( currentPostType );
    }, [ currentPostType ] );

    return postType?.labels;
};

export default usePostTypeLabels;