import getCookie from "./getCookie";
import { parseStorageJSON, readStorageItem } from "./storage";

/**
 * Read a JSON-serialized value from sessionStorage or the matching cookie fallback.
 *
 * @param {string} key
 * @returns {unknown}
 */
const getSessionData = ( key ) => {
    let serialized = readStorageItem( "sessionStorage", key );

    if ( !serialized ) {
        serialized = getCookie( key );
    }

    return parseStorageJSON( serialized );
};

export default getSessionData;
