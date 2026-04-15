import { strim, bisect } from "@steveush/utils";
import removeCookie from "./removeCookie";
import { UNIQUE_ID_STORAGE_KEY } from "./getUniqueID";
import { listStorageKeys, removeStorageItem } from "./storage";

const STORAGE_KEY_PREFIX = 'FOOCONVERT_';

const STORAGE_KEY_FILTER = ( includeUniqueId ) => {
    return ( key ) => {
        if ( includeUniqueId ) {
            return key.startsWith( STORAGE_KEY_PREFIX );
        }
        return key !== UNIQUE_ID_STORAGE_KEY && key.startsWith( STORAGE_KEY_PREFIX );
    };
};

/**
 *
 * @param {string} storageName
 * @param {boolean} includeUniqueId
 */
const resetStorage = ( storageName, includeUniqueId ) => {
    listStorageKeys( storageName ).filter( STORAGE_KEY_FILTER( includeUniqueId ) ).forEach( key => {
        removeStorageItem( storageName, key );
    } );
};

const resetCookies = ( includeUniqueId ) => {
    const cookieJar = globalThis?.document?.cookie;

    if ( typeof cookieJar !== 'string' || cookieJar.length === 0 ) {
        return;
    }

    strim( cookieJar, ';' ).filter( cookie => cookie.startsWith( 'FOOCONVERT_' ) ).forEach( cookie => {
        const [ key ] = bisect( cookie, '=', true );
        if ( includeUniqueId || ( !includeUniqueId && key !== UNIQUE_ID_STORAGE_KEY ) ) {
            removeCookie( key );
        }
    } );
};

const resetAll = ( includeUniqueId = false ) => {
    resetCookies( includeUniqueId );
    resetStorage( "localStorage", includeUniqueId );
    resetStorage( "sessionStorage", includeUniqueId );
};

export default resetAll;
