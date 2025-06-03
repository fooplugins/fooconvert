import getCookie from "./getCookie";
import { isString } from "@steveush/utils";

const getLocalData = ( key ) => {
    let serialized;

    try {
        serialized = globalThis?.localStorage?.getItem( key );
    } catch ( e ) {
        // eat possible security exception
        // see: https://developer.mozilla.org/en-US/docs/Web/API/Window/localStorage#exceptions
    }

    if ( !serialized ) {
        serialized = getCookie( key );
    }
    return isString( serialized ) ? JSON.parse( serialized ) : undefined;
}

export default getLocalData;