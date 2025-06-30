/**
 *
 * @param {string} key
 */
const removeCookie = ( key ) => {
    try {
        const date = new globalThis.Date();
        date.setTime( date.getTime() - 10000 );
        globalThis.document.cookie = key + "=; expires=" + date.toUTCString() + "; path=/; SameSite=Lax";
    } catch ( e ) {
        return false;
    }
    return true;
};

export default removeCookie;