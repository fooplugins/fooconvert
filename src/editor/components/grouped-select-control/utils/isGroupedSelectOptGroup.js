import { hasKeys, isArray, isString } from "@steveush/utils";
import isGroupedSelectOption from "./isGroupedSelectOption";

/**
 * An object used to represent an <optgroup> within a `GroupedSelectControl`.
 *
 * The first value is the group name, the second is an array of `GroupedSelectOption` objects.
 *
 * @typedef GroupedSelectOptGroup
 * @property {string} group - The slug for the group.
 * @property {string} label - The label for the group.
 * @property {GroupedSelectOption[]} options - An array of `GroupedSelectOption` objects.
 */

/**
 * Check if a value is `GroupedSelectOptGroup` object.
 *
 * @param {any} value - The value to check.
 * @return {value is GroupedSelectOptGroup} `true` if the value is a `GroupedSelectOptGroup` object, otherwise `false`.
 */
const isGroupedSelectOptGroup = value => hasKeys( value, {
    group: grp => isString( grp, true ),
    label: lbl => isString( lbl, true ),
    options: opts => isArray( opts, true, isGroupedSelectOption )
} );

export default isGroupedSelectOptGroup;