import WidgetElement from "./WidgetElement";
import { isFunction, isNumber, isPlainObject, isString, strim } from "@steveush/utils";
import { getClickableData, LOG_EVENT_TYPES, isSelector, getSeen, setSeen } from "../utils";
import { getEventBus, observeVisibilityIds } from "../triggers";

/**
 *
 */
class TriggeredElement extends WidgetElement {

    // noinspection JSUnusedGlobalSymbols

    static get observedAttributes() {
        return [ "open" ];
    }

    static get triggerEvents() {
        return [
            "fc.immediate",
            "fc.anchor.click",
            "fc.element.click",
            "fc.scroll.percent",
            "fc.timer.elapsed",
            "fc.element.visible",
            "fc.exit_intent",
            "cart.add",
            "cart.updated",
            "cart.remove",
            "coupon.applied",
            "coupon.invalid",
            "checkout.error",
            "checkout.payment_failed"
        ];
    }

    static isTriggerEvent( value ) {
        return TriggeredElement.triggerEvents.includes( value );
    }

    constructor() {
        super();
        this.onRequestClose = this.onRequestClose.bind( this );
        this.onOpenTrigger = this.onOpenTrigger.bind( this );
        this.onClickableClicked = this.onClickableClicked.bind( this );
    }

    get triggerConfig() {
        return isPlainObject( this.config?.triggerConfig ) ? this.config.triggerConfig : null;
    }

    get triggerStep() {
        const steps = this.triggerConfig?.steps;
        return Array.isArray( steps ) && isPlainObject( steps.at( 0 ) ) ? steps.at( 0 ) : null;
    }

    get triggerEvent() {
        const event = this.triggerStep?.event;
        return this.constructor.isTriggerEvent( event ) ? event : null;
    }

    get triggerWhere() {
        return isPlainObject( this.triggerStep?.where ) ? this.triggerStep.where : {};
    }

    get triggerOnce() {
        return this.triggerConfig?.frequency?.mode === "once";
    }

    get open() {
        return this.hasAttribute( "open" );
    }

    set open( state ) {
        this.toggleAttribute( "open", Boolean( state ) );
    }

    onRequestClose( event ) {
        this.setOpen( false );
    }

    connected() {
        super.connected();
        if ( this.dispatch( "can-connect", { cancelable: true } ) ) {
            this.addEventListener( "request-close", this.onRequestClose );
            this.connectTrigger();
            this.connectClickable();
        }
    }

    disconnected() {
        super.disconnected();
        this.removeEventListener( "request-close", this.onRequestClose );
        this.disconnectTrigger();
        this.disconnectClickable();
    }

    // noinspection JSUnusedGlobalSymbols,JSUnusedLocalSymbols
    attributeChangedCallback( name, oldValue, newValue ) {
        if ( name === "open" ) {
            this.onOpenChanged( this.open );
        }
    }

    connectClickable() {
        this.addEventListener( "click", this.onClickableClicked );
    }

    disconnectClickable() {
        this.removeEventListener( "click", this.onClickableClicked );
    }

    onClickableClicked( event ) {
        if ( event.composed ) {
            const path = event.composedPath();
            if ( Array.isArray( path ) ) {
                const target = path.shift();
                if ( this.isClickable( target ) ) {
                    const data = getClickableData( target );
                    if ( data ) {
                        this.log( LOG_EVENT_TYPES.CLICK, data );
                    }
                }
            }
        }
    }

    isClickable( element ) {
        return element instanceof HTMLElement && element.matches( this.clickableSelector );
    }

    /**
     * @type {?string}
     */
    #clickableSelector;

    /**
     *
     * @returns {string}
     */
    get clickableSelector() {
        if ( !this.#clickableSelector ) {
            this.#clickableSelector = this.createClickableSelector();
        }
        return this.#clickableSelector;
    }

    createClickableSelector() {
        const selectors = [ 'a,button,input,textarea,select' ];
        if ( [ "fc.anchor.click", "fc.element.visible" ].includes( this.triggerEvent ) ) {
            const ids = Array.isArray( this.triggerWhere?.ids ) ? this.triggerWhere.ids : [];
            if ( ids.length > 0 ) {
                const idToSelector = ids.map( id => `#${ id }` ).join( ',' );
                if ( isSelector( idToSelector ) ) {
                    selectors.push( idToSelector );
                }
            }
        } else if ( this.triggerEvent === "fc.element.click" ) {
            const selector = this.triggerWhere?.selector;
            if ( isSelector( selector ) ) {
                selectors.push( selector );
            }
        }
        return selectors.join( ',' );
    }

    /**
     *
     * @type {object}
     */
    #openData = null;

    /**
     *
     * @param {boolean} state
     * @param {object} [data]
     */
    setOpen( state, data = null ) {
        this.#openData = data;
        this.open = state;
    }

    #openTimestamp = null;

    onOpenChanged( state ) {
        const { postId, postType } = this.config;
        if ( this.triggerOnce && state && getSeen( postId ) ) {
            // this.#openData = { silent: true };
            // this.open = false;
            console.log( 'Not allowed, shown once already' );
            this.remove();
            return;
        }

        if ( isString( postType, true ) ) {
            this.ownerDocument.documentElement.classList.toggle( `${ postType }__open`, state );
        }
        let duration = null;
        if ( state ) {
            this.#openTimestamp = Date.now();
        } else if ( isNumber( this.#openTimestamp ) ) {
            duration = Date.now() - this.#openTimestamp;
            this.#openTimestamp = null;
        }

        let data = this.#openData;
        if ( isNumber( duration ) ) {
            if ( isPlainObject( data ) ) {
                data.duration = duration;
            } else {
                data = { duration };
            }
        }

        if ( this.triggerOnce && !state ) {
            setSeen( postId );
        }

        if ( !data.silent ) {
            this.dispatch( state ? "open" : "close" );
            this.log( state ? LOG_EVENT_TYPES.OPEN : LOG_EVENT_TYPES.CLOSE, data );
        }
        this.#openData = null;
    }

    onOpenTrigger( trigger, triggerData ) {
        this.setOpen( true, { trigger, triggerData } );
    }

    /**
     *
     * @type {?function}
     */
    #destroyOpenTrigger = null;

    connectTrigger() {
        this.disconnectTrigger();
        this.#destroyOpenTrigger = this.connectTriggerEvent();
    }

    disconnectTrigger() {
        if ( isFunction( this.#destroyOpenTrigger ) ) {
            this.#destroyOpenTrigger();
            this.#destroyOpenTrigger = null;
        }
    }

    connectTriggerEvent() {
        const eventBus = getEventBus();
        const event = this.triggerEvent;
        const where = this.triggerWhere;
        const parseCurrencyAmountToMinor = ( value, minorUnit = 2 ) => {
            if ( value === null || value === undefined || value === "" ) {
                return null;
            }
            const normalized = Number( `${ value }`.replace( /,/g, "" ) );
            if ( !Number.isFinite( normalized ) ) {
                return null;
            }
            const precision = Number.isInteger( minorUnit ) && minorUnit >= 0 ? minorUnit : 2;
            return Math.round( normalized * ( 10 ** precision ) );
        };
        const matchesCartAddWhere = payload => {
            const productIds = Array.isArray( where?.productIds )
                ? where.productIds.map( value => Number( value ) ).filter( Number.isInteger )
                : [];
            if ( productIds.length === 0 ) {
                return true;
            }

            const addedItems = Array.isArray( payload?.delta?.addedItems ) ? payload.delta.addedItems : [];
            return addedItems.some( item => productIds.includes( Number( item?.id ) ) );
        };
        const matchesCartUpdatedWhere = payload => {
            const operator = isString( where?.subtotal?.operator, true ) ? where.subtotal.operator : "";
            const amountMinor = parseCurrencyAmountToMinor(
                where?.subtotal?.amount,
                Number( payload?.current?.totals?.currency?.minorUnit ?? 2 )
            );

            if ( !operator || amountMinor === null ) {
                return true;
            }

            const subtotalMinor = Number( payload?.current?.totals?.subtotalMinor );
            if ( !Number.isFinite( subtotalMinor ) ) {
                return false;
            }

            switch ( operator ) {
                case "gt":
                    return subtotalMinor > amountMinor;
                case "gte":
                    return subtotalMinor >= amountMinor;
                case "lt":
                    return subtotalMinor < amountMinor;
                case "lte":
                    return subtotalMinor <= amountMinor;
                default:
                    return true;
            }
        };
        const matchesCartRemoveWhere = payload => {
            const productIds = Array.isArray( where?.productIds )
                ? where.productIds.map( value => Number( value ) ).filter( Number.isInteger )
                : [];
            if ( productIds.length === 0 ) {
                return true;
            }

            const removedItems = Array.isArray( payload?.delta?.removedItems ) ? payload.delta.removedItems : [];
            return removedItems.some( item => productIds.includes( Number( item?.id ) ) );
        };
        const matchesCouponAppliedWhere = payload => {
            const couponCodes = Array.isArray( where?.couponCodes )
                ? where.couponCodes.map( value => `${ value ?? "" }`.trim().toLowerCase() ).filter( Boolean )
                : [];
            if ( couponCodes.length === 0 ) {
                return true;
            }

            const couponCode = `${ payload?.couponCode ?? "" }`.trim().toLowerCase();
            return couponCode !== "" && couponCodes.includes( couponCode );
        };
        const createOneShotListener = ( eventName, callback ) => {
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

        if ( !isString( event, true ) ) {
            return null;
        }

        switch ( event ) {
            case "fc.immediate":
                return eventBus.on( event, () => this.onOpenTrigger( event ) );
            case "fc.anchor.click": {
                const ids = new Set( Array.isArray( where?.ids ) ? where.ids : [] );
                return eventBus.on( event, payload => {
                    const id = payload?.id;
                    if ( ids.size === 0 || ids.has( id ) ) {
                        this.onOpenTrigger( event, id ?? null );
                    }
                } );
            }
            case "fc.element.click": {
                const selector = isString( where?.selector, true ) ? where.selector : null;
                return eventBus.on( event, payload => {
                    const target = payload?.target instanceof Element ? payload.target : null;
                    if ( target && selector && target.closest( selector ) ) {
                        this.onOpenTrigger( event, selector );
                    }
                } );
            }
            case "fc.element.visible": {
                const ids = new Set( Array.isArray( where?.ids ) ? where.ids : [] );
                observeVisibilityIds( [ ...ids ] );
                return createOneShotListener( event, payload => {
                    const id = payload?.id;
                    if ( ids.size === 0 || ids.has( id ) ) {
                        this.onOpenTrigger( event, id ?? null );
                        return true;
                    }
                    return false;
                } );
            }
            case "fc.scroll.percent": {
                const threshold = Number( where?.percent );
                if ( !Number.isFinite( threshold ) ) {
                    return null;
                }
                return createOneShotListener( event, ( payload, destroy ) => {
                    const percent = Number( payload?.percent );
                    if ( Number.isFinite( percent ) && percent >= threshold ) {
                        this.onOpenTrigger( event, threshold );
                        destroy();
                    }
                } );
            }
            case "fc.timer.elapsed": {
                const threshold = Number( where?.seconds );
                if ( !Number.isFinite( threshold ) ) {
                    return null;
                }
                return createOneShotListener( "fc.timer.tick", ( payload, destroy ) => {
                    const elapsed = Number( payload?.elapsedSeconds );
                    if ( Number.isFinite( elapsed ) && elapsed >= threshold ) {
                        this.onOpenTrigger( event, threshold );
                        destroy();
                    }
                } );
            }
            case "fc.exit_intent": {
                const delay = Number( where?.delaySeconds ?? 0 );
                return createOneShotListener( event, ( payload, destroy ) => {
                    const secondsOnPage = Number( payload?.secondsOnPage ?? 0 );
                    if ( secondsOnPage >= delay ) {
                        this.onOpenTrigger( event, delay );
                        destroy();
                    }
                } );
            }
            case "cart.add":
                return eventBus.on( event, payload => {
                    if ( matchesCartAddWhere( payload ) ) {
                        this.onOpenTrigger( event, payload ?? null );
                    }
                } );
            case "cart.updated":
                return eventBus.on( event, payload => {
                    if ( matchesCartUpdatedWhere( payload ) ) {
                        this.onOpenTrigger( event, payload ?? null );
                    }
                } );
            case "cart.remove":
                return eventBus.on( event, payload => {
                    if ( matchesCartRemoveWhere( payload ) ) {
                        this.onOpenTrigger( event, payload ?? null );
                    }
                } );
            case "coupon.applied":
                return eventBus.on( event, payload => {
                    if ( matchesCouponAppliedWhere( payload ) ) {
                        this.onOpenTrigger( event, payload ?? null );
                    }
                } );
            default:
                return eventBus.on( event, payload => this.onOpenTrigger( event, payload ?? null ) );
        }
    }

    /**
     *
     * @param {string} target
     * @param {(trigger:string, triggerData?:(number|string|null)) => void} callback
     * @returns {?function}
     */
    initAnchorTrigger( target, callback ) {
        if ( isString( target, true ) ) {
            const listener = event => {
                event.stopPropagation();
                const data = getClickableData( event.target );
                callback( "anchor", data.id );
            };
            const targets = [];
            strim( target, "," ).forEach( id => {
                const element = this.ownerDocument.getElementById( id );
                if ( element instanceof globalThis.HTMLElement ) {
                    element.addEventListener( "click", listener );
                    targets.push( element );
                }
            } );
            return () => {
                targets.forEach( element => element.removeEventListener( "click", listener ) );
            };
        }
    }
}

export default TriggeredElement;
