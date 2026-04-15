import getBackgroundImageStyle from "../components/background-image-panel/utils/getBackgroundImageStyle";
import getColorStyle from "../components/color-tools-panel/utils/getColorStyle";

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
