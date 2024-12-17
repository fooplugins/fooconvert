import { __ } from "@wordpress/i18n";
import { BorderBoxControl, ToolsPanel, ToolsPanelItem, useMultipleOriginColorsAndGradients } from "../experimental";

import { isPossibleBorderValue, isPossibleBorderBox } from "./utils";
import { BorderRadiusControl, isBorderRadius } from "../border-radius-control";
import { isBoxShadow, BoxShadowControl } from "../box-shadow-control";

/**
 * @typedef {Omit<import('@wordpress/components/build-types/tools-panel/types').ToolsPanelProps, "children", "resetAll">} FCBorderToolsPanelProps
 * @property {string}
 */


const BorderToolsPanel = ( {
                               value,
                               onChange,
                               panelId,
                               title,
                               defaults = {},
    showShadow = true,
                               ...props
} ) => {
    const {} = props;

    const colorGradientSettings = useMultipleOriginColorsAndGradients();

    const { radius, shadow, ...border } = value ?? {};
    const { radius: defaultRadius, shadow: defaultShadow, ...defaultBorder } = defaults ?? {};
    const hasBorder = isPossibleBorderValue( border ) || isPossibleBorderBox( border );
    const hasBorderRadius = isBorderRadius( radius );
    const hasBoxShadow = isBoxShadow( shadow );

    const setBorder = border => {
        border = border ?? {};
        const prev = hasBorderRadius ? { radius, ...border } : { ...border };
        if ( hasBoxShadow ) prev.shadow = shadow;
        const newValue = { ...prev, ...border };
        const keys = Object.keys( newValue );
        onChange( keys.length === 0 ? undefined : newValue );
    };

    const setBorderRadius = radius => {
        const prev = { ...( border ?? {} ) };
        if ( hasBoxShadow ) prev.shadow = shadow;
        const newValue = isBorderRadius( radius ) ? { radius, ...prev } : prev;
        const keys = Object.keys( newValue );
        onChange( keys.length === 0 ? undefined : newValue );
    };

    const setBoxShadow = shadow => {
        const prev = { ...( border ?? {} ) };
        if ( hasBorderRadius ) prev.radius = radius;
        const newValue = isBoxShadow( shadow ) ? { shadow, ...prev } : prev;
        const keys = Object.keys( newValue );
        onChange( keys.length === 0 ? undefined : newValue );
        console.log( 'shadow', keys.length === 0 ? undefined : newValue );
    };

    const resetAll = () => {
        onChange( undefined );
    };

    return (
        <ToolsPanel
            label={ title ?? showShadow ? __( "Border & Shadow", "fooconvert" ) : __( "Border", "fooconvert" ) }
            resetAll={ resetAll }
            panelId={ panelId }
            { ...props }
        >
            <ToolsPanelItem
                panelId={ panelId }
                hasValue={ () => hasBorder }
                label={ __( "Border", "fooconvert" ) }
                onDeselect={ () => setBorder( undefined ) }
                isShownByDefault
            >
                <BorderBoxControl
                    label={ __( "Border", "fooconvert" ) }
                    hideLabelFromVision={ !showShadow }
                    value={ Object.keys( border ).length > 0 ? border : defaultBorder }
                    onChange={ setBorder }
                    size={ "__unstable-large" }
                    popoverOffset={ 40 }
                    popoverPlacement="left-start"
                    __experimentalIsRenderedInSidebar
                    { ...colorGradientSettings }
                />
            </ToolsPanelItem>
            <ToolsPanelItem
                panelId={ panelId }
                hasValue={ () => hasBorderRadius }
                label={ __( "Radius", "fooconvert" ) }
                onDeselect={ () => setBorderRadius( undefined ) }
                isShownByDefault
            >
                <BorderRadiusControl
                    label={ __( "Radius", "fooconvert" ) }
                    value={ radius ?? defaultRadius }
                    onChange={ setBorderRadius }
                />
            </ToolsPanelItem>
            { showShadow && (
                <ToolsPanelItem
                    panelId={ panelId }
                    hasValue={ () => hasBoxShadow }
                    label={ __( "Shadow", "fooconvert" ) }
                    onDeselect={ () => setBoxShadow( undefined ) }
                    isShownByDefault
                >
                    <BoxShadowControl
                        label={ __( 'Shadow', 'fooconvert' ) }
                        value={ shadow ?? defaultShadow }
                        onChange={ setBoxShadow }
                    />
                </ToolsPanelItem>
            ) }
        </ToolsPanel>
    )
};

export default BorderToolsPanel;