import { InspectorControls } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import { BorderToolsPanel, ColorToolsPanel, DimensionToolsPanel, TypographyToolsPanel } from "#editor";
import { cleanObject } from "@steveush/utils";

const EXAMPLE_DEFAULTS = {
    styles: {
        dimensions: {
            padding: { top: '24px', right: '32px', bottom: '24px', left: '32px' }
        },
        typography: {
            fontSize: '24px'
        }
    }
};

const Settings = ( {
                       attributes: {
                           styles
                       },
                       clientId,
                       setAttributes,
                       defaults = EXAMPLE_DEFAULTS
                   } ) => {

    const setStyles = newValue => {
        const previousValue = styles ?? {};
        const nextValue = typeof newValue === 'object' ? {
            ...previousValue,
            ...newValue
        } : undefined;
        setAttributes( { styles: cleanObject( nextValue ) } );
    };

    const setColor = nextValue => setStyles( { color: nextValue } );
    const setBorder = nextValue => setStyles( { border: nextValue } );
    const setDimensions = nextValue => setStyles( { dimensions: nextValue } );
    const setTypography = nextValue => setStyles( { typography: nextValue } );

    const colors = [
        {
            key: 'background',
            label: __( 'Background', "fooconvert" ),
            enableAlpha: true,
            enableGradient: true
        },
        {
            key: 'text',
            label: __( 'Text', "fooconvert" ),
        }
    ];

    return (
        <>
            <InspectorControls group="styles">
                <ColorToolsPanel
                    panelId={ clientId }
                    value={ styles?.color }
                    onChange={ setColor }
                    options={ colors }
                />
                <TypographyToolsPanel
                    panelId={ clientId }
                    value={ styles?.typography }
                    onChange={ setTypography }
                    defaults={ defaults?.typography }
                />
                <DimensionToolsPanel
                    panelId={ clientId }
                    value={ styles?.dimensions }
                    onChange={ setDimensions }
                    defaults={ defaults?.dimensions }
                />
                <BorderToolsPanel
                    panelId={ clientId }
                    value={ styles?.border }
                    onChange={ setBorder }
                />
            </InspectorControls>
        </>
    );
};

export default Settings;