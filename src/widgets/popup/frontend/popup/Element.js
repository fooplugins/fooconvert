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
        return [ "open" ];
    }

    constructor() {
        super();
        this.attachShadow( { mode: "open" } ).append( template.content.cloneNode( true ) );
        this.shadowRoot.adoptedStyleSheets.push( styles );
        this.#backdropElement = this.shadowRoot.querySelector( "[part~=backdrop]" );
        this.#containerElement = this.shadowRoot.querySelector( "[part~=container]" );
        this.#buttonElement = this.shadowRoot.querySelector( "[part~=button]" );
        this.#contentElement = this.shadowRoot.querySelector( "[part~=content]" );
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
    #buttonElement = null;
    get buttonElement() {
        return this.#buttonElement;
    }

    /**
     * @type {?HTMLDivElement}
     */
    #contentElement = null;
    get contentElement() {
        return this.#contentElement;
    }

    updateButtonTransform() {
        const position = this.buttonPosition;
        if ( [ 'left', 'right' ].includes( position ) ) {
            const alignment = this.buttonAlignment;
            const {
                marginTop,
                marginRight,
                marginBottom,
                marginLeft
            } = getComputedStyle( this.buttonElement );

            const vertical = `(${ marginTop } + ${ marginBottom })`;
            const horizontal = `(${ marginLeft } + ${ marginRight })`;

            const {
                borderTopWidth,
                borderLeftWidth,
                borderRightWidth
            } = getComputedStyle( this.contentElement );

            let transform = '';
            if ( position === 'left' ) {
                switch ( alignment ) {
                    case 'inside':
                        break;
                    case 'outside':
                        transform = `translateX(calc(-100% - ${ horizontal })) translateY(calc(-100% - ${ vertical }))`;
                        break;
                    case 'corner':
                        transform = `translateX(calc(-50% - ${ marginLeft } + (${ borderLeftWidth }/2))) translateY(calc(-50% - ${ marginTop } + (${ borderTopWidth }/2)))`;
                        break;
                }
            } else {
                switch ( alignment ) {
                    case 'inside':
                        break;
                    case 'outside':
                        transform = `translateX(calc(100% + ${ horizontal })) translateY(calc(-100% - ${ vertical }))`;
                        break;
                    case 'corner':
                        transform = `translateX(calc(50% + ${ marginRight } - (${ borderRightWidth }/2))) translateY(calc(-50% - ${ marginTop } + (${ borderTopWidth }/2)))`;
                        break;
                }
            }
            if ( transform !== '' ) {
                this.buttonElement.style.setProperty( 'transform', transform, 'important' );
            } else {
                this.buttonElement.style.removeProperty( 'transform' );
            }
        }
    }

    initialize() {
        if ( !this.hasAttribute( "tabindex" ) ) {
            this.setAttribute( "tabindex", "0" );
        }
        this.setAttribute( "role", "dialog" );
    }

    connected() {
        super.connected();
        this.updateButtonTransform();
        this.buttonElement.addEventListener( "click", this.onCloseTriggered );
        if ( !this.backdropIgnore ) {
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
        this.buttonElement.removeEventListener( "click", this.onCloseTriggered );
        this.backdropElement.removeEventListener( "click", this.onCloseTriggered );
        if ( isFunction( this.#closeAnchor ) ) {
            this.#closeAnchor();
            this.#closeAnchor = null;
        }
    }

    triggeredCallback( type, ...args ) {
        this.open = true;
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

    get backdropIgnore() {
        return this.hasAttribute( "backdrop-ignore" );
    }

    set backdropIgnore( state ) {
        this.toggleAttribute( "backdrop-ignore", Boolean( state ) );
    }

    get hideScrollbar() {
        return this.hasAttribute( "hide-scrollbar" );
    }

    set hideScrollbar( state ) {
        this.toggleAttribute( "hide-scrollbar", Boolean( state ) );
    }

    get transitions() {
        return this.hasAttribute( 'transitions' );
    }

    get buttonPosition() {
        if ( this.hasAttribute( 'button-none' ) ) {
            return 'none';
        } else if ( this.hasAttribute( 'button-left' ) ) {
            return 'left';
        } else {
            return 'right';
        }
    }

    get buttonAlignment() {
        if ( this.hasAttribute( 'button-corner' ) ) {
            return 'corner';
        } else if ( this.hasAttribute( 'button-outside' ) ) {
            return 'outside';
        } else {
            return 'inside';
        }
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
        if ( this.hideScrollbar ) {
            dom.classList.toggle( 'fc-popup__hide-scroll', state );
        }
        dom.classList.toggle( 'fc-popup__open', state );
        dom.classList.toggle( className, state );
        this.dispatch( type );
    }
}

export default PopupElement;