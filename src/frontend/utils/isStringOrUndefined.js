import { isString, isUndefined } from "@steveush/utils";

/**
 * @param {unknown} value
 * @returns {value is string|undefined}
 */
const isStringOrUndefined = value => isString( value, true ) || isUndefined( value );

export default isStringOrUndefined;
