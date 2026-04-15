import { Notice, PanelRow, TextControl, ToggleControl } from "@wordpress/components";
import { PluginDocumentSettingPanel, store as editorStore } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";
import { $object, ExperimentPanel, OpenTriggerComponent, useExperimentVariantLock, useRootAttributes } from "#editor";
import { useEffect, useState } from "@wordpress/element";
import { isString } from "@steveush/utils";
import { POPUP_DEFAULTS } from "../../Edit";
import { dispatch } from "@wordpress/data";

const TriggerControls = () => {

    const [ attributes, setAttributes ] = useRootAttributes( 'fc/overlay' );

    const {
        settings,
        closeButton,
    } = attributes;
    const { isLocked } = useExperimentVariantLock();

    useEffect( () => {
        dispatch( editorStore )?.toggleEditorPanelOpened( 'fc/fc--open-trigger' );
    }, [] );

    const closeAnchor = settings?.closeAnchor;
    const setCloseAnchor = value => setSettings( { closeAnchor: isString( value, true ) && value !== settingsDefaults?.closeAnchor ? value : undefined } );
    const hasCloseAnchor = isString( closeAnchor, true );
    const [ closeAnchorChecked, setCloseAnchorChecked ] = useState( hasCloseAnchor );

    useEffect( () => {
        if ( !closeAnchorChecked && hasCloseAnchor ) {
            setCloseAnchor( undefined );
        }
    }, [ closeAnchor, closeAnchorChecked ] );

    const attributesDefaults = { ...POPUP_DEFAULTS };

    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( attributesDefaults?.settings ?? {} ) };

    const setCloseButton = value => setAttributes( { closeButton: $object( closeButton, value ) } );
    const closeButtonDefaults = { ...( attributesDefaults?.closeButton ?? {} ) };

    const closeButtonSettings = { ...( closeButton?.settings ?? {} ) };
    const setCloseButtonSettings = value => setCloseButton( { settings: $object( closeButton?.settings, value ) } );
    const closeButtonSettingsDefaults = { ...( closeButtonDefaults?.settings ?? {} ) };

    const setTrigger = ( value ) => setSettings( { trigger: value } );
    const setBackdropIgnore = value => setSettings( { backdropIgnore: value !== settingsDefaults?.backdropIgnore ? value : undefined } );
    const setCloseOnSubmit = value => setSettings( { closeOnSubmit: value !== settingsDefaults?.closeOnSubmit ? value : undefined } );

    const setCloseButtonHidden = value => setCloseButtonSettings( { hidden: value !== closeButtonSettingsDefaults?.hidden ? value : undefined } );

    return (
        <>
            <ExperimentPanel/>
            <PluginDocumentSettingPanel name="fc--open-trigger" title={ __( 'Open Trigger', 'fooconvert' ) }>
                { isLocked && (
                    <PanelRow>
                        <Notice status="info" isDismissible={ false }>
                            { __( "Inherits open trigger from control.", "fooconvert" ) }
                        </Notice>
                    </PanelRow>
                ) }
                <PanelRow>
                    <OpenTriggerComponent
                        value={ settings?.trigger ?? settingsDefaults?.trigger }
                        onChange={ setTrigger }
                        locked={ isLocked }
                        allowEmpty={ true }
                        help={ __( 'Select how the overlay is opened.', 'fooconvert' ) }
                        hideLabelFromVision
                    />
                </PanelRow>
            </PluginDocumentSettingPanel>
            <PluginDocumentSettingPanel name="fc--close-trigger" title={ __( 'Close Trigger', 'fooconvert' ) }>
                { isLocked && (
                    <PanelRow>
                        <Notice status="info" isDismissible={ false }>
                            { __( "Inherits close trigger from control.", "fooconvert" ) }
                        </Notice>
                    </PanelRow>
                ) }
                <PanelRow>
                    <ToggleControl
                        label={ __( 'Hide close button', 'fooconvert' ) }
                        help={ __( 'Hide the default close button.', 'fooconvert' ) }
                        checked={ closeButtonSettings?.hidden ?? closeButtonSettingsDefaults?.hidden ?? false }
                        onChange={ setCloseButtonHidden }
                        disabled={ isLocked }
                        __nextHasNoMarginBottom
                    />
                </PanelRow>
                <PanelRow>
                    <ToggleControl
                        label={ __( 'Close on backdrop click', 'fooconvert' ) }
                        help={ __( 'Close the overlay when the backdrop is clicked.', 'fooconvert' ) }
                        checked={ !( settings?.backdropIgnore ?? settingsDefaults?.backdropIgnore ?? false ) }
                        onChange={ value => setBackdropIgnore( !value ) }
                        disabled={ isLocked }
                        __nextHasNoMarginBottom
                    />
                </PanelRow>
                {/*<PanelRow>*/}
                {/*    <ToggleControl*/}
                {/*        label={ __( 'Close on form submit', 'fooconvert' ) }*/}
                {/*        help={ __( 'Close the overlay when a child form is submitted.', 'fooconvert' ) }*/}
                {/*        checked={ settings?.closeOnSubmit ?? settingsDefaults?.closeOnSubmit ?? false }*/}
                {/*        onChange={ setCloseOnSubmit }*/}
                {/*    />*/}
                {/*</PanelRow>*/}
                <PanelRow>
                    <ToggleControl
                        label={ __( 'Close on anchor click', 'fooconvert' ) }
                        help={ __( 'Clicking specific anchors closes the overlay.', 'fooconvert' ) }
                        checked={ closeAnchorChecked }
                        onChange={ value => setCloseAnchorChecked( value ) }
                        disabled={ isLocked }
                        __nextHasNoMarginBottom
                    />
                </PanelRow>
                { closeAnchorChecked && (
                    <PanelRow>
                        <TextControl
                            label={ __( 'Anchor', 'fooconvert' ) }
                            help={ __( 'Add an anchor to a button block and then insert the same value here to close the overlay on click.', 'fooconvert' ) }
                            value={ settings?.closeAnchor ?? settingsDefaults?.closeAnchor ?? "" }
                            onChange={ setCloseAnchor }
                            disabled={ isLocked }
                        />
                    </PanelRow>
                ) }
            </PluginDocumentSettingPanel>
        </>
    );
};

export default TriggerControls;
