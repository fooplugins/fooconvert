import { isString } from "@steveush/utils";

/**
 *
 * @param {unknown} value
 * @returns {string|undefined}
 */
const stringOrUndefined = value => isString( value, true ) ? value : undefined;

export default stringOrUndefined;
