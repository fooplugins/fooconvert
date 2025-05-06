import { isPlainObject, isString } from "@steveush/utils";

const KNOWN = {
    text: 'color',
    background: 'backgroundColor',
    icon: 'fill'
};

const getColorStyle = ( colors, keyToCSSMap = KNOWN ) => {
    const css = {};
    if ( isPlainObject( colors ) ) {
        for ( const [ key, value ] of Object.entries( colors ) ) {
            if ( Object.hasOwn( keyToCSSMap, key ) && isString( value, true ) ) {
                css[ keyToCSSMap[ key ] ] = value;
            }
        }
    }
    return css;
};

export default getColorStyle;