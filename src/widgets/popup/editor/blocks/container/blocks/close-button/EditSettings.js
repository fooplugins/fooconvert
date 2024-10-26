import { __ } from "@wordpress/i18n";
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow } from "@wordpress/components";

import {
    ToggleSelectControl
} from "#editor";
import { IconToolsPanel } from "./components";

const EditSettings = props => {

    const {
        clientId,
        settings,
        setSettings,
        defaultSettings,
        iconSets
    } = props;

    const setPosition = value => setSettings( { position: value !== defaultSettings?.position ? value : undefined } );
    const setIcon = value => setSettings( { icon: value } );

    const positions = [{
        value: 'left',
        label: __( 'Left', 'fooconvert' )
    },{
        value: 'right',
        label: __( 'Right', 'fooconvert' )
    }];

    return (
        <InspectorControls group="settings">
            <PanelBody title={ __( "Position", "fooconvert" ) }>
                <PanelRow>
                    <ToggleSelectControl
                        label={ __( 'Position', 'fooconvert' ) }
                        hideLabelFromVision={ true }
                        value={ settings?.position ?? defaultSettings?.position }
                        onChange={ setPosition }
                        options={ positions }
                        help={ __( 'Choose which side the button is displayed.', 'fooconvert' ) }
                    />
                </PanelRow>
            </PanelBody>
            <IconToolsPanel
                panelId={ clientId }
                value={ settings?.icon }
                onChange={ setIcon }
                defaults={ defaultSettings?.icon }
                iconSets={ iconSets }
            />
        </InspectorControls>
    );
};

export default EditSettings;