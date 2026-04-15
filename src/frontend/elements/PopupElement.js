import CustomElement from "./CustomElement";
import { logEvent } from "../utils";

class PopupElement extends CustomElement {

    constructor() {
        super();
        this.log = this.log.bind( this );
    }

    /**
     *
     * @param {string} type
     * @param {object} [data]
     * @returns {Promise<LogEventResult>}
     */
    log( type, data ) {
        const { postId, postType, template = '' } = this.config;
        return logEvent( postId, postType, template, type, data );
    }
}

export default PopupElement;
