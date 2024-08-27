import {
    getBorderRadiusStyle,
    getBorderStyle,
    getColorStyle,
    getDimensionStyle,
    getTypographyStyle
} from "../components";

const getStyles = ( value, colorMap ) => {
    return {
        ...getBorderStyle( value?.border, true ),
        ...getBorderRadiusStyle( value?.border?.radius, true ),
        ...getColorStyle( value?.color, colorMap ),
        ...getDimensionStyle( value?.dimensions ),
        ...getTypographyStyle( value?.typography )
    };
};

export default getStyles;