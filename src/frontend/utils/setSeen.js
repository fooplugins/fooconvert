import getLocalData from "./getLocalData";
import setLocalData from "./setLocalData";
import { SEEN_STORAGE_KEY } from "./getSeen";

const setSeen = ( postId ) => {
    let seenList = getLocalData( SEEN_STORAGE_KEY );
    if ( !Array.isArray( seenList ) ) {
        seenList = [];
    }
    if ( !seenList.includes( postId ) ) {
        seenList.push( postId );
        setLocalData( SEEN_STORAGE_KEY, seenList );
    }
};

export default setSeen;