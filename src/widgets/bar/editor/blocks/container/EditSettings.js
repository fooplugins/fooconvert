import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { PositionControl } from "../../components";
import { $object, UnitControl } from "#editor";

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

    const maxWidth = settings?.maxWidth ?? settingsDefaults?.maxWidth;
    const setMaxWidth = value => setSettings( { maxWidth: value !== settingsDefaults?.maxWidth ? value : undefined } );

    return (
        <InspectorControls group="settings">
            <PanelBody title={ __( 'Position', 'fooconvert' ) }>
                <PanelRow>
                    <PositionControl
                        label={ __( 'Position', 'fooconvert' ) }
                        value={ position }
                        onChange={ setPosition }
                    />
                </PanelRow>
                <PanelRow>
                    <UnitControl
                        label={ __( 'Max Width', 'fooconvert' ) }
                        help={ __( 'Set the maximum width of the bar.', 'fooconvert' ) }
                        value={ maxWidth }
                        onChange={ setMaxWidth }
                        __next40pxDefaultSize
                    />
                </PanelRow>
            </PanelBody>
        </InspectorControls>
    );
};

export default EditSettings;