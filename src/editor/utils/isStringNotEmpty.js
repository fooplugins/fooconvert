import { isString } from "@steveush/utils";

/**
 *
 * @param {any} value
 * @returns {value is string}
 */
const isStringNotEmpty = value => isString( value, true );

export default isStringNotEmpty;