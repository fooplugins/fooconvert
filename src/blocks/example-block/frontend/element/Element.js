import { CustomElement } from "#frontend";
import "./root.scss";
import cssText from '!!css-loader?{"sourceMap":false,"exportType":"string"}!postcss-loader!sass-loader!./host.scss';
import markup from "./template.html";

const styles = new CSSStyleSheet();
styles.replaceSync( cssText );

const template = document.createElement( "template" );
template.innerHTML = markup;

class ExampleElement extends CustomElement {
    static get observedAttributes() {
        return [ 'prompt' ];
    }

    constructor() {
        super();
        this.attachShadow( { mode: "open" } ).append( template.content.cloneNode( true ) );
        this.shadowRoot.adoptedStyleSheets.push( styles );
        this.#buttonElement = this.shadowRoot.querySelector( "[part~=button]" );
        this.onClick = this.onClick.bind( this );
    }

    /**
     * @type {?HTMLButtonElement}
     */
    #buttonElement = null;
    get buttonElement() {
        return this.#buttonElement;
    }

    connected() {
        super.connected();
        this.buttonElement.addEventListener( "click", this.onClick );
    }

    disconnected() {
        super.disconnected();
        this.buttonElement.removeEventListener( "click", this.onClick );
    }

    onClick( event ) {
        event.preventDefault();
        alert( 'Hello World!' );
    }
}

export default ExampleElement;