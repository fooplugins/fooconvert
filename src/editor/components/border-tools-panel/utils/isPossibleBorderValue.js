// import isLinkedBorder from "./isLinkedBorder";
// import isUnlinkedBorder from "./isUnlinkedBorder";
//
// /**
//  * Check if the `value` is either a linked or unlinked border.
//  *
//  * @param {*} value - The value to check.
//  * @param {boolean} [strict=false] - Optional. If `true` the value must contain all the keys associated with either a linked or unlinked border.
//  * @return {boolean} `true` if the `value` is either a linked or unlinked border, otherwise `false`.
//  */
// const isBorder = ( value, strict = false ) => isLinkedBorder( value, strict ) || isUnlinkedBorder( value, strict );
//
// export default isBorder;
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
 * @type {Record<string, ( value: any ) => boolean>}
 */
const KEYS = {
    width: isStringNotEmpty,
    style: isStringNotEmpty,
    color: isStringNotEmpty
};

/**
 * Check if a value contains any {@link FCBorderValue|border value} object keys with a `string` value.
 *
 * @param {any} value - The value to check.
 * @returns {value is Partial<FCBorderValue>} - `true` if the value contains any {@link FCBorderValue|border value} object keys with a `string` value, otherwise `false`.
 */
const isPossibleBorderValue = value => someKeys( value, KEYS );

export default isPossibleBorderValue;