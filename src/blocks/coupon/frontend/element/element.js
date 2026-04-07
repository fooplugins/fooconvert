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
        this.el.applyResult = this.shadowRoot.querySelector( '[part="apply-result"]' );
        this.el.applyResultText = this.shadowRoot.querySelector( '[part="apply-result__text"]' );

        this.onActionButtonClick = this.onActionButtonClick.bind( this );
        this.onCopyToClipboard = this.onCopyToClipboard.bind( this );
    }

    /**
     *
     * @type {{ copyButton?: HTMLButtonElement, codeText?: HTMLSpanElement, result?: HTMLDivElement, resultText?: HTMLSpanElement, applyResult?: HTMLDivElement, applyResultText?: HTMLSpanElement }}
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

    get actionMode() {
        return this.config?.actionMode === 'apply' ? 'apply' : 'copy';
    }

    get isApplyMode() {
        return this.actionMode === 'apply';
    }

    get applyHandler() {
        return globalThis?.FooConvertPro?.applyCouponForElement;
    }

    get applyUnavailableMessage() {
        return this.config?.applyUnavailableMessage ?? 'Coupon apply unavailable.';
    }

    get override() {
        return this.config?.override ?? '';
    }

    get hasOverride() {
        return isStringNotEmpty( this.override );
    }

    connected() {
        super.connected();
        this.toggleAttribute( 'apply-mode', this.isApplyMode );
        this.el.codeText.textContent = this.code;
        this.el.copyButton.addEventListener( 'click', this.onActionButtonClick );
    }

    disconnected() {
        super.disconnected();
        this.el.copyButton.removeEventListener( 'click', this.onActionButtonClick );
    }

    get isSuccess() {
        return this.hasAttribute( 'is-success' );
    }

    set isSuccess( value ) {
        return this.toggleAttribute( 'is-success', value );
    }

    get isError() {
        return this.hasAttribute( 'is-error' );
    }

    set isError( value ) {
        return this.toggleAttribute( 'is-error', value );
    }

    get isPending() {
        return this.hasAttribute( 'is-pending' );
    }

    set isPending( value ) {
        return this.toggleAttribute( 'is-pending', value );
    }

    #resultTimeout;

    clearResultState() {
        this.isSuccess = false;
        this.isError = false;
        this.isPending = false;
    }

    clearResultTimeout() {
        clearTimeout( this.#resultTimeout );
        this.#resultTimeout = undefined;
    }

    hideResult() {
        this.el.result.part.remove( 'show' );
        this.el.applyResult.part.remove( 'show' );
        this.clearResultState();
    }

    showResult( message, status = 'success' ) {
        this.clearResultTimeout();
        this.hideResult();
        const text = isStringNotEmpty( message ) ? message : '';
        this.isSuccess = status === 'success';
        this.isError = status === 'error';
        this.isPending = status === 'pending';
        if ( this.isApplyMode ) {
            this.el.applyResultText.textContent = text;
            this.el.applyResult.part.add( 'show' );
            return;
        }
        this.el.resultText.textContent = text;
        this.el.result.part.add( 'show' );
    }

    scheduleSuccessEffects( {
        delay = 1000,
        close = false,
        redirectURL = ''
    } = {} ) {
        this.clearResultTimeout();
        this.#resultTimeout = setTimeout( () => {
            if ( close ) {
                this.dispatch( 'request-close', { bubbles: true } );
            }
            if ( isStringNotEmpty( redirectURL ) ) {
                window.location.href = redirectURL;
                return;
            }
            this.hideResult();
        }, delay );
    }

    resolveSuccess( message, options ) {
        this.showResult( message, 'success' );
        this.scheduleSuccessEffects( options );
    }

    resolveError( message ) {
        this.showResult( message ?? 'Something went wrong!', 'error' );
    }

    resolvePending( message ) {
        this.showResult( message, 'pending' );
    }

    onActionButtonClick( event ) {
        event?.preventDefault();
        if ( this.isApplyMode && typeof this.applyHandler === 'function' ) {
            return this.applyHandler( this, event );
        }
        if ( this.isApplyMode ) {
            this.resolveError( this.applyUnavailableMessage );
            return;
        }
        return this.onCopyToClipboard( event );
    }

    onCopyToClipboard( event ) {
        const textToCopy = this.hasOverride ? this.override : this.code;
        copyToClipboard( textToCopy )
            .then( () => this.log( LOG_EVENT_TYPES.CONVERSION, { source: 'coupon', code: textToCopy } ) )
            .then( response => {
                if ( !!response?.success ) {
                    this.resolveSuccess( this.copiedMessage, {
                        close: this.copyClose,
                        redirectURL: this.canRedirect ? this.redirectURL : ''
                    } );
                    return;
                }
                this.resolveError( response?.data ?? 'Something went wrong!' );
            } )
            .catch( error => this.resolveError( error?.message ?? error ?? 'Something went wrong!' ) );
    }
}

export default CouponElement;
