import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    justifyBottom,
    justifyCenter,
    justifyCenterVertical,
    justifyLeft,
    justifyRight,
    justifyStretch,
    justifyTop,
} from "@wordpress/icons";
import {
    BackgroundImagePanel,
    BorderToolsPanel,
    ColorToolsPanel,
    DimensionToolsPanel,
    ToggleSelectControl,
} from "#editor";

const VERTICAL_ALIGNMENT_OPTIONS = [
    {
        value: "flex-start",
        label: __( "Top", "fooconvert" ),
        icon: justifyTop,
    },
    {
        value: "center",
        label: __( "Center", "fooconvert" ),
        icon: justifyCenterVertical,
    },
    {
        value: "flex-end",
        label: __( "Bottom", "fooconvert" ),
        icon: justifyBottom,
    },
];

const HORIZONTAL_ALIGNMENT_OPTIONS = [
    {
        value: "flex-start",
        label: __( "Left", "fooconvert" ),
        icon: justifyLeft,
    },
    {
        value: "center",
        label: __( "Center", "fooconvert" ),
        icon: justifyCenter,
    },
    {
        value: "flex-end",
        label: __( "Right", "fooconvert" ),
        icon: justifyRight,
    },
    {
        value: "stretch",
        label: __( "Stretch", "fooconvert" ),
        icon: justifyStretch,
    },
];

const EditStyles = ( props ) => {
    const {
        clientId,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults,
    } = props;

    const setColor = value => setStyles( { color: value } );
    const setBorder = value => setStyles( { border: value } );
    const setDimensions = value => setStyles( { dimensions: value } );
    const setJustifyContent = value => setSettings( {
        justifyContent: value !== settingsDefaults?.justifyContent ? value : undefined,
    } );
    const setHorizontalAlignment = value => setSettings( {
        horizontalAlignment: value !== settingsDefaults?.horizontalAlignment ? value : undefined,
    } );

    return (
        <>
            <InspectorControls group="settings">
                <PanelBody title={ __( "Layout", "fooconvert" ) } initialOpen={ false }>
                    <PanelRow>
                        <ToggleSelectControl
                            label={ __( "Vertical", "fooconvert" ) }
                            value={ settings?.justifyContent ?? settingsDefaults?.justifyContent }
                            onChange={ setJustifyContent }
                            options={ VERTICAL_ALIGNMENT_OPTIONS }
                            iconOnly={ true }
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleSelectControl
                            label={ __( "Horizontal", "fooconvert" ) }
                            value={ settings?.horizontalAlignment ?? settingsDefaults?.horizontalAlignment }
                            onChange={ setHorizontalAlignment }
                            options={ HORIZONTAL_ALIGNMENT_OPTIONS }
                            iconOnly={ true }
                        />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <InspectorControls group="styles">
                <ColorToolsPanel
                    panelId={ clientId }
                    value={ styles?.color }
                    onChange={ setColor }
                    options={ [
                        {
                            key: "background",
                            label: __( "Background", "fooconvert" ),
                            enableAlpha: true,
                            enableGradient: true,
                        },
                        {
                            key: "text",
                            label: __( "Text", "fooconvert" ),
                        },
                    ] }
                    defaults={ stylesDefaults?.color }
                />
                <BackgroundImagePanel
                    panelId={ clientId }
                    value={ styles }
                    defaultValues={ stylesDefaults }
                    onChange={ setStyles }
                />
                <BorderToolsPanel
                    panelId={ clientId }
                    value={ styles?.border }
                    onChange={ setBorder }
                    defaults={ stylesDefaults?.border }
                    shownByDefault={ [] }
                />
                <DimensionToolsPanel
                    panelId={ clientId }
                    value={ styles?.dimensions }
                    onChange={ setDimensions }
                    controls={ [ "padding", "margin", "gap" ] }
                    defaults={ stylesDefaults?.dimensions }
                />
            </InspectorControls>
        </>
    );
};

export default EditStyles;
