import getLocalData from "./getLocalData";
import setLocalData from "./setLocalData";

/**
 * The key used to store the seen widget IDs in both localStorage and as a cookie.
 * @type {string}
 */
export const SEEN_STORAGE_KEY = 'FOOCONVERT_SEEN';

const getSeen = ( widgetId ) => {
    let seenList = getLocalData( SEEN_STORAGE_KEY );
    return Array.isArray( seenList ) && seenList.includes( widgetId );
};

export default getSeen;