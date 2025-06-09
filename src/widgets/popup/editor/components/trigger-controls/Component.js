import { PanelRow, TextControl, ToggleControl } from "@wordpress/components";
import { PluginDocumentSettingPanel, store as editorStore } from "@wordpress/editor";
import { __ } from "@wordpress/i18n";
import { $object, OpenTriggerComponent, useRootAttributes } from "#editor";
import { useEffect, useState } from "@wordpress/element";
import { isString } from "@steveush/utils";
import { POPUP_DEFAULTS } from "../../Edit";
import { dispatch } from "@wordpress/data";

const TriggerControls = () => {

    const [ attributes, setAttributes ] = useRootAttributes( 'fc/popup' );

    const {
        settings,
        closeButton,
    } = attributes;

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
            <PluginDocumentSettingPanel name="fc--open-trigger" title={ __( 'Open Trigger', 'fooconvert' ) }>
                <PanelRow>
                    <OpenTriggerComponent
                        value={ settings?.trigger ?? settingsDefaults?.trigger }
                        onChange={ setTrigger }
                        allowEmpty={ true }
                        hideLabelFromVision
                    />
                </PanelRow>
            </PluginDocumentSettingPanel>
            <PluginDocumentSettingPanel name="fc--close-trigger" title={ __( 'Close Trigger', 'fooconvert' ) }>
                <PanelRow>
                    <ToggleControl
                        label={ __( 'Hide close button', 'fooconvert' ) }
                        help={ __( 'Hide the default close button.', 'fooconvert' ) }
                        checked={ closeButtonSettings?.hidden ?? closeButtonSettingsDefaults?.hidden ?? false }
                        onChange={ setCloseButtonHidden }
                        __nextHasNoMarginBottom
                    />
                </PanelRow>
                <PanelRow>
                    <ToggleControl
                        label={ __( 'Close on backdrop click', 'fooconvert' ) }
                        help={ __( 'Close the popup when the backdrop is clicked.', 'fooconvert' ) }
                        checked={ !( settings?.backdropIgnore ?? settingsDefaults?.backdropIgnore ?? false ) }
                        onChange={ value => setBackdropIgnore( !value ) }
                        __nextHasNoMarginBottom
                    />
                </PanelRow>
                {/*<PanelRow>*/}
                {/*    <ToggleControl*/}
                {/*        label={ __( 'Close on form submit', 'fooconvert' ) }*/}
                {/*        help={ __( 'Close the popup when a child form is submitted.', 'fooconvert' ) }*/}
                {/*        checked={ settings?.closeOnSubmit ?? settingsDefaults?.closeOnSubmit ?? false }*/}
                {/*        onChange={ setCloseOnSubmit }*/}
                {/*    />*/}
                {/*</PanelRow>*/}
                <PanelRow>
                    <ToggleControl
                        label={ __( 'Close on anchor click', 'fooconvert' ) }
                        help={ __( 'Clicking specific anchors closes the popup.', 'fooconvert' ) }
                        checked={ closeAnchorChecked }
                        onChange={ value => setCloseAnchorChecked( value ) }
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
                        />
                    </PanelRow>
                ) }
            </PluginDocumentSettingPanel>
        </>
    );
};

export default TriggerControls;