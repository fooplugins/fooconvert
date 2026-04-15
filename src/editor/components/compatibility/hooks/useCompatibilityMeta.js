import usePostMetaKey from "../../../hooks/usePostMetaKey";

import editorData from "../editorData";

/**
 * @typedef {import("../types").CompatibilityMeta} CompatibilityMeta
 */

/**
 * Hook to wrap the display rules metadata logic.
 *
 * @return {import("../../../types/postMeta").PostMetaState<CompatibilityMeta>} A tuple containing the `CompatibilityMeta` object and a setter callback.
 */
const useCompatibilityMeta = () => usePostMetaKey( editorData.meta.key, editorData.meta.defaults );

export default useCompatibilityMeta;
