import { ContentElement, getCountdown, getTimeDifference, setCountdown } from "#frontend";
import cssText
    from '!!css-loader?{"sourceMap":false,"exportType":"string"}!postcss-loader!sass-loader!./element.host.scss';
import markup from "./element.shadow-dom.html";
import { isNumber } from "@steveush/utils";

const styles = new CSSStyleSheet();
styles.replaceSync( cssText );

const template = document.createElement( "template" );
template.innerHTML = markup;

class CountdownElement extends ContentElement {

    static get observedAttributes() {
        return [ 'value', 'expired' ];
    }

    constructor() {
        super();
        this.attachShadow( { mode: "open" } ).append( template.content.cloneNode( true ) );
        this.shadowRoot.adoptedStyleSheets.push( styles );
        this.#segmentValueDays = this.shadowRoot.querySelector( "[part='segment-value days']" );
        this.#segmentValueHours = this.shadowRoot.querySelector( "[part='segment-value hours']" );
        this.#segmentValueMinutes = this.shadowRoot.querySelector( "[part='segment-value minutes']" );
        this.#segmentValueSeconds = this.shadowRoot.querySelector( "[part='segment-value seconds']" );
        this.onWidgetCanConnect = this.onWidgetCanConnect.bind( this );
    }

    /**
     * @type {?HTMLSpanElement}
     */
    #segmentValueDays = null;
    get segmentValueDays() {
        return this.#segmentValueDays;
    }

    /**
     * @type {?HTMLSpanElement}
     */
    #segmentValueHours = null;
    get segmentValueHours() {
        return this.#segmentValueHours;
    }

    /**
     * @type {?HTMLSpanElement}
     */
    #segmentValueMinutes = null;
    get segmentValueMinutes() {
        return this.#segmentValueMinutes;
    }

    /**
     * @type {?HTMLSpanElement}
     */
    #segmentValueSeconds = null;
    get segmentValueSeconds() {
        return this.#segmentValueSeconds;
    }

    #started = false;
    get started() {
        return this.#started;
    }

    get persist() {
        return this.hasAttribute('persist');
    }

    get fomo() {
        const minutes = parseInt( this.getAttribute( 'fomo' ) );
        if ( !isNaN( minutes ) ) {
            return minutes;
        }
        return null;
    }

    get value() {
        const timestamp = Date.parse( this.getAttribute( 'value' ) );
        if ( !isNaN( timestamp ) ) {
            return new Date( timestamp );
        }
        return null;
    }

    set value( date ) {
        if ( typeof date === 'string' ) {
            date = Date.parse( date );
        }
        if ( typeof date === 'number' && !isNaN( date ) ) {
            date = new Date( date );
        }
        if ( date instanceof Date ) {
            this.setAttribute( 'value', date.toISOString() );
        } else {
            this.removeAttribute( 'value' );
        }
    }

    get minDigits() {
        let min = parseInt( this.getAttribute( 'min-digits' ) );
        if ( !isNaN( min ) && min > 1 ) {
            return min;
        }
        return 1;
    }

    get layout() {
        return this.getAttribute( 'layout' ) ?? 'row';
    }

    get expireClose() {
        return this.hasAttribute( 'expire-close' );
    }

    get expired() {
        return this.hasAttribute( 'expired' );
    }

    set expired( value ) {
        return this.toggleAttribute( 'expired', value );
    }

    onWidgetCanConnect( event ) {
        if ( this.expired ) {
            event.preventDefault();
        }
    }

    connected() {
        super.connected();
        this.parentWidgetElement?.addEventListener( 'can-connect', this.onWidgetCanConnect );
        if ( isNumber( this.fomo ) ) {
            let timestamp = getCountdown( this.id, this.persist );
            if ( !isNumber( timestamp ) ) {
                timestamp = Date.now() + ( this.fomo * 60000 );
            }
            setCountdown( this.id, timestamp, this.persist );
            this.value = timestamp;
        }
        if ( !this.expired ) {
            this.start();
        }
    }

    disconnected() {
        super.disconnected();
        this.stop();
    }

    attributeChangedCallback( name, oldValue, newValue ) {
        if ( name === 'value' ) {
            this.update();
        }
        if ( name === 'expired' ) {
            if ( this.expired ) {
                this.stop();
                if ( this.expireClose ) {
                    this.dispatch( 'request-close', { bubbles: true } );
                }
            }
        }
    }

    update() {
        const time = getTimeDifference( this.value );
        const min = this.minDigits;
        this.segmentValueDays.textContent = `${ time.days }`.padStart( min, '0' );
        this.segmentValueHours.textContent = `${ time.hours }`.padStart( min, '0' );
        this.segmentValueMinutes.textContent = `${ time.minutes }`.padStart( min, '0' );
        this.segmentValueSeconds.textContent = `${ time.seconds }`.padStart( min, '0' );
        this.expired = time.expired;
    }

    #intervalID;

    start() {
        this.stop();
        if ( !this.started && !this.expired ) {
            this.#started = true;
            this.#intervalID = setInterval( () => {
                this.update();
            }, 1000 );
        }
    }

    stop() {
        if ( this.started ) {
            clearInterval( this.#intervalID );
            this.#started = false;
        }
    }
}

export default CountdownElement;


