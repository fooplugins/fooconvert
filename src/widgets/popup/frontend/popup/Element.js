import { TriggeredElement } from "#frontend";
import "./root.scss";
import cssText from '!!css-loader?{"sourceMap":false,"exportType":"string"}!postcss-loader!sass-loader!./host.scss';
import markup from "./template.html";
import { isFunction } from "@steveush/utils";

const styles = new CSSStyleSheet();
styles.replaceSync( cssText );

const template = document.createElement( "template" );
template.innerHTML = markup;

class PopupElement extends TriggeredElement {

    constructor() {
        super();
        this.attachShadow( { mode: "open" } ).append( template.content.cloneNode( true ) );
        this.shadowRoot.adoptedStyleSheets.push( styles );
        this.#backdropElement = this.shadowRoot.querySelector( "[part~=backdrop]" );
        this.#containerElement = this.shadowRoot.querySelector( "[part~=container]" );
        this.#closeButtonElement = this.shadowRoot.querySelector( "[part~=close-button]" );
        this.#contentElement = this.shadowRoot.querySelector( "[part~=content]" );
        this.onCloseButtonClicked = this.onCloseButtonClicked.bind( this );
        this.onBackdropClicked = this.onBackdropClicked.bind( this );
        this.onCloseAnchorTrigger = this.onCloseAnchorTrigger.bind( this );
    }

    /**
     * @property {{trigger?: string|null, triggerData?: string|null}} config
     */

    /**
     *
     * @type {?function}
     */
    #destroyCloseAnchorTrigger = null;

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

    get transitions() {
        return this.hasAttribute( 'transitions' );
    }

    get hideScrollbar() {
        return this.hasAttribute( "hide-scrollbar" );
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
            this.backdropElement.addEventListener( "click", this.onBackdropClicked );
        }
    }

    disconnected() {
        super.disconnected();
        this.closeButtonElement.removeEventListener( "click", this.onCloseButtonClicked );
        this.backdropElement.removeEventListener( "click", this.onBackdropClicked );
    }

    connectTrigger() {
        super.connectTrigger();
        this.#destroyCloseAnchorTrigger = this.initAnchorTrigger( this.config?.closeAnchor, this.onCloseAnchorTrigger );
    }

    disconnectTrigger() {
        super.disconnectTrigger();
        if ( isFunction( this.#destroyCloseAnchorTrigger ) ) {
            this.#destroyCloseAnchorTrigger();
            this.#destroyCloseAnchorTrigger = null;
        }
    }

    onOpenChanged( state ) {
        if ( this.hideScrollbar ) {
            this.ownerDocument.documentElement.classList.toggle( 'fc-popup__hide-scroll', state );
        }
        super.onOpenChanged( state );
    }

    onCloseButtonClicked() {
        this.setOpen( false, { 'trigger': 'close-button' } );
    }

    onBackdropClicked() {
        this.setOpen( false, { 'trigger': 'backdrop' } );
    }

    onCloseAnchorTrigger( trigger, triggerData ) {
        this.setOpen( false, { trigger, triggerData } );
    }
}

export default PopupElement;