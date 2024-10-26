import isGroupedSelectOption from "./isGroupedSelectOption";
import isGroupedSelectOptGroup from "./isGroupedSelectOptGroup";
import { isArray } from "@steveush/utils";

/**
 * An array where each item can be either a `GroupedSelectOption` or `GroupedSelectOptGroup` object.
 * @typedef {(GroupedSelectOption | GroupedSelectOptGroup)[]} GroupedSelectOptions
 */

/**
 * Check if a value is an array of grouped select options.
 *
 * @param {any} value - The value to check.
 * @returns {value is GroupedSelectOptions} `true` if the value is an empty array or contains only `GroupedSelectOption`
 * and `GroupedSelectOptGroup` objects, otherwise `false`.
 */
const isGroupedSelectOptions = value => isArray( value, false, item => isGroupedSelectOption( item ) || isGroupedSelectOptGroup( item ) );

export default isGroupedSelectOptions;