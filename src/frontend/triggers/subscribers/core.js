import { isFunction, isString } from "@steveush/utils";
import { observeVisibilityIds } from "../adapters/core";
import { registerTriggerSubscriber } from "../registry";

const createOneShotListener = ( eventBus, eventName, callback ) => {
    let unsubscribe = null;
    unsubscribe = eventBus.on( eventName, payload => {
        const handled = callback( payload, () => {
            if ( isFunction( unsubscribe ) ) {
                unsubscribe();
                unsubscribe = null;
            }
        } );
        if ( handled && isFunction( unsubscribe ) ) {
            unsubscribe();
            unsubscribe = null;
        }
    } );
    return unsubscribe;
};

registerTriggerSubscriber( "fc.immediate", ( { event, eventBus, onOpenTrigger } ) => {
    return eventBus.on( event, () => onOpenTrigger( event ) );
} );

registerTriggerSubscriber( "fc.anchor.click", ( { event, where, eventBus, onOpenTrigger } ) => {
    const ids = new Set( Array.isArray( where?.ids ) ? where.ids : [] );
    return eventBus.on( event, payload => {
        const id = payload?.id;
        if ( ids.size === 0 || ids.has( id ) ) {
            onOpenTrigger( event, id ?? null );
        }
    } );
} );

registerTriggerSubscriber( "fc.element.click", ( { event, where, eventBus, onOpenTrigger } ) => {
    const selector = isString( where?.selector, true ) ? where.selector : null;
    return eventBus.on( event, payload => {
        const target = payload?.target instanceof Element ? payload.target : null;
        if ( target && selector && target.closest( selector ) ) {
            onOpenTrigger( event, selector );
        }
    } );
} );

registerTriggerSubscriber( "fc.element.visible", ( { event, where, eventBus, onOpenTrigger } ) => {
    const ids = new Set( Array.isArray( where?.ids ) ? where.ids : [] );
    observeVisibilityIds( [ ...ids ] );
    return createOneShotListener( eventBus, event, payload => {
        const id = payload?.id;
        if ( ids.size === 0 || ids.has( id ) ) {
            onOpenTrigger( event, id ?? null );
            return true;
        }
        return false;
    } );
} );

registerTriggerSubscriber( "fc.scroll.percent", ( { event, where, eventBus, onOpenTrigger } ) => {
    const threshold = Number( where?.percent );
    if ( !Number.isFinite( threshold ) ) {
        return null;
    }
    return createOneShotListener( eventBus, event, ( payload, destroy ) => {
        const percent = Number( payload?.percent );
        if ( Number.isFinite( percent ) && percent >= threshold ) {
            onOpenTrigger( event, threshold );
            destroy();
        }
    } );
} );

registerTriggerSubscriber( "fc.timer.elapsed", ( { event, where, eventBus, onOpenTrigger } ) => {
    const threshold = Number( where?.seconds );
    if ( !Number.isFinite( threshold ) ) {
        return null;
    }
    return createOneShotListener( eventBus, "fc.timer.tick", ( payload, destroy ) => {
        const elapsed = Number( payload?.elapsedSeconds );
        if ( Number.isFinite( elapsed ) && elapsed >= threshold ) {
            onOpenTrigger( event, threshold );
            destroy();
        }
    } );
} );

registerTriggerSubscriber( "fc.exit_intent", ( { event, where, eventBus, onOpenTrigger } ) => {
    const delay = Number( where?.delaySeconds ?? 0 );
    return createOneShotListener( eventBus, event, ( payload, destroy ) => {
        const secondsOnPage = Number( payload?.secondsOnPage ?? 0 );
        if ( secondsOnPage >= delay ) {
            onOpenTrigger( event, delay );
            destroy();
        }
    } );
} );
