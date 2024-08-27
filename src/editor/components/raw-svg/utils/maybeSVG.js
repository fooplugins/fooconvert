import { isString } from "@steveush/utils";

/**
 * Check if a value might be an SVG string.
 *
 * @param {any} value
 * @return {value is string}
 */
const maybeSVG = value => {
    if ( isString( value, true ) ) {
        const trimmed = value.trim();
        return trimmed.startsWith( '<svg' ) && trimmed.endsWith( '</svg>' );
    }
    return false;
};

export default maybeSVG;