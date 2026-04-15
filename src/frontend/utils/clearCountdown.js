import removeLocalData from "./removeLocalData";
import removeSessionData from "./removeSessionData";
import { COUNTDOWN_STORAGE_KEY } from "./getCountdown";

const clearCountdown = ( countdownId, persist ) => {
    const countdownData = persist ? globalThis?.localStorage : globalThis?.sessionStorage;
    if ( countdownData ) {
        try {
            const serialized = persist ? globalThis?.localStorage?.getItem( COUNTDOWN_STORAGE_KEY ) : globalThis?.sessionStorage?.getItem( COUNTDOWN_STORAGE_KEY );
            const data = serialized ? JSON.parse( serialized ) : null;
            if ( data && Object.hasOwn( data, countdownId ) ) {
                delete data[ countdownId ];
                if ( Object.keys( data ).length > 0 ) {
                    const serializedData = JSON.stringify( data );
                    if ( persist ) {
                        globalThis?.localStorage?.setItem( COUNTDOWN_STORAGE_KEY, serializedData );
                    } else {
                        globalThis?.sessionStorage?.setItem( COUNTDOWN_STORAGE_KEY, serializedData );
                    }
                    return;
                }
            }
        } catch ( e ) {
            // fall through to remove the full key
        }
    }
    persist ? removeLocalData( COUNTDOWN_STORAGE_KEY ) : removeSessionData( COUNTDOWN_STORAGE_KEY );
};

export default clearCountdown;
