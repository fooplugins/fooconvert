import { isString } from "@steveush/utils";

/**
 * Check whether a string is a valid CSS selector.
 *
 * @param {unknown} value
 * @returns {boolean}
 */
const isSelector = value => {
    if ( isString( value, true ) ) {
        try {
            globalThis.document.querySelector( value );
            return true;
        } catch {
            return false;
        }
    }
    return false;
};

export default isSelector;
