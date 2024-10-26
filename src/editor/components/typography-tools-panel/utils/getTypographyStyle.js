import { isString } from "@steveush/utils";
import { isFontAppearance } from "../../font-appearance-control";

export const getTypographyStyle = ( typography ) => {
    const css = {};
    if ( isString( typography?.fontFamily, true ) ) {
        css.fontFamily = typography.fontFamily;
    }
    if ( isString( typography?.fontSize, true ) ) {
        css.fontSize = typography.fontSize;
    }
    let fontAppearance = { fontStyle: typography?.fontStyle, fontWeight: typography?.fontWeight };
    if ( isFontAppearance( fontAppearance ) ) {
        css.fontStyle = fontAppearance.fontStyle;
        css.fontWeight = fontAppearance.fontWeight;
    }
    if ( isString( typography?.lineHeight, true ) ) {
        css.lineHeight = typography.lineHeight;
    }
    if ( isString( typography?.letterSpacing, true ) ) {
        css.letterSpacing = typography.letterSpacing;
    }
    if ( isString( typography?.textDecoration, true ) ) {
        css.textDecoration = typography.textDecoration;
    }
    if ( isString( typography?.textTransform, true ) ) {
        css.textTransform = typography.textTransform;
    }
    return css;
};

export default getTypographyStyle;