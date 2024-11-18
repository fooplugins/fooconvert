import { isPlainObject, isString } from "@steveush/utils";
import { getElementConfiguration } from "../utils";

class CustomElement extends HTMLElement {
    constructor() {
        super();
    }

    //region Private Fields

    /**
     * Configuration from the server.
     * @type {{}}
     */
    #config = {};
    /**
     * Represents whether the configuration has been initialized.
     * @type {boolean}
     */
    #isConfigurationInitialized = false;
    /**
     * Represents the state of initialization.
     * @type {boolean}
     */
    #isInitialized = false;
    /**
     *
     * @type {number|null}
     */
    #connectedNextFrameRequestId = null;

    //endregion

    //region Public Fields

    /**
     *
     * @returns {{}}
     */
    get config() {
        return this.#config;
    }

    get isConfigurationInitialized() {
        return this.#isConfigurationInitialized;
    }

    /**
     * Returns the value indicating whether the object is initialized.
     * @returns {boolean} - true if the object is initialized, false otherwise.
     */
    get isInitialized() {
        return this.#isInitialized;
    }

    //endregion

    //region Global Callbacks

    // noinspection JSUnusedGlobalSymbols
    connectedCallback(){
        if ( !this.isInitialized ) {
            this.initializeConfiguration();
            this.initialize();
            this.#isInitialized = true;
        }
        this.connected();
        this.#connectedNextFrameRequestId = requestAnimationFrame( () => {
            this.#connectedNextFrameRequestId = null;
            this.connectedNextFrame();
        } );
    }

    // noinspection JSUnusedGlobalSymbols
    disconnectedCallback() {
        if ( this.#connectedNextFrameRequestId !== null )
            cancelAnimationFrame( this.#connectedNextFrameRequestId );

        this.disconnected();
    }

    //endregion

    //region Local Callbacks

    initialize() {}

    connected() {}

    connectedNextFrame() {}

    disconnected() {}

    //endregion

    //region Helpers

    initializeConfiguration() {
        if ( !this.#isConfigurationInitialized ) {
            const config = getElementConfiguration( this.id );
            if ( isPlainObject( config ) ) {
                this.#config = config;
            } else {
                console.warn( `[FooConvert] No configuration found for element ID '${ this.id }'. Falling back to defaults.` );
            }
            this.#isConfigurationInitialized = true;
        }
    }

    /**
     *
     * @param {string} type
     * @param {EventInit|CustomEventInit} [options]
     * @returns {boolean} `false` if `type` is cancelable, and at least one of the event handlers which received the event called `Event.preventDefault()`, otherwise `true`.
     */
    dispatch( type, options ) {
        if ( isString( type, true ) ) {
            if ( isPlainObject( options ) ) {
                let event = Object.hasOwn( options, "detail" ) ? new CustomEvent( type, options ) : new Event( type, options );
                return this.dispatchEvent( event );
            }
            return this.dispatchEvent( new Event( type ) );
        }
        throw new DOMException( "Failed to execute 'dispatch' on 'CustomElement': parameter 1 is not of type 'string' or is empty.", "InvalidStateError" )
    }

    //endregion
}

export default CustomElement;