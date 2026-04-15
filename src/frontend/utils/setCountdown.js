import { isPlainObject } from "@steveush/utils";
import getLocalData from "./getLocalData";
import getSessionData from "./getSessionData";
import setLocalData from "./setLocalData";
import setSessionData from "./setSessionData";
import { COUNTDOWN_STORAGE_KEY } from "./getCountdown";

/**
 * Store countdown state for a popup.
 *
 * @typedef {{state: 'running'|'paused', value: number}} CountdownEntry
 */

/**
 * Store countdown data for a popup.
 *
 * @param {string|number} countdownId
 * @param {CountdownEntry|number} value
 * @param {boolean} persist
 * @param {boolean} [overwrite=false]
 * @returns {void}
 */
const setCountdown = ( countdownId, value, persist, overwrite = false ) => {
    let countdownData = persist ? getLocalData( COUNTDOWN_STORAGE_KEY ) : getSessionData( COUNTDOWN_STORAGE_KEY );
    if ( !isPlainObject( countdownData ) ) {
        countdownData = {};
    }
    if ( overwrite || !Object.hasOwn( countdownData, countdownId ) ) {
        countdownData[ countdownId ] = value;
        persist ? setLocalData( COUNTDOWN_STORAGE_KEY, countdownData ) : setSessionData( COUNTDOWN_STORAGE_KEY, countdownData );
    }
};

export default setCountdown;
