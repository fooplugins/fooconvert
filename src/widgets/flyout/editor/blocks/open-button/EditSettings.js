import { InspectorControls } from "@wordpress/block-editor";

import { IconToolsPanel } from "./components";

const EditSettings = props => {

    const {
        clientId,
        settings,
        setSettings,
        settingsDefaults,
        iconSets
    } = props;

    const setIcon = value => setSettings( { icon: value } );

    return (
        <InspectorControls group="settings">
            <IconToolsPanel
                panelId={ clientId }
                value={ settings?.icon }
                onChange={ setIcon }
                defaults={ settingsDefaults?.icon }
                iconSets={ iconSets }
            />
        </InspectorControls>
    );
};

export default EditSettings;