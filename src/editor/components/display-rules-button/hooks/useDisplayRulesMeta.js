import usePostMetaKey from "../../../hooks/usePostMetaKey";

import editorData from "../editorData";

/**
 * An object representing the display rules metadata.
 *
 * @typedef DisplayRulesMeta
 * @property {DisplayRulesLocation[]} location - An array of locations where this post should be displayed.
 * @property {DisplayRulesLocation[]} exclude - An array of locations where this post should not be displayed.
 * @property {string[]} users - An array of user roles that can view this post.
 */

/**
 * The 'set' callback supplied from a call to `useDisplayRulesMeta`.
 *
 * @callback SetDisplayRulesMetaCallback
 * @param {DisplayRulesMeta} value - The value to set.
 * @param {boolean} [save] - Optional. Whether to call savePost after editPost. Defaults to `false`.
 * @returns void
 */

/**
 * Hook to wrap the display rules metadata logic.
 *
 * @return {[ meta: DisplayRulesMeta, setMeta: SetDisplayRulesMetaCallback ]} A tuple containing the `DisplayRulesMeta` object and a `SetDisplayRulesMetaCallback` function.
 */
const useDisplayRulesMeta = () => usePostMetaKey( editorData.meta.key, editorData.meta.defaults );

export default useDisplayRulesMeta;