import { hasKeys } from "@steveush/utils";
import isStringNotEmpty from "../../../utils/isStringNotEmpty";

/**
 * An object used to represent an <option> within a `GroupedSelectControl`.
 *
 * @typedef {{ label:string, value:string }} GroupedSelectOption
 */

/**
 * Check if a value is a `GroupedSelectOption` object.
 *
 * @param {any} value - The value to check.
 * @return {value is GroupedSelectOption} `true` if the value is a `GroupedSelectOption` object, otherwise `false`.
 */
const isGroupedSelectOption = value => hasKeys( value, { value: isStringNotEmpty, label: isStringNotEmpty } );

export default isGroupedSelectOption;