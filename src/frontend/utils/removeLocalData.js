import removeCookie from "./removeCookie";
import { removeStorageItem } from "./storage";

/**
 * Remove the stored localStorage value and its cookie fallback.
 *
 * @param {string} key
 * @returns {void}
 */
const removeLocalData = ( key ) => {
    removeStorageItem( "localStorage", key );
    removeCookie( key );
};

export default removeLocalData;
