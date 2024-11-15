import { LOG_EVENT_TYPES, TriggeredElement } from "#frontend";
import "./root.scss";
import cssText from '!!css-loader?{"sourceMap":false,"exportType":"string"}!postcss-loader!sass-loader!./host.scss';
import markup from "./template.html";
import { isBoolean, isFunction, isString, strim } from "@steveush/utils";

const styles = new CSSStyleSheet();
styles.replaceSync( cssText );

const template = document.createElement( "template" );
template.innerHTML = markup;

class FlyoutElement extends TriggeredElement {

    static get observedAttributes() {
        return [ "open", "position" ];
    }

    constructor() {
        super();
        this.attachShadow( { mode: "open" } ).append( template.content.cloneNode( true ) );
        this.shadowRoot.adoptedStyleSheets.push( styles );
        this.#containerElement = this.shadowRoot.querySelector( "[part~=container]" );
        this.#openButtonElement = this.shadowRoot.querySelector( "[part~=open-button]" );
        this.#closeButtonElement = this.shadowRoot.querySelector( "[part~=close-button]" );
        this.#contentElement = this.shadowRoot.querySelector( "[part~=content]" );
        this.onOpenButtonClicked = this.onOpenButtonClicked.bind( this );
        this.onCloseButtonClicked = this.onCloseButtonClicked.bind( this );
    }

    /**
     * @property {{trigger?: string|null, triggerData?: string|null}} config
     */

    /**
     * @type {?HTMLDivElement}
     */
    #containerElement = null;
    get containerElement() {
        return this.#containerElement;
    }

    /**
     * @type {?HTMLButtonElement}
     */
    #openButtonElement = null;
    get openButtonElement() {
        return this.#openButtonElement;
    }

    /**
     * @type {?HTMLButtonElement}
     */
    #closeButtonElement = null;
    get closeButtonElement() {
        return this.#closeButtonElement;
    }

    /**
     * @type {?HTMLDivElement}
     */
    #contentElement = null;
    get contentElement() {
        return this.#contentElement;
    }

    initialize() {
        if ( !this.hasAttribute( "tabindex" ) ) {
            this.setAttribute( "tabindex", "0" );
        }
        this.setAttribute( "role", "dialog" );
    }

    connected() {
        super.connected();
        this.closeButtonElement.addEventListener( "click", this.onCloseButtonClicked );
        this.openButtonElement.addEventListener( "click", this.onOpenButtonClicked );
        this.#closeAnchor = this.initCloseAnchor( this.config?.closeAnchor );
    }

    /**
     *
     * @type {?function}
     */
    #closeAnchor = null;

    /**
     *
     * @param {string} target
     * @returns {?function}
     */
    initCloseAnchor( target ) {
        if ( isString( target, true ) ) {
            const listener = event => {
                event.preventDefault();
                this.open = false;
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

    disconnected() {
        super.disconnected();
        this.closeButtonElement.removeEventListener( "click", this.onCloseButtonClicked );
        this.openButtonElement.removeEventListener( "click", this.onOpenButtonClicked );
        if ( isFunction( this.#closeAnchor ) ) {
            this.#closeAnchor();
            this.#closeAnchor = null;
        }
    }

    triggeredCallback( type, ...args ) {
        super.triggeredCallback( type, ...args );
        this.open = true;
    }

    onOpenButtonClicked() {
        this.log( LOG_EVENT_TYPES.OPEN, { 'trigger': 'open-button' } );
        this.open = true;
    }

    onCloseButtonClicked() {
        this.log( LOG_EVENT_TYPES.CLOSE, { 'trigger': 'close-button' } );
        this.open = false;
    }

    get open() {
        return this.hasAttribute( "open" );
    }

    set open( state ) {
        this.toggleAttribute( "open", Boolean( state ) );
    }

    get position() {
        return this.hasAttribute( "position" );
    }

    get transitions() {
        return this.hasAttribute( 'transitions' );
    }

    // noinspection JSUnusedGlobalSymbols,JSUnusedLocalSymbols
    attributeChangedCallback( name, oldValue, newValue ) {
        if ( name === "open" ) {
            this.#onOpenChanged( this.open );
        }
    }

    #onOpenChanged( state ) {
        const type = state ? "open" : "close";
        const dom = document.documentElement;
        const className = `${ this.id }__open`;
        dom.classList.toggle( 'fc-flyout__open', state );
        dom.classList.toggle( className, state );
        this.dispatch( type );

        this.log( state ? LOG_EVENT_TYPES.VIEW : LOG_EVENT_TYPES.DISMISS );
    }
}

export default FlyoutElement;