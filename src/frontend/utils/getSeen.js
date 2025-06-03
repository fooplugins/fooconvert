import getLocalData from "./getLocalData";
import setLocalData from "./setLocalData";

/**
 * The key used to store the seen widget IDs in both localStorage and as a cookie.
 * @type {string}
 */
const STORAGE_KEY = 'fc_seen-list';

const getSeen = ( widgetId ) => {
    let seenList = getLocalData( STORAGE_KEY );
    return Array.isArray( seenList ) && seenList.includes( widgetId );
};

export default getSeen;