import { isString, isUndefined } from "@steveush/utils";

/**
 * Check if a value is a string or `undefined`.
 *
 * @param {unknown} value - The value to check
 * @returns {value is undefined|string} - `true` if the value is `undefined` or a string, otherwise `false`.
 */
const isStringOrUndefined = value => isUndefined( value ) || isString( value );

export default isStringOrUndefined;
