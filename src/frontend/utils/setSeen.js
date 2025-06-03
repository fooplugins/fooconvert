import getLocalData from "./getLocalData";
import setLocalData from "./setLocalData";


/**
 * The key used to store the seen widget IDs in both localStorage and as a cookie.
 * @type {string}
 */
const STORAGE_KEY = 'fc_seen-list';

const setSeen = ( widgetId ) => {
    let seenList = getLocalData( STORAGE_KEY );
    if ( !Array.isArray( seenList ) ) {
        seenList = [];
    }
    if ( !seenList.includes( widgetId ) ) {
        seenList.push( widgetId );
        setLocalData( STORAGE_KEY, seenList );
    }
};

export default setSeen;