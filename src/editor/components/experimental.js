// noinspection JSUnresolvedReference - turned off as the expected stable names do not yet exist

import {
    __experimentalParseQuantityAndUnitFromRawValue,
    parseQuantityAndUnitFromRawValue as stableParseQuantityAndUnitFromRawValue,
    __experimentalUnitControl,
    UnitControl as stableUnitControl,
    __experimentalBorderBoxControl,
    BorderBoxControl as stableBorderBoxControl,
    __experimentalToolsPanel,
    ToolsPanel as stableToolsPanel,
    __experimentalToolsPanelItem,
    ToolsPanelItem as stableToolsPanelItem,
    __experimentalToggleGroupControl,
    ToggleGroupControl as stableToggleGroupControl,
    __experimentalToggleGroupControlOption,
    ToggleGroupControlOption as stableToggleGroupControlOption,
    __experimentalNumberControl,
    NumberControl as stableNumberControl,
} from "@wordpress/components";

import {
    __experimentalColorGradientSettingsDropdown,
    ColorGradientSettingsDropdown as stableColorGradientSettingsDropdown,
    __experimentalUseMultipleOriginColorsAndGradients,
    useMultipleOriginColorsAndGradients as stableUseMultipleOriginColorsAndGradients
} from "@wordpress/block-editor";

/**
 * A wrapper around experimental imports that will warn when things change or throw an error if the experimental or expected stable name does not exist.
 * @template Experimental
 * @template Stable
 * @param {Experimental} experimental - The value from importing the __experimental* variable.
 * @param {string} experimentalName - The name of the experimental variable.
 * @param {Stable} stable - The value from importing the expected stable variable.
 * @param {string} stableName - The name of the stable variable.
 * @param {string} pkgName - The package the experimental and stable variables were imported from.
 * @returns {Experimental extends undefined ? (Stable extends undefined ? never : Stable) : Experimental}
 * @throws Error If both the experimental and stable variables are undefined.
 */
const import_x = ( experimental, experimentalName, stable, stableName, pkgName ) => {
    if ( typeof experimental !== 'undefined' ) {
        if ( typeof stable !== 'undefined' ) {
            console.warn( `Using '${ experimentalName }' from '${ pkgName }' when '${ stableName }' is defined.` );
            return stable;
        }
        return experimental;
    }
    if ( typeof stable !== 'undefined' ) {
        console.warn( `Removed '${ experimentalName }' from '${ pkgName }', use '${ stableName }' instead.` );
        return stable;
    }
    throw new Error( `Could not import '${ experimentalName }' or '${ stableName }' from '${ pkgName }'.` );
};

export const parseQuantityAndUnitFromRawValue = import_x(
    __experimentalParseQuantityAndUnitFromRawValue, '__experimentalParseQuantityAndUnitFromRawValue',
    stableParseQuantityAndUnitFromRawValue, 'parseQuantityAndUnitFromRawValue',
    '@wordpress/components'
);

export const UnitControl = import_x(
    __experimentalUnitControl, '__experimentalUnitControl',
    stableUnitControl, 'UnitControl',
    '@wordpress/components'
);

export const BorderBoxControl = import_x(
    __experimentalBorderBoxControl, '__experimentalBorderBoxControl',
    stableBorderBoxControl, 'BorderBoxControl',
    '@wordpress/components'
);

export const ToolsPanel = import_x(
    __experimentalToolsPanel, '__experimentalToolsPanel',
    stableToolsPanel, 'ToolsPanel',
    '@wordpress/components'
);

export const ToolsPanelItem = import_x(
    __experimentalToolsPanelItem, '__experimentalToolsPanelItem',
    stableToolsPanelItem, 'ToolsPanelItem',
    '@wordpress/components'
);

export const ToggleGroupControl = import_x(
    __experimentalToggleGroupControl, '__experimentalToggleGroupControl',
    stableToggleGroupControl, 'ToggleGroupControl',
    '@wordpress/components'
);

export const ToggleGroupControlOption = import_x(
    __experimentalToggleGroupControlOption, '__experimentalToggleGroupControlOption',
    stableToggleGroupControlOption, 'ToggleGroupControlOption',
    '@wordpress/components'
);

export const NumberControl = import_x(
    __experimentalNumberControl, '__experimentalNumberControl',
    stableNumberControl, 'NumberControl',
    '@wordpress/components'
);

export const ColorGradientSettingsDropdown = import_x(
    __experimentalColorGradientSettingsDropdown, '__experimentalColorGradientSettingsDropdown',
    stableColorGradientSettingsDropdown, 'ColorGradientSettingsDropdown',
    '@wordpress/block-editor'
);

export const useMultipleOriginColorsAndGradients = import_x(
    __experimentalUseMultipleOriginColorsAndGradients, '__experimentalUseMultipleOriginColorsAndGradients',
    stableUseMultipleOriginColorsAndGradients, 'useMultipleOriginColorsAndGradients',
    '@wordpress/block-editor'
);