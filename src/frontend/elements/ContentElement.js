import CustomElement from "./CustomElement";
import { getParentPopupElement } from "../utils";

class ContentElement extends CustomElement {

    constructor() {
        super();
        this.log = this.log.bind( this );
    }

    #parentPopupElement = null;
    get parentPopupElement() {
        if ( !this.#parentPopupElement ) {
            this.#parentPopupElement = getParentPopupElement( this );
        }
        return this.#parentPopupElement;
    }

    /**
     *
     * @param {string} type
     * @param {object} [data]
     * @returns {Promise<LogEventResult>}
     */
    log( type, data ) {
        if ( this.parentPopupElement ) {
            return this.parentPopupElement?.log( type, data );
        }
        return Promise.resolve( { success: false, data: 'No parent popup' } );
    }
}

export default ContentElement;
