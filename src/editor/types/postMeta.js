/**
 * Shared editor post meta types.
 *
 * @template T
 * @typedef {object} PostMetaConfig
 * @property {string} key - The post meta key used by the editor.
 * @property {T} defaults - The default value returned when the meta key is missing.
 *
 * @template T
 * @callback PostMetaSetCallback
 * @param {T} value - The value to store.
 * @param {boolean} [save] - Whether to save the post after editing.
 * @returns void
 *
 * @template T
 * @typedef {[T, PostMetaSetCallback<T>]} PostMetaState
 */
export {};
