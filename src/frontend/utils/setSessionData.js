import setCookie from "./setCookie";

const setSessionData = ( key, data ) => {
    const serialized = JSON.stringify( data );
    try {
        globalThis?.sessionStorage?.setItem( key, serialized );
    } catch ( e ) {
        // eat possible security exception
        // see: https://developer.mozilla.org/en-US/docs/Web/API/Window/sessionStorage#exceptions
    }
    setCookie( key, serialized );
}

export default setSessionData;