import { __ } from "@wordpress/i18n";
import { BorderToolsPanel, ColorToolsPanel, DimensionToolsPanel, TypographyToolsPanel } from "#editor";

const EditStyles = props => {

    const {
        clientId,
        styles,
        setStyles,
        stylesDefaults,
    } = props;

    const setColor = value => setStyles( { color: value } );
    const setBorder = value => setStyles( { border: value } );
    const setDimensions = value => setStyles( { dimensions: value } );

    const colors = [ {
        key: 'background',
        label: __( 'Background', 'fooconvert' ),
        enableAlpha: true,
        enableGradient: true
    } ];

    return (
        <>
            <ColorToolsPanel
                key={ `container/color/${ clientId }` }
                panelId={ clientId }
                value={ styles?.color }
                onChange={ setColor }
                options={ colors }
                defaults={ stylesDefaults?.color }
            />
            <BorderToolsPanel
                key={ `container/border/${ clientId }` }
                panelId={ clientId }
                value={ styles?.border }
                onChange={ setBorder }
                defaults={ stylesDefaults?.border }
                shownByDefault={ [] }
            />
            <DimensionToolsPanel
                key={ `container/dimensions/${ clientId }` }
                label={ __( 'Dimensions', 'fooconvert' ) }
                panelId={ clientId }
                value={ styles?.dimensions }
                onChange={ setDimensions }
                controls={ [ 'padding', 'margin', 'gap' ] }
                defaults={ stylesDefaults?.dimensions }
            />
        </>
    );
};

export default EditStyles;