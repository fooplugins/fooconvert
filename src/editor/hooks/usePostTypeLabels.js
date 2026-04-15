import { useDispatch, useSelect } from "@wordpress/data";
import { store as editorStore } from "@wordpress/editor";
import { store as coreStore } from "@wordpress/core-data";
import { useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

const POPUP_TYPE_META_KEY = "_fooconvert_popup_type";

const POPUP_TYPE_LABELS = {
    bar: {
        name: __( "Bars", "fooconvert" ),
        singular_name: __( "Bar", "fooconvert" ),
    },
    flyout: {
        name: __( "Flyouts", "fooconvert" ),
        singular_name: __( "Flyout", "fooconvert" ),
    },
    overlay: {
        name: __( "Overlays", "fooconvert" ),
        singular_name: __( "Overlay", "fooconvert" ),
    },
};

const normalizePopupType = value => {
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

const getPopupTypeFromLocation = () => {
    if ( typeof window?.location?.search !== "string" ) {
        return "";
    }

    return normalizePopupType( new URLSearchParams( window.location.search ).get( "popup_type" ) );
};

const usePostTypeLabels = ( defaults = {} ) => {
    const { editPost } = useDispatch( editorStore );

    const { currentPostType, meta } = useSelect( select => {
        const editor = select( editorStore );

        return {
            currentPostType: editor?.getCurrentPostType() || "",
            meta: editor?.getEditedPostAttribute( "meta" ) || {},
        };
    }, [] );

    const postType = useSelect( select => {
        return select( coreStore )?.getPostType( currentPostType );
    }, [ currentPostType ] );

    const popupTypeFromMeta = normalizePopupType( meta?.[ POPUP_TYPE_META_KEY ] );
    const popupTypeFromLocation = getPopupTypeFromLocation();
    const popupType = currentPostType === "fc-popup"
        ? ( popupTypeFromLocation || popupTypeFromMeta )
        : "";

    useEffect( () => {
        if ( currentPostType !== "fc-popup" || popupTypeFromLocation.length === 0 ) {
            return;
        }

        if ( popupTypeFromMeta === popupTypeFromLocation ) {
            return;
        }

        editPost( {
            meta: {
                ...meta,
                [ POPUP_TYPE_META_KEY ]: popupTypeFromLocation,
            }
        } );
    }, [ currentPostType, editPost, meta, popupTypeFromLocation, popupTypeFromMeta ] );

    const found = popupType.length > 0
        ? ( POPUP_TYPE_LABELS[ popupType ] ?? {} )
        : ( postType?.labels ?? {} );

    return {
        ...defaults,
        ...found
    };
};

export default usePostTypeLabels;
