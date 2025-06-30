import removeCookie from "./removeCookie";

const removeLocalData = ( key ) => {
    try {
        globalThis?.localStorage?.removeItem( key );
    } catch ( e ) {
        // eat possible security exception
        // see: https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage#exceptions
    }
    removeCookie( key );
}

export default removeLocalData;