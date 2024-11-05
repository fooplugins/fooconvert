import { InspectorControls } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import {
    BorderToolsPanel,
    ColorToolsPanel,
    DimensionToolsPanel,
    SizeControl,
    ToolsPanelItem,
} from "#editor";

const EditStyles = props => {
    const {
        clientId,
        styles,
        setStyles,
        stylesDefaults
    } = props;

    const setColor = value => setStyles( { color: value } );
    const setBorder = value => setStyles( { border: value } );
    const setDimensions = value => setStyles( { dimensions: value } );
    const setWidth = value => setStyles( { width: value } );
    const hasWidth = typeof styles?.width === 'string' && styles.width !== stylesDefaults?.width;

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
                controls={ [ 'padding', 'margin' ] }
                defaults={ stylesDefaults?.dimensions }
                itemRenderer={ () => (
                    <ToolsPanelItem
                        panelId={ clientId }
                        hasValue={ () => hasWidth }
                        label={ __( "Width", "fooconvert" ) }
                        onDeselect={ () => setWidth( undefined ) }
                        isShownByDefault={ true }
                    >
                        <SizeControl
                            label={ __( 'Width', 'fooconvert' ) }
                            value={ styles?.width ?? stylesDefaults?.width }
                            onChange={ setWidth }
                            sizes={ [{
                                value: 'fit-content',
                                abbr: __( 'Fit', 'fooconvert' ),
                                label: __( 'Fit Content', 'fooconvert' )
                            },{
                                value: '280px',
                                abbr: __( 'S', 'fooconvert' ),
                                label: __( 'Small', 'fooconvert' )
                            },{
                                value: '480px',
                                abbr: __( 'M', 'fooconvert' ),
                                label: __( 'Medium', 'fooconvert' )
                            },{
                                value: '720px',
                                abbr: __( 'L', 'fooconvert' ),
                                label: __( 'Large', 'fooconvert' )
                            },{
                                value: '1024px',
                                abbr: __( 'XL', 'fooconvert' ),
                                label: __( 'Extra Large', 'fooconvert' )
                            }] }
                            units={ [
                                { value: 'px', label: 'px', default: 24, step: 4, min: 200, max: 2048 },
                                { value: '%', label: '%', default: 1, step: 1, min: 1, max: 100 }
                            ] }
                        />
                    </ToolsPanelItem>
                ) }
            />
        </InspectorControls>
    );
};

export default EditStyles;