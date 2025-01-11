import CustomElement from "./CustomElement";
import { isFunction, isNumber, isPlainObject, isString, isUndefined, strim } from "@steveush/utils";
import { getClickableData, getDocumentScrollPercent, logEvent, LOG_EVENT_TYPES, isSelector } from "../utils";

/**
 * @typedef {"immediate"|"anchor"|"element"|"exit-intent"|"scroll"|"timer"|"visible"} TriggerType
 */

/**
 *
 */
class TriggeredElement extends CustomElement {

    // noinspection JSUnusedGlobalSymbols

    static get observedAttributes() {
        return [ "open" ];
    }

    /**
     *
     * @returns {TriggerType[]}
     */
    static get triggerTypes() {
        return [ "immediate", "anchor", "element", "scroll", "timer", "visible", "exit-intent" ];
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
        this.onOpenTrigger = this.onOpenTrigger.bind( this );
        this.onClickableClicked = this.onClickableClicked.bind( this );
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
                case "element":
                case "visible":
                    return isString( data, true ) ? data : null;
                case "exit-intent":
                case "timer":
                case "scroll":
                    return isNumber( data ) ? data : null;
            }
        }
        return null;
    }

    get open() {
        return this.hasAttribute( "open" );
    }

    set open( state ) {
        this.toggleAttribute( "open", Boolean( state ) );
    }

    /**
     *
     * @param {string} type
     * @param {object} [data]
     */
    log( type, data ) {
        if ( !this.isConfigurationInitialized ) {
            this.initializeConfiguration();
        }
        const { postId, postType, template = '' } = this.config;
        logEvent( postId, postType, template, type, data );
    }

    connected() {
        super.connected();
        this.connectTrigger();
        this.connectClickable();
    }

    disconnected() {
        super.disconnected();
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
        if ( [ 'anchor', 'visible' ].includes( this.trigger ) ) {
            if ( isString( this.triggerData, true ) ) {
                const idToSelector = strim( this.triggerData, ',' ).map( a => `#${ a }` ).join( ',' );
                if ( isSelector( idToSelector ) ) {
                    selectors.push( idToSelector );
                }
            }
        } else if ( this.trigger === 'element' ) {
            if ( isSelector( this.triggerData ) ) {
                selectors.push( this.triggerData );
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
        if ( !this.isConfigurationInitialized ) {
            this.initializeConfiguration();
        }
        const { postType } = this.config;
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

        this.dispatch( state ? "open" : "close" );
        this.log( state ? LOG_EVENT_TYPES.OPEN : LOG_EVENT_TYPES.CLOSE, data );
        this.#openData = null;
    }

    /**
     *
     * @param {TriggerType} trigger
     * @param {(number|string|null|undefined)} [triggerData]
     */
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
        switch ( this.trigger ) {
            case "immediate":
                this.#destroyOpenTrigger = this.initImmediateTrigger( this.onOpenTrigger );
                break;
            case "anchor":
                this.#destroyOpenTrigger = this.initAnchorTrigger( this.triggerData, this.onOpenTrigger );
                break;
            case "element":
                this.#destroyOpenTrigger = this.initElementTrigger( this.triggerData, this.onOpenTrigger );
                break;
            case "exit-intent":
                this.#destroyOpenTrigger = this.initExitIntentTrigger( this.triggerData, this.onOpenTrigger );
                break;
            case "scroll":
                this.#destroyOpenTrigger = this.initScrollTrigger( this.triggerData, this.onOpenTrigger );
                break;
            case "timer":
                this.#destroyOpenTrigger = this.initTimerTrigger( this.triggerData, this.onOpenTrigger );
                break;
            case "visible":
                this.#destroyOpenTrigger = this.initVisibleTrigger( this.triggerData, this.onOpenTrigger );
                break;
            default:
                this.#destroyOpenTrigger = null;
        }
    }

    disconnectTrigger() {
        if ( isFunction( this.#destroyOpenTrigger ) ) {
            this.#destroyOpenTrigger();
            this.#destroyOpenTrigger = null;
        }
    }

    /**
     *
     * @param {(trigger:string, triggerData?:(number|string|null)) => void} callback
     * @returns {?function}
     */
    initImmediateTrigger( callback ) {
        const handle = globalThis.requestAnimationFrame( () => {
            callback( "immediate" );
        } );
        return () => {
            globalThis.cancelAnimationFrame( handle );
        };
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

    /**
     *
     * @param {string} target
     * @param {(trigger:string, triggerData?:(number|string|null)) => void} callback
     * @returns {?function}
     */
    initElementTrigger( target, callback ) {
        if ( isSelector( target ) ) {
            const listener = event => {
                event.stopPropagation();
                callback( "element", target );
            };
            const targets = [];
            this.ownerDocument.querySelectorAll( target )?.forEach( element => {
                element.addEventListener( "click", listener );
                targets.push( element );
            } );
            return () => {
                targets.forEach( element => element.removeEventListener( "click", listener ) );
            };
        }
    }

    /**
     *
     * @param {number} delay
     * @param {(trigger:string, triggerData?:(number|string|null)) => void} callback
     * @returns {?function}
     */
    initExitIntentTrigger( delay, callback ) {
        if ( isNumber( delay ) ) {
            const listener = event => {
                if ( event.clientY < 0 ) {
                    this.ownerDocument.body.removeEventListener( "mouseleave", listener );
                    callback( "exit-intent", delay );
                }
            };
            const init = () => {
                this.ownerDocument.body.addEventListener( "mouseleave", listener, { passive: true } );
            };
            const destroy = () => {
                this.ownerDocument.body.removeEventListener( "mouseleave", listener );
            };
            if ( delay > 0 ) {
                const timeoutId = globalThis.setTimeout( init, delay * 1000 );
                return () => {
                    globalThis.clearTimeout( timeoutId );
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
     * @param {(trigger:string, triggerData?:(number|string|null)) => void} callback
     * @returns {?function}
     */
    initTimerTrigger( timeout, callback ) {
        if ( isNumber( timeout ) ) {
            const timeoutId = globalThis.setTimeout( () => {
                callback( "timer", timeout );
            }, timeout * 1000 );
            return () => {
                globalThis.clearTimeout( timeoutId );
            };
        }
    }

    /**
     *
     * @param {number} percent
     * @param {(trigger:string, triggerData?:(number|string|null)) => void} callback
     * @returns {?function}
     */
    initScrollTrigger( percent, callback ) {
        if ( isNumber( percent ) ) {
            if ( getDocumentScrollPercent() > percent ) {
                callback( "scroll", percent );
            } else {
                const listener = () => {
                    if ( getDocumentScrollPercent() > percent ) {
                        this.ownerDocument.removeEventListener( "scroll", listener );
                        callback( "scroll", percent );
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
     * @param {(trigger:string, triggerData?:(number|string|null)) => void} callback
     * @returns {?function}
     */
    initVisibleTrigger( target, callback ) {
        if ( isString( target, true ) ) {
            const targets = strim( target, "," ).reduce( ( acc, id ) => {
                const element = this.ownerDocument.getElementById( id );
                if ( element instanceof globalThis.HTMLElement ) {
                    acc.push( element );
                }
                return acc;
            }, [] );
            if ( targets.length > 0 ) {
                const observer = new globalThis.IntersectionObserver( entries => {
                    const visible = entries.find( entry => entry.isIntersecting );
                    if ( visible ) {
                        observer.disconnect();
                        callback( "visible", target );
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