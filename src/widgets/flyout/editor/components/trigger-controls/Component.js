import { Notice, PanelRow, TextControl, ToggleControl } from "@wordpress/components";
import { PluginDocumentSettingPanel, store as editorStore } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";
import { $object, ExperimentPanel, OpenTriggerComponent, useExperimentVariantLock, useRootAttributes } from "#editor";
import { useEffect, useState } from "@wordpress/element";
import { isString } from "@steveush/utils";
import { FLYOUT_DEFAULTS } from "../../Edit";
import { dispatch } from "@wordpress/data";

const TriggerControls = () => {

    const [ attributes, setAttributes ] = useRootAttributes( 'fc/flyout' );

    const {
        settings,
        openButton,
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

    const attributesDefaults = { ...FLYOUT_DEFAULTS };

    const setSettings = value => setAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( attributesDefaults?.settings ?? {} ) };

    const setOpenButton = value => setAttributes( { openButton: $object( openButton, value ) } );
    const openButtonDefaults = { ...( attributesDefaults?.openButton ?? {} ) };

    const openButtonSettings = { ...( openButton?.settings ?? {} ) };
    const setOpenButtonSettings = value => setOpenButton( { settings: $object( openButton?.settings, value ) } );
    const openButtonSettingsDefaults = { ...( openButtonDefaults?.settings ?? {} ) };

    const setCloseButton = value => setAttributes( { closeButton: $object( closeButton, value ) } );
    const closeButtonDefaults = { ...( attributesDefaults?.closeButton ?? {} ) };

    const closeButtonSettings = { ...( closeButton?.settings ?? {} ) };
    const setCloseButtonSettings = value => setCloseButton( { settings: $object( closeButton?.settings, value ) } );
    const closeButtonSettingsDefaults = { ...( closeButtonDefaults?.settings ?? {} ) };

    const setTrigger = ( value ) => setSettings( { trigger: value } );

    const setCloseButtonHidden = value => setCloseButtonSettings( { hidden: value !== closeButtonSettingsDefaults?.hidden ? value : undefined } );
    const setOpenButtonHidden = value => setOpenButtonSettings( { hidden: value !== openButtonSettingsDefaults?.hidden ? value : undefined } );

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
                        hideLabelFromVision
                    />
                </PanelRow>
                <PanelRow>
                    <ToggleControl
                        label={ __( 'Hide open button', 'fooconvert' ) }
                        help={ __( 'Hide the default open button.', 'fooconvert' ) }
                        checked={ openButtonSettings?.hidden ?? openButtonSettingsDefaults?.hidden ?? false }
                        onChange={ setOpenButtonHidden }
                        disabled={ isLocked }
                        __nextHasNoMarginBottom
                    />
                </PanelRow>
            </PluginDocumentSettingPanel>
            <PluginDocumentSettingPanel name="fc--close-trigger" title={ __( 'Close Trigger', 'fooconvert' ) } initialOpen={ false }>
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
                        checked={ closeButtonSettings?.hidden ?? closeButtonSettingsDefaults?.hidden ?? false  }
                        onChange={ setCloseButtonHidden }
                        disabled={ isLocked }
                        __nextHasNoMarginBottom
                    />
                </PanelRow>
                <PanelRow>
                    <ToggleControl
                        label={ __( 'Close on anchor click', 'fooconvert' ) }
                        help={ __( 'Clicking specific anchors closes the flyout.', 'fooconvert' ) }
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
                            help={ __( 'Add an anchor to a button block and then insert the same value here to close the flyout on click.', 'fooconvert' ) }
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
