import { TriggeredElement } from "#frontend";
import "./root.scss";
import cssText from '!!css-loader?{"sourceMap":false,"exportType":"string"}!postcss-loader!sass-loader!./host.scss';
import markup from "./template.html";
import { isBoolean, isFunction, isString, strim } from "@steveush/utils";

const styles = new CSSStyleSheet();
styles.replaceSync( cssText );

const template = document.createElement( "template" );
template.innerHTML = markup;

class BarElement extends TriggeredElement {

    static get observedAttributes() {
        return [ "open" ];
    }

    constructor() {
        super();
        this.attachShadow( { mode: "open" } ).append( template.content.cloneNode( true ) );
        this.shadowRoot.adoptedStyleSheets.push( styles );
        this.#containerElement = this.shadowRoot.querySelector( "[part~=container]" );
        this.#buttonElement = this.shadowRoot.querySelector( "[part~=button]" );
        this.#contentElement = this.shadowRoot.querySelector( "[part~=content]" );
        this.onButtonClicked = this.onButtonClicked.bind( this );
        this.onCloseTriggered = this.onCloseTriggered.bind( this );
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

    updateButtonPosition() {
        const position = this.buttonPosition;
        if ( [ 'left', 'right' ].includes( position ) ) {
            const {
                marginRight,
                marginLeft
            } = getComputedStyle( this.buttonElement );

            const {
                paddingRight,
                paddingLeft
            } = getComputedStyle( this.contentElement );

            const buttonOuterWidth = `calc(${ this.buttonElement.offsetWidth }px + ${ marginLeft } + ${ marginRight })`;
            const prop = position === "right" ? "padding-right" : "padding-left";
            const value = position === "right" ? paddingRight : paddingLeft;
            this.contentElement.style.setProperty( prop, `calc(${ value } + ${ buttonOuterWidth })`, 'important' );
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
        this.updateButtonPosition();
        if ( this.pagePush ) {
            this.updatePagePush();
            if ( this.transitions ) {
                document.documentElement.classList.add( 'fc-bar__page-push-transition' );
            }
        }
        this.buttonElement.addEventListener( "click", this.onButtonClicked );
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
        if ( this.pagePush ) {
            document.documentElement.classList.remove( 'fc-bar__page-push-transition' );
            this.updatePagePush( false );
        }
        this.buttonElement.removeEventListener( "click", this.onButtonClicked );
        if ( isFunction( this.#closeAnchor ) ) {
            this.#closeAnchor();
            this.#closeAnchor = null;
        }
    }

    triggeredCallback( type, ...args ) {
        this.open = true;
    }

    onButtonClicked() {
        if ( this.buttonIsToggle ) {
            this.open = !this.open;
        } else {
            this.open = false;
        }
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

    get pagePush() {
        return this.hasAttribute( 'page-push' );
    }

    get position() {
        if ( this.hasAttribute( 'bottom' ) ) {
            return 'bottom';
        } else {
            return 'top';
        }
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

    get buttonIsToggle() {
        return this.hasAttribute( 'button-toggle' );
    }

    // noinspection JSUnusedGlobalSymbols,JSUnusedLocalSymbols
    attributeChangedCallback( name, oldValue, newValue ) {
        if ( name === "open" ) {
            this.#onOpenChanged( this.open );
        }
    }

    #onOpenChanged( state ) {
        const type = state ? "open" : "close";
        if ( this.pagePush ) {
            this.updatePagePush( state );
        }
        this.dispatch( type );
    }

    updatePagePush( force ) {
        let state = isBoolean( force ) ? force : this.open;
        if ( state ) {
            this.#addPagePush();
        } else {
            this.#removePagePush();
        }
    }

    #addPagePush() {
        const dom = document.documentElement;
        const className = `${ this.id }__page-push`;
        if ( !dom.classList.contains( className ) ) {
            dom.classList.add( className );
            const styles = getComputedStyle( dom );
            const currentMargin = parseInt( styles.getPropertyValue( `margin-${ this.position }` ) );
            if ( !isNaN( currentMargin ) ) {
                const offset = `${ currentMargin + this.offsetHeight }px`;
                dom.style.setProperty( `margin-${ this.position }`, offset, 'important' );
                dom.style.setProperty( `scroll-padding-${ this.position }`, offset );
            }
        }
    }

    #removePagePush() {
        const dom = document.documentElement;
        const className = `${ this.id }__page-push`;
        if ( dom.classList.contains( className ) ) {
            dom.classList.remove( className );
            const styles = getComputedStyle( dom );
            const currentMargin = parseInt( styles.getPropertyValue( `margin-${ this.position }` ) );
            if ( !isNaN( currentMargin ) ) {
                let defaultMargin = this.position === 'bottom' ? 0 : parseInt( styles.getPropertyValue( '--wp-admin--admin-bar--height' ) );
                if ( isNaN( defaultMargin ) ) {
                    defaultMargin = dom.body?.classList?.contains( 'admin-bar' ) ? 32 : 0;
                }
                const offset = currentMargin - this.offsetHeight;
                if ( defaultMargin === offset ) {
                    dom.style.removeProperty( `margin-${ this.position }` );
                    dom.style.removeProperty( `scroll-padding-${ this.position }` );
                } else {
                    dom.style.setProperty( `margin-${ this.position }`, `${ offset }px`, 'important' );
                    dom.style.setProperty( `scroll-padding-${ this.position }`, `${ offset }px` );
                }
            } else {
                dom.style.removeProperty( `margin-${ this.position }` );
                dom.style.removeProperty( `scroll-padding-${ this.position }` );
            }
        }
    }

}

export default BarElement;