import { isFunction, isString, capitalize, distinct } from "@steveush/utils";
import makeBoxUnitTuple from "./makeBoxUnitTuple";

const getCSSProperty = ( side, propertyName ) => {
    if ( isFunction( propertyName ) ) {
        const result = propertyName( side );
        if ( isString( result, true ) ) {
            return result;
        }
    } else if ( isString( propertyName, true ) ) {
        return side === "all" ? propertyName : `${ propertyName }${ capitalize( side ) }`;
    }
    return undefined;
};

/**
 * Get the CSS equivalent for the box unit value.
 *
 * @param {string|FCBoxUnit} value - The value to express as a CSS value.
 * @param {(string|((side: "all"|"top"|"right"|"bottom"|"left") => string))} propertyName - The CSS property name the box unit represents.
 * @param {boolean} [shorthand=false] - Whether to use the shorthand syntax for the output. i.e. a single property containing the `top right bottom left` values.
 * @returns {object} - An object containing the CSS style for the value.
 */
const getBoxUnitStyle = ( value, propertyName, shorthand = false ) => {
    const css = {};
    const values = makeBoxUnitTuple( value );
    if ( typeof value !== 'undefined' ) {
        if ( shorthand ) {
            const prop = getCSSProperty( "all", propertyName );
            if ( isString( prop, true ) ) {
                const [ top = "0", right = "0", bottom = "0", left = "0" ] = values;
                if ( top === right && right === bottom && bottom === left ) {
                    css[ prop ] = top;
                } else if ( right === left ) {
                    css[ prop ] = top === bottom ? `${ top } ${ right }` : `${ top } ${ right } ${ bottom }`;
                } else {
                    css[ prop ] = `${ top } ${ right } ${ bottom } ${ left }`;
                }
            }
        } else {
            const setSide = ( side, propertyValue ) => {
                if ( isString( propertyValue, true ) ) {
                    const prop = getCSSProperty( side, propertyName );
                    if ( isString( prop, true ) ) css[ `${ prop }` ] = propertyValue;
                }
            };
            const [ top, right, bottom, left ] = values;
            setSide( "top", top );
            setSide( "right", right );
            setSide( "bottom", bottom );
            setSide( "left", left );
        }
    }
    return css;
};

export default getBoxUnitStyle;