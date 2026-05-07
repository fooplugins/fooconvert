import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { PositionControl } from "../../components";
import { $object, UnitControl } from "#editor";
import { isBarContentWidthMode } from "../../size-controls";

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
    const isContentWidth = isBarContentWidthMode( settings, settingsDefaults );

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
            </PanelBody>
            { !isContentWidth ? (
                <PanelBody title={ __( 'Size', 'fooconvert' ) } initialOpen={ false }>
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
            ) : null }
        </InspectorControls>
    );
};

export default EditSettings;
