import { InspectorControls } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import {
    BorderToolsPanel,
    ColorToolsPanel,
    DimensionToolsPanel,
    SizeControl, ToggleSelectControl,
    ToolsPanelItem,
} from "#editor";
import { PanelBody, PanelRow } from "@wordpress/components";
import { alignCenter, alignLeft, alignRight } from "@wordpress/icons";

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
                controls={ [ 'padding', 'margin', 'gap' ] }
                defaults={ stylesDefaults?.dimensions }
            />
        </InspectorControls>
    );
};

export default EditStyles;