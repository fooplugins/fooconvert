import {
    getBorderRadiusStyle,
    getBorderStyle,
    getDimensionStyle,
    getTypographyStyle,
    getBoxShadowStyle
} from "../components";
import getBackgroundAndColorStyles from "./getBackgroundAndColorStyles";

const getStyles = ( value, colorMap ) => {
    return {
        ...getBorderStyle( value?.border ),
        ...getBorderRadiusStyle( value?.border?.radius, true ),
        ...getBoxShadowStyle( value?.border?.shadow ),
        ...getBackgroundAndColorStyles( value, colorMap ),
        ...getDimensionStyle( value?.dimensions ),
        ...getTypographyStyle( value?.typography )
    };
};

export default getStyles;