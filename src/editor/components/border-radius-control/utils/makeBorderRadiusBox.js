import { isString } from "@steveush/utils";
import isPartialBorderRadiusBox from "./isPartialBorderRadiusBox";
import isBorderRadiusTuple from "./isBorderRadiusTuple";
import { BORDER_RADIUS_BOX_DEFAULTS, BORDER_RADIUS_BOX_DEFINITION } from "./isBorderRadiusBox";

/**
 * Given a value make a `BorderRadiusBox` object.
 *
 * @param {BorderRadiusValue|BorderRadiusTuple|undefined} value - The value to turn into an object. If passed:
 * - a non-empty `string`, the value is used for all properties.
 * - a `Partial<BorderRadiusBox>` or `BorderRadiusTuple`, the values are combined with the defaults.
 * - Any other value results in just the default values being returned.
 * @param {Partial<BorderRadiusBox>} [defaults] - Optional. The default values to use for any missing properties. Defaults to `undefined` for all properties.
 * @returns {BorderRadiusBox} A `BorderRadiusBox` object.
 * @example
 * makeBorderRadiusBox();
 * // => { topLeft: undefined, topRight: undefined, bottomRight: undefined, bottomLeft: undefined }
 *
 * makeBorderRadiusBox( '' );
 * // => { topLeft: undefined, topRight: undefined, bottomRight: undefined, bottomLeft: undefined }
 *
 * makeBorderRadiusBox( '4px' );
 * // => { topLeft: '4px', topRight: '4px', bottomRight: '4px', bottomLeft: '4px' }
 *
 * makeBorderRadiusBox( { topLeft: '4px' } );
 * // => { topLeft: '4px', topRight: undefined, bottomRight: undefined, bottomLeft: undefined }
 *
 * makeBorderRadiusBox( { topLeft: '' } );
 * // => { topLeft: undefined, topRight: undefined, bottomRight: undefined, bottomLeft: undefined }
 * @see BorderRadiusBox
 * @see BorderRadiusTuple
 * @see BorderRadiusValue
 */
const makeBorderRadiusBox = ( value, defaults = BORDER_RADIUS_BOX_DEFAULTS ) => {
    if ( isString( value, true ) ) {
        return { topLeft: value, topRight: value, bottomRight: value, bottomLeft: value };
    }

    if ( defaults !== BORDER_RADIUS_BOX_DEFAULTS ) {
        defaults = Object.assign( {}, BORDER_RADIUS_BOX_DEFAULTS, defaults );
    }
    let result = defaults;
    if ( isBorderRadiusTuple( value ) ) {
        const [ topLeft, topRight, bottomRight, bottomLeft ] = value;
        result = { topLeft, topRight, bottomRight, bottomLeft };
    }
    if ( isPartialBorderRadiusBox( value ) ) {
        result = Object.assign( {}, defaults, value );
    }
    if ( result !== defaults ) {
        Object.entries( BORDER_RADIUS_BOX_DEFINITION ).forEach( ( [ key, check ] ) => {
            if ( !check( result[ key ] ) ) {
                result[ key ] = defaults[ key ];
            }
        } );
    }
    return result;
};

export default makeBorderRadiusBox;