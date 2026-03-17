import { isPlainObject } from "@steveush/utils";
import getLocalData from "./getLocalData";
import getSessionData from "./getSessionData";
import setLocalData from "./setLocalData";
import setSessionData from "./setSessionData";
import { COUNTDOWN_STORAGE_KEY } from "./getCountdown";

const setCountdown = ( countdownId, value, persist ) => {
    let countdownData = persist ? getLocalData( COUNTDOWN_STORAGE_KEY ) : getSessionData( COUNTDOWN_STORAGE_KEY );
    if ( !isPlainObject( countdownData ) ) {
        countdownData = {};
    }
    if ( !Object.hasOwn( countdownData, countdownId ) ) {
        countdownData[ countdownId ] = value;
        persist ? setLocalData( COUNTDOWN_STORAGE_KEY, countdownData ) : setSessionData( COUNTDOWN_STORAGE_KEY, countdownData );
    }
};

export default setCountdown;
