import removeCookie from "./removeCookie";
import { removeStorageItem } from "./storage";

/**
 * Remove the stored sessionStorage value and its cookie fallback.
 *
 * @param {string} key
 * @returns {void}
 */
const removeSessionData = ( key ) => {
    removeStorageItem( "sessionStorage", key );
    removeCookie( key );
};

export default removeSessionData;
