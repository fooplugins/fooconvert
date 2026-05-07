import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, PanelRow, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import {
    DimensionToolsPanel,
    InnerBlocksButton,
    UnitControl,
    $object,
} from "#editor";
import { PositionControl } from "./components";
import {
    BAR_WIDTH_DEFAULT,
    BarWidthControl,
    BarWidthModeControl,
    getBarContentWidth,
    getBarWidthMode,
    isBarContentWidthMode,
} from "./size-controls";

const EditSettings = props => {
    const {
        clientId,
        settings,
        setSettings,
        settingsDefaults,
        styles,
        setStyles,
        stylesDefaults,
        content,
        setContent,
        contentDefaults,
    } = props;

    const setTransitions = value => setSettings( { transitions: value !== settingsDefaults?.transitions ? value : undefined } );
    const setPosition = value => setSettings( { position: value !== settingsDefaults?.position ? value : undefined } );
    const widthMode = getBarWidthMode( settings, settingsDefaults );
    const setWidthMode = value => setSettings( { widthMode: value !== settingsDefaults?.widthMode ? value : undefined } );

    const setDimensions = value => setStyles( { dimensions: value } );
    const maxWidth = settings?.maxWidth ?? settingsDefaults?.maxWidth;
    const setMaxWidth = value => setSettings( { maxWidth: value !== settingsDefaults?.maxWidth ? value : undefined } );

    const contentStyles = content?.styles ?? {};
    const contentStylesDefaults = contentDefaults?.styles ?? {};
    const setContentStyles = value => setContent( { styles: $object( contentStyles, value ) } );
    const setWidth = value => setContentStyles( {
        width: value !== contentStylesDefaults?.width && value !== BAR_WIDTH_DEFAULT ? value : undefined,
    } );

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
                <PanelBody title={ __( 'Size', 'fooconvert' ) } initialOpen={ false }>
                    <PanelRow>
                        <BarWidthModeControl
                            value={ widthMode }
                            onChange={ setWidthMode }
                        />
                    </PanelRow>
                    { isBarContentWidthMode( settings, settingsDefaults ) ? (
                        <PanelRow>
                            <BarWidthControl
                                value={ getBarContentWidth( contentStyles, contentStylesDefaults ) }
                                onChange={ setWidth }
                            />
                        </PanelRow>
                    ) : (
                        <PanelRow>
                            <UnitControl
                                label={ __( 'Max Width', 'fooconvert' ) }
                                help={ __( 'Set the maximum width of the bar.', 'fooconvert' ) }
                                value={ maxWidth }
                                onChange={ setMaxWidth }
                                __next40pxDefaultSize
                            />
                        </PanelRow>
                    ) }
                </PanelBody>
                <PanelBody title={ __( 'Behavior', 'fooconvert' ) } initialOpen={ false }>
                    <PanelRow>
                        <ToggleControl
                            label={ __( 'Enable transitions', 'fooconvert' ) }
                            help={ __( 'Choose if transitions are used when toggling the bar.', 'fooconvert' ) }
                            checked={ settings?.transitions ?? settingsDefaults?.transitions ?? false }
                            onChange={ setTransitions }
                            __nextHasNoMarginBottom
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
