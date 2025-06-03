import { getBackgroundImageStyle, getColorStyle } from "../components";

const getBackgroundAndColorStyles = ( value, colorMap ) => {
    const colorStyles = getColorStyle( value?.color, colorMap );
    const backgroundImageStyles = getBackgroundImageStyle( value );
    const backgroundAndColorStyles = {
        ...colorStyles,
        ...backgroundImageStyles
    };
    if ( colorStyles?.backgroundImage && backgroundImageStyles?.backgroundImage ) {
        backgroundAndColorStyles.backgroundImage = `${ backgroundImageStyles.backgroundImage }, ${ colorStyles.backgroundImage }`;
    }
    return backgroundAndColorStyles;
};

export default getBackgroundAndColorStyles;