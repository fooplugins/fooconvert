import { PanelRow, TextControl, ToggleControl } from "@wordpress/components";
import { PluginDocumentSettingPanel, store as editorStore } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";
import { $object, OpenTriggerComponent, useRootAttributes } from "#editor";
import { useEffect, useState } from "@wordpress/element";
import { isString } from "@steveush/utils";
import { BAR_DEFAULTS } from "../../Edit";
import { dispatch } from "@wordpress/data";

const TriggerControls = () => {

    const [ attributes, setAttributes ] = useRootAttributes( 'fc/bar' );

    const {
        settings,
        openButton,
        closeButton,
    } = attributes;

    const [ closeAnchorChecked, setCloseAnchorChecked ] = useState( isString( settings?.closeAnchor, true ) );

    useEffect( () => {
        dispatch( editorStore )?.toggleEditorPanelOpened( 'fc/fc--open-trigger' );
    }, [] );

    const attributesDefaults = { ...BAR_DEFAULTS };

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
    const setCloseAnchor = value => setSettings( { closeAnchor: isString( value, true ) && value !== settingsDefaults?.closeAnchor ? value : undefined } );

    const setCloseButtonHidden = value => setCloseButtonSettings( { hidden: value !== closeButtonSettingsDefaults?.hidden ? value : undefined } );
    const setOpenButtonHidden = value => setOpenButtonSettings( { hidden: value !== openButtonSettingsDefaults?.hidden ? value : undefined } );

    return (
        <>
            <PluginDocumentSettingPanel name="fc--open-trigger" title={ __( 'Open Trigger', 'fooconvert' ) }>
                <PanelRow>
                    <OpenTriggerComponent
                        value={ settings?.trigger ?? settingsDefaults?.trigger }
                        onChange={ setTrigger }
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
                    />
                </PanelRow>
            </PluginDocumentSettingPanel>
            <PluginDocumentSettingPanel name="fc--close-trigger" title={ __( 'Close Trigger', 'fooconvert' ) } initialOpen={ false }>
                <PanelRow>
                    <ToggleControl
                        label={ __( 'Hide close button', 'fooconvert' ) }
                        help={ __( 'Hide the default close button.', 'fooconvert' ) }
                        checked={ closeButtonSettings?.hidden ?? closeButtonSettingsDefaults?.hidden ?? false  }
                        onChange={ setCloseButtonHidden }
                    />
                </PanelRow>
                <PanelRow>
                    <ToggleControl
                        label={ __( 'Close on anchor click', 'fooconvert' ) }
                        help={ __( 'Clicking specific anchors closes the bar.', 'fooconvert' ) }
                        checked={ closeAnchorChecked }
                        onChange={ value => setCloseAnchorChecked( value ) }
                    />
                </PanelRow>
                { closeAnchorChecked && (
                    <PanelRow>
                        <TextControl
                            label={ __( 'Anchor', 'fooconvert' ) }
                            help={ __( 'Add an anchor to a button block and then insert the same value here to close the bar on click.', 'fooconvert' ) }
                            value={ settings?.closeAnchor ?? settingsDefaults?.closeAnchor ?? "" }
                            onChange={ setCloseAnchor }
                        />
                    </PanelRow>
                ) }
            </PluginDocumentSettingPanel>
        </>
    );
};

export default TriggerControls;