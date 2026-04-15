import { isPlainObject } from "@steveush/utils";
import getLocalData from "./getLocalData";
import getSessionData from "./getSessionData";

/**
 * The key used to store the countdown popup data in both localStorage and as a cookie.
 * @type {string}
 */
export const COUNTDOWN_STORAGE_KEY = 'FOOCONVERT_COUNTDOWN';

/**
 * Countdown state persisted for a popup.
 *
 * @typedef {{state: 'running'|'paused', value: number}} CountdownEntry
 */

/**
 * Get the stored countdown data for a popup.
 *
 * @param {string|number} countdownId
 * @param {boolean} persist
 * @returns {CountdownEntry|number|undefined}
 */
const getCountdown = ( countdownId, persist ) => {
    let countdownData = persist ? getLocalData( COUNTDOWN_STORAGE_KEY ) : getSessionData( COUNTDOWN_STORAGE_KEY );
    return isPlainObject( countdownData ) && Object.hasOwn( countdownData, countdownId ) ? countdownData[ countdownId ] : undefined;
};

export default getCountdown;
