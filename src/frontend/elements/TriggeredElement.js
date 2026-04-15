import PopupElement from "./PopupElement";
import { isFunction, isNumber, isPlainObject, isString, strim } from "@steveush/utils";
import { getClickableData, LOG_EVENT_TYPES, isSelector, getSeen, setSeen } from "../utils";
import { getEventBus, subscribeTrigger } from "../triggers";

/**
 *
 */
class TriggeredElement extends PopupElement {

    // noinspection JSUnusedGlobalSymbols

    static get observedAttributes() {
        return [ "open" ];
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
        return isString( event, true ) ? event : null;
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
        const event = this.triggerEvent;
        if ( !isString( event, true ) ) {
            return null;
        }
        return subscribeTrigger( {
            element: this,
            event,
            where: this.triggerWhere,
            triggerOnce: this.triggerOnce,
            eventBus: getEventBus(),
            onOpenTrigger: this.onOpenTrigger
        } );
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
