import { InspectorControls } from "@wordpress/block-editor";
import { IconToolsPanel } from "#editor";

const EditSettings = props => {

    const {
        clientId,
        settings,
        setSettings,
        settingsDefaults,
    } = props;

    const setIcon = value => setSettings( { icon: value } );

    return (
        <InspectorControls group="settings">
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