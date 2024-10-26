import { isString, isUndefined } from "@steveush/utils";

/**
 * Check if a value is `undefined` or a non-empty `string`.
 *
 * @remarks
 * A string is considered empty if it:
 * - has zero length
 * - contains only whitespace
 *
 * @param {any} value - The value to check.
 * @returns {value is undefined|value is string} - `true` if the value is `undefined` or a non-empty `string`, otherwise `false`.
 * @see $string
 */
const is_$string = value => isUndefined( value ) || isString( value, true );

export default is_$string;