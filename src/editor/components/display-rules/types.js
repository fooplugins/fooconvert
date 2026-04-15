/**
 * Shared display-rules domain types.
 *
 * @typedef {import("../grouped-select-control/utils/isGroupedSelectOptions").GroupedSelectOptions} GroupedSelectOptions
 */

/**
 * @typedef {object} DisplayRulesLocationData
 * @property {number} id - The selected entity ID.
 * @property {string} label - The display label for the entity.
 */

/**
 * @typedef {object} DisplayRulesLocation
 * @property {string} type - The location type identifier.
 * @property {DisplayRulesLocationData[]} data - The selected entity data for the location.
 */

/**
 * @typedef {object} DisplayRulesMeta
 * @property {DisplayRulesLocation[]} location - Locations where the content should be shown.
 * @property {DisplayRulesLocation[]} exclude - Locations where the content should be hidden.
 * @property {string[]} users - User roles allowed to view the content.
 */

/**
 * @typedef {object} DisplayRulesCompilationReason
 * @property {"location"|"users"|"rules"} source - The validation source that produced the reason.
 * @property {string} message - The validation message.
 */

/**
 * @typedef {object} DisplayRulesCompiledRules
 * @property {boolean} success - Whether the rules are valid and complete.
 * @property {DisplayRulesCompilationReason[]} reasons - Validation reasons explaining failures.
 * @property {DisplayRulesLocation[]} location - Normalized location rules.
 * @property {DisplayRulesLocation[]} exclude - Normalized exclusion rules.
 * @property {string[]} users - Normalized user rules.
 */

/**
 * @template T
 * @typedef {object} DisplayRulesRepeaterControlProps
 * @property {GroupedSelectOptions} options - The options available in each nested control.
 * @property {T[]} items - The values to display and edit.
 * @property {( items: T[] ) => void} onChange - Called whenever the nested items change.
 * @property {string} [className] - Optional CSS class name.
 * @property {string} [label] - Optional label text.
 * @property {string} [help] - Optional help text.
 * @property {string} [noItemsLabel] - Optional empty-state label.
 * @property {string} [addItemLabel] - Optional add-button label.
 * @property {string} [removeItemLabel] - Optional remove-button label.
 */

/**
 * @template T
 * @typedef {object} DisplayRulesItemControlProps
 * @property {GroupedSelectOptions} options - The options available in the selector.
 * @property {T} value - The value being edited.
 * @property {( value: T, previousValue: T ) => void} onChange - Called whenever the value changes.
 * @property {( value: T ) => void} onRequestRemove - Called when the item should be removed.
 * @property {string} [className] - Optional CSS class name.
 * @property {boolean} [disabled] - Whether the control is disabled.
 * @property {string} [removeItemLabel] - The remove button label.
 */

/**
 * @typedef {DisplayRulesRepeaterControlProps<DisplayRulesLocation>} DisplayRulesLocationsControlProps
 */

/**
 * @typedef {DisplayRulesItemControlProps<DisplayRulesLocation>} DisplayRulesLocationControlProps
 */

/**
 * @typedef {DisplayRulesRepeaterControlProps<string>} DisplayRulesRolesControlProps
 */

/**
 * @typedef {DisplayRulesItemControlProps<string>} DisplayRulesRoleControlProps
 */

/**
 * @typedef {object} DisplayRulesContentControlProps
 * @property {DisplayRulesMeta} rules - The current display rules metadata.
 * @property {import("../../types/postMeta").PostMetaSetCallback<DisplayRulesMeta>} setRules - Updates the display rules metadata.
 * @property {DisplayRulesCompiledRules} compiledRules - The compiled/validated display rules.
 * @property {string} [className] - Optional CSS class name.
 * @property {boolean} [showDescription] - Whether to render the description copy.
 */

/**
 * @typedef {object} DisplayRulesEditorData
 * @property {import("../../types/postMeta").PostMetaConfig<DisplayRulesMeta>} meta - The editor meta configuration.
 * @property {GroupedSelectOptions} location - Location selector options.
 * @property {GroupedSelectOptions} exclude - Exclusion selector options.
 * @property {GroupedSelectOptions} users - User selector options.
 */
export {};
