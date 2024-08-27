import CustomElement from "./CustomElement";
import { isFunction, isNumber, isString, isUndefined, strim } from "@steveush/utils";
import { getDocumentScrollPercent, hasAdBlock } from "../utils";

/**
 * @typedef {"immediate"|"adblock"|"anchor"|"exit-intent"|"scroll"|"timer"|"visible"} TriggerType
 */

/**
 *
 */
class TriggeredElement extends CustomElement {

    // noinspection JSUnusedGlobalSymbols

    static #triggerTypes = [ "immediate", "adblock", "anchor", "scroll", "timer", "visible", "exit-intent" ];
    /**
     *
     * @returns {TriggerType[]}
     */
    static get triggerTypes() {
        return TriggeredElement.#triggerTypes;
    }

    /**
     *
     * @param {any} value
     * @returns {value is TriggerType}
     */
    static isTriggerType( value ) {
        return TriggeredElement.triggerTypes.includes( value );
    }

    constructor() {
        super();
    }

    /**
     *
     * @returns {?TriggerType}
     */
    get trigger() {
        const trigger = this.config.trigger;
        return this.constructor.isTriggerType( trigger ) ? trigger : null;
    }

    /**
     *
     * @returns {number|string|null}
     */
    get triggerData() {
        const data = this.config.triggerData;
        if ( !isUndefined( data ) ) {
            switch ( this.trigger ) {
                case "anchor":
                case "visible":
                    return isString( data, true ) ? data : null;
                case "adblock":
                case "exit-intent":
                case "timer":
                case "scroll":
                    return isNumber( data ) ? data : null;
            }
        }
        return null;
    }

    connected() {
        super.connected();
        this.connectTrigger();
    }

    disconnected() {
        super.disconnected();
        this.disconnectTrigger();
    }

    /**
     *
     * @param {TriggerType} type
     * @param {...any} args
     */
    triggeredCallback( type, ...args ) {}

    /**
     *
     * @type {?function}
     */
    #destroyTrigger = null;

    connectTrigger() {
        this.disconnectTrigger();
        switch ( this.trigger ) {
            case "immediate":
                this.#destroyTrigger = this.initImmediateTrigger();
                break;
            case "anchor":
                this.#destroyTrigger = this.initAnchorTrigger( this.triggerData );
                break;
            case "adblock":
                this.#destroyTrigger = this.initAdBlockTrigger( this.triggerData );
                break;
            case "exit-intent":
                this.#destroyTrigger = this.initExitIntentTrigger( this.triggerData );
                break;
            case "scroll":
                this.#destroyTrigger = this.initScrollTrigger( this.triggerData );
                break;
            case "timer":
                this.#destroyTrigger = this.initTimerTrigger( this.triggerData );
                break;
            case "visible":
                this.#destroyTrigger = this.initVisibleTrigger( this.triggerData );
                break;
            default:
                this.#destroyTrigger = null;
        }
    }

    disconnectTrigger() {
        if ( isFunction( this.#destroyTrigger ) ) {
            this.#destroyTrigger();
            this.#destroyTrigger = null;
        }
    }

    /**
     *
     * @returns {?function}
     */
    initImmediateTrigger() {
        const handle = requestAnimationFrame( () => {
            this.triggeredCallback( "immediate" );
        } );
        return () => {
            cancelAnimationFrame( handle );
        };
    }

    /**
     *
     * @param {string} target
     * @returns {?function}
     */
    initAnchorTrigger( target ) {
        if ( isString( target, true ) ) {
            const listener = event => {
                event.preventDefault();
                this.triggeredCallback( "anchor", event.target );
            };
            const targets = [];
            strim( target, "," ).forEach( id => {
                const element = this.ownerDocument.getElementById( id );
                if ( element instanceof HTMLElement ) {
                    element.addEventListener( "click", listener );
                    targets.push( element );
                }
            } );
            return () => {
                targets.forEach( element => element.removeEventListener( "click", listener ) );
            };
        }
    }

    /**
     *
     * @param {number} percent
     * @returns {?function}
     */
    initAdBlockTrigger( percent ) {
        if ( isNumber( percent ) ) {
            const listener = () => {
                if ( getDocumentScrollPercent() > percent ) {
                    this.ownerDocument.removeEventListener( "scroll", listener );
                    this.triggeredCallback( "adblock", percent );
                }
            };
            const destroy = () => {
                this.ownerDocument.removeEventListener( "scroll", listener );
            };
            hasAdBlock().then( result => {
                console.log( "adblock", result, this.isConnected, this.#destroyTrigger === destroy );
                if ( result && this.isConnected && this.#destroyTrigger === destroy ) {
                    if ( getDocumentScrollPercent() > percent ) {
                        this.triggeredCallback( "adblock", percent );
                    } else {
                        this.ownerDocument.addEventListener( "scroll", listener, { passive: true } );
                    }
                }
            } );
            return destroy;
        }
    }

    /**
     *
     * @param {number} delay
     * @returns {?function}
     */
    initExitIntentTrigger( delay ) {
        if ( isNumber( delay ) ) {
            const listener = event => {
                if ( event.clientY < 0 ) {
                    this.ownerDocument.body.removeEventListener( "mouseleave", listener );
                    this.triggeredCallback( "exit-intent" );
                }
            };
            const init = () => {
                this.ownerDocument.body.addEventListener( "mouseleave", listener, { passive: true } );
            };
            const destroy = () => {
                this.ownerDocument.body.removeEventListener( "mouseleave", listener );
            };
            if ( delay > 0 ) {
                const timeoutId = setTimeout( init, delay * 1000 );
                return () => {
                    clearTimeout( timeoutId );
                    destroy();
                };
            }
            init();
            return destroy;
        }
    }

    /**
     *
     * @param {number} timeout
     * @returns {?function}
     */
    initTimerTrigger( timeout ) {
        if ( isNumber( timeout ) ) {
            const timeoutId = setTimeout( () => {
                this.triggeredCallback( "timer", timeout );
            }, timeout * 1000 );
            return () => {
                clearTimeout( timeoutId );
            };
        }
    }

    /**
     *
     * @param {number} percent
     * @returns {?function}
     */
    initScrollTrigger( percent ) {
        if ( isNumber( percent ) ) {
            if ( getDocumentScrollPercent() > percent ) {
                this.triggeredCallback( "scroll", percent );
            } else {
                const listener = () => {
                    if ( getDocumentScrollPercent() > percent ) {
                        this.ownerDocument.removeEventListener( "scroll", listener );
                        this.triggeredCallback( "scroll", percent );
                    }
                };
                this.ownerDocument.addEventListener( "scroll", listener, { passive: true } );
                return () => {
                    this.ownerDocument.removeEventListener( "scroll", listener );
                };
            }
        }
    }

    /**
     *
     * @param {string} target
     * @returns {?function}
     */
    initVisibleTrigger( target ) {
        if ( isString( target, true ) ) {
            const targets = strim( target, "," ).reduce( ( acc, id ) => {
                const element = this.ownerDocument.getElementById( id );
                if ( element instanceof HTMLElement ) {
                    acc.push( element );
                }
                return acc;
            }, [] );
            if ( targets.length > 0 ) {
                const observer = new global.IntersectionObserver( entries => {
                    const visible = entries.find( entry => entry.isIntersecting );
                    if ( visible ) {
                        observer.disconnect();
                        this.triggeredCallback( "visible", visible.target );
                    }
                }, { root: this.ownerDocument } );
                targets.forEach( element => observer.observe( element ) );
                return () => {
                    observer.disconnect();
                };
            }
        }
    }
}

export default TriggeredElement;