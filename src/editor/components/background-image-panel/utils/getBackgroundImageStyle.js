import hasBackgroundImageValue from "./hasBackgroundImageValue";
import isStringNotEmpty from "../../../utils/isStringNotEmpty";

const getBackgroundImageStyle = style => {
    const css = {};
    if ( hasBackgroundImageValue( style ) ) {
        const imgUrl = style?.background?.backgroundImage?.url;
        if ( isStringNotEmpty( imgUrl ) ) {
            css.backgroundImage = `url(${ imgUrl })`;
            if ( isStringNotEmpty( style?.background?.backgroundPosition ) ) {
                css.backgroundPosition = style?.background?.backgroundPosition;
            }
            if ( isStringNotEmpty( style?.background?.backgroundRepeat ) ) {
                css.backgroundRepeat = style?.background?.backgroundRepeat;
            }
            if ( isStringNotEmpty( style?.background?.backgroundSize ) ) {
                css.backgroundSize = style?.background?.backgroundSize;
            }
            if ( isStringNotEmpty( style?.background?.backgroundAttachment ) ) {
                css.backgroundAttachment = style?.background?.backgroundAttachment;
            }
        }
    }
    return css;
};

export default getBackgroundImageStyle;
