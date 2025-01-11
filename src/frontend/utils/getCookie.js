/**
 * Retrieves a cookie by name.
 *
 * @param {string} name The name of the cookie to retrieve.
 * @returns {string|null} The value of the cookie, or null if the cookie does not exist.
 */
const getCookie = ( name ) => {
    const nameEQ = name + "=";
    const ca = globalThis.document.cookie.split( ';' );
    for ( let i = 0; i < ca.length; i++ ) {
        let c = ca[ i ];
        while ( c.charAt( 0 ) === ' ' ) c = c.substring( 1, c.length );
        if ( c.indexOf( nameEQ ) === 0 ) return c.substring( nameEQ.length, c.length );
    }
    return null;
};

export default getCookie;