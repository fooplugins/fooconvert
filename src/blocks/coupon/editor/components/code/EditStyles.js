import { __ } from "@wordpress/i18n";
import {
    BorderToolsPanel,
    BoxUnitsControl,
    ColorToolsPanel,
    DimensionToolsPanel,
    isBoxUnit,
    TypographyToolsPanel
} from "#editor";
import { __experimentalToolsPanelItem as ToolsPanelItem } from "@wordpress/components";
import { isString } from "@steveush/utils";

const EditStyles = props => {

    const {
        clientId,
        codeStyles,
        setCodeStyles,
        codeStylesDefaults,
    } = props;

    const setColor = value => setCodeStyles( { color: value } );
    const setTypography = value => setCodeStyles( { typography: value } );
    const setBorder = value => setCodeStyles( { border: value } );
    const setDimensions = value => setCodeStyles( { dimensions: value } );

    const colors = [ {
        key: 'background',
        label: __( 'Background', 'fooconvert' ),
        enableAlpha: true,
        enableGradient: true
    }, {
        key: 'text',
        label: __( 'Text', 'fooconvert' )
    } ];

    const innerPadding = codeStyles?.innerPadding ?? codeStylesDefaults?.innerPadding;
    const setInnerPadding = value => setCodeStyles( { innerPadding: value !== codeStylesDefaults.innerPadding ? value : undefined } );
    const hasInnerPadding = isString( innerPadding, true ) || isBoxUnit( innerPadding, true );

    return (
        <>
            <ColorToolsPanel
                key={ `code/color/${ clientId }` }
                panelId={ clientId }
                value={ codeStyles?.color }
                onChange={ setColor }
                options={ colors }
                defaults={ codeStylesDefaults?.color }
            />
            <TypographyToolsPanel
                key={ `code/typography/${ clientId }` }
                panelId={ clientId }
                value={ codeStyles?.typography }
                onChange={ setTypography }
                defaults={ codeStylesDefaults?.typography }
            />
            <BorderToolsPanel
                key={ `code/border/${ clientId }` }
                panelId={ clientId }
                value={ codeStyles?.border }
                onChange={ setBorder }
                defaults={ codeStylesDefaults?.border }
                shownByDefault={ [] }
            />
            <DimensionToolsPanel
                key={ `code/dimensions/${ clientId }` }
                label={ __( 'Dimensions', 'fooconvert' ) }
                panelId={ clientId }
                value={ codeStyles?.dimensions }
                onChange={ setDimensions }
                controls={ [ 'padding', 'margin', 'gap' ] }
                defaults={ codeStylesDefaults?.dimensions }
                itemRenderer={ () => (
                    <ToolsPanelItem
                        panelId={ clientId }
                        hasValue={ () => hasInnerPadding }
                        label={ __( "Inner Padding", "fooconvert" ) }
                        onDeselect={ () => setInnerPadding( undefined ) }
                    >
                        <BoxUnitsControl
                            label={ __( "Inner Padding", "fooconvert" ) }
                            value={ innerPadding }
                            onChange={ setInnerPadding }
                        />
                    </ToolsPanelItem>
                ) }
            />
        </>
    );
};

export default EditStyles;