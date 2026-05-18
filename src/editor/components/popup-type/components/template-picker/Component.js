import { createBlock } from "@wordpress/blocks";
import { Button } from "@wordpress/components";
import { useDispatch, useSelect } from "@wordpress/data";
import { store as editPostStore } from "@wordpress/edit-post";
import { store as editorStore } from "@wordpress/editor";
import { useEffect, useRef } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

import editorData from "../../../../plugins/ai-builder/editorData";
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

const AiPopupBuilderButton = ( { href } ) => {
    if ( !href ) {
        return null;
    }

    return (
        <Button
            className={ `${ ROOT_CLASS }__ai-button` }
            href={ href }
            target="_top"
        >
            <svg
                className={ `${ ROOT_CLASS }__ai-button-icon` }
                viewBox="0 0 24 24"
                focusable="false"
                aria-hidden="true"
            >
                <path d="M12 2.75l1.9 5.12 5.35 1.98-5.35 1.98L12 17.25l-1.9-5.42-5.35-1.98 5.35-1.98L12 2.75z" />
                <path d="m18.25 14.25.75 2.03 2 .72-2 .72-.75 2.03-.75-2.03-2-.72 2-.72.75-2.03z" />
                <path d="m5.75 14.75.5 1.32 1.25.43-1.25.43-.5 1.32-.5-1.32L4 16.5l1.25-.43.5-1.32z" />
            </svg>
            <span>{ __( "AI Popup Builder", "fooconvert" ) }</span>
        </Button>
    );
};

const PopupTypeTemplatePicker = ( {
    clientId,
    currentPopupType,
    ...props
} ) => {
    const requestedPopupType = getPopupTypeFromLocation();
    const aiPopupBuilderUrl = editorData?.builderUrl || "";
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
                toolbarAction={ aiPopupBuilderUrl ? <AiPopupBuilderButton href={ aiPopupBuilderUrl } /> : null }
                reset={ { template: undefined } }
            />
        </div>
    );
};

export default PopupTypeTemplatePicker;
