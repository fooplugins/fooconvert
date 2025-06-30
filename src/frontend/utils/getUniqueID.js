import getCookie from "./getCookie";
import generateGUID from "./generateGUID";
import setCookie from "./setCookie";

/**
 * The key used to store the unique ID in both localStorage and as a cookie.
 * @type {string}
 */
export const UNIQUE_ID_STORAGE_KEY = 'FOOCONVERT_UNIQUE_ID';

/**
 * The duration in days before the unique ID cookie expires. This only applies to the cookie, not localStorage.
 * @type {number}
 */
const STORAGE_DURATION = 365;

/**
 * Get a unique ID for the current browser/user.
 *
 * @returns {string|undefined}
 */
const getUniqueID = () => {

    let uniqueID;

    try {
        uniqueID = globalThis?.localStorage?.getItem( UNIQUE_ID_STORAGE_KEY );
    } catch ( e ) {
        // eat possible security exception
        // see: https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage#exceptions
    }

    if ( !uniqueID ) {
        uniqueID = getCookie( UNIQUE_ID_STORAGE_KEY );
    }

    if ( !uniqueID ) {
        uniqueID = generateGUID();

        try {
            globalThis?.localStorage?.setItem( UNIQUE_ID_STORAGE_KEY, uniqueID );
        } catch ( e ) {
            // eat possible security exception
            // see: https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage#exceptions
        }

        setCookie( UNIQUE_ID_STORAGE_KEY, uniqueID, STORAGE_DURATION ); // Setting the cookie to expire in 365 days
    }

    return uniqueID;
};

export default getUniqueID;