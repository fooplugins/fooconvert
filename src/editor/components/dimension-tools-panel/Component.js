import {
    __experimentalToolsPanel as ToolsPanel,
    __experimentalToolsPanelItem as ToolsPanelItem
} from "@wordpress/components";
import { __ } from "@wordpress/i18n";
import { BoxUnitsControl, isBoxUnit } from "../box-units-control";
import { cleanObject, isFunction, isString } from "@steveush/utils";
import { SizeControl } from "../size-control";

const DimensionToolsPanel = ( {
                                  value,
                                  onChange,
                                  panelId,
                                  title,
                                  controls = [ "padding", "margin", "gap" ],
                                  defaults = {},
                                  itemRenderer,
                                  ...props
                              } ) => {

    const hasItemRenderer = isFunction( itemRenderer );
    const { padding, margin, gap } = value ?? {};
    const hasPadding = isString( padding, true ) || isBoxUnit( padding, true );
    const hasMargin = isString( margin, true ) || isBoxUnit( margin, true );
    const hasGap = isString( gap, true );

    const setPadding = nextValue => onChange( cleanObject( {
        ...( value ?? {} ),
        padding: nextValue
    } ) );
    const setMargin = nextValue => onChange( cleanObject( {
        ...( value ?? {} ),
        margin: nextValue
    } ) );
    const setGap = nextValue => onChange( cleanObject( {
        ...( value ?? {} ),
        gap: nextValue
    } ) );
    const resetAll = () => onChange( undefined );

    if ( controls.length === 0 ) {
        return null;
    }

    return (
        <ToolsPanel
            label={ title ?? __( "Dimensions", "fooconvert" ) }
            resetAll={ resetAll }
            panelId={ panelId }
            { ...props }
        >
            { controls.includes( "padding" ) && (
                <ToolsPanelItem
                    panelId={ panelId }
                    hasValue={ () => hasPadding }
                    label={ __( "Padding", "fooconvert" ) }
                    onDeselect={ () => setPadding( undefined ) }
                >
                    <BoxUnitsControl
                        label={ __( "Padding", "fooconvert" ) }
                        value={ padding ?? defaults?.padding }
                        onChange={ setPadding }
                    />
                </ToolsPanelItem>
            ) }
            { controls.includes( "margin" ) && (
                <ToolsPanelItem
                    panelId={ panelId }
                    hasValue={ () => hasMargin }
                    label={ __( "Margin", "fooconvert" ) }
                    onDeselect={ () => setMargin( undefined ) }
                >
                    <BoxUnitsControl
                        label={ __( "Margin", "fooconvert" ) }
                        value={ margin ?? defaults?.margin }
                        onChange={ setMargin }
                    />
                </ToolsPanelItem>
            ) }
            { controls.includes( "gap" ) && (
                <ToolsPanelItem
                    panelId={ panelId }
                    hasValue={ () => hasGap }
                    label={ __( "Gap", "fooconvert" ) }
                    onDeselect={ () => setGap( undefined ) }
                >
                    <SizeControl
                        label={ __( "Gap", "fooconvert" ) }
                        value={ gap ?? defaults?.gap }
                        onChange={ setGap }
                        sizes={ [
                            {
                                abbr: __( 'S', 'fooconvert' ),
                                label: __( 'Small', 'fooconvert' ),
                                slug: 'small',
                                value: '8px',
                            },
                            {
                                abbr: __( 'M', 'fooconvert' ),
                                label: __( 'Medium', 'fooconvert' ),
                                slug: 'medium',
                                value: '12px',
                            },
                            {
                                abbr: __( 'L', 'fooconvert' ),
                                label: __( 'Large', 'fooconvert' ),
                                slug: 'large',
                                value: '16px',
                            },
                            {
                                abbr: __( 'XL', 'fooconvert' ),
                                label: __( 'Extra Large', 'fooconvert' ),
                                slug: 'x-large',
                                value: '24px',
                            },
                        ] }
                    />
                </ToolsPanelItem>
            ) }
            { hasItemRenderer && itemRenderer( panelId ) }
        </ToolsPanel>
    )
};

export default DimensionToolsPanel;