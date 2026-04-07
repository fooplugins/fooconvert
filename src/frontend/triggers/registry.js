import { isFunction, isString } from "@steveush/utils";
import { getEventBus } from "./eventBus";

const TRIGGER_SUBSCRIBERS = new Map();
const PASSTHROUGH_TRIGGERS = new Set();
const REGISTRATION_LISTENERS = new Map();

const isEventName = value => isString( value, true );

const notifyRegistered = eventName => {
    const listeners = REGISTRATION_LISTENERS.get( eventName );
    if ( !( listeners instanceof Set ) ) {
        return;
    }
    listeners.forEach( callback => callback( eventName ) );
};

export const registerTriggerSubscriber = ( eventName, subscriber ) => {
    if ( !isEventName( eventName ) || !isFunction( subscriber ) ) {
        return null;
    }

    if ( TRIGGER_SUBSCRIBERS.has( eventName ) && TRIGGER_SUBSCRIBERS.get( eventName ) !== subscriber ) {
        console.warn( `[FooConvert] Replacing trigger subscriber for "${ eventName }".` );
    }

    TRIGGER_SUBSCRIBERS.set( eventName, subscriber );
    notifyRegistered( eventName );
    return subscriber;
};

export const getTriggerSubscriber = eventName => {
    return TRIGGER_SUBSCRIBERS.get( eventName ) ?? null;
};

export const hasTriggerSubscriber = eventName => {
    return TRIGGER_SUBSCRIBERS.has( eventName );
};

export const registerPassthroughTrigger = eventName => {
    if ( !isEventName( eventName ) ) {
        return false;
    }

    PASSTHROUGH_TRIGGERS.add( eventName );
    notifyRegistered( eventName );
    return true;
};

export const hasPassthroughTrigger = eventName => {
    return PASSTHROUGH_TRIGGERS.has( eventName );
};

export const onTriggerSubscriberRegistered = ( eventName, callback ) => {
    if ( !isEventName( eventName ) || !isFunction( callback ) ) {
        return () => {};
    }

    if ( !REGISTRATION_LISTENERS.has( eventName ) ) {
        REGISTRATION_LISTENERS.set( eventName, new Set() );
    }

    const listeners = REGISTRATION_LISTENERS.get( eventName );
    listeners.add( callback );

    return () => {
        listeners.delete( callback );
        if ( listeners.size === 0 ) {
            REGISTRATION_LISTENERS.delete( eventName );
        }
    };
};

const createPassthroughSubscription = context => {
    const { event, eventBus, onOpenTrigger } = context;
    return eventBus.on( event, payload => onOpenTrigger( event, payload ?? null ) );
};

const canSubscribe = eventName => {
    return hasTriggerSubscriber( eventName ) || hasPassthroughTrigger( eventName );
};

export const subscribeTrigger = context => {
    const event = context?.event;
    const onOpenTrigger = context?.onOpenTrigger;
    if ( !isEventName( event ) || !isFunction( onOpenTrigger ) ) {
        return null;
    }

    const runtimeContext = {
        ...context,
        eventBus: context?.eventBus ?? getEventBus()
    };

    let destroyCurrent = null;
    let destroyRegistration = null;

    const disconnectCurrent = () => {
        if ( isFunction( destroyCurrent ) ) {
            destroyCurrent();
            destroyCurrent = null;
        }
    };

    const disconnectRegistration = () => {
        if ( isFunction( destroyRegistration ) ) {
            destroyRegistration();
            destroyRegistration = null;
        }
    };

    const connect = () => {
        disconnectCurrent();

        const subscriber = getTriggerSubscriber( event );
        if ( isFunction( subscriber ) ) {
            destroyCurrent = subscriber( runtimeContext );
            disconnectRegistration();
            return;
        }

        if ( hasPassthroughTrigger( event ) ) {
            destroyCurrent = createPassthroughSubscription( runtimeContext );
        }
    };

    connect();

    if ( !hasTriggerSubscriber( event ) ) {
        destroyRegistration = onTriggerSubscriberRegistered( event, () => {
            if ( canSubscribe( event ) ) {
                connect();
            }
        } );
    }

    return () => {
        disconnectCurrent();
        disconnectRegistration();
    };
};
