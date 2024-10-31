import { __ } from "@wordpress/i18n";
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow } from "@wordpress/components";

import {
    ToggleSelectControl,
    IconToolsPanel
} from "#editor";

const EditSettings = props => {

    const {
        clientId,
        settings,
        setSettings,
        settingsDefaults
    } = props;

    const setPosition = value => setSettings( { position: value !== settingsDefaults?.position ? value : undefined } );
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
                        value={ settings?.position ?? settingsDefaults?.position }
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
                defaults={ settingsDefaults?.icon }
            />
        </InspectorControls>
    );
};

export default EditSettings;