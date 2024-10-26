import { isArray, isString } from "@steveush/utils";
import isBoxUnit from "./isBoxUnit";
import { isStringOrUndefined } from "../../../utils";

/**
 * A tuple containing the `top`, `right`, `bottom` and `left` values, in that order.
 *
 * @typedef {[ top: string|undefined, right: string|undefined, bottom: string|undefined, left: string|undefined ]} FCBoxUnitTuple
 */

/**
 * Given a box unit value create a tuple containing the `top`, `right`, `bottom` and `left` values.
 *
 * @param {string|Partial<FCBoxUnit>|FCBoxUnitTuple|any} value - The value to turn into a tuple. Can be a `string` or a {@link FCBoxUnit|box unit object}.
 * If passed a {@link FCBoxUnitTuple|box unit tuple}, it is simply returned. Any other value results in `undefined` being returned.
 * @returns {FCBoxUnitTuple|undefined} A {@link FCBoxUnitTuple|box unit tuple}, otherwise `undefined`.
 */
const makeBoxUnitTuple = value => {
    if ( isArray( value, true, value => isStringOrUndefined( value ) ) && value.length === 4 ) {
        return value;
    }
    if ( isString( value, true ) ) {
        return [ value, value, value, value ];
    }
    if ( isBoxUnit( value, true ) ) {
        const { top, right, bottom, left } = value;
        return [ top, right, bottom, left ];
    }
    return [];
};

export default makeBoxUnitTuple;