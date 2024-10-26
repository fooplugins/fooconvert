import toCSSBorderSyntax from "./toCSSBorderSyntax";
import isPossibleBorderValue from "./isPossibleBorderValue";
import isPossibleBorderBox from "./isPossibleBorderBox";

/**
 * An object containing the CSS style properties for a border.
 *
 * @typedef {{border?: string, borderTop?: string, borderRight?: string, borderBottom?: string, borderLeft?: string}} FCBorderStyle
 */

/**
 * Get the CSS style for the border.
 *
 * @param {any} value
 * @param {boolean} [styleRequired=false]
 * @returns {FCBorderStyle}
 */
const getBorderStyle = ( value, styleRequired = false ) => {
    const result = {};
    if ( isPossibleBorderValue( value ) ) {
        const simple = toCSSBorderSyntax( value, styleRequired );
        if ( simple !== "" ) result.border = simple;
    } else if ( isPossibleBorderBox( value ) ) {
        const top = toCSSBorderSyntax( value?.top, styleRequired );
        if ( top !== "" ) result.borderTop = top;
        const left = toCSSBorderSyntax( value?.left, styleRequired );
        if ( left !== "" ) result.borderLeft = left;
        const bottom = toCSSBorderSyntax( value?.bottom, styleRequired );
        if ( bottom !== "" ) result.borderBottom = bottom;
        const right = toCSSBorderSyntax( value?.right, styleRequired );
        if ( right !== "" ) result.borderRight = right;
    }
    return result;
};

export default getBorderStyle;