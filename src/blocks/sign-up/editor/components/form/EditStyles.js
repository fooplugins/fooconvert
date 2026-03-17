import {
    BackgroundImagePanel,
    BorderToolsPanel,
    ColorToolsPanel,
    DimensionToolsPanel,
    TypographyToolsPanel
} from "#editor";
import { __ } from "@wordpress/i18n";

const EditStyles = ( props ) => {

    const {
        clientId,
        styles,
        setStyles,
        stylesDefaults,
    } = props;

    const setColor = value => setStyles( { color: value } );
    const setBorder = value => setStyles( { border: value } );
    const setTypography = value => setStyles( { typography: value } );
    const setDimensions = value => setStyles( { dimensions: value } );

    const colors = [ {
        key: 'background',
        label: __( 'Background', 'fooconvert' ),
        enableAlpha: true,
        enableGradient: true
    },{
        key: 'text',
        label: __( 'Text', 'fooconvert' )
    } ];

    return (
        <>
            <ColorToolsPanel
                key={ `form/color/${ clientId }` }
                panelId={ clientId }
                value={ styles?.color }
                onChange={ setColor }
                options={ colors }
                defaults={ stylesDefaults?.color }
            />
            <BackgroundImagePanel
                key={ `form/backgroundImage/${ clientId }` }
                panelId={ clientId }
                value={ styles }
                defaultValues={ stylesDefaults }
                onChange={ setStyles }
            />
            <TypographyToolsPanel
                key={ `form/typography/${ clientId }` }
                panelId={ clientId }
                value={ styles?.typography }
                onChange={ setTypography }
                defaults={ stylesDefaults?.typography }
            />
            <BorderToolsPanel
                key={ `form/border/${ clientId }` }
                panelId={ clientId }
                value={ styles?.border }
                onChange={ setBorder }
                defaults={ stylesDefaults?.border }
            />
            <DimensionToolsPanel
                key={ `form/dimensions/${ clientId }` }
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