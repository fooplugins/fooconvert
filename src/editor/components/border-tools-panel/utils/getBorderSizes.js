import isPossibleBorderValue from "./isPossibleBorderValue";
import isPossibleBorderBox from "./isPossibleBorderBox";

/**
 * Get an object containing the sizes for the top, right, bottom and left borders.
 *
 * @param {*} value - The value to parse.
 * @param {Partial<FCBoxUnitSizes>} [defaults] - Optional. An object containing the default values for each side. Defaults to `0px` for all values.
 * @return {FCBoxUnitSizes} An object containing the sizes for the top, right, bottom and left borders.
 */
const getBorderSizes = ( value, defaults ) => {
    defaults = Object.assign( { top: "0px", right: "0px", bottom: "0px", left: "0px" }, defaults );
    const linked = isPossibleBorderValue( value );
    const unlinked = isPossibleBorderBox( value );
    if ( linked || unlinked ) {
        const width = side => {
            const supplied = linked ? value?.width : value[ side ]?.width;
            return typeof supplied === "string" ? supplied : defaults[ side ];
        };
        return {
            top: width( "top" ),
            right: width( "right" ),
            bottom: width( "bottom" ),
            left: width( "left" )
        };
    }
    return defaults;
};

export default getBorderSizes;