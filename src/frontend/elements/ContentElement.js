import CustomElement from "./CustomElement";
import { getParentWidgetElement } from "../utils";

class ContentElement extends CustomElement {

    constructor() {
        super();
        this.log = this.log.bind( this );
    }

    #parentWidgetElement = null;
    get parentWidgetElement() {
        if ( !this.#parentWidgetElement ) {
            this.#parentWidgetElement = getParentWidgetElement( this );
        }
        return this.#parentWidgetElement;
    }

    /**
     *
     * @param {string} type
     * @param {object} [data]
     * @returns {Promise<LogEventResult>}
     */
    log( type, data ) {
        if ( this.parentWidgetElement ) {
            return this.parentWidgetElement?.log( type, data );
        }
        return Promise.resolve( { success: false, data: 'No parent popup' } );
    }
}

export default ContentElement;
