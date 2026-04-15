import { isString } from "@steveush/utils";

/**
 * Check if a value is a non-empty string.
 *
 * @param {unknown} value - The value to check.
 * @returns {value is string}
 */
const isStringNotEmpty = value => isString( value, true );

export default isStringNotEmpty;
