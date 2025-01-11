/**
 * Sets a cookie with the given name, value and number of days until it expires.
 * If days is not given, the cookie will expire at the end of the session.
 *
 * @param {string} name The name of the cookie.
 * @param {string} value The value of the cookie.
 * @param {number} [days] The number of days until the cookie expires.
 */
const setCookie = ( name, value, days ) => {
    let expires = "";
    if ( days ) {
        const date = new globalThis.Date();
        date.setTime( date.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
        expires = "; expires=" + date.toUTCString();
    }
    globalThis.document.cookie = name + "=" + ( value || "" ) + expires + "; path=/; SameSite=Lax";
};

export default setCookie;