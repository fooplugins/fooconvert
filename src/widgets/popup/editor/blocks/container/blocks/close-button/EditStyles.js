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
        defaultStyles,
    } = props;

    const setColor = newValue => setStyles( { color: newValue } );
    const setBorder = newValue => setStyles( { border: newValue } );
    const setDimensions = newValue => setStyles( { dimensions: newValue } );

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
                defaults={ defaultStyles?.color }
            />
            <BorderToolsPanel
                panelId={ clientId }
                value={ styles?.border }
                onChange={ setBorder }
                defaults={ defaultStyles?.border }
            />
            <DimensionToolsPanel
                panelId={ clientId }
                value={ styles?.dimensions }
                onChange={ setDimensions }
                defaults={ defaultStyles?.dimensions }
                controls={ [ 'margin', 'padding' ] }
            />
        </InspectorControls>
    );
};

export default EditStyles;