/**
 *
 * @param {string} key
 * @returns {boolean}
 */
const removeCookie = ( key ) => {
    try {
        const date = new globalThis.Date();
        date.setTime( date.getTime() - 10000 );
        globalThis.document.cookie = key + "=; expires=" + date.toUTCString() + "; path=/; SameSite=Lax";
    } catch {
        return false;
    }
    return true;
};

export default removeCookie;
