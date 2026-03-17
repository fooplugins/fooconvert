import { __ } from "@wordpress/i18n";
import {
    BackgroundImagePanel,
    BorderToolsPanel,
    ColorToolsPanel,
    DimensionToolsPanel,
    TypographyToolsPanel
} from "#editor";

const EditStyles = props => {

    const {
        clientId,
        styles,
        setStyles,
        stylesDefaults,
    } = props;

    const setColor = value => setStyles( { color: value } );
    const setTypography = value => setStyles( { typography: value } );
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
        <>
            <ColorToolsPanel
                key={ `container/color/${ clientId }` }
                panelId={ clientId }
                value={ styles?.color }
                onChange={ setColor }
                options={ colors }
                defaults={ stylesDefaults?.color }
            />
            <BackgroundImagePanel
                key={ `container/backgroundImage/${ clientId }` }
                panelId={ clientId }
                value={ styles }
                defaultValues={ stylesDefaults }
                onChange={ setStyles }
            />
            <TypographyToolsPanel
                key={ `container/typography/${ clientId }` }
                panelId={ clientId }
                value={ styles?.typography }
                onChange={ setTypography }
                defaults={ stylesDefaults?.typography }
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