import makeBorderRadiusTuple from "./makeBorderRadiusTuple";
import { distinct } from "@steveush/utils";

/**
 * An object containing the CSS style properties for a border radius.
 *
 * @typedef {{borderRadius?:string,borderTopLeftRadius?:string,borderTopRightRadius?:string,borderBottomRightRadius?:string,borderBottomLeftRadius?:string}} BorderRadiusStyle
 */

/**
 * Get the CSS equivalent for the border radius value.
 *
 * @param {string|BorderRadiusBox} value - The value to express as a CSS value.
 * @param {boolean} [shorthand=false] - Whether to use the shorthand syntax for the output. i.e. a single property containing the `topLeft topRight bottomRight bottomLeft` values.
 * @returns {BorderRadiusStyle} - An object containing the CSS style for the value.
 */
const getBorderRadiusStyle = ( value, shorthand = false ) => {
    const css = {};
    const values = makeBorderRadiusTuple( value );
    if ( typeof values !== 'undefined' ) {
        if ( distinct( values ).length === 1 ) {
            const [ radius ] = values;
            if ( radius ) css.borderRadius = radius;
        } else if ( shorthand ) {
            const [ topLeft = "0", topRight = "0", bottomRight = "0", bottomLeft = "0" ] = values;
            if ( topLeft === topRight && topRight === bottomRight && bottomRight === bottomLeft ) {
                css.borderRadius = topLeft;
            } else if ( topRight === bottomLeft ) {
                css.borderRadius = topLeft === bottomRight ? `${ topLeft } ${ topRight }` : `${ topLeft } ${ topRight } ${ bottomRight }`;
            } else {
                css.borderRadius = `${ topLeft } ${ topRight } ${ bottomRight } ${ bottomLeft }`;
            }
        } else {
            const [ topLeft, topRight, bottomRight, bottomLeft ] = values;
            if ( topLeft ) css.borderTopLeftRadius = topLeft;
            if ( topRight ) css.borderTopRightRadius = topRight;
            if ( bottomRight ) css.borderBottomRightRadius = bottomRight;
            if ( bottomLeft ) css.borderBottomLeftRadius = bottomLeft;
        }
    }
    return css;
};

export default getBorderRadiusStyle;