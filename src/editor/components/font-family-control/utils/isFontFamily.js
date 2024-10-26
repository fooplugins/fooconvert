import { isString } from "@steveush/utils";
import { FONT_FAMILY_OPTIONS_DEFAULTS } from "../Component";

const isFontFamily = ( value, options = FONT_FAMILY_OPTIONS_DEFAULTS ) => isString( value, true )
    && Array.isArray( options )
    && options.some( option => option?.style?.fontFamily === value );

export default isFontFamily;