import { createBlock } from "@wordpress/blocks";
import { useDispatch, useSelect } from "@wordpress/data";
import { store as editorStore } from "@wordpress/editor";
import { useEffect } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

import { VariationPicker } from "../../../variation-picker";
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
    const meta = useSelect( select => {
        return select( editorStore )?.getEditedPostAttribute( "meta" ) || {};
    }, [] );

    const { editPost, resetEditorBlocks } = useDispatch( editorStore );

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

    const focusTab = nextPopupType => {
        if ( typeof document?.getElementById !== "function" ) {
            return;
        }

        const element = document.getElementById( `${ ROOT_CLASS }__tab-${ nextPopupType }` );
        if ( element instanceof HTMLElement ) {
            element.focus();
        }
    };

    const moveTabSelection = offset => {
        const currentIndex = POPUP_TYPE_OPTIONS.findIndex( option => option.value === currentPopupType );
        if ( currentIndex === -1 ) {
            return;
        }

        const nextIndex = ( currentIndex + offset + POPUP_TYPE_OPTIONS.length ) % POPUP_TYPE_OPTIONS.length;
        const nextPopupType = POPUP_TYPE_OPTIONS[ nextIndex ]?.value;
        if ( typeof nextPopupType !== "string" ) {
            return;
        }

        switchPopupType( nextPopupType );
        window?.requestAnimationFrame?.( () => focusTab( nextPopupType ) );
    };

    const handleTabKeyDown = event => {
        switch ( event.key ) {
            case "ArrowLeft":
            case "ArrowUp":
                event.preventDefault();
                moveTabSelection( -1 );
                break;
            case "ArrowRight":
            case "ArrowDown":
                event.preventDefault();
                moveTabSelection( 1 );
                break;
            case "Home":
                event.preventDefault();
                switchPopupType( POPUP_TYPE_OPTIONS[0].value );
                window?.requestAnimationFrame?.( () => focusTab( POPUP_TYPE_OPTIONS[0].value ) );
                break;
            case "End": {
                event.preventDefault();
                const lastPopupType = POPUP_TYPE_OPTIONS[ POPUP_TYPE_OPTIONS.length - 1 ].value;
                switchPopupType( lastPopupType );
                window?.requestAnimationFrame?.( () => focusTab( lastPopupType ) );
                break;
            }
        }
    };

    const activeOption = POPUP_TYPE_OPTIONS.find( option => option.value === currentPopupType ) ?? POPUP_TYPE_OPTIONS[0];
    const panelId = `${ ROOT_CLASS }__panel-${ activeOption.value }`;
    const activeTabId = `${ ROOT_CLASS }__tab-${ activeOption.value }`;

    const tabs = (
        <div className={ `${ ROOT_CLASS }__header` }>
            <div className={ `${ ROOT_CLASS }__label` }>
                { __( "Popup type", "fooconvert" ) }
            </div>
            <div
                className={ `${ ROOT_CLASS }__tabs nav-tab-wrapper` }
                role="tablist"
                aria-label={ __( "Popup type", "fooconvert" ) }
            >
                { POPUP_TYPE_OPTIONS.map( option => {
                    const isActive = option.value === currentPopupType;
                    const tabId = `${ ROOT_CLASS }__tab-${ option.value }`;

                    return (
                        <button
                            type="button"
                            key={ option.value }
                            id={ tabId }
                            role="tab"
                            className={ `${ ROOT_CLASS }__tab nav-tab${ isActive ? " nav-tab-active" : "" }` }
                            aria-selected={ isActive }
                            aria-controls={ panelId }
                            tabIndex={ isActive ? 0 : -1 }
                            onClick={ () => switchPopupType( option.value ) }
                            onKeyDown={ handleTabKeyDown }
                        >
                            { option.label }
                        </button>
                    );
                } ) }
            </div>
        </div>
    );

    return (
        <div className={ ROOT_CLASS }>
            <div
                id={ panelId }
                className={ `${ ROOT_CLASS }__panel` }
                role="tabpanel"
                aria-labelledby={ activeTabId }
            >
                <VariationPicker
                    { ...props }
                    clientId={ clientId }
                    beforeToolbar={ tabs }
                    label={ __( "Choose a template", "fooconvert" ) }
                    reset={ { template: undefined } }
                    media="thumbnail"
                    className={ `${ ROOT_CLASS }__picker` }
                />
            </div>
        </div>
    );
};

export default PopupTypeTemplatePicker;
