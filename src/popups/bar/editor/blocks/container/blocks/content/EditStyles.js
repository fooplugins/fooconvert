import { InspectorControls } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import {
    BackgroundImagePanel,
    BorderToolsPanel,
    ColorToolsPanel,
    DimensionToolsPanel,
    ToolsPanelItem,
} from "#editor";
import {
    BAR_WIDTH_DEFAULT,
    BarWidthControl,
    getBarContentWidth,
    isBarContentWidthMode,
} from "../../../../size-controls";

const EditStyles = props => {
    const {
        clientId,
        parentAttributes,
        parentAttributesDefaults,
        styles,
        setStyles,
        stylesDefaults
    } = props;

    const setColor = value => setStyles( { color: value } );
    const setBorder = value => setStyles( { border: value } );
    const setDimensions = value => setStyles( { dimensions: value } );
    const setWidth = value => setStyles( {
        width: value !== stylesDefaults?.width && value !== BAR_WIDTH_DEFAULT ? value : undefined
    } );
    const settings = parentAttributes?.settings ?? {};
    const settingsDefaults = parentAttributesDefaults?.settings ?? {};
    const isContentWidth = isBarContentWidthMode( settings, settingsDefaults );
    const hasWidth = isContentWidth && typeof styles?.width === 'string' && styles.width !== stylesDefaults?.width;

    const colors = [ {
        key: 'background',
        label: __( 'Background', 'fooconvert' ),
        enableAlpha: true,
        enableGradient: true
    }, {
        key: 'text',
        label: __( 'Text', 'fooconvert' )
    } ];

    return (
        <InspectorControls group="styles">
            <ColorToolsPanel
                panelId={ clientId }
                value={ styles?.color }
                onChange={ setColor }
                options={ colors }
                defaults={ stylesDefaults?.color }
            />
            <BackgroundImagePanel
                panelId={ clientId }
                value={ styles }
                defaultValues={ stylesDefaults }
                onChange={ setStyles }
                backgroundGenerator={ {
                    popupType: 'bar'
                } }
            />
            <BorderToolsPanel
                panelId={ clientId }
                value={ styles?.border }
                onChange={ setBorder }
                defaults={ stylesDefaults?.border }
            />
            <DimensionToolsPanel
                panelId={ clientId }
                value={ styles?.dimensions }
                onChange={ setDimensions }
                controls={ [ 'padding', 'margin', 'gap' ] }
                defaults={ stylesDefaults?.dimensions }
                itemRenderer={ () => isContentWidth ? (
                    <ToolsPanelItem
                        panelId={ clientId }
                        hasValue={ () => hasWidth }
                        label={ __( "Width", "fooconvert" ) }
                        onDeselect={ () => setWidth( undefined ) }
                        isShownByDefault={ true }
                    >
                        <BarWidthControl
                            value={ getBarContentWidth( styles, stylesDefaults ) }
                            onChange={ setWidth }
                        />
                    </ToolsPanelItem>
                ) : null }
            />
        </InspectorControls>
    );
};

export default EditStyles;
