import { isStringNotEmpty } from "../../../utils";
import { someKeys } from "@steveush/utils";

/**
 * An object containing the `width`, `style` and `color` border values.
 *
 * @typedef {{width: string | undefined, style: string | undefined, color: string | undefined}} FCBorderValue
 */

/**
 * A mapping of key to type check for a partial border object.
 *
 * @type {Record<string, ( value: unknown ) => boolean>}
 */
const KEYS = {
    width: isStringNotEmpty,
    style: isStringNotEmpty,
    color: isStringNotEmpty
};

/**
 * Check if a value contains any {@link FCBorderValue|border value} object keys with a `string` value.
 *
 * @param {unknown} value - The value to check.
 * @returns {value is Partial<FCBorderValue>} - `true` if the value contains any {@link FCBorderValue|border value} object keys with a `string` value, otherwise `false`.
 */
const isPossibleBorderValue = value => someKeys( value, KEYS );

export default isPossibleBorderValue;
