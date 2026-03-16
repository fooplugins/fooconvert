import { getDocumentScrollPercent } from "../../utils";
import { getEventBus } from "../eventBus";

const OBSERVED_VISIBLE_IDS = new Set();
const OBSERVED_VISIBLE_FOUND = new Set();

let visibilityObserver = null;

const ensureVisibilityObserver = () => {
    if ( visibilityObserver || !( "IntersectionObserver" in window ) ) {
        return;
    }
    visibilityObserver = new IntersectionObserver( entries => {
        const eventBus = getEventBus();
        entries.forEach( entry => {
            if ( !entry.isIntersecting ) {
                return;
            }
            const id = entry?.target?.id;
            if ( !id || OBSERVED_VISIBLE_FOUND.has( id ) ) {
                return;
            }
            OBSERVED_VISIBLE_FOUND.add( id );
            eventBus.emit( "fc.element.visible", { id, target: entry.target } );
        } );
    }, { root: null } );
};

const refreshObservedVisibleTargets = () => {
    if ( !visibilityObserver ) {
        return;
    }
    OBSERVED_VISIBLE_IDS.forEach( id => {
        const element = document.getElementById( id );
        if ( element instanceof HTMLElement ) {
            visibilityObserver.observe( element );
        }
    } );
};

export const observeVisibilityIds = ids => {
    const values = Array.isArray( ids ) ? ids : [];
    values.forEach( id => {
        const value = `${ id ?? "" }`.trim();
        if ( value ) {
            OBSERVED_VISIBLE_IDS.add( value );
        }
    } );
    ensureVisibilityObserver();
    refreshObservedVisibleTargets();
};

export const initCoreAdapter = () => {
    const eventBus = getEventBus();
    const startedAt = Date.now();
    let scheduled = false;

    const onClick = event => {
        const target = event.target instanceof Element ? event.target : null;
        if ( !target ) {
            return;
        }
        eventBus.emit( "fc.element.click", { target } );
        const anchor = target.closest( "[id]" );
        if ( anchor && anchor.id ) {
            eventBus.emit( "fc.anchor.click", { id: anchor.id, target: anchor } );
        }
    };

    const onScroll = () => {
        if ( scheduled ) {
            return;
        }
        scheduled = true;
        requestAnimationFrame( () => {
            scheduled = false;
            eventBus.emit( "fc.scroll.percent", { percent: getDocumentScrollPercent() } );
        } );
    };

    const onMouseLeave = event => {
        if ( event.clientY < 0 ) {
            eventBus.emit( "fc.exit_intent", {
                clientY: event.clientY,
                secondsOnPage: Math.floor( ( Date.now() - startedAt ) / 1000 )
            } );
        }
    };

    let timerSeconds = 0;
    setInterval( () => {
        timerSeconds += 1;
        eventBus.emit( "fc.timer.tick", { elapsedSeconds: timerSeconds } );
    }, 1000 );

    document.addEventListener( "click", onClick, { passive: true } );
    document.addEventListener( "scroll", onScroll, { passive: true } );
    document.body?.addEventListener( "mouseleave", onMouseLeave, { passive: true } );

    eventBus.emit( "fc.immediate", { source: "core" } );
    ensureVisibilityObserver();
};
