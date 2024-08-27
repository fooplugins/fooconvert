import { addFilter } from "@wordpress/hooks";
import { isArray, isString } from "@steveush/utils";
import { parseSVG, renderSVGPrimitive } from "../components";

const check = ( value ) => {
    const svg = parseSVG( value );
    if ( svg instanceof Element ) {
        return renderSVGPrimitive( svg );
    }
    return value;
};

/**
 * Convert icons supplied as raw SVG strings into JSX.Elements for blocks in the 'fc/' namespace.
 *
 * @param {WPBlockSettings} settings
 * @param {string} name
 * @return {WPBlockSettings}
 */
const rawSVGIcons = ( settings, name ) => {
    if ( !name.startsWith( 'fc/' ) ) {
        return settings;
    }
    if ( isString( settings?.icon, true ) ) {
        settings.icon = check( settings.icon );
    }
    if ( isArray( settings?.variations, true ) ) {
        settings.variations.forEach( variation => {
            if ( isString( variation?.icon, true ) ) {
                variation.icon = check( variation.icon );
            }
        } );
    }
    return settings;
};

addFilter( 'blocks.registerBlockType', 'fooconvert/editor/raw-svg-icon', rawSVGIcons );