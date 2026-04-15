import { ContentElement, clearCountdown, getCountdown, getTimeDifference, setCountdown } from "#frontend";
import cssText
    from '!!css-loader?{"sourceMap":false,"exportType":"string"}!postcss-loader!sass-loader!./element.host.scss';
import markup from "./element.shadow-dom.html";
import { isNumber, isPlainObject } from "@steveush/utils";

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
        this.onPopupCanConnect = this.onPopupCanConnect.bind( this );
        this.onCountdownStart = this.onCountdownStart.bind( this );
        this.onCountdownPause = this.onCountdownPause.bind( this );
        this.onCountdownStop = this.onCountdownStop.bind( this );
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

    #stopped = false;
    get stopped() {
        return this.#stopped;
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

    onPopupCanConnect( event ) {
        if ( this.expired ) {
            event.preventDefault();
        }
    }

    connected() {
        super.connected();
        const parentPopup = this.parentPopupElement;
        parentPopup?.addEventListener( 'can-connect', this.onPopupCanConnect );
        parentPopup?.addEventListener( 'fc.countdown.start', this.onCountdownStart );
        parentPopup?.addEventListener( 'fc.countdown.pause', this.onCountdownPause );
        parentPopup?.addEventListener( 'fc.countdown.stop', this.onCountdownStop );
        if ( isNumber( this.fomo ) ) {
            const stored = getCountdown( this.id, this.persist );
            let timestamp = null;
            if ( isPlainObject( stored ) ) {
                if ( stored.state === 'paused' && isNumber( stored.value ) ) {
                    timestamp = Date.now() + stored.value;
                } else if ( stored.state === 'running' && isNumber( stored.value ) ) {
                    timestamp = stored.value;
                }
            } else if ( isNumber( stored ) ) {
                timestamp = stored;
            }
            if ( !isNumber( timestamp ) ) {
                timestamp = Date.now() + ( this.fomo * 60000 );
            }
            setCountdown( this.id, {
                state: 'running',
                value: timestamp
            }, this.persist, true );
            this.value = timestamp;
        }
        if ( !this.expired ) {
            this.start();
        }
    }

    disconnected() {
        super.disconnected();
        const parentPopup = this.parentPopupElement;
        parentPopup?.removeEventListener( 'can-connect', this.onPopupCanConnect );
        parentPopup?.removeEventListener( 'fc.countdown.start', this.onCountdownStart );
        parentPopup?.removeEventListener( 'fc.countdown.pause', this.onCountdownPause );
        parentPopup?.removeEventListener( 'fc.countdown.stop', this.onCountdownStop );
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
        if ( this.stopped || this.expired ) {
            return;
        }
        if ( this.started ) {
            clearInterval( this.#intervalID );
            this.#started = false;
        }
        this.#intervalID = undefined;
        if ( !this.started ) {
            this.#started = true;
            this.#intervalID = setInterval( () => {
                this.update();
            }, 1000 );
        }
    }

    pause() {
        const wasStarted = this.started;
        if ( this.started ) {
            clearInterval( this.#intervalID );
            this.#intervalID = undefined;
            this.#started = false;
        }
        if ( wasStarted && isNumber( this.fomo ) && this.value instanceof Date ) {
            const remaining = Math.max( 0, this.value.getTime() - Date.now() );
            if ( remaining > 0 ) {
                setCountdown( this.id, {
                    state: 'paused',
                    value: remaining
                }, this.persist, true );
            }
        }
        this.#stopped = false;
    }

    stop() {
        if ( this.started ) {
            clearInterval( this.#intervalID );
            this.#intervalID = undefined;
            this.#started = false;
        }
        if ( isNumber( this.fomo ) ) {
            clearCountdown( this.id, this.persist );
        }
        this.#stopped = true;
    }

    onCountdownStart() {
        this.start();
    }

    onCountdownPause() {
        this.pause();
    }

    onCountdownStop() {
        this.stop();
    }
}

export default CountdownElement;

