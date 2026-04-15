import { useSelect } from "@wordpress/data";
import { store as editorStore } from "@wordpress/editor";

const EXPERIMENT_META_KEY = "_fooconvert_popup_experiment_id";
const EXPERIMENT_ROLE_META_KEY = "_fooconvert_popup_experiment_role";
const EXPERIMENT_LABEL_META_KEY = "_fooconvert_popup_experiment_label";

const useExperimentVariantLock = () => {
    return useSelect( select => {
        const editor = select( editorStore );
        const meta = editor?.getEditedPostAttribute( "meta" ) || {};
        const experimentId = Number( meta?.[ EXPERIMENT_META_KEY ] || 0 );
        const role = `${ meta?.[ EXPERIMENT_ROLE_META_KEY ] || "" }`;
        const label = `${ meta?.[ EXPERIMENT_LABEL_META_KEY ] || "" }`;
        const isVariant = experimentId > 0 && role === "variant";

        return {
            experimentId,
            role,
            label,
            isVariant,
            isLocked: isVariant,
        };
    }, [] );
};

export default useExperimentVariantLock;
