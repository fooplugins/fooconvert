import { checkInputValidity, ContentElement, LOG_EVENT_TYPES } from "#frontend";
import "./root.scss";
import cssText from '!!css-loader?{"sourceMap":false,"exportType":"string"}!postcss-loader!sass-loader!./host.scss';
import markup from "./template.html";
import { isString } from "@steveush/utils";

const styles = new CSSStyleSheet();
styles.replaceSync( cssText );

const template = document.createElement( "template" );
template.innerHTML = markup;

class SignUpElement extends ContentElement {

    constructor() {
        super();
        this.attachShadow( { mode: "open" } ).append( template.content.cloneNode( true ) );
        this.shadowRoot.adoptedStyleSheets.push( styles );
        this.#nameInputElement = this.shadowRoot.querySelector( "[part='input name']" );
        this.#emailInputElement = this.shadowRoot.querySelector( "[part='input email']" );
        this.#submitButtonElement = this.shadowRoot.querySelector( "[part='submit-button']" );
        this.#resultTextElement = this.shadowRoot.querySelector( "[part='result__text']" );
        this.onInputChanged = this.onInputChanged.bind( this );
        this.onClick = this.onClick.bind( this );
    }

    /**
     * @type {?HTMLButtonElement}
     */
    #submitButtonElement = null;
    get submitButtonElement() {
        return this.#submitButtonElement;
    }

    /**
     * @type {?HTMLInputElement}
     */
    #nameInputElement = null;
    get nameInputElement() {
        return this.#nameInputElement;
    }

    /**
     * @type {?HTMLInputElement}
     */
    #emailInputElement = null;
    get emailInputElement() {
        return this.#emailInputElement;
    }

    /**
     * @type {?HTMLSpanElement}
     */
    #resultTextElement = null;
    get resultTextElement() {
        return this.#resultTextElement;
    }

    get emailOnly() {
        return this.hasAttribute( 'email-only' );
    }

    get namePlaceholder() {
        return this.config?.namePlaceholder ?? '';
    }

    get emailPlaceholder() {
        return this.config?.emailPlaceholder ?? '';
    }

    get successMessage() {
        return this.config?.successMessage ?? '';
    }

    get layout() {
        return this.getAttribute( 'layout' ) ?? 'row';
    }

    get noLabels() {
        return this.hasAttribute( 'no-labels' );
    }

    get successClose() {
        return this.hasAttribute( 'success-close' );
    }

    get stackLabels() {
        return this.hasAttribute( 'stack-labels' );
    }

    get buttonLayout() {
        return this.getAttribute( 'button-layout' ) ?? 'text-only';
    }

    get isSuccess() {
        return this.hasAttribute( 'is-success' );
    }

    set isSuccess( value ) {
        return this.toggleAttribute( 'is-success', value );
    }

    get isSubmitted() {
        return this.hasAttribute( 'is-submitted' );
    }

    set isSubmitted( value ) {
        return this.toggleAttribute( 'is-submitted', value );
    }

    initialize() {
        super.initialize();

        const namePlaceholder = this.namePlaceholder;
        if ( isString( namePlaceholder, true ) ) {
            this.nameInputElement.placeholder = namePlaceholder;
        }

        const emailPlaceholder = this.emailPlaceholder;
        if ( isString( emailPlaceholder, true ) ) {
            this.emailInputElement.placeholder = emailPlaceholder;
        }
    }

    connected() {
        super.connected();

        this.nameInputElement.addEventListener( "input", this.onInputChanged );
        this.emailInputElement.addEventListener( "input", this.onInputChanged );
        this.submitButtonElement.addEventListener( "click", this.onClick );
    }

    disconnected() {
        super.disconnected();
        this.nameInputElement.removeEventListener( "input", this.onInputChanged );
        this.emailInputElement.removeEventListener( "input", this.onInputChanged );
        this.submitButtonElement.removeEventListener( "click", this.onClick );
    }

    checkInputValidity( input, attributes = [{ name: 'required' }] ) {
        const valid = checkInputValidity( input, attributes );
        input.part.toggle( 'invalid', !valid );
        return valid;
    }

    validate() {
        let nameValid = true;
        if ( !this.emailOnly ) {
            nameValid = this.checkInputValidity( this.nameInputElement );
        }
        return this.checkInputValidity( this.emailInputElement ) && nameValid;
    }

    reset() {
        this.isSuccess = false;
        this.isSubmitted = false;
        this.nameInputElement.value = '';
        this.nameInputElement.part.remove( 'invalid' );
        this.emailInputElement.value = '';
        this.emailInputElement.part.remove( 'invalid' );
        this.resultTextElement.textContent = '';
    }

    onInputChanged( event ) {
        this.checkInputValidity( event.target );
    }

    #resultTimeout = null;

    onClick( event ) {
        event.preventDefault();
        if ( this.validate() ) {
            const data = {
                source: 'sign-up',
                email: this.emailInputElement.value
            };
            if ( !this.emailOnly ) {
                data.name = this.nameInputElement.value;
            }

            this.resultTextElement.textContent = '';
            this.isSubmitted = true;
            this.log( LOG_EVENT_TYPES.CONVERSION, data )
                .then( response => {
                    if ( ( this.isSuccess = !!response?.success ) ) {
                        this.resultTextElement.textContent = this.successMessage;
                    } else {
                        this.resultTextElement.textContent = response?.data ?? 'Something went wrong!';
                    }
                    clearTimeout( this.#resultTimeout );
                    this.#resultTimeout = setTimeout( () => {
                        if ( this.isSuccess ) {
                            if ( this.successClose ) this.dispatch( 'request-close', { bubbles: true } );
                            this.reset();
                        }
                        this.isSubmitted = false;
                    }, 3000 );
                } );
        }
    }
}

export default SignUpElement;


