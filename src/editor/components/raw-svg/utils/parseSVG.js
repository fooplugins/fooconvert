import maybeSVG from "./maybeSVG";

const parser = new globalThis.DOMParser();

/**
 * Parse a SVG from a string.
 * 
 * @param {string} value
 * @return {Element|null}
 */
const parseSVG = value => {
    if ( maybeSVG( value ) ) {
        const parsed = parser.parseFromString( value, 'image/svg+xml' );
        return parsed.firstElementChild?.tagName?.toLowerCase() === 'svg' && parsed.firstElementChild?.childElementCount > 0 ? parsed.firstElementChild : null;
    }
    return null;
};

export default parseSVG;