import { createBlock } from "@wordpress/blocks";
import { useDispatch, useSelect } from "@wordpress/data";
import { store as editPostStore } from "@wordpress/edit-post";
import { store as editorStore } from "@wordpress/editor";
import { useEffect, useRef } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

import { VariationPicker } from "../../../variation-picker";
import { getPopupTypeFromLocation } from "../../../../utils/popupType";
import "./Component.scss";

const POPUP_TYPE_META_KEY = "_fooconvert_popup_type";

const POPUP_TYPE_OPTIONS = [
    {
        value: "bar",
        label: __( "Bar", "fooconvert" ),
        blockName: "fc/bar",
    },
    {
        value: "flyout",
        label: __( "Flyout", "fooconvert" ),
        blockName: "fc/flyout",
    },
    {
        value: "overlay",
        label: __( "Overlay", "fooconvert" ),
        blockName: "fc/overlay",
    },
];

const ROOT_CLASS = "fc--popup-type-template-picker";

const PopupTypeTemplatePicker = ( {
    clientId,
    currentPopupType,
    ...props
} ) => {
    const requestedPopupType = getPopupTypeFromLocation();
    const restoreSidebarRef = useRef( "" );
    const meta = useSelect( select => {
        return select( editorStore )?.getEditedPostAttribute( "meta" ) || {};
    }, [] );
    const { isEditorSidebarOpen, activeGeneralSidebarName } = useSelect( select => {
        const sidebarStore = select( editPostStore );
        return {
            isEditorSidebarOpen: sidebarStore?.isEditorSidebarOpened?.() ?? false,
            activeGeneralSidebarName: sidebarStore?.getActiveGeneralSidebarName?.() ?? "",
        };
    }, [] );

    const { editPost, resetEditorBlocks } = useDispatch( editorStore );
    const { closeGeneralSidebar, openGeneralSidebar } = useDispatch( editPostStore );

    const restoreEditorSidebar = () => {
        if ( restoreSidebarRef.current ) {
            openGeneralSidebar( restoreSidebarRef.current );
            restoreSidebarRef.current = "";
        }
    };

    useEffect( () => {
        if ( meta?.[ POPUP_TYPE_META_KEY ] === currentPopupType ) {
            return;
        }

        editPost( {
            meta: {
                ...meta,
                [ POPUP_TYPE_META_KEY ]: currentPopupType,
            }
        } );
    }, [ currentPopupType, editPost, meta ] );

    useEffect( () => {
        if ( isEditorSidebarOpen ) {
            restoreSidebarRef.current = activeGeneralSidebarName || "edit-post/document";
            closeGeneralSidebar();
        }

        return () => {
            restoreEditorSidebar();
        };
    }, [] );

    const switchPopupType = nextPopupType => {
        if ( nextPopupType === currentPopupType ) {
            return;
        }

        const nextOption = POPUP_TYPE_OPTIONS.find( option => option.value === nextPopupType );
        if ( !nextOption ) {
            return;
        }

        editPost( {
            meta: {
                ...meta,
                [ POPUP_TYPE_META_KEY ]: nextPopupType,
            }
        } );

        resetEditorBlocks( [ createBlock( nextOption.blockName ) ] );
    };

    return (
        <div className={ ROOT_CLASS }>
            <VariationPicker
                { ...props }
                clientId={ clientId }
                className={ `${ ROOT_CLASS }__picker` }
                currentPopupType={ requestedPopupType }
                popupTypeOptions={ POPUP_TYPE_OPTIONS }
                onSelectPopupType={ switchPopupType }
                onSelectTemplate={ restoreEditorSidebar }
                label={ __( "Choose a popup template", "fooconvert" ) }
                reset={ { template: undefined } }
            />
        </div>
    );
};

export default PopupTypeTemplatePicker;
