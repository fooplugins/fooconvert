import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    $object,
    DimensionToolsPanel,
    InnerBlocksButton,
    OpenTriggerComponent,
} from "#editor";
import { cleanObject, isString } from "@steveush/utils";
import { useState } from "@wordpress/element";
import FlyoutPositionControl from "./components/position-control/Component";

const EditSettings = props => {
    const {
        clientId,
        settings,
        setSettings,
        styles,
        setStyles,
        openButton,
        setOpenButton,
        closeButton,
        setCloseButton,
        defaults
    } = props;

    const [ closeAnchorChecked, setCloseAnchorChecked ] = useState( isString( settings?.closeAnchor, true ) );

    const setTrigger = ( value ) => setSettings( { trigger: value } );
    const setCloseAnchor = value => setSettings( { closeAnchor: isString( value, true ) ? value : undefined } );
    const setTransitions = value => setSettings( { transitions: value !== defaults?.settings?.transitions ? value : undefined } );
    const setPosition = value => setSettings( { position: value !== defaults?.settings?.position ? value : undefined } );
    const setMaxOnMobile = value => setSettings( { maxOnMobile: value !== defaults?.settings?.maxOnMobile ? value : undefined } );

    const setDimensions = value => setStyles( { dimensions: value } );

    const setHideCloseButton = value => setCloseButton( { settings: $object( closeButton?.settings, { hidden: value !== defaults?.elements?.closeButton?.settings?.hidden ? value : undefined } ) } );
    const setHideOpenButton = value => setOpenButton( { settings: $object( openButton?.settings, { hidden: value !== defaults?.elements?.openButton?.settings?.hidden ? value : undefined } ) } );

    return (
        <>
            <InspectorControls group="settings">
                <PanelBody title={ __( 'Position', 'fooconvert' ) }>
                    <PanelRow>
                        <FlyoutPositionControl
                            value={ settings?.position ?? defaults?.settings?.position }
                            onChange={ setPosition }
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Maximize on mobile', 'fooconvert' ) }
                            help={ __( 'Expand the flyout on mobile to use as much screen space as possible.', 'fooconvert' ) }
                            checked={ settings?.maxOnMobile ?? defaults?.settings?.maxOnMobile ?? false }
                            onChange={ setMaxOnMobile }
                        />
                    </PanelRow>
                </PanelBody>
                <PanelBody title={ __( 'Open Trigger', 'fooconvert' ) }>
                    <PanelRow>
                        <OpenTriggerComponent
                            value={ settings?.trigger }
                            onChange={ setTrigger }
                            allowEmpty={ true }
                            hideLabelFromVision
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Hide open button', 'fooconvert' ) }
                            help={ __( 'Hide the default open button.', 'fooconvert' ) }
                            checked={ openButton?.settings?.hidden ?? defaults?.elements?.openButton?.settings?.hidden ?? false }
                            onChange={ setHideOpenButton }
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
                            label={ __( 'Close on anchor click', 'fooconvert' ) }
                            help={ __( 'Clicking specific anchors closes the notification.', 'fooconvert' ) }
                            checked={ closeAnchorChecked }
                            onChange={ value => setCloseAnchorChecked( value ) }
                        />
                    </PanelRow>
                    { closeAnchorChecked && (
                        <PanelRow>
                            <TextControl
                                label={ __( 'Anchor', 'fooconvert' ) }
                                help={ __( 'Add an anchor to a button block and then insert the same value here to close the notification on click.', 'fooconvert' ) }
                                value={ settings?.closeAnchor ?? "" }
                                onChange={ value => setCloseAnchor( value !== "" ? value : undefined ) }
                            />
                        </PanelRow>
                    ) }
                </PanelBody>
                <PanelBody title={ __( 'Behavior', 'fooconvert' ) } initialOpen={ false }>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Enable transitions', 'fooconvert' ) }
                            help={ __( 'Choose if transitions are used when toggling the bar.', 'fooconvert' ) }
                            checked={ settings?.transitions ?? defaults?.settings?.transitions ?? false }
                            onChange={ setTransitions }
                        />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <InspectorControls group="styles">
                <DimensionToolsPanel
                    panelId={ clientId }
                    value={ styles?.dimensions }
                    onChange={ setDimensions }
                    controls={ [ 'padding' ] }
                    defaults={ defaults?.styles?.dimensions }
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