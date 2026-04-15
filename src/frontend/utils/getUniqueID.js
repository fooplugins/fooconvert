import getCookie from "./getCookie";
import generateGUID from "./generateGUID";
import setCookie from "./setCookie";
import { readStorageItem, writeStorageItem } from "./storage";

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
    let uniqueID = readStorageItem( "localStorage", UNIQUE_ID_STORAGE_KEY );

    if ( !uniqueID ) {
        uniqueID = getCookie( UNIQUE_ID_STORAGE_KEY );
    }

    if ( !uniqueID ) {
        uniqueID = generateGUID();

        writeStorageItem( "localStorage", UNIQUE_ID_STORAGE_KEY, uniqueID );

        setCookie( UNIQUE_ID_STORAGE_KEY, uniqueID, STORAGE_DURATION );
    }

    return uniqueID;
};

export default getUniqueID;
