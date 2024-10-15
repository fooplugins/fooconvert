import { cleanObject } from "@steveush/utils";
import { __ } from "@wordpress/i18n";
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow } from "@wordpress/components";

import {
    BorderToolsPanel,
    ColorToolsPanel,
    DimensionToolsPanel,
    ToggleSelectControl
} from "#editor";
import { IconToolsPanel } from "./components";

const EditSettings = props => {

    const {
        clientId,
        attributes: {
            icon,
            position,
            alignment,
            styles
        },
        setAttributes,
        defaults,
        iconSets
    } = props;

    const setPosition = newValue => setAttributes( { position: newValue === defaults?.position ? undefined : newValue } );
    const setAlignment = newValue => setAttributes( { alignment: newValue === defaults?.alignment ? undefined : newValue } );
    const setIcon = newValue => setAttributes( { icon: newValue } );

    const setStyles = newValue => {
        const previousValue = styles ?? {};
        const nextValue = typeof newValue === 'object' ? {
            ...previousValue,
            ...newValue
        } : undefined;
        setAttributes( { styles: cleanObject( nextValue ) } );
    };

    const setColor = newValue => setStyles( { color: newValue } );
    const setBorder = newValue => setStyles( { border: newValue } );
    const setDimensions = newValue => setStyles( { dimensions: newValue } );

    const positions = [{
        value: 'left',
        label: __( 'Left', 'fooconvert' )
    },{
        value: 'right',
        label: __( 'Right', 'fooconvert' )
    }];

    const alignments = [{
        value: 'inside',
        label: __( 'Inside', 'fooconvert' )
    },{
        value: 'corner',
        label: __( 'Corner', 'fooconvert' )
    },{
        value: 'outside',
        label: __( 'Outside', 'fooconvert' )
    }];

    const colors = [
        {
            key: 'background',
            label: __( 'Background', "fooconvert" ),
            enableAlpha: true,
            enableGradient: true
        },
        {
            key: 'icon',
            label: __( 'Icon', "fooconvert" ),
        }
    ];

    return (
        <>
            <InspectorControls group="settings">
                <PanelBody title={ __( "Position", "fooconvert" ) }>
                    <PanelRow>
                        <ToggleSelectControl
                            label={ __( 'Position', 'fooconvert' ) }
                            hideLabelFromVision={ true }
                            value={ position ?? defaults?.position }
                            onChange={ setPosition }
                            options={ positions }
                            help={ __( 'Choose which side the button is displayed.', 'fooconvert' ) }
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleSelectControl
                            label={ __( 'Position Anchor', 'fooconvert' ) }
                            hideLabelFromVision={ true }
                            value={ alignment ?? defaults?.alignment }
                            onChange={ setAlignment }
                            options={ alignments }
                            help={ __( 'Choose how the button is anchored to the popup.', 'fooconvert' ) }
                        />
                    </PanelRow>
                </PanelBody>
                <IconToolsPanel
                    panelId={ clientId }
                    value={ icon }
                    onChange={ setIcon }
                    defaults={ defaults?.icon }
                    iconSets={ iconSets }
                />
            </InspectorControls>
            <InspectorControls group="styles">
                <ColorToolsPanel
                    value={ styles?.color }
                    onChange={ setColor }
                    panelId={ clientId }
                    options={ colors }
                />
                <BorderToolsPanel
                    panelId={ clientId }
                    value={ styles?.border }
                    onChange={ setBorder }
                />
                <DimensionToolsPanel
                    panelId={ clientId }
                    value={ styles?.dimensions }
                    onChange={ setDimensions }
                    defaults={ defaults?.styles?.dimensions }
                    controls={ [ 'margin', 'padding' ] }
                />
            </InspectorControls>
        </>
    );
};

export default EditSettings;