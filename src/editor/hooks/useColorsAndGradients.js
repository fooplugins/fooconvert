import { useMemo } from "@wordpress/element";
import resolveCSSVars from "../utils/resolveCSSVars";
import { useMultipleOriginColorsAndGradients } from "../components/experimental";

/**
 * A single color within a palette.
 *
 * @typedef {{slug: string, name: string, color: string}} ColorPaletteItem
 */

/**
 * A named collection of colors.
 *
 * @typedef {{name: string, colors: ColorPaletteItem[]}} ColorPalette
 */

/**
 * A single gradient within a palette.
 *
 * @typedef {{slug: string, name: string, gradient: string}} GradientPaletteItem
 */

/**
 * A named collection of gradients.
 *
 * @typedef {{name: string, gradients: GradientPaletteItem[]}} GradientPalette
 */

/**
 * Color and gradient related settings.
 *
 * @typedef {{disableCustomColors: boolean, disableCustomGradients: boolean, colors: ColorPalette[], gradients: GradientPalette[], hasColorsOrGradients: boolean}} ColorsAndGradientsSettings
 */


/**
 * This hook is a wrapper around the `useMultipleOriginColorsAndGradients()` hook however it differs
 * in one important way, it provides the option to resolve CSS variables within the various palettes with their computed value.
 *
 * @param {boolean} [replaceVars] - Optional. If `true` CSS variables within the returned palettes will be resolved to their current computed values. Defaults to `false`.
 * @returns {ColorsAndGradientsSettings} An object containing the color and gradient settings.
 */
const useColorsAndGradients = ( replaceVars = false ) => {
    const result = /** @type {ColorsAndGradientsSettings} */ useMultipleOriginColorsAndGradients();
    return useMemo( () => {
        if ( replaceVars ) {
            const computedStyles = globalThis.getComputedStyle( globalThis.document.documentElement );
            result.colors = result.colors.map( palette => {
                const { colors, ...restPalette } = palette;
                return {
                    ...restPalette,
                    colors: colors.map( item => ( {
                        ...item,
                        color: resolveCSSVars( item.color, computedStyles )
                    } ) )
                };
            } );
            result.gradients = result.gradients.map( palette => {
                const { gradients, ...restPalette } = palette;
                return {
                    ...restPalette,
                    gradients: gradients.map( item => ( {
                        ...item,
                        gradient: resolveCSSVars( item.gradient, computedStyles )
                    } ) )
                };
            } );
        }
        return result;
    }, [ result, replaceVars ] );
};

export default useColorsAndGradients;