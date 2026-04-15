import getLocalData from "./getLocalData";
import setLocalData from "./setLocalData";

/**
 * The key used to store the seen popup IDs in both localStorage and as a cookie.
 * @type {string}
 */
export const SEEN_STORAGE_KEY = 'FOOCONVERT_SEEN';

const getSeen = ( postId ) => {
    let seenList = getLocalData( SEEN_STORAGE_KEY );
    return Array.isArray( seenList ) && seenList.includes( postId );
};

export default getSeen;