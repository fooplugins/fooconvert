/**
 * Shared compatibility domain types.
 */

/**
 * @typedef {object} CompatibilityMeta
 * @property {boolean} required - Whether compatibility mode is required.
 * @property {boolean} enabled - Whether compatibility mode is enabled.
 */

/**
 * @typedef {object} CompatibilityContentControlProps
 * @property {CompatibilityMeta} compatibility - The current compatibility metadata.
 * @property {import("../../types/postMeta").PostMetaSetCallback<CompatibilityMeta>} setCompatibility - Updates the compatibility metadata.
 * @property {string} [className] - Optional CSS class name.
 */

/**
 * @typedef {object} CompatibilityEditorData
 * @property {import("../../types/postMeta").PostMetaConfig<CompatibilityMeta>} meta - The editor meta configuration.
 */
export {};
