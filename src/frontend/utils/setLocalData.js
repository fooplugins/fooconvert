import setCookie from "./setCookie";
import { writeStorageItem } from "./storage";

/**
 * Persist a JSON-serializable value to localStorage and the matching cookie fallback.
 *
 * @param {string} key
 * @param {unknown} data
 * @param {number} [days]
 * @returns {void}
 */
const setLocalData = ( key, data, days = 365 ) => {
    const serialized = JSON.stringify( data );

    writeStorageItem( "localStorage", key, serialized );
    setCookie( key, serialized, days );
};

export default setLocalData;
