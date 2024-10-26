import { useDispatch } from "@wordpress/data";
import { useEffect } from "@wordpress/element";

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
    const { setTemplateValidity } = useDispatch( 'core/block-editor' );
    useEffect( () => {
        setTemplateValidity( true );
    }, [] );
};

export default useOverrideTemplateValidity;