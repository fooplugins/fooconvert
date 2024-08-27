import { isArray, isString } from "@steveush/utils";
import { isStringOrUndefined } from "../../../utils";
import isBoxUnit from "./isBoxUnit";

/**
 * Given a box unit value create an object containing the `top`, `right`, `bottom` and `left` values.
 * @param {string|Partial<FCBoxUnit>|FCBoxUnitTuple} value - The value to turn into an object. Can be a `string` or a {@link FCBoxUnitTuple|box unit tuple}.
 * If passed a {@link FCBoxUnit|box unit object}, it is simply returned. Any other value results in `undefined` being returned.
 * @returns {FCBoxUnit|undefined} A {@link FCBoxUnit|box unit object}, otherwise `undefined`.
 */
const makeBoxUnit = value => {
    if ( isString( value, true ) ) {
        return { top: value, right: value, bottom: value, left: value };
    }
    if ( isArray( value, true, value => isStringOrUndefined( value ) ) && value.length === 4 ) {
        const [ top, right, bottom, left ] = value;
        return { top, right, bottom, left };
    }
    if ( isBoxUnit( value, true ) ) {
        return Object.assign( {
            top: undefined,
            right: undefined,
            bottom: undefined,
            left: undefined
        }, value );
    }
    return undefined;
};

export default makeBoxUnit;