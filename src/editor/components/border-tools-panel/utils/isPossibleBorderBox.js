import { someKeys } from "@steveush/utils";
import isPossibleBorderValue from "./isPossibleBorderValue";

/**
 * An object representing a border box value which contains a top, right, bottom and left border properties.
 * @typedef {{top: FCBorderValue | undefined, right: FCBorderValue | undefined, bottom: FCBorderValue | undefined, left: FCBorderValue | undefined}} FCBorderBox
 */

/**
 * A mapping of key to type check for a partial box unit object.
 *
 * @type {Record<string, ( value: any ) => boolean>}
 */
const KEYS = {
    top: isPossibleBorderValue,
    right: isPossibleBorderValue,
    bottom: isPossibleBorderValue,
    left: isPossibleBorderValue
};

/**
 * Check if a value contains any {@link FCBorderBox|border box} object keys with a `string` value.
 *
 * @param {any} value - The value to check.
 * @returns {value is Partial<FCBorderBox>} - `true` if the value contains any {@link FCBorderBox|border box} object keys with a `string` value, otherwise `false`.
 */
const isPossibleBorderBox = value => someKeys( value, KEYS );

export default isPossibleBorderBox;