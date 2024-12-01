import usePostMetaKey from "../../../hooks/usePostMetaKey";

import editorData from "../editorData";

/**
 * An object representing the compatibility metadata.
 *
 * @typedef CompatibilityMeta
 * @property {boolean} required - Compatibility mode is required for the post.
 * @property {boolean} enabled - Compatibility mode is enabled for the post.
 */

/**
 * The 'set' callback supplied from a call to `useCompatibilityMeta`.
 *
 * @callback SetCompatibilityMetaCallback
 * @param {CompatibilityMeta} value - The value to set.
 * @param {boolean} [save] - Optional. Whether to call savePost after editPost. Defaults to `false`.
 * @returns void
 */

/**
 * Hook to wrap the display rules metadata logic.
 *
 * @return {[ meta: CompatibilityMeta, setMeta: SetCompatibilityMetaCallback ]} A tuple containing the `CompatibilityMeta` object and a `SetCompatibilityMetaCallback` function.
 */
const useCompatibilityMeta = () => usePostMetaKey( editorData.meta.key, editorData.meta.defaults );

export default useCompatibilityMeta;