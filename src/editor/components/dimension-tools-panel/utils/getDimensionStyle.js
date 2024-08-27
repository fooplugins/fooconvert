import { getBoxUnitStyle } from "../../box-units-control";
import { isString } from "@steveush/utils";

export const getDimensionStyle = ( dimensions, properties = [ 'padding', 'margin', 'gap' ] ) => {
    const padding = properties.includes( 'padding' ) ? getBoxUnitStyle( dimensions?.padding, "padding" ) : {};
    const margin = properties.includes( 'margin' ) ? getBoxUnitStyle( dimensions?.margin, "margin" ) : {};
    const gap = properties.includes( 'gap' ) && isString( dimensions?.gap, true ) ? { gap: dimensions.gap } : {};
    return {
        ...padding,
        ...margin,
        ...gap
    };
};

export default getDimensionStyle;