import usePostMetaKey from "../../../hooks/usePostMetaKey";

import editorData from "../editorData";

/**
 * @typedef {import("../types").DisplayRulesMeta} DisplayRulesMeta
 */

/**
 * Hook to wrap the display rules metadata logic.
 *
 * @return {import("../../../types/postMeta").PostMetaState<DisplayRulesMeta>} A tuple containing the `DisplayRulesMeta` object and a setter callback.
 */
const useDisplayRulesMeta = () => usePostMetaKey( editorData.meta.key, editorData.meta.defaults );

export default useDisplayRulesMeta;
