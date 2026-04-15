import removeLocalData from "./removeLocalData";
import removeSessionData from "./removeSessionData";
import { COUNTDOWN_STORAGE_KEY } from "./getCountdown";
import { parseStorageJSON, readStorageItem, writeStorageItem } from "./storage";

const clearCountdown = ( countdownId, persist ) => {
    const storageName = persist ? "localStorage" : "sessionStorage";
    const serialized = readStorageItem( storageName, COUNTDOWN_STORAGE_KEY );
    const data = parseStorageJSON( serialized );

    if ( data && Object.hasOwn( data, countdownId ) ) {
        delete data[ countdownId ];

        if ( Object.keys( data ).length > 0 ) {
            writeStorageItem( storageName, COUNTDOWN_STORAGE_KEY, JSON.stringify( data ) );
            return;
        }
    }

    if ( persist ) {
        removeLocalData( COUNTDOWN_STORAGE_KEY );
    } else {
        removeSessionData( COUNTDOWN_STORAGE_KEY );
    }
};

export default clearCountdown;
