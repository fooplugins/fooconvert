import getCookie from "./getCookie";
import { parseStorageJSON, readStorageItem } from "./storage";

/**
 * Read a JSON-serialized value from localStorage or the matching cookie fallback.
 *
 * @param {string} key
 * @returns {unknown}
 */
const getLocalData = ( key ) => {
    let serialized = readStorageItem( "localStorage", key );

    if ( !serialized ) {
        serialized = getCookie( key );
    }

    return parseStorageJSON( serialized );
};

export default getLocalData;
