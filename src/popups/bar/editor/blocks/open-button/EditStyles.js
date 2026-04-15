import { __ } from "@wordpress/i18n";
import { InspectorControls } from "@wordpress/block-editor";

import {
    BorderToolsPanel,
    ColorToolsPanel,
    DimensionToolsPanel
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

    const colors = [
        {
            key: 'background',
            label: __( 'Background', "fooconvert" ),
            enableAlpha: true,
            enableGradient: true
        },
        {
            key: 'icon',
            label: __( 'Icon', "fooconvert" ),
        }
    ];

    return (
        <InspectorControls group="styles">
            <ColorToolsPanel
                value={ styles?.color }
                onChange={ setColor }
                panelId={ clientId }
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
                defaults={ stylesDefaults?.dimensions }
                controls={ [ 'margin', 'padding' ] }
            />
        </InspectorControls>
    );
};

export default EditStyles;