import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    justifyBottom,
    justifyCenterVertical,
    justifyStretchVertical,
    justifyTop,
} from "@wordpress/icons";
import {
    DimensionToolsPanel,
    SizeControl,
    ToggleSelectControl,
} from "#editor";

const WIDTH_SIZES = [
    {
        value: "280px",
        abbr: __( "S", "fooconvert" ),
        label: __( "Small", "fooconvert" ),
    },
    {
        value: "360px",
        abbr: __( "M", "fooconvert" ),
        label: __( "Medium", "fooconvert" ),
    },
    {
        value: "480px",
        abbr: __( "L", "fooconvert" ),
        label: __( "Large", "fooconvert" ),
    },
];

const WIDTH_UNITS = [
    { value: "px", label: "px", default: 360, step: 4, min: 160, max: 1024 },
    { value: "%", label: "%", default: 40, step: 1, min: 10, max: 90 },
];

const VERTICAL_ALIGNMENT_OPTIONS = [
    {
        value: "top",
        label: __( "Top", "fooconvert" ),
        icon: justifyTop,
    },
    {
        value: "center",
        label: __( "Center", "fooconvert" ),
        icon: justifyCenterVertical,
    },
    {
        value: "bottom",
        label: __( "Bottom", "fooconvert" ),
        icon: justifyBottom,
    },
    {
        value: "stretch",
        label: __( "Stretch", "fooconvert" ),
        icon: justifyStretchVertical,
    },
];

const EditSettings = ( props ) => {
    const {
        clientId,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults,
    } = props;

    const fixedSide = settings?.fixedSide ?? settingsDefaults?.fixedSide;
    const fixedWidth = settings?.fixedWidth ?? settingsDefaults?.fixedWidth;
    const verticalAlignment = settings?.verticalAlignment ?? settingsDefaults?.verticalAlignment;

    const setFixedSide = value => setSettings( { fixedSide: value !== settingsDefaults?.fixedSide ? value : undefined } );
    const setFixedWidth = value => setSettings( { fixedWidth: value !== settingsDefaults?.fixedWidth ? value : undefined } );
    const setVerticalAlignment = value => setSettings( {
        verticalAlignment: value !== settingsDefaults?.verticalAlignment ? value : undefined,
    } );
    const setDimensions = value => setStyles( { dimensions: value } );

    return (
        <>
            <InspectorControls group="settings">
                <PanelBody title={ __( "Layout", "fooconvert" ) }>
                    <PanelRow>
                        <ToggleSelectControl
                            label={ __( "Fixed side", "fooconvert" ) }
                            value={ fixedSide }
                            onChange={ setFixedSide }
                            options={ [
                                {
                                    value: "left",
                                    label: __( "Left", "fooconvert" ),
                                },
                                {
                                    value: "right",
                                    label: __( "Right", "fooconvert" ),
                                },
                            ] }
                        />
                    </PanelRow>
                    <PanelRow>
                        <SizeControl
                            label={ __( "Fixed width", "fooconvert" ) }
                            value={ fixedWidth }
                            onChange={ setFixedWidth }
                            sizes={ WIDTH_SIZES }
                            units={ WIDTH_UNITS }
                        />
                    </PanelRow>
                    <PanelRow>
                        <ToggleSelectControl
                            label={ __( "Vertical alignment", "fooconvert" ) }
                            value={ verticalAlignment }
                            onChange={ setVerticalAlignment }
                            options={ VERTICAL_ALIGNMENT_OPTIONS }
                            iconOnly={ true }
                        />
                    </PanelRow>
                </PanelBody>
            </InspectorControls>
            <InspectorControls group="styles">
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

export default EditSettings;
