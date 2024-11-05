import { __ } from "@wordpress/i18n";
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow } from "@wordpress/components";

import {
    IconToolsPanel, ToggleSelectControl
} from "#editor";
import { arrowLeft, arrowRight } from "@wordpress/icons";

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
        label: __( 'Left', 'fooconvert' ),
        icon: arrowLeft
    },{
        value: 'right',
        label: __( 'Right', 'fooconvert' ),
        icon: arrowRight
    }];

    return (
        <InspectorControls group="settings">
            <PanelBody title={ __( "Position", "fooconvert" ) }>
                <PanelRow>
                    <ToggleSelectControl
                        label={ __( 'Horizontal', 'fooconvert' ) }
                        help={ __( 'Choose which side of the bar to display the button.', 'fooconvert' ) }
                        value={ settings?.position ?? settingsDefaults?.position }
                        onChange={ setPosition }
                        options={ positions }
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