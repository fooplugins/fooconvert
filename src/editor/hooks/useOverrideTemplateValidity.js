import { useDispatch, useSelect } from "@wordpress/data";
import { useEffect } from "@wordpress/element";
import { store as blockEditorStore } from "@wordpress/block-editor";
import { store as editorStore } from "@wordpress/editor";

/**
 * Overrides the computed template validity and sets it to `true`.
 *
 * @remarks
 * This method overrides the validation result of templates within the editor. The reason for this is an invalid error is
 * displayed when the CPT `template_lock` property is set to `"all"` and one of the child blocks contains an
 * `<InnerBlocks/>` with the `templateLock` property set to `false`.
 *
 * @see https://github.com/WordPress/gutenberg/issues/11681
 */
const useOverrideTemplateValidity = () => {
    const { setTemplateValidity } = useDispatch( blockEditorStore );
    const { currentPostType, isValidTemplate } = useSelect( ( select ) => ( {
        currentPostType: select( editorStore ).getCurrentPostType(),
        isValidTemplate: select( blockEditorStore ).isValidTemplate(),
    } ), [] );

    useEffect( () => {
        if ( currentPostType === "fc-popup" && isValidTemplate === false ) {
            setTemplateValidity( true );
        }
    }, [ currentPostType, isValidTemplate, setTemplateValidity ] );
};

export default useOverrideTemplateValidity;
