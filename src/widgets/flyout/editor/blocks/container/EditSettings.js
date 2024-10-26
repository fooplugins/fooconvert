import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { FlyoutPositionControl } from "../../components/position-control";
import { $object } from "#editor";

const EditSettings = props => {
    const {
        parentAttributes,
        setParentAttributes,
        parentAttributesDefaults
    } = props;

    const settings = parentAttributes?.settings ?? {};
    const setSettings = value => setParentAttributes( { settings: $object( settings, value ) } );
    const settingsDefaults = { ...( parentAttributesDefaults?.settings ?? {} ) };

    const position = settings?.position ?? settingsDefaults?.position;
    const setPosition = value => setSettings( { position: value !== settingsDefaults?.position ? value : undefined } );

    return (
        <InspectorControls group="settings">
            <PanelBody title={ __( 'Position', 'fooconvert' ) }>
                <PanelRow>
                    <FlyoutPositionControl
                        value={ position }
                        onChange={ setPosition }
                    />
                </PanelRow>
                <PanelRow>
                    <ToggleControl
                    />
                </PanelRow>
            </PanelBody>
        </InspectorControls>
    );
};

export default EditSettings;