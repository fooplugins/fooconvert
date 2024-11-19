import { LOG_EVENT_TYPES, TriggeredElement } from "#frontend";
import "./root.scss";
import cssText from '!!css-loader?{"sourceMap":false,"exportType":"string"}!postcss-loader!sass-loader!./host.scss';
import markup from "./template.html";
import { isFunction } from "@steveush/utils";

const styles = new CSSStyleSheet();
styles.replaceSync( cssText );

const template = document.createElement( "template" );
template.innerHTML = markup;

class BarElement extends TriggeredElement {

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
     *
     * @type {?function}
     */
    #destroyCloseAnchorTrigger = null;

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

    get position() {
        return this.hasAttribute( "position" );
    }

    get transitions() {
        return this.hasAttribute( 'transitions' );
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
    }

    disconnected() {
        super.disconnected();
        this.closeButtonElement.removeEventListener( "click", this.onCloseButtonClicked );
        this.openButtonElement.removeEventListener( "click", this.onOpenButtonClicked );
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

    onOpenButtonClicked() {
        this.setOpen( true, { 'trigger': 'open-button' } );
    }

    onCloseButtonClicked() {
        this.setOpen( false, { 'trigger': 'close-button' } );
    }
}

export default BarElement;