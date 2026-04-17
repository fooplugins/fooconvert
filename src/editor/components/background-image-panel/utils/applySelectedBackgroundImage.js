import { setImmutably } from "../../../utils";
import hasBackgroundImageValue from "./hasBackgroundImageValue";

const applySelectedBackgroundImage = ( {
    style,
    media,
    defaultValues = {}
} ) => {
    if ( ! media || ! media.url ) {
        return setImmutably(
            style,
            [ "background", "backgroundImage" ],
            undefined
        );
    }

    const hasExplicitBackgroundImage = hasBackgroundImageValue( style );
    const hasExplicitBackgroundSize = style?.background?.backgroundSize !== undefined;
    const sizeValue = ! hasExplicitBackgroundImage && ! hasExplicitBackgroundSize
        ? "cover"
        : style?.background?.backgroundSize || defaultValues?.backgroundSize;
    const positionValue = style?.background?.backgroundPosition;

    return setImmutably( style, [ "background" ], {
        ...style?.background,
        backgroundImage: {
            url: media.url,
            id: media.id,
            source: "file",
            title: media.title || undefined,
        },
        backgroundPosition:
            ! positionValue && ( "auto" === sizeValue || ! sizeValue )
                ? "50% 0"
                : positionValue,
        backgroundSize: sizeValue,
    } );
};

export default applySelectedBackgroundImage;
