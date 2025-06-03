import setCookie from "./setCookie";

const setLocalData = ( key, data, days = 365 ) => {
    const serialized = JSON.stringify( data );
    try {
        globalThis?.localStorage?.setItem( key, serialized );
    } catch ( e ) {
        // eat possible security exception
        // see: https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage#exceptions
    }
    setCookie( key, serialized, days );
}

export default setLocalData;