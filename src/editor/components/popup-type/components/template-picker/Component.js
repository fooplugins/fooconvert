import { createBlock } from "@wordpress/blocks";
import { Button, ButtonGroup } from "@wordpress/components";
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

    const tabs = (
        <div className="fc--popup-type-template-picker__header">
            <label className="fc--popup-type-template-picker__label">
                { __( "Popup type", "fooconvert" ) }
            </label>
            <ButtonGroup
                className="fc--popup-type-template-picker__tabs"
                aria-label={ __( "Popup type", "fooconvert" ) }
            >
                { POPUP_TYPE_OPTIONS.map( option => {
                    const isActive = option.value === currentPopupType;

                    return (
                        <Button
                            key={ option.value }
                            variant="tertiary"
                            isPressed={ isActive }
                            onClick={ () => switchPopupType( option.value ) }
                            aria-pressed={ isActive }
                        >
                            { option.label }
                        </Button>
                    );
                } ) }
            </ButtonGroup>
        </div>
    );

    return (
        <VariationPicker
            { ...props }
            clientId={ clientId }
            beforeToolbar={ tabs }
            label={ __( "Choose a template", "fooconvert" ) }
            reset={ { template: undefined } }
            media="thumbnail"
            className="fc--popup-type-template-picker"
        />
    );
};

export default PopupTypeTemplatePicker;
