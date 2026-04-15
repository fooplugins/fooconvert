import setCookie from "./setCookie";
import { writeStorageItem } from "./storage";

/**
 * Persist a JSON-serializable value to sessionStorage and the matching cookie fallback.
 *
 * @param {string} key
 * @param {unknown} data
 * @returns {void}
 */
const setSessionData = ( key, data ) => {
    const serialized = JSON.stringify( data );

    writeStorageItem( "sessionStorage", key, serialized );
    setCookie( key, serialized );
};

export default setSessionData;
