import {
    BorderToolsPanel,
    ColorToolsPanel,
    DimensionToolsPanel,
    TypographyToolsPanel
} from "#editor";
import { __ } from "@wordpress/i18n";

const EditStyles = ( props ) => {

    const {
        clientId,
        buttonSettings,
        buttonSettingsDefaults,
        buttonStyles,
        setButtonStyles,
        buttonStylesDefaults,
    } = props;

    const layout = buttonSettings?.layout ?? buttonSettingsDefaults?.layout ?? 'text';
    const showIcon = [ 'icon-only', 'icon-text', 'text-icon' ].includes( layout );

    const setColor = value => setButtonStyles( { color: value } );
    const setBorder = value => setButtonStyles( { border: value } );
    const setTypography = value => setButtonStyles( { typography: value } );
    const setDimensions = value => setButtonStyles( { dimensions: value } );

    const colors = [ {
        key: 'background',
        label: __( 'Background', 'fooconvert' ),
        enableAlpha: true,
        enableGradient: true
    },{
        key: 'text',
        label: __( 'Text', 'fooconvert' )
    } ];

    if ( showIcon ) {
        colors.push( {
            key: 'icon',
            label: __( 'Icon', 'fooconvert' )
        } );
    }

    return (
        <>
            <ColorToolsPanel
                key={ `button/color/${ clientId }` }
                panelId={ clientId }
                value={ buttonStyles?.color }
                onChange={ setColor }
                options={ colors }
                defaults={ buttonStylesDefaults?.color }
            />
            <TypographyToolsPanel
                key={ `button/typography/${ clientId }` }
                panelId={ clientId }
                value={ buttonStyles?.typography }
                onChange={ setTypography }
                defaults={ buttonStylesDefaults?.typography }
            />
            <BorderToolsPanel
                key={ `button/border/${ clientId }` }
                panelId={ clientId }
                value={ buttonStyles?.border }
                onChange={ setBorder }
                defaults={ buttonStylesDefaults?.border }
            />
            <DimensionToolsPanel
                key={ `button/dimensions/${ clientId }` }
                label={ __( 'Dimensions', 'fooconvert' ) }
                panelId={ clientId }
                value={ buttonStyles?.dimensions }
                onChange={ setDimensions }
                controls={ [ 'padding', 'gap' ] }
                defaults={ buttonStylesDefaults?.dimensions }
            />
        </>
    );
};

export default EditStyles;