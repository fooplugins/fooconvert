import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    DimensionToolsPanel,
    InnerBlocksButton,
} from "#editor";
import { PositionControl } from "./components";

const EditSettings = props => {
    const {
        clientId,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults,
    } = props;

    const setTransitions = value => setSettings( { transitions: value !== settingsDefaults?.transitions ? value : undefined } );
    const setPosition = value => setSettings( { position: value !== settingsDefaults?.position ? value : undefined } );

    const setDimensions = value => setStyles( { dimensions: value } );

    return (
        <>
            <InspectorControls group="settings">
                <PanelBody title={ __( 'Position', 'fooconvert' ) }>
                    <PanelRow>
                        <PositionControl
                            value={ settings?.position ?? settingsDefaults?.position }
                            onChange={ setPosition }
                        />
                    </PanelRow>
                </PanelBody>
                <PanelBody title={ __( 'Behavior', 'fooconvert' ) } initialOpen={ false }>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Enable transitions', 'fooconvert' ) }
                            help={ __( 'Choose if transitions are used when toggling the bar.', 'fooconvert' ) }
                            checked={ settings?.transitions ?? settingsDefaults?.transitions ?? false }
                            onChange={ setTransitions }
                        />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <InspectorControls group="styles">
                <DimensionToolsPanel
                    panelId={ clientId }
                    value={ styles?.dimensions }
                    onChange={ setDimensions }
                    controls={ [ 'padding' ] }
                    defaults={ stylesDefaults?.dimensions }
                />
            </InspectorControls>
            <InspectorControls group="advanced">
                <PanelRow>
                    <InnerBlocksButton
                        targetClientId={ clientId }
                        prepareAttributes={ ( attr, slug ) => {
                            const { clientId, postId, ...result } = attr;
                            return { ...result, variation: slug };
                        } }
                        variant="secondary">
                        { __( 'Make Variation', 'fooconvert' ) }
                    </InnerBlocksButton>
                </PanelRow>
            </InspectorControls>
        </>
    );
};

export default EditSettings;