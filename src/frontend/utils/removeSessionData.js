import removeCookie from "./removeCookie";

const removeSessionData = ( key ) => {
    try {
        globalThis?.sessionStorage?.removeItem( key );
    } catch ( e ) {
        // eat possible security exception
        // see: https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage#exceptions
    }
    removeCookie( key );
}

export default removeSessionData;