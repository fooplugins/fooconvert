import { ContentElement, copyToClipboard, isStringNotEmpty, LOG_EVENT_TYPES } from "#frontend";
import cssText
    from '!!css-loader?{"sourceMap":false,"exportType":"string"}!postcss-loader!sass-loader!./element.host.scss';
import markup from "./element.shadow-dom.html";

const styles = new CSSStyleSheet();
styles.replaceSync( cssText );

const template = document.createElement( "template" );
template.innerHTML = markup;

class CouponElement extends ContentElement {

    constructor() {
        super();
        this.attachShadow( { mode: "open" } ).append( template.content.cloneNode( true ) );
        this.shadowRoot.adoptedStyleSheets.push( styles );
        this.el.codeText = this.shadowRoot.querySelector( '[part="code__text"]' );
        this.el.copyButton = this.shadowRoot.querySelector( '[part="copy-button"]' );
        this.el.result = this.shadowRoot.querySelector( '[part="result"]' );
        this.el.resultText = this.shadowRoot.querySelector( '[part="result__text"]' );

        this.onCopyToClipboard = this.onCopyToClipboard.bind( this );
    }

    /**
     *
     * @type {{ copyButton?: HTMLButtonElement, codeText?: HTMLSpanElement, result?: HTMLDivElement, resultText?: HTMLSpanElement }}
     */
    el = {};

    get code() {
        return this.config?.code ?? '';
    }

    get copyClose() {
        return this.hasAttribute( 'copy-close' );
    }

    get copyRedirect() {
        return this.hasAttribute( 'copy-redirect' );
    }

    get redirectURL() {
        return this.getAttribute( 'copy-redirect' );
    }

    get canRedirect() {
        return this.copyRedirect && isStringNotEmpty( this.redirectURL );
    }

    get copiedMessage() {
        return this.config?.copiedMessage ?? '';
    }

    get override() {
        return this.config?.override ?? '';
    }

    get hasOverride() {
        return isStringNotEmpty( this.override );
    }

    connected() {
        super.connected();
        this.el.codeText.textContent = this.code;
        this.el.copyButton.addEventListener( 'click', this.onCopyToClipboard );
    }

    disconnected() {
        super.disconnected();
        this.el.copyButton.removeEventListener( 'click', this.onCopyToClipboard );
    }

    get isSuccess() {
        return this.hasAttribute( 'is-success' );
    }

    set isSuccess( value ) {
        return this.toggleAttribute( 'is-success', value );
    }

    #resultTimeout;

    onCopyToClipboard( event ) {
        event?.preventDefault();
        clearTimeout( this.#resultTimeout );
        this.el.result.part.remove( 'show' );
        const textToCopy = this.hasOverride ? this.override : this.code;
        copyToClipboard( textToCopy )
            .then( () => this.log( LOG_EVENT_TYPES.CONVERSION, { source: 'coupon', code: textToCopy } ) )
            .then( response => {
                if ( ( this.isSuccess = !!response?.success ) ) {
                    this.el.resultText.textContent = this.copiedMessage;
                } else {
                    this.el.resultText.textContent = response?.data ?? 'Something went wrong!';
                }
                clearTimeout( this.#resultTimeout );
                this.el.result.part.add( 'show' );
                this.#resultTimeout = setTimeout( () => {
                    if ( this.isSuccess ) {
                        if ( this.copyClose ) this.dispatch( 'request-close', { bubbles: true } );
                        this.el.result.part.remove( 'show' );
                        if ( this.canRedirect ) {
                            window.location.href = this.redirectURL;
                        }
                    }
                }, 1000 );
            } );
    }
}

export default CouponElement;


