import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    $object, ColorToolsPanel,
    DimensionToolsPanel,
    InnerBlocksButton,
    OpenTriggerComponent,
} from "#editor";
import { isString } from "@steveush/utils";
import { useState } from "@wordpress/element";

const EditSettings = props => {
    const {
        clientId,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults,
        closeButton,
        setCloseButton,
        defaults
    } = props;

    const [ closeAnchorChecked, setCloseAnchorChecked ] = useState( isString( settings?.closeAnchor, true ) );

    const setTrigger = ( value ) => setSettings( { trigger: value } );
    const setCloseAnchor = value => setSettings( { closeAnchor: isString( value, true ) ? value : undefined } );
    const setTransitions = value => setSettings( { transitions: value !== settingsDefaults?.transitions ? value : undefined } );
    const setMaxOnMobile = value => setSettings( { maxOnMobile: value !== settingsDefaults?.maxOnMobile ? value : undefined } );
    const setHideScrollbar = value => setSettings( { hideScrollbar: value !== settingsDefaults?.hideScrollbar ? value : undefined } );
    const setBackdropIgnore = value => setSettings( { backdropIgnore: value !== settingsDefaults?.backdropIgnore ? value : undefined } );

    const setDimensions = value => setStyles( { dimensions: value } );
    const setColor = value => setStyles( { color: value } );

    const setHideCloseButton = value => setCloseButton( { settings: $object( closeButton?.settings, { hidden: value !== defaults?.elements?.closeButton?.settings?.hidden ? value : undefined } ) } );

    const colors = [ {
        key: 'backdrop',
        label: __( 'Backdrop', 'fooconvert' ),
        enableAlpha: true,
        enableGradient: true
    } ];

    return (
        <>
            <InspectorControls group="settings">
                <PanelBody title={ __( 'Open Trigger', 'fooconvert' ) }>
                    <PanelRow>
                        <OpenTriggerComponent
                            value={ settings?.trigger ?? settingsDefaults?.trigger }
                            onChange={ setTrigger }
                            allowEmpty={ true }
                            hideLabelFromVision
                        />
                    </PanelRow>
                </PanelBody>
                <PanelBody title={ __( 'Close Trigger', 'fooconvert' ) } initialOpen={ false }>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Hide close button', 'fooconvert' ) }
                            help={ __( 'Hide the default close button.', 'fooconvert' ) }
                            checked={ closeButton?.settings?.hidden ?? defaults?.elements?.closeButton?.settings?.hidden ?? false  }
                            onChange={ setHideCloseButton }
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Ignore backdrop click', 'fooconvert' ) }
                            help={ __( 'Do not close the popup when the backdrop is clicked.', 'fooconvert' ) }
                            checked={ settings?.backdropIgnore ?? settingsDefaults?.backdropIgnore ?? false  }
                            onChange={ setBackdropIgnore }
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Close on anchor click', 'fooconvert' ) }
                            help={ __( 'Clicking specific anchors closes the popup.', 'fooconvert' ) }
                            checked={ closeAnchorChecked }
                            onChange={ value => setCloseAnchorChecked( value ) }
                        />
                    </PanelRow>
                    { closeAnchorChecked && (
                        <PanelRow>
                            <TextControl
                                label={ __( 'Anchor', 'fooconvert' ) }
                                help={ __( 'Add an anchor to a button block and then insert the same value here to close the notification on click.', 'fooconvert' ) }
                                value={ settings?.closeAnchor ?? settingsDefaults?.closeAnchor ?? "" }
                                onChange={ value => setCloseAnchor( value !== "" ? value : undefined ) }
                            />
                        </PanelRow>
                    ) }
                </PanelBody>
                <PanelBody title={ __( 'Behavior', 'fooconvert' ) } initialOpen={ false }>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Maximize on mobile', 'fooconvert' ) }
                            help={ __( 'Expand the popup on mobile to use all available screen space.', 'fooconvert' ) }
                            checked={ settings?.maxOnMobile ?? settingsDefaults?.maxOnMobile ?? false }
                            onChange={ setMaxOnMobile }
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Enable transitions', 'fooconvert' ) }
                            help={ __( 'Choose if transitions are used when toggling the popup.', 'fooconvert' ) }
                            checked={ settings?.transitions ?? settingsDefaults?.transitions ?? false }
                            onChange={ setTransitions }
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Hide scrollbar', 'fooconvert' ) }
                            help={ __( 'Hide the page scrollbar when open.', 'fooconvert' ) }
                            checked={ settings?.hideScrollbar ?? settingsDefaults?.hideScrollbar ?? false }
                            onChange={ setHideScrollbar }
                        />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <InspectorControls group="styles">
                <ColorToolsPanel
                    panelId={ clientId }
                    value={ styles?.color }
                    onChange={ setColor }
                    options={ colors }
                    defaults={ stylesDefaults?.color }
                />
                <DimensionToolsPanel
                    panelId={ clientId }
                    value={ styles?.dimensions }
                    onChange={ setDimensions }
                    controls={ [ 'padding' ] }
                    defaults={ stylesDefaults?.dimensions }
                />
            </InspectorControls>
            <InspectorControls group="advanced">
                <PanelRow>
                    <InnerBlocksButton
                        targetClientId={ clientId }
                        prepareAttributes={ ( attr, slug ) => {
                            const { clientId, postId, ...result } = attr;
                            return { ...result, variation: slug };
                        } }
                        variant="secondary">
                        { __( 'Make Variation', 'fooconvert' ) }
                    </InnerBlocksButton>
                </PanelRow>
            </InspectorControls>
        </>
    );
};

export default EditSettings;