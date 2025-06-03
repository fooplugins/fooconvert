import { isFunction, isPlainObject, isString } from "@steveush/utils";
import { getCSSBackgroundProperty } from "../../../utils";

const KNOWN = {
    text: 'color',
    background: getCSSBackgroundProperty,
    icon: 'fill'
};

const getColorStyle = ( colors, keyToCSSMap = KNOWN ) => {
    const css = {};
    if ( isPlainObject( colors ) ) {
        for ( const [ key, value ] of Object.entries( colors ) ) {
            if ( Object.hasOwn( keyToCSSMap, key ) && isString( value, true ) ) {
                const mapped = keyToCSSMap[ key ];
                if ( isString( mapped, true ) ) {
                    css[ mapped ] = value;
                } else if ( isFunction( mapped ) ) {
                    const result = mapped( value );
                    if ( isString( result, true ) ) {
                        css[ result ] = value;
                    }
                }
            }
        }
    }
    return css;
};

export default getColorStyle;