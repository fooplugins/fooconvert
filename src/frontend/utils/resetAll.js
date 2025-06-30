import { strim, bisect } from "@steveush/utils";
import removeCookie from "./removeCookie";
import { UNIQUE_ID_STORAGE_KEY } from "./getUniqueID";

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
 * @param {Storage} storage
 * @param {boolean} includeUniqueId
 */
const resetStorage = ( storage, includeUniqueId ) => {
    try {
        Object.keys( storage ).filter( STORAGE_KEY_FILTER( includeUniqueId ) ).forEach( key => {
            storage.removeItem( key );
        } );
    } catch ( e ) {

    }
};

const resetCookies = ( includeUniqueId ) => {
    try {
        strim( globalThis.document.cookie, ';' ).filter( cookie => cookie.startsWith( 'FOOCONVERT_' ) ).forEach( cookie => {
            const [ key ] = bisect( cookie, '=', true );
            if ( includeUniqueId || ( !includeUniqueId && key !== UNIQUE_ID_STORAGE_KEY ) ) {
                removeCookie( key );
            }
        } );
    } catch ( e ) {

    }
};

const resetAll = ( includeUniqueId = false ) => {
    resetCookies( includeUniqueId );
    resetStorage( globalThis?.localStorage, includeUniqueId );
    resetStorage( globalThis?.sessionStorage, includeUniqueId );
};

export default resetAll;