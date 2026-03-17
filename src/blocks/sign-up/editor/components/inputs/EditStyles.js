import { BorderToolsPanel, ColorToolsPanel, DimensionToolsPanel, TypographyToolsPanel } from "#editor";
import { __ } from "@wordpress/i18n";

const EditStyles = ( props ) => {

    const {
        clientId,
        inputsStyles,
        setInputsStyles,
        inputsStylesDefaults,
    } = props;

    const setInputsColor = value => setInputsStyles( { color: value } );
    const setInputsBorder = value => setInputsStyles( { border: value } );
    const setInputsTypography = value => setInputsStyles( { typography: value } );
    const setInputsDimensions = value => setInputsStyles( { dimensions: value } );

    const inputsColors = [ {
        key: 'background',
        label: __( 'Background', 'fooconvert' ),
        enableAlpha: true,
        enableGradient: true
    },{
        key: 'text',
        label: __( 'Text', 'fooconvert' )
    },{
        key: 'placeholder',
        label: __( 'Placeholder', 'fooconvert' )
    } ];

    return (
        <>
            <ColorToolsPanel
                key={ `inputs/color/${ clientId }` }
                panelId={ clientId }
                value={ inputsStyles?.color }
                onChange={ setInputsColor }
                options={ inputsColors }
                defaults={ inputsStylesDefaults?.color }
            />
            <TypographyToolsPanel
                key={ `inputs/typography/${ clientId }` }
                panelId={ clientId }
                value={ inputsStyles?.typography }
                onChange={ setInputsTypography }
                defaults={ inputsStylesDefaults?.typography }
            />
            <BorderToolsPanel
                key={ `inputs/border/${ clientId }` }
                panelId={ clientId }
                value={ inputsStyles?.border }
                onChange={ setInputsBorder }
                defaults={ inputsStylesDefaults?.border }
            />
            <DimensionToolsPanel
                key={ `inputs/dimensions/${ clientId }` }
                label={ __( 'Dimensions', 'fooconvert' ) }
                panelId={ clientId }
                value={ inputsStyles?.dimensions }
                onChange={ setInputsDimensions }
                controls={ [ 'padding', 'margin' ] }
                defaults={ inputsStylesDefaults?.dimensions }
            />
        </>
    );
};

export default EditStyles;