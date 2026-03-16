const LISTENERS = new Map();

const addListener = ( eventName, callback ) => {
    if ( !LISTENERS.has( eventName ) ) {
        LISTENERS.set( eventName, new Set() );
    }
    const listeners = LISTENERS.get( eventName );
    listeners.add( callback );
    return () => listeners.delete( callback );
};

const emit = ( eventName, payload = {} ) => {
    const listeners = LISTENERS.get( eventName );
    if ( !( listeners instanceof Set ) ) {
        return;
    }
    listeners.forEach( callback => callback( payload ) );
};

const on = ( eventName, callback ) => {
    if ( typeof callback !== "function" ) {
        return () => {};
    }
    return addListener( eventName, callback );
};

let instance;

export const getEventBus = () => {
    if ( !instance ) {
        instance = { emit, on };
    }
    return instance;
};

export default getEventBus;
