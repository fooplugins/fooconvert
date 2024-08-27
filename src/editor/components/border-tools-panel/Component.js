import { __ } from "@wordpress/i18n";
import { BorderBoxControl, ToolsPanel, ToolsPanelItem, useMultipleOriginColorsAndGradients } from "../experimental";

import { isPossibleBorderValue, isPossibleBorderBox } from "./utils";
import { BorderRadiusControl, isBorderRadius } from "../border-radius-control";

/**
 * @typedef {Omit<import('@wordpress/components/build-types/tools-panel/types').ToolsPanelProps, "children", "resetAll">} FCBorderToolsPanelProps
 * @property {string}
 */


const BorderToolsPanel = ( { value, onChange, panelId, title, ...props } ) => {
    const {} = props;

    const colorGradientSettings = useMultipleOriginColorsAndGradients();

    const { radius, ...border } = value ?? {};
    const hasBorder = isPossibleBorderValue( border ) || isPossibleBorderBox( border );
    const hasBorderRadius = isBorderRadius( radius );

    const setBorder = border => {
        border = border ?? {};
        const prev = hasBorderRadius ? { radius, ...border } : { ...border };
        const newValue = { ...prev, ...border };
        const keys = Object.keys( newValue );
        onChange( keys.length === 0 ? undefined : newValue );
    };

    const setBorderRadius = radius => {
        const prev = { ...(border ?? {}) };
        const newValue = isBorderRadius( radius ) ? { radius, ...prev } : prev;
        const keys = Object.keys( newValue );
        onChange( keys.length === 0 ? undefined : newValue );
    };

    const resetAll = () => {
        onChange( undefined );
    };

    return (
        <ToolsPanel
            label={ title ?? __( "Border", "fooconvert" ) }
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
                    hideLabelFromVision={ true }
                    value={ border }
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
                    value={ radius }
                    onChange={ setBorderRadius }
                />
            </ToolsPanelItem>
        </ToolsPanel>
    )
};

export default BorderToolsPanel;