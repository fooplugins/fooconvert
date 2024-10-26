import { isPlainObject } from "@steveush/utils";
import isCSSBorderStyle from "./isCSSBorderStyle";

/**
 *
 * @param value
 * @param styleRequired
 * @return {string}
 */
const toCSSBorderSyntax = ( value, styleRequired = false ) => {
    if ( isPlainObject( value ) ) {
        const { width = "", style = "", color = "" } = value;
        if ( width !== "" && ( !styleRequired || ( styleRequired && style !== "none" && isCSSBorderStyle( style ) ) ) ) {
            return [ width, style, color ].filter( Boolean ).join( " " );
        }
    }
    return "";
};

export default toCSSBorderSyntax;