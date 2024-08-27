import { isString } from "@steveush/utils";
import isPartialBorderRadiusBox from "./isPartialBorderRadiusBox";
import { BORDER_RADIUS_TUPLE_DEFAULTS, BORDER_RADIUS_TUPLE_DEFINITION } from "./isBorderRadiusTuple";

/**
 * Given a value make a `BorderRadiusTuple` array.
 *
 * @param {BorderRadiusValue|undefined} value - The value to turn into a tuple. If passed:
 * - a non-empty `string`, the value is used for all properties.
 * - a `Partial<BorderRadiusBox>`, the values are combined with the defaults.
 * - Any other value results in just the default values being returned.
 * @param {BorderRadiusTuple} [defaults] - Optional. The default values to use for any missing items. Defaults to `undefined` for the 4 possible items.
 * @returns {BorderRadiusTuple} A `BorderRadiusTuple` array.
 */
const makeBorderRadiusTuple = ( value, defaults = BORDER_RADIUS_TUPLE_DEFAULTS ) => {
    if ( isString( value, true ) ) {
        return [ value, value, value, value ];
    }
    if ( defaults !== BORDER_RADIUS_TUPLE_DEFAULTS ) {
        defaults = BORDER_RADIUS_TUPLE_DEFINITION.map( ( check, i ) => {
            return i < defaults.length && check( defaults[ i ] ) ? defaults[ i ] : BORDER_RADIUS_TUPLE_DEFAULTS[ i ];
        } );
    }
    let result = defaults;
    if ( isPartialBorderRadiusBox( value ) ) {
        const { topLeft = defaults[ 0 ], topRight = defaults[ 1 ], bottomRight = defaults[ 2 ], bottomLeft = defaults[ 3 ] } = value;
        result = [ topLeft, topRight, bottomRight, bottomLeft ];
    }
    if ( result !== defaults ) {
        result = BORDER_RADIUS_TUPLE_DEFINITION.map( ( check, i ) => {
            return i < result.length && check( result[ i ] ) ? result[ i ] : defaults[ i ];
        } );
    }
    return result;
};

export default makeBorderRadiusTuple;