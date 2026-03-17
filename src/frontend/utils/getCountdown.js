import { isPlainObject } from "@steveush/utils";
import getLocalData from "./getLocalData";
import getSessionData from "./getSessionData";

/**
 * The key used to store the countdown widget data in both localStorage and as a cookie.
 * @type {string}
 */
export const COUNTDOWN_STORAGE_KEY = 'FOOCONVERT_COUNTDOWN';

const getCountdown = ( countdownId, persist ) => {
    let countdownData = persist ? getLocalData( COUNTDOWN_STORAGE_KEY ) : getSessionData( COUNTDOWN_STORAGE_KEY );
    return isPlainObject( countdownData ) && Object.hasOwn( countdownData, countdownId ) ? countdownData[ countdownId ] : undefined;
};

export default getCountdown;
