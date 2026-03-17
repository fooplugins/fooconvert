import { __ } from "@wordpress/i18n";
import { BorderToolsPanel, ColorToolsPanel, DimensionToolsPanel, TypographyToolsPanel } from "#editor";

const EditStyles = props => {

    const {
        clientId,
        segmentSettings,
        segmentSettingsDefaults,
        segmentStyles,
        setSegmentStyles,
        segmentStylesDefaults,
        digitsStyles,
        setDigitsStyles,
        digitsStylesDefaults,
    } = props;

    const setColor = value => setSegmentStyles( { color: value } );
    const setBorder = value => setSegmentStyles( { border: value } );
    const setTypography = value => setSegmentStyles( { typography: value } );
    const setDimensions = value => setSegmentStyles( { dimensions: value } );
    const setDigitsTypography = value => setDigitsStyles( { typography: value } );

    const colors = [ {
        key: 'background',
        label: __( 'Background', 'fooconvert' ),
        enableAlpha: true,
        enableGradient: true
    },{
        key: 'text',
        label: __( 'Text', 'fooconvert' )
    },{
        key: 'digits',
        label: __( 'Digits', 'fooconvert' )
    } ];

    return (
        <>
            <ColorToolsPanel
                key={ `segment/color/${ clientId }` }
                panelId={ clientId }
                value={ segmentStyles?.color }
                onChange={ setColor }
                options={ colors }
                defaults={ segmentStylesDefaults?.color }
            />
            <TypographyToolsPanel
                key={ `segment/typography/${ clientId }` }
                panelId={ clientId }
                value={ segmentStyles?.typography }
                onChange={ setTypography }
                defaults={ segmentStylesDefaults?.typography }
            />
            <TypographyToolsPanel
                key={ `digits/typography/${ clientId }` }
                panelId={ clientId }
                value={ digitsStyles?.typography }
                onChange={ setDigitsTypography }
                defaults={ digitsStylesDefaults?.typography }
                label={ __( 'Typography (Digits)', 'fooconvert' ) }
            />
            <BorderToolsPanel
                key={ `segment/border/${ clientId }` }
                panelId={ clientId }
                value={ segmentStyles?.border }
                onChange={ setBorder }
                defaults={ segmentStylesDefaults?.border }
            />
            <DimensionToolsPanel
                key={ `segment/dimensions/${ clientId }` }
                label={ __( 'Dimensions', 'fooconvert' ) }
                panelId={ clientId }
                value={ segmentStyles?.dimensions }
                onChange={ setDimensions }
                controls={ [ 'padding', 'margin', 'gap' ] }
                defaults={ segmentStylesDefaults?.dimensions }
            />
        </>
    );
};

export default EditStyles;