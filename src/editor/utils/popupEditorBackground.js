import { isString } from "@steveush/utils";

export const normalizePopupEditorBackground = value => {
    const normalized = isString( value ) ? value.trim().toLowerCase() : "";

    switch ( normalized ) {
        case "transparent":
        case "white":
        case "black":
            return normalized;
        default:
            return "transparent";
    }
};

const getPopupEditorBackground = ( config = globalThis?.FOOCONVERT_EDITOR_CONFIG ) => {
    return normalizePopupEditorBackground( config?.popupEditorBackground );
};

export default getPopupEditorBackground;
