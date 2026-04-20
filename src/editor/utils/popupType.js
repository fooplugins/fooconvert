export const normalizePopupType = value => {
    switch ( `${ value || "" }`.trim().toLowerCase() ) {
        case "bar":
        case "fc-bar":
        case "fc/bar":
            return "bar";
        case "flyout":
        case "fc-flyout":
        case "fc/flyout":
            return "flyout";
        case "overlay":
        case "popup":
        case "fc-popup":
        case "fc-overlay":
        case "fc/overlay":
        case "fc/popup":
            return "overlay";
        default:
            return "";
    }
};

export const getPopupTypeFromLocation = ( locationSearch = globalThis?.window?.location?.search ) => {
    if ( typeof locationSearch !== "string" ) {
        return "";
    }

    return normalizePopupType( new URLSearchParams( locationSearch ).get( "popup_type" ) );
};

