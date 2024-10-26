import { TriggeredElement } from "#frontend";
import "./root.scss";
import cssText from '!!css-loader?{"sourceMap":false,"exportType":"string"}!postcss-loader!sass-loader!./host.scss';
import markup from "./template.html";
import { isBoolean, isFunction, isString, strim } from "@steveush/utils";

const styles = new CSSStyleSheet();
styles.replaceSync( cssText );

const template = document.createElement( "template" );
template.innerHTML = markup;

class PopupElement extends TriggeredElement {

    static get observedAttributes() {
        return [ "open", "position" ];
    }

    constructor() {
        super();
        this.attachShadow( { mode: "open" } ).append( template.content.cloneNode( true ) );
        this.shadowRoot.adoptedStyleSheets.push( styles );
        this.#backdropElement = this.shadowRoot.querySelector( "[part~=backdrop]" );
        this.#containerElement = this.shadowRoot.querySelector( "[part~=container]" );
        this.#closeButtonElement = this.shadowRoot.querySelector( "[part~=close-button]" );
        this.#contentElement = this.shadowRoot.querySelector( "[part~=content]" );
        this.onCloseButtonClicked = this.onCloseButtonClicked.bind( this );
        this.onCloseTriggered = this.onCloseTriggered.bind( this );
    }

    /**
     * @property {{trigger?: string|null, triggerData?: string|null}} config
     */

    /**
     * @type {?HTMLDivElement}
     */
    #backdropElement = null;
    get backdropElement() {
        return this.#backdropElement;
    }

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
        if ( !this.config?.backdropIgnore ) {
            this.backdropElement.addEventListener( "click", this.onCloseTriggered );
        }
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
        this.backdropElement.removeEventListener( "click", this.onCloseTriggered );
        if ( isFunction( this.#closeAnchor ) ) {
            this.#closeAnchor();
            this.#closeAnchor = null;
        }
    }

    triggeredCallback( type, ...args ) {
        this.open = true;
    }

    onCloseButtonClicked() {
        this.open = false;
    }

    onCloseTriggered() {
        this.open = false;
    }

    get open() {
        return this.hasAttribute( "open" );
    }

    set open( state ) {
        this.toggleAttribute( "open", Boolean( state ) );
    }

    get transitions() {
        return this.hasAttribute( 'transitions' );
    }

    get hideScrollbar() {
        return this.hasAttribute( "hide-scrollbar" );
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
        if ( this.hideScrollbar ) {
            dom.classList.toggle( 'fc-popup__hide-scroll', state );
        }
        const className = `${ this.id }__open`;
        dom.classList.toggle( 'fc-popup__open', state );
        dom.classList.toggle( className, state );
        this.dispatch( type );
    }
}

export default PopupElement;