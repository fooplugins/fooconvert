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
            action,
            icon,
            position,
            styles
        },
        setAttributes,
        defaults,
        iconSets
    } = props;

    const setAction = newValue => setAttributes( { action: newValue === defaults?.action ? undefined : newValue } );
    const setPosition = newValue => setAttributes( { position: newValue === defaults?.position ? undefined : newValue } );
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

    const actions = [{
        value: 'close',
        label: __( 'Close', 'fooconvert' )
    },{
        value: 'toggle',
        label: __( 'Toggle', 'fooconvert' )
    }];

    const positions = [{
        value: 'left',
        label: __( 'Left', 'fooconvert' )
    },{
        value: 'right',
        label: __( 'Right', 'fooconvert' )
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
                            help={ __( 'Choose where to display the button within the bar.', 'fooconvert' ) }
                        />
                    </PanelRow>
                </PanelBody>
                <PanelBody title={ __( "Behavior", "fooconvert" ) }>
                    <PanelRow>
                        <ToggleSelectControl
                            label={ __( 'Action', 'fooconvert' ) }
                            value={ action ?? defaults?.action }
                            onChange={ setAction }
                            options={ actions }
                            help={ __( 'Choose the action to perform when the button is clicked.', 'fooconvert' ) }
                        />
                    </PanelRow>
                </PanelBody>
                <IconToolsPanel
                    panelId={ clientId }
                    value={ icon }
                    onChange={ setIcon }
                    defaults={ defaults?.icon }
                    iconSets={ iconSets }
                    currentAction={ action }
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