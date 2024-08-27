import { hasKeys, someKeys } from "@steveush/utils";
import { isStringNotEmpty, isStringOrUndefined } from "../../../utils";

/**
 * An object representing a box unit value which contains a top, right, bottom and left string properties.
 * @typedef {{top: string | undefined, right: string | undefined, bottom: string | undefined, left: string | undefined}} FCBoxUnit
 */

/**
 * A mapping of key to type check for a box unit object.
 *
 * @type {Record<string, ( value: any ) => boolean>}
 */
const KEYS = {
    top: isStringOrUndefined,
    right: isStringOrUndefined,
    bottom: isStringOrUndefined,
    left: isStringOrUndefined
};

/**
 * A mapping of key to type check for a partial box unit object.
 *
 * @type {Record<string, ( value: any ) => boolean>}
 */
const PARTIAL_KEYS = {
    top: isStringNotEmpty,
    right: isStringNotEmpty,
    bottom: isStringNotEmpty,
    left: isStringNotEmpty
};

/**
 * Check if a value is a {@link FCBoxUnit|box unit object}.
 *
 * @template {boolean} TBoolean
 * @param {any} value - The value to check.
 * @param {TBoolean} [partial] - Optional. If `true`, a partial match is performed. Defaults to `false`.
 * @returns {value is (TBoolean extends true ? Partial<FCBoxUnit> : FCBoxUnit)} - `true` if the value is a {@link FCBoxUnit|box unit object}, otherwise `false`.
 */
const isBoxUnit = ( value, partial = false ) => partial ? someKeys( value, PARTIAL_KEYS ) : hasKeys( value, KEYS );

export default isBoxUnit;