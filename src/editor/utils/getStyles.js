import {
    getBorderRadiusStyle,
    getBorderStyle,
    getColorStyle,
    getDimensionStyle,
    getTypographyStyle,
    getBoxShadowStyle
} from "../components";

const getStyles = ( value, colorMap ) => {
    return {
        ...getBorderStyle( value?.border, true ),
        ...getBorderRadiusStyle( value?.border?.radius, true ),
        ...getBoxShadowStyle( value?.border?.shadow ),
        ...getColorStyle( value?.color, colorMap ),
        ...getDimensionStyle( value?.dimensions ),
        ...getTypographyStyle( value?.typography )
    };
};

export default getStyles;